<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Credits become a balance: every student starts with 20 credits (good standing),
 * violations deduct credits and the Head Librarian restores them; 0 = blocked.
 *
 * Before, credits counted up from 0 (good) to 20 (blocked). This converts the
 * stored data: balance = 20 − old credits, and every credit history entry
 * changes sign so deductions are negative and restorations positive.
 */
class FlipCreditsToBalance extends Migration
{
    private const MAX = 20;

    public function up()
    {
        $this->flip();
        $this->forge->modifyColumn('users', [
            'credits' => ['name' => 'credits', 'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'null' => false, 'default' => self::MAX],
        ]);
    }

    public function down()
    {
        $this->flip();
        $this->forge->modifyColumn('users', [
            'credits' => ['name' => 'credits', 'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'null' => false, 'default' => 0],
        ]);
    }

    /** Same conversion both ways: x → 20 − x, and history signs flipped. */
    private function flip(): void
    {
        $max = self::MAX;
        $db  = $this->db;

        $db->query("UPDATE users SET credits = {$max} - LEAST(credits, {$max}) WHERE role = 'student'");
        $db->query("UPDATE users SET credits = {$max} WHERE role <> 'student'");
        $db->query("UPDATE credit_logs SET `change` = -`change`, balance_after = {$max} - balance_after");

        // Activity Log wording for credit entries, so old entries read the new way.
        $rows = $db->table('activity_logs')->select('id, action, description')->where('category', 'credit')->whereIn('action', ['added', 'reduced', 'deducted', 'restored'])->get()->getResultArray();
        foreach ($rows as $r) {
            $d = $r['description'];
            $d = preg_replace_callback('/ now (\d+)/', static fn ($m) => ' now ' . ($max - (int) $m[1]), $d, 1);
            $map = [
                'added'    => ['deducted', '/^Added (\d+) credit(s?) to /', 'Deducted $1 credit$2 from '],
                'reduced'  => ['restored', '/^Reduced (\d+) credit(s?) for /', 'Restored $1 credit$2 to '],
                'deducted' => ['added', '/^Deducted (\d+) credit(s?) from /', 'Added $1 credit$2 to '],
                'restored' => ['reduced', '/^Restored (\d+) credit(s?) to /', 'Reduced $1 credit$2 for '],
            ];
            [$action, $pattern, $replace] = $map[$r['action']];
            $d = preg_replace($pattern, $replace, $d, 1);
            $db->table('activity_logs')->where('id', $r['id'])->update(['action' => $action, 'description' => mb_substr($d, 0, 255)]);
        }
    }
}
