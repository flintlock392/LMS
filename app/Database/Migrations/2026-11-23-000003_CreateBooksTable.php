<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Book titles. stock_count is the number of physical copies the library owns;
 * copies on loan are subtracted to get "available".
 */
class CreateBooksTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'isbn'             => ['type' => 'VARCHAR', 'constraint' => 20],
            'author'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'category_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'publisher'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'publication_year' => ['type' => 'SMALLINT', 'constraint' => 4, 'unsigned' => true, 'null' => true],
            'shelf_location'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'stock_count'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'description'      => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('isbn');
        $this->forge->addKey('author');
        $this->forge->addKey('title');
        // onUpdate, onDelete: deleting a category leaves its books uncategorized
        $this->forge->addForeignKey('category_id', 'categories', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('books');
    }

    public function down()
    {
        $this->forge->dropTable('books', true);
    }
}
