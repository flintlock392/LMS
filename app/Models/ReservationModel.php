<?php

namespace App\Models;

use CodeIgniter\Model;

class ReservationModel extends Model
{
    public const OPEN = ['pending', 'ready'];

    protected $table         = 'reservations';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'book_id', 'user_id', 'status', 'reserved_at', 'ready_at',
        'expires_at', 'closed_at', 'handled_by', 'borrowing_id',
    ];
    protected $useTimestamps = true;

    public function detailed(): self
    {
        return $this->select('reservations.*, books.title, books.isbn, books.author,
                u.name AS borrower_name, u.id_number AS borrower_id_number')
            ->join('books', 'books.id = reservations.book_id')
            ->join('users u', 'u.id = reservations.user_id');
    }

    /** Filters: status (open|pending|ready|closed|all), q, user_id */
    public function filtered(array $f): self
    {
        $this->detailed();

        switch ($f['status'] ?? 'open') {
            case 'open':
                $this->whereIn('reservations.status', self::OPEN);
                break;
            case 'pending':
            case 'ready':
                $this->where('reservations.status', $f['status']);
                break;
            case 'closed':
                $this->whereNotIn('reservations.status', self::OPEN);
                break;
        }

        if (! empty($f['q'])) {
            $this->groupStart()
                ->like('books.title', $f['q'])
                ->orLike('u.name', $f['q'])
                ->orLike('u.id_number', $f['q'])
                ->groupEnd();
        }
        if (! empty($f['user_id'])) {
            $this->where('reservations.user_id', (int) $f['user_id']);
        }

        return $this->orderBy("FIELD(reservations.status, 'ready', 'pending', 'fulfilled', 'expired', 'cancelled')", '', false)
            ->orderBy('reservations.reserved_at', 'ASC');
    }

    public function openCountForUser(int $userId): int
    {
        return $this->where('user_id', $userId)->whereIn('status', self::OPEN)->countAllResults();
    }

    public function openForUserAndBook(int $userId, int $bookId): ?array
    {
        return $this->where('user_id', $userId)->where('book_id', $bookId)
            ->whereIn('status', self::OPEN)->first();
    }

    /** Position in the queue (1 = next) for a pending reservation. */
    public function queuePosition(array $reservation): int
    {
        return $this->where('book_id', $reservation['book_id'])
            ->where('status', 'pending')
            ->where('reserved_at <=', $reservation['reserved_at'])
            ->countAllResults();
    }

    public function counts(): array
    {
        return [
            'pending' => $this->where('status', 'pending')->countAllResults(),
            'ready'   => $this->where('status', 'ready')->countAllResults(),
        ];
    }
}
