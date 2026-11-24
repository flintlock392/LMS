<?php

namespace App\Database\Seeds;

use App\Models\InventoryLogModel;
use App\Models\UserModel;
use CodeIgniter\Database\Seeder;

/**
 * Builds Activity Log entries from records that existed before the log did
 * (loans, returns, credits, inventory changes, accounts).
 * Safe to run more than once: it does nothing if the log already has entries.
 *
 * Run:  php spark db:seed ActivityBackfillSeeder
 */
class ActivityBackfillSeeder extends Seeder
{
    public function run()
    {
        $db = $this->db;
        if ($db->table('activity_logs')->countAllResults() > 0) {
            return;
        }

        $rows = [];
        $add  = static function (?string $at, ?int $user, string $cat, string $action, string $desc, ?string $type = null, ?int $id = null) use (&$rows) {
            if ($at) {
                $rows[] = ['created_at' => $at, 'user_id' => $user ?: null, 'category' => $cat, 'action' => $action,
                    'description' => mb_substr($desc, 0, 255), 'subject_type' => $type, 'subject_id' => $id, 'ip_address' => null];
            }
        };

        foreach ($db->table('users')->get()->getResultArray() as $u) {
            $isStaff = in_array($u['role'], UserModel::STAFF_ROLES, true);
            $add($u['created_at'], null, $isStaff ? 'staff' : 'user', $isStaff ? 'added' : 'registered',
                ($isStaff ? 'Added ' . UserModel::roleLabel($u['role']) . ' ' : 'Registered student ') . $u['name'] . ' (' . $u['id_number'] . ')', 'user', (int) $u['id']);
        }

        $logs = $db->table('inventory_logs l')->select('l.*, b.title')->join('books b', 'b.id = l.book_id')->get()->getResultArray();
        foreach ($logs as $l) {
            $n = abs((int) $l['change']);
            $add($l['created_at'], (int) $l['user_id'], $l['reason'] === 'initial' ? 'book' : 'inventory', $l['reason'] === 'initial' ? 'added' : ($l['change'] > 0 ? 'added' : 'removed'),
                $l['reason'] === 'initial'
                    ? 'Added "' . $l['title'] . '" to the catalog with ' . $n . ' cop' . ($n === 1 ? 'y' : 'ies')
                    : ($l['change'] > 0 ? 'Added ' : 'Removed ') . $n . ' cop' . ($n === 1 ? 'y' : 'ies') . ' of "' . $l['title'] . '" (' . (InventoryLogModel::LABELS[$l['reason']] ?? $l['reason']) . ')',
                'book', (int) $l['book_id']);
        }

        $loans = $db->table('borrowings br')->select('br.*, b.title, u.name, u.id_number')
            ->join('books b', 'b.id = br.book_id')->join('users u', 'u.id = br.user_id')->get()->getResultArray();
        foreach ($loans as $l) {
            $add($l['borrowed_at'], (int) $l['issued_by'], 'circulation', 'issued', 'Issued "' . $l['title'] . '" to ' . $l['name'] . ' (' . $l['id_number'] . '), due ' . date('M j, Y', strtotime($l['due_date'])), 'borrowing', (int) $l['id']);
            if ($l['status'] !== 'borrowed') {
                $add($l['returned_at'], (int) $l['received_by'], 'circulation', $l['status'] === 'lost' ? 'lost' : 'returned',
                    ($l['status'] === 'lost' ? 'Marked "' . $l['title'] . '" as lost by ' : 'Received "' . $l['title'] . '" from ') . $l['name'], 'borrowing', (int) $l['id']);
            }
        }

        $credits = $db->table('credit_logs c')->select('c.*, u.name, u.id_number')->join('users u', 'u.id = c.user_id')->get()->getResultArray();
        foreach ($credits as $c) {
            $add($c['created_at'], (int) $c['created_by'], 'credit', $c['change'] > 0 ? 'added' : 'reduced',
                ($c['change'] > 0 ? 'Added ' . $c['change'] : 'Reduced ' . abs((int) $c['change'])) . ' credit(s) for ' . $c['name'] . ' (' . $c['id_number'] . ') · now ' . $c['balance_after'],
                'user', (int) $c['user_id']);
        }

        usort($rows, static fn ($a, $b) => strcmp($a['created_at'], $b['created_at']));
        if ($rows) {
            $db->table('activity_logs')->insertBatch($rows);
        }
    }
}
