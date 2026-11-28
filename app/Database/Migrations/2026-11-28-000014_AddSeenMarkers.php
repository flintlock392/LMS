<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * When a student last opened My Loans / My Credits, so the sidebar can count
 * what is new since then (answered requests, answered appeals, deducted credits).
 */
class AddSeenMarkers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'loans_seen_at'   => ['type' => 'DATETIME', 'null' => true, 'after' => 'last_login_at'],
            'credits_seen_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'loans_seen_at'],
        ]);
        // Start from now, so older history isn't shown as new.
        $this->db->query('UPDATE users SET loans_seen_at = NOW(), credits_seen_at = NOW()');
    }

    public function down()
    {
        $this->forge->dropColumn('users', ['loans_seen_at', 'credits_seen_at']);
    }
}
