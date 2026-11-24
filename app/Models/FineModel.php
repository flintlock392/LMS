<?php

namespace App\Models;

use CodeIgniter\Model;

class FineModel extends Model
{
    public const REASONS = ['overdue' => 'Overdue', 'damaged' => 'Damaged book', 'lost' => 'Lost book', 'other' => 'Other'];

    protected $table         = 'fines';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'user_id', 'borrowing_id', 'reason', 'amount', 'status', 'notes',
        'created_by', 'processed_by', 'paid_at',
    ];
    protected $useTimestamps = true;

    public function detailed(): self
    {
        return $this->select('fines.*, u.name AS borrower_name, u.id_number AS borrower_id_number,
                books.title, p.name AS processed_by_name')
            ->join('users u', 'u.id = fines.user_id')
            ->join('borrowings b', 'b.id = fines.borrowing_id', 'left')
            ->join('books', 'books.id = b.book_id', 'left')
            ->join('users p', 'p.id = fines.processed_by', 'left');
    }

    /** Filters: status (unpaid|paid|waived|all), q, user_id */
    public function filtered(array $f): self
    {
        $this->detailed();

        if (in_array($f['status'] ?? 'unpaid', ['unpaid', 'paid', 'waived'], true)) {
            $this->where('fines.status', $f['status'] ?? 'unpaid');
        }
        if (! empty($f['q'])) {
            // "Search by": student_no | name | book | (anything else) = all three
            switch ($f['by'] ?? 'all') {
                case 'student_no':
                    $this->like('u.id_number', $f['q']);
                    break;
                case 'name':
                    $this->like('u.name', $f['q']);
                    break;
                case 'book':
                    $this->like('books.title', $f['q']);
                    break;
                default:
                    $this->groupStart()
                        ->like('u.name', $f['q'])
                        ->orLike('u.id_number', $f['q'])
                        ->orLike('books.title', $f['q'])
                        ->groupEnd();
            }
        }
        if (! empty($f['user_id'])) {
            $this->where('fines.user_id', (int) $f['user_id']);
        }

        return $this->orderBy('fines.created_at', 'DESC');
    }

    public function unpaidTotalForUser(int $userId): float
    {
        $row = $this->selectSum('amount')->where('user_id', $userId)->where('status', 'unpaid')->first();

        return (float) ($row['amount'] ?? 0);
    }

    public function totals(): array
    {
        $rows = $this->db->table($this->table)
            ->select('status, COUNT(*) AS n, COALESCE(SUM(amount), 0) AS total')
            ->groupBy('status')->get()->getResultArray();

        $out = ['unpaid' => ['n' => 0, 'total' => 0.0], 'paid' => ['n' => 0, 'total' => 0.0], 'waived' => ['n' => 0, 'total' => 0.0]];
        foreach ($rows as $r) {
            $out[$r['status']] = ['n' => (int) $r['n'], 'total' => (float) $r['total']];
        }

        $today = $this->db->table($this->table)->selectSum('amount')
            ->where('status', 'paid')->where('paid_at >=', date('Y-m-d') . ' 00:00:00')
            ->get()->getRowArray();
        $out['collected_today'] = (float) ($today['amount'] ?? 0);

        return $out;
    }
}
