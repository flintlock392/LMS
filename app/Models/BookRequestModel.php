<?php

namespace App\Models;

use CodeIgniter\Model;

/** Students' borrow / return requests (see Library → Requested). */
class BookRequestModel extends Model
{
    protected $table         = 'book_requests';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['type', 'user_id', 'book_id', 'borrowing_id', 'status', 'reason', 'handled_by', 'handled_at'];
    protected $useTimestamps = true;

    /** Requests with book, student and loan details. */
    public function detailed(): self
    {
        return $this->select('book_requests.*, books.title, books.isbn, books.author, books.cover, books.edition, books.course AS book_course,
                u.name AS borrower_name, u.id_number AS borrower_id_number, u.course AS student_course,
                br.due_date, br.borrowed_at', false)
            ->select(BookModel::availableSql() . ' AS available', false)
            ->join('books', 'books.id = book_requests.book_id')
            ->join('users u', 'u.id = book_requests.user_id')
            ->join('borrowings br', 'br.id = book_requests.borrowing_id', 'left');
    }

    /** Pending requests for the staff list. Filters: q, from, to, course, category_id */
    public function pendingFiltered(array $f): self
    {
        $this->detailed()->where('book_requests.status', 'pending');

        if (! empty($f['q'])) {
            $this->groupStart()
                ->like('books.title', $f['q'])->orLike('books.isbn', $f['q'])
                ->orLike('u.name', $f['q'])->orLike('u.id_number', $f['q'])
                ->groupEnd();
        }
        if (! empty($f['from'])) {
            $this->where('book_requests.created_at >=', $f['from'] . ' 00:00:00');
        }
        if (! empty($f['to'])) {
            $this->where('book_requests.created_at <=', $f['to'] . ' 23:59:59');
        }
        apply_course_filter($this, 'books.course', (string) ($f['course'] ?? ''));
        apply_shelf_filter($this, 'books.shelf_location', (string) ($f['shelf'] ?? ''));
        if (! empty($f['category_id'])) {
            $f['category_id'] === 'none'
                ? $this->where('books.category_id', null)
                : $this->where('books.category_id', (int) $f['category_id']);
        }

        return $this->orderBy('book_requests.created_at', 'ASC');   // oldest first: first come, first served
    }

    public function pendingCount(): int
    {
        return $this->where('status', 'pending')->countAllResults();
    }

    /** How many different students have at least one request waiting. */
    public function pendingStudentCount(): int
    {
        return (int) ($this->db->table($this->table)->select('COUNT(DISTINCT user_id) AS n')->where('status', 'pending')->get()->getRow()->n ?? 0);
    }

    /** A student's pending requests, keyed for the catalog: [book_id => ['borrow' => id, 'return' => id]]. */
    public function pendingMapFor(int $userId): array
    {
        $map = [];
        foreach ($this->where('user_id', $userId)->where('status', 'pending')->findAll() as $r) {
            $map[(int) $r['book_id']][$r['type']] = (int) $r['id'];
        }

        return $map;
    }

    public function findPending(int $userId, int $bookId, string $type): ?array
    {
        return $this->where('user_id', $userId)->where('book_id', $bookId)
            ->where('type', $type)->where('status', 'pending')->first();
    }
}
