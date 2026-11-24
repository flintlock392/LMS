<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Credit Management (replaces fines).
 *
 * users.credits   - each student's credits, 0 (good) to 20 (blocked). Everyone starts at 0.
 * credit_logs     - every change: added for a violation, or reduced (appeal, library service, settlement…)
 * credit_appeals  - students ask the Head Librarian to reduce their credits
 */
class CreateCredits extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'credits' => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'after' => 'status'],
        ]);

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'change'        => ['type' => 'SMALLINT', 'constraint' => 5],
            'balance_after' => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true],
            'reason'        => ['type' => 'ENUM', 'constraint' => ['overdue', 'damaged', 'lost', 'misconduct', 'other', 'appeal', 'library_service', 'settlement', 'correction'], 'default' => 'other'],
            'borrowing_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'appeal_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'notes'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'created_at']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('borrowing_id', 'borrowings', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('credit_logs');

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'type'        => ['type' => 'ENUM', 'constraint' => ['appeal', 'library_service'], 'default' => 'appeal'],
            'message'     => ['type' => 'TEXT'],
            'hours'       => ['type' => 'DECIMAL', 'constraint' => '4,1', 'null' => true],
            'status'      => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'declined', 'cancelled'], 'default' => 'pending'],
            'reduced_by'  => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'null' => true],
            'response'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'handled_by'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'handled_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['status', 'created_at']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('handled_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('credit_appeals');

        // Fine rules are replaced by credit rules.
        $this->db->table('settings')->whereIn('key', ['fine_per_day', 'max_overdue_fine', 'lost_book_fee', 'damaged_book_fee', 'block_unpaid_fines', 'currency'])->delete();
    }

    public function down()
    {
        $this->forge->dropTable('credit_appeals', true);
        $this->forge->dropTable('credit_logs', true);
        $this->forge->dropColumn('users', 'credits');
    }
}
