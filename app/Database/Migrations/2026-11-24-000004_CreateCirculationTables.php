<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Borrowing, fines, inventory history and system settings.
 */
class CreateCirculationTables extends Migration
{
    public function up()
    {
        // ---- Borrowings (one row per copy lent out) -------------------------
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'book_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'issued_by'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'borrowed_at' => ['type' => 'DATETIME'],
            'due_date'    => ['type' => 'DATE'],
            'returned_at' => ['type' => 'DATETIME', 'null' => true],
            'received_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'status'      => ['type' => 'ENUM', 'constraint' => ['borrowed', 'returned', 'lost'], 'default' => 'borrowed'],
            'condition'   => ['type' => 'ENUM', 'constraint' => ['good', 'damaged', 'lost'], 'null' => true],
            'notes'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['status', 'due_date']);
        $this->forge->addForeignKey('book_id', 'books', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('issued_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('received_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('borrowings');

        // ---- Fines -----------------------------------------------------------
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'borrowing_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'reason'       => ['type' => 'ENUM', 'constraint' => ['overdue', 'damaged', 'lost', 'other'], 'default' => 'overdue'],
            'amount'       => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'status'       => ['type' => 'ENUM', 'constraint' => ['unpaid', 'paid', 'waived'], 'default' => 'unpaid'],
            'notes'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'processed_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'paid_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'status']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('borrowing_id', 'borrowings', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('processed_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('fines');

        // ---- Inventory history (every change to a title's copy count) -------
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'book_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'change'     => ['type' => 'INT', 'constraint' => 11],
            'reason'     => ['type' => 'ENUM', 'constraint' => ['initial', 'acquired', 'donated', 'found', 'damaged', 'lost', 'removed', 'correction'], 'default' => 'correction'],
            'stock_after' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'notes'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('book_id', 'books', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('inventory_logs');

        // ---- Settings (key/value) -------------------------------------------
        $this->forge->addField([
            'key'        => ['type' => 'VARCHAR', 'constraint' => 50],
            'value'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('key', true);
        $this->forge->createTable('settings');
    }

    public function down()
    {
        $this->forge->dropTable('settings', true);
        $this->forge->dropTable('inventory_logs', true);
        $this->forge->dropTable('fines', true);
        $this->forge->dropTable('borrowings', true);
    }
}
