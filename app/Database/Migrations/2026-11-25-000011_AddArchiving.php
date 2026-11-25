<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * "Delete" becomes "Archive": books, categories and accounts are no longer
 * erased. They get an archived_at date, disappear from every list, and the
 * Head Librarian can restore them from the Archive page.
 */
class AddArchiving extends Migration
{
    private const TABLES = ['books', 'categories', 'users'];

    public function up()
    {
        foreach (self::TABLES as $table) {
            $this->forge->addColumn($table, [
                'archived_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
        }
    }

    public function down()
    {
        foreach (self::TABLES as $table) {
            $this->forge->dropColumn($table, 'archived_at');
        }
    }
}
