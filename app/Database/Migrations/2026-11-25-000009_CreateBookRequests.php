<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Students' requests from Browse Books:
 *   borrow  - "Request to borrow" a book that is on the shelf
 *   return  - "Request return" of a book they have on loan
 * Library staff approve or decline them in Library → Requested.
 */
class CreateBookRequests extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'type'         => ['type' => 'ENUM', 'constraint' => ['borrow', 'return']],
            'user_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'book_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'borrowing_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'status'       => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'declined', 'cancelled'], 'default' => 'pending'],
            'reason'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'handled_by'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'handled_at'   => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['status', 'type']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('book_id', 'books', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('borrowing_id', 'borrowings', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('handled_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('book_requests');
    }

    public function down()
    {
        $this->forge->dropTable('book_requests', true);
    }
}
