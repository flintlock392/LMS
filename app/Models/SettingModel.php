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
        'fine_per_day'       => ['Overdue fine per day', '5.00', 'money', 'Charged for each day a book is late.'],
        'max_overdue_fine'   => ['Max overdue fine per book', '200.00', 'money', 'Cap on a single overdue fine. 0 = no cap.'],
        'lost_book_fee'      => ['Lost book fee', '500.00', 'money', 'Charged when a borrowed book is reported lost.'],
        'damaged_book_fee'   => ['Damaged book fee', '150.00', 'money', 'Default charge when a book comes back damaged.'],
        'block_unpaid_fines' => ['Block borrowing with unpaid fines', '1', 'bool', 'Students with unpaid fines cannot borrow.'],
        'currency'           => ['Currency symbol', '₱', 'text', 'Used when showing fines.'],
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
