<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    /** category => [label, bootstrap-icon] */
    public const CATEGORIES = [
        'circulation' => ['Borrowing & returns', 'arrow-left-right'],
        'inventory'   => ['Inventory', 'box-seam'],
        'book'        => ['Books & categories', 'book'],
        'credit'      => ['Credits', 'shield-exclamation'],
        'fine'        => ['Fines (old records)', 'cash-coin'],
        'user'        => ['Students', 'people'],
        'staff'       => ['Staff', 'person-badge'],
        'settings'    => ['Settings', 'gear'],
        'auth'        => ['Sign-ins', 'box-arrow-in-right'],
    ];

    protected $table         = 'activity_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'category', 'action', 'description', 'subject_type', 'subject_id', 'ip_address', 'created_at'];
    protected $useTimestamps = false;

    /**
     * Actions between two dates (inclusive, whole days).
     * Filters: from, to (Y-m-d), category, user_id, q
     */
    public function between(array $f): self
    {
        $this->select('activity_logs.*, users.name AS user_name, users.role AS user_role, users.id_number AS user_id_number')
            ->join('users', 'users.id = activity_logs.user_id', 'left')
            ->where('activity_logs.created_at >=', $f['from'] . ' 00:00:00')
            ->where('activity_logs.created_at <=', $f['to'] . ' 23:59:59');

        if (! empty($f['category']) && isset(self::CATEGORIES[$f['category']])) {
            $this->where('activity_logs.category', $f['category']);
        }
        if (($f['user_id'] ?? '') === 'system') {
            $this->where('activity_logs.user_id', null);
        } elseif (! empty($f['user_id'])) {
            $this->where('activity_logs.user_id', (int) $f['user_id']);
        }
        if (! empty($f['q'])) {
            $this->groupStart()->like('activity_logs.description', $f['q'])->orLike('users.name', $f['q'])->groupEnd();
        }

        return $this->orderBy('activity_logs.created_at', 'DESC')->orderBy('activity_logs.id', 'DESC');
    }

    /** category => count for the same range (ignores the category filter). */
    public function countsByCategory(string $from, string $to): array
    {
        $rows = $this->db->table($this->table)
            ->select('category, COUNT(*) AS n')
            ->where('created_at >=', $from . ' 00:00:00')
            ->where('created_at <=', $to . ' 23:59:59')
            ->groupBy('category')->get()->getResultArray();

        return array_column($rows, 'n', 'category');
    }
}
