<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The Course / Program drop-down uses full names ("Bachelor of Science in ...").
 * Rename the short forms already saved on students and books so they match.
 */
class UseFullCourseNames extends Migration
{
    private const NAMES = [
        'BS Information Technology' => 'Bachelor of Science in Information Technology',
        'BS Computer Science'       => 'Bachelor of Science in Computer Science',
        'BS Education'              => 'Bachelor of Science in Education',
        'BS Hospitality Management' => 'Bachelor of Science in Hospitality Management',
        'BS Psychology'             => 'Bachelor of Science in Psychology',
        'BA Communication'          => 'Bachelor of Arts in Communication',
        'BS Math'                   => 'Bachelor of Science in Mathematics',
    ];

    public function up()
    {
        foreach (self::NAMES as $short => $full) {
            $this->db->table('users')->where('course', $short)->update(['course' => $full]);
            $this->db->table('books')->where('course', $short)->update(['course' => $full]);
        }
    }

    public function down()
    {
        foreach (self::NAMES as $short => $full) {
            $this->db->table('users')->where('course', $full)->update(['course' => $short]);
            $this->db->table('books')->where('course', $full)->update(['course' => $short]);
        }
    }
}
