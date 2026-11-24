<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Key/value system settings with defaults, editable by the Head Librarian.
 */
class SettingModel extends Model
{
    /** key => [label, default, type, help] */
    public const DEFINITIONS = [
        'library_name'       => ['Library name', 'The Cozy Nook', 'text', 'Shown in the header and on reports.'],
        'loan_days'          => ['Loan period (days)', '7', 'int', 'How long a student may keep a book.'],
        'max_loans'          => ['Max books per student', '3', 'int', 'Books a student may have out at once.'],
        // Credits added for violations (a student is blocked at 20 credits).
        'credits_per_late_day' => ['Credits per day late', '1', 'credits', 'Added for each day a book is returned late.'],
        'max_late_credits'     => ['Max credits per late return', '5', 'credits', 'Cap on credits from a single late return.'],
        'damaged_credits'      => ['Credits for a damaged book', '3', 'credits', 'Added when a book comes back damaged.'],
        'lost_credits'         => ['Credits for a lost book', '5', 'credits', 'Added when a borrowed book is reported lost.'],
    ];

    protected $table         = 'settings';
    protected $primaryKey    = 'key';
    protected $returnType    = 'array';
    protected $allowedFields = ['key', 'value', 'updated_at'];
    protected $useAutoIncrement = false;

    private static ?array $cache = null;

    public function all(): array
    {
        if (self::$cache === null) {
            $values = [];
            foreach (self::DEFINITIONS as $key => $def) {
                $values[$key] = $def[1];
            }
            foreach ($this->findAll() as $row) {
                if (array_key_exists($row['key'], $values)) {
                    $values[$row['key']] = $row['value'];
                }
            }
            self::$cache = $values;
        }

        return self::$cache;
    }

    public function value(string $key): string
    {
        return (string) ($this->all()[$key] ?? '');
    }

    public function saveAll(array $values): void
    {
        $now = date('Y-m-d H:i:s');
        foreach ($values as $key => $value) {
            if (! isset(self::DEFINITIONS[$key])) {
                continue;
            }
            $this->db->table($this->table)->replace(['key' => $key, 'value' => (string) $value, 'updated_at' => $now]);
        }
        self::$cache = null;
    }
}
