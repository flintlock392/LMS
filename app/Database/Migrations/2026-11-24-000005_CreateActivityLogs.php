<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Audit trail: one row per action taken in the system (who, what, when).
 * Shown to the Head Librarian in Book & Return → Activity Log.
 */
class CreateActivityLogs extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'category'     => ['type' => 'VARCHAR', 'constraint' => 20],
            'action'       => ['type' => 'VARCHAR', 'constraint' => 40],
            'description'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'subject_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'subject_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'ip_address'   => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at'   => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('created_at');
        $this->forge->addKey(['category', 'created_at']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('activity_logs');
    }

    public function down()
    {
        $this->forge->dropTable('activity_logs', true);
    }
}
