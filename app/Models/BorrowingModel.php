<?php

namespace App\Models;

use CodeIgniter\Model;

class BorrowingModel extends Model
{
    protected $table         = 'borrowings';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'book_id', 'user_id', 'issued_by', 'borrowed_at', 'due_date',
        'returned_at', 'received_by', 'status', 'condition', 'notes',
    ];
    protected $useTimestamps = true;

    /** Base query with book, borrower and staff names joined in. */
    public function detailed(): self
    {
        return $this->select('borrowings.*, books.title, books.isbn, books.author,
                u.name AS borrower_name, u.id_number AS borrower_id_number,
                iss.name AS issued_by_name, rec.name AS received_by_name')
            ->join('books', 'books.id = borrowings.book_id')
            ->join('users u', 'u.id = borrowings.user_id')
            ->join('users iss', 'iss.id = borrowings.issued_by', 'left')
            ->join('users rec', 'rec.id = borrowings.received_by', 'left');
    }

    /**
     * Filters: status (active|overdue|returned|lost|all), q (title, ISBN, student),
     * user_id, from, to (borrowed_at date range).
     */
    public function filtered(array $f): self
    {
        $this->detailed();
        $today = date('Y-m-d');

        switch ($f['status'] ?? 'active') {
            case 'active':
                $this->where('borrowings.status', 'borrowed');
                break;
            case 'overdue':
                $this->where('borrowings.status', 'borrowed')->where('borrowings.due_date <', $today);
                break;
            case 'returned':
                $this->where('borrowings.status', 'returned');
                break;
            case 'lost':
                $this->where('borrowings.status', 'lost');
                break;
        }

        if (! empty($f['q'])) {
            $this->groupStart()
                ->like('books.title', $f['q'])
                ->orLike('books.isbn', $f['q'])
                ->orLike('u.name', $f['q'])
                ->orLike('u.id_number', $f['q'])
                ->groupEnd();
        }
        // Course the borrowed book is meant for (one of the four programs, or General reading).
        apply_course_filter($this, 'books.course', (string) ($f['course'] ?? ''));

        // Category of the borrowed book ("none" = uncategorized).
        if (! empty($f['category_id'])) {
            $f['category_id'] === 'none'
                ? $this->where('books.category_id', null)
                : $this->where('books.category_id', (int) $f['category_id']);
        }
        if (! empty($f['user_id'])) {
            $this->where('borrowings.user_id', (int) $f['user_id']);
        }
        if (! empty($f['from'])) {
            $this->where('borrowings.borrowed_at >=', $f['from'] . ' 00:00:00');
        }
        if (! empty($f['to'])) {
            $this->where('borrowings.borrowed_at <=', $f['to'] . ' 23:59:59');
        }

        return $this->orderBy('borrowings.status = \'borrowed\'', 'DESC', false)
            ->orderBy('borrowings.due_date', 'ASC')
            ->orderBy('borrowings.id', 'DESC');
    }

    public function activeCountForUser(int $userId): int
    {
        return $this->where('user_id', $userId)->where('status', 'borrowed')->countAllResults();
    }

    public function overdueCountForUser(int $userId): int
    {
        return $this->where('user_id', $userId)->where('status', 'borrowed')
            ->where('due_date <', date('Y-m-d'))->countAllResults();
    }

    public function userHasBook(int $userId, int $bookId): bool
    {
        return $this->where('user_id', $userId)->where('book_id', $bookId)
            ->where('status', 'borrowed')->countAllResults() > 0;
    }

    public function counts(): array
    {
        $today = date('Y-m-d');

        return [
            'active'        => $this->where('status', 'borrowed')->countAllResults(),
            'overdue'       => $this->where('status', 'borrowed')->where('due_date <', $today)->countAllResults(),
            'due_today'     => $this->where('status', 'borrowed')->where('due_date', $today)->countAllResults(),
            'issued_today'  => $this->where('borrowed_at >=', $today . ' 00:00:00')->countAllResults(),
            'returned_today' => $this->where('returned_at >=', $today . ' 00:00:00')->countAllResults(),
        ];
    }
}
