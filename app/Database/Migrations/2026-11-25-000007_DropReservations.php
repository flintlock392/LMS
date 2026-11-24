<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The reservation feature was removed. This deletes everything it stored:
 * the reservations table, its Activity Log entries and its settings.
 *
 * Fresh installs never create the table (see CreateCirculationTables), so
 * dropping it here only affects databases set up before the feature was removed.
 */
class DropReservations extends Migration
{
    public function up()
    {
        $this->db->table('activity_logs')
            ->groupStart()->where('category', 'reservation')->orWhere('subject_type', 'reservation')->groupEnd()
            ->delete();

        $this->db->table('settings')->whereIn('key', ['max_reservations', 'hold_days'])->delete();

        $this->forge->dropTable('reservations', true);
    }

    public function down()
    {
        // Intentionally empty: the reservation feature no longer exists, so there is nothing to restore.
    }
}
