<?php

namespace App\Models;

use CodeIgniter\Model;

/** Every change to a student's credits. */
class CreditLogModel extends Model
{
    /** Reasons that deduct credits (violations). */
    public const DEDUCT_REASONS = [
        'overdue'    => 'Late return',
        'damaged'    => 'Damaged book',
        'lost'       => 'Lost book',
        'misconduct' => 'Library misconduct',
        'other'      => 'Other violation',
    ];

    /** Reasons that restore credits. */
    public const RESTORE_REASONS = [
        'appeal'          => 'Appeal approved',
        'library_service' => 'Helped in the library',
        'settlement'      => 'Status settled by Head Librarian',
        'correction'      => 'Correction',
    ];

    public const LABELS = self::DEDUCT_REASONS + self::RESTORE_REASONS;

    protected $table         = 'credit_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'change', 'balance_after', 'reason', 'borrowing_id', 'appeal_id', 'notes', 'created_by'];
    protected $useTimestamps = true;
    protected $updatedField  = '';

    /** History with student, staff and book names. Filters: q, user_id, direction (deducted|restored), from, to */
    public function filtered(array $f): self
    {
        $this->select('credit_logs.*, u.name AS student_name, u.id_number AS student_id_number, s.name AS staff_name, books.title')
            ->join('users u', 'u.id = credit_logs.user_id')
            ->join('users s', 's.id = credit_logs.created_by', 'left')
            ->join('borrowings br', 'br.id = credit_logs.borrowing_id', 'left')
            ->join('books', 'books.id = br.book_id', 'left');

        if (! empty($f['user_id'])) {
            $this->where('credit_logs.user_id', (int) $f['user_id']);
        }
        if (($f['direction'] ?? '') === 'deducted') {
            $this->where('credit_logs.change <', 0);
        } elseif (($f['direction'] ?? '') === 'restored') {
            $this->where('credit_logs.change >', 0);
        }
        if (! empty($f['q'])) {
            $this->groupStart()->like('u.name', $f['q'])->orLike('u.id_number', $f['q'])->orLike('books.title', $f['q'])->groupEnd();
        }
        if (! empty($f['from'])) {
            $this->where('credit_logs.created_at >=', $f['from'] . ' 00:00:00');
        }
        if (! empty($f['to'])) {
            $this->where('credit_logs.created_at <=', $f['to'] . ' 23:59:59');
        }

        return $this->orderBy('credit_logs.id', 'DESC');
    }
}
