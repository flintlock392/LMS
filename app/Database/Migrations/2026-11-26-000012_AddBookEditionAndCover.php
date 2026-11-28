<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Books get an edition ("4th edition", "30th anniversary edition"…) and a cover
 * image: either a web address (e.g. Open Library) or a file uploaded by staff
 * into public/uploads/covers.
 */
class AddBookEditionAndCover extends Migration
{
    public function up()
    {
        $this->forge->addColumn('books', [
            'edition' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'after' => 'author'],
            'cover'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'description'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('books', ['edition', 'cover']);
    }
}
