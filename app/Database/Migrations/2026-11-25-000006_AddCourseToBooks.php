<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The course (program) a book is meant for, e.g. "BS Information Technology".
 * Empty = a general book for every course. Used to filter books when reserving.
 */
class AddCourseToBooks extends Migration
{
    public function up()
    {
        $this->forge->addColumn('books', [
            'course' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'category_id'],
        ]);
        $this->db->query('CREATE INDEX books_course ON books (course)');
    }

    public function down()
    {
        $this->forge->dropColumn('books', 'course');
    }
}
