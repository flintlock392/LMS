<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Users of all three levels: head librarian, librarian staff and students.
 *
 * id_number is the login username: the Student ID number for students
 * (it doubles as the library card number) and the Staff ID for staff.
 */
class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'id_number'     => ['type' => 'VARCHAR', 'constraint' => 30],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'phone'         => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'password'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'role'          => ['type' => 'ENUM', 'constraint' => ['head_librarian', 'librarian', 'student'], 'default' => 'student'],
            'status'        => ['type' => 'ENUM', 'constraint' => ['active', 'suspended'], 'default' => 'active'],
            'course'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'year_level'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'position'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('id_number');
        $this->forge->addUniqueKey('email');
        $this->forge->addKey('role');
        $this->forge->createTable('users');
    }

    public function down()
    {
        $this->forge->dropTable('users', true);
    }
}
