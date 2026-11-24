<?php

namespace App\Libraries;

use App\Models\BookModel;
use App\Models\BorrowingModel;
use App\Models\FineModel;
use App\Models\InventoryLogModel;
use App\Models\ReservationModel;
use App\Models\SettingModel;
use App\Models\UserModel;
use RuntimeException;

/**
 * All library rules live here so every screen (staff desk, student portal)
 * enforces them the same way. Methods throw RuntimeException with a
 * user-friendly message when a rule blocks the action.
 */
class Circulation
{
    private BookModel $books;
    private BorrowingModel $borrowings;
    private ReservationModel $reservations;
    private FineModel $fines;
    private UserModel $users;
    private SettingModel $settings;

    public function __construct()
    {
        $this->books        = new BookModel();
        $this->borrowings   = new BorrowingModel();
        $this->reservations = new ReservationModel();
        $this->fines        = new FineModel();
        $this->users        = new UserModel();
        $this->settings     = new SettingModel();
    }

    // ------------------------------------------------------------------
    // Borrowing
    // ------------------------------------------------------------------

    /** Lend a book to a student. Returns the new borrowing id. */
    public function checkout(int $bookId, int $userId, int $staffId): int
    {
        $this->housekeep();

        $book = $this->books->find($bookId);
        if (! $book) {
            throw new RuntimeException('Book not found.');
        }
        $user = $this->borrowerOrFail($userId);

        $maxLoans = (int) $this->settings->value('max_loans');
        if ($this->borrowings->activeCountForUser($userId) >= $maxLoans) {
            throw new RuntimeException($user['name'] . ' already has the maximum of ' . $maxLoans . ' book(s) on loan.');
        }
        if ($this->borrowings->overdueCountForUser($userId) > 0) {
            throw new RuntimeException($user['name'] . ' has overdue books. They must be returned first.');
        }
        if ($this->settings->value('block_unpaid_fines') === '1') {
            $unpaid = $this->fines->unpaidTotalForUser($userId);
            if ($unpaid > 0) {
                throw new RuntimeException($user['name'] . ' has unpaid fines of ' . money($unpaid) . '. Settle them before borrowing.');
            }
        }
        if ($this->borrowings->userHasBook($userId, $bookId)) {
            throw new RuntimeException($user['name'] . ' already has a copy of "' . $book['title'] . '".');
        }

        // A ready reservation for this student already holds a copy at the desk.
        $reservation = $this->reservations->where('user_id', $userId)->where('book_id', $bookId)
            ->where('status', 'ready')->first();

        if (! $reservation && $this->books->availableCopies($bookId) <= 0) {
            throw new RuntimeException('No copies of "' . $book['title'] . '" are available. You can place a reservation instead.');
        }

        $db = db_connect();
        $db->transStart();

        $now = date('Y-m-d H:i:s');
        $id  = (int) $this->borrowings->insert([
            'book_id'     => $bookId,
            'user_id'     => $userId,
            'issued_by'   => $staffId,
            'borrowed_at' => $now,
            'due_date'    => date('Y-m-d', strtotime('+' . (int) $this->settings->value('loan_days') . ' days')),
            'status'      => 'borrowed',
        ], true);

        // A pending reservation by the same student is satisfied by this loan too.
        $open = $reservation ?? $this->reservations->openForUserAndBook($userId, $bookId);
        if ($open) {
            $this->reservations->update($open['id'], [
                'status'       => 'fulfilled',
                'closed_at'    => $now,
                'handled_by'   => $staffId,
                'borrowing_id' => $id,
            ]);
        }

        $db->transComplete();

        return $id;
    }

    /**
     * Receive a book back. $condition is good, damaged or lost.
     * Returns a summary message including any fines charged.
     */
    public function checkin(int $borrowingId, int $staffId, string $condition = 'good', ?float $damageFee = null, string $notes = ''): string
    {
        $loan = $this->borrowings->find($borrowingId);
        if (! $loan || $loan['status'] !== 'borrowed') {
            throw new RuntimeException('That loan is not active.');
        }
        if (! in_array($condition, ['good', 'damaged', 'lost'], true)) {
            $condition = 'good';
        }

        $book    = $this->books->find($loan['book_id']);
        $now     = date('Y-m-d H:i:s');
        $charged = [];

        $db = db_connect();
        $db->transStart();

        $this->borrowings->update($borrowingId, [
            'status'      => $condition === 'lost' ? 'lost' : 'returned',
            'condition'   => $condition,
            'returned_at' => $now,
            'received_by' => $staffId,
            'notes'       => $notes !== '' ? mb_substr($notes, 0, 255) : null,
        ]);

        if ($condition === 'lost') {
            $fee = (float) $this->settings->value('lost_book_fee');
            $this->addFine((int) $loan['user_id'], $borrowingId, 'lost', $fee, $staffId, 'Lost: ' . $book['title']);
            $charged[] = 'lost book ' . money($fee);

            $this->adjustStock((int) $book['id'], -1, 'lost', $staffId, 'Reported lost by borrower', true);
        } else {
            [$days, $fine] = $this->overdueFine($loan['due_date'], date('Y-m-d'));
            if ($fine > 0) {
                $this->addFine((int) $loan['user_id'], $borrowingId, 'overdue', $fine, $staffId, $days . ' day(s) late');
                $charged[] = 'overdue ' . money($fine) . ' (' . $days . ' day' . ($days === 1 ? '' : 's') . ')';
            }
            if ($condition === 'damaged') {
                $fee = $damageFee ?? (float) $this->settings->value('damaged_book_fee');
                if ($fee > 0) {
                    $this->addFine((int) $loan['user_id'], $borrowingId, 'damaged', $fee, $staffId, $notes ?: 'Returned damaged');
                    $charged[] = 'damage ' . money($fee);
                }
            }
        }

        $promoted = $this->promoteQueue((int) $book['id']);

        $db->transComplete();

        $message = '"' . $book['title'] . '" ' . ($condition === 'lost' ? 'marked as lost.' : 'returned.');
        if ($charged) {
            $message .= ' Fines charged: ' . implode(', ', $charged) . '.';
        }
        if ($promoted) {
            $message .= ' It is now held for the next reservation in the queue.';
        }

        return $message;
    }

    /** [days late, fine amount] for a due date and a return date (Y-m-d). */
    public function overdueFine(string $dueDate, string $returnDate): array
    {
        $days = (int) floor((strtotime($returnDate) - strtotime($dueDate)) / 86400);
        if ($days <= 0) {
            return [0, 0.0];
        }

        $fine = $days * (float) $this->settings->value('fine_per_day');
        $cap  = (float) $this->settings->value('max_overdue_fine');
        if ($cap > 0) {
            $fine = min($fine, $cap);
        }

        return [$days, round($fine, 2)];
    }

    // ------------------------------------------------------------------
    // Reservations
    // ------------------------------------------------------------------

    /** Place a reservation. Returns the reservation row after queue processing. */
    public function reserve(int $bookId, int $userId, ?int $actorId = null): array
    {
        $this->housekeep();

        $book = $this->books->find($bookId);
        if (! $book) {
            throw new RuntimeException('Book not found.');
        }
        $user = $this->borrowerOrFail($userId);

        if ($this->reservations->openForUserAndBook($userId, $bookId)) {
            throw new RuntimeException($user['name'] . ' already has an open reservation for "' . $book['title'] . '".');
        }
        if ($this->borrowings->userHasBook($userId, $bookId)) {
            throw new RuntimeException($user['name'] . ' is currently borrowing "' . $book['title'] . '".');
        }
        $max = (int) $this->settings->value('max_reservations');
        if ($this->reservations->openCountForUser($userId) >= $max) {
            throw new RuntimeException('The limit of ' . $max . ' active reservation(s) has been reached.');
        }

        $id = (int) $this->reservations->insert([
            'book_id'     => $bookId,
            'user_id'     => $userId,
            'status'      => 'pending',
            'reserved_at' => date('Y-m-d H:i:s'),
            'handled_by'  => $actorId,
        ], true);

        $this->promoteQueue($bookId);

        return $this->reservations->find($id);
    }

    public function cancelReservation(int $id, int $actorId, ?int $mustBelongTo = null): void
    {
        $r = $this->reservations->find($id);
        if (! $r || ! in_array($r['status'], ReservationModel::OPEN, true)) {
            throw new RuntimeException('That reservation is no longer active.');
        }
        if ($mustBelongTo !== null && (int) $r['user_id'] !== $mustBelongTo) {
            throw new RuntimeException('That reservation is not yours.');
        }

        $this->reservations->update($id, [
            'status'     => 'cancelled',
            'closed_at'  => date('Y-m-d H:i:s'),
            'handled_by' => $actorId,
        ]);

        if ($r['status'] === 'ready') {
            $this->promoteQueue((int) $r['book_id']);
        }
    }

    /**
     * Move the oldest pending reservations to "ready" while copies are free.
     * Returns how many were promoted.
     */
    public function promoteQueue(int $bookId): int
    {
        $promoted = 0;
        $holdDays = max(1, (int) $this->settings->value('hold_days'));

        while ($this->books->availableCopies($bookId) > 0) {
            $next = $this->reservations->where('book_id', $bookId)->where('status', 'pending')
                ->orderBy('reserved_at', 'ASC')->first();
            if (! $next) {
                break;
            }
            $this->reservations->update($next['id'], [
                'status'     => 'ready',
                'ready_at'   => date('Y-m-d H:i:s'),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+' . $holdDays . ' days')),
            ]);
            $promoted++;
        }

        return $promoted;
    }

    /** Expire desk holds that were not collected in time and pass the copy on. */
    public function housekeep(): void
    {
        $expired = $this->reservations->where('status', 'ready')
            ->where('expires_at <', date('Y-m-d H:i:s'))->findAll();

        foreach ($expired as $r) {
            $this->reservations->update($r['id'], ['status' => 'expired', 'closed_at' => date('Y-m-d H:i:s')]);
        }
        foreach (array_unique(array_column($expired, 'book_id')) as $bookId) {
            $this->promoteQueue((int) $bookId);
        }
    }

    // ------------------------------------------------------------------
    // Fines and inventory
    // ------------------------------------------------------------------

    public function addFine(int $userId, ?int $borrowingId, string $reason, float $amount, ?int $staffId, string $notes = ''): int
    {
        return (int) $this->fines->insert([
            'user_id'      => $userId,
            'borrowing_id' => $borrowingId,
            'reason'       => array_key_exists($reason, FineModel::REASONS) ? $reason : 'other',
            'amount'       => round($amount, 2),
            'status'       => 'unpaid',
            'notes'        => $notes !== '' ? mb_substr($notes, 0, 255) : null,
            'created_by'   => $staffId,
        ], true);
    }

    /**
     * Change a title's copy count and record why.
     * $fromLoan = true when the copy being removed was the one on loan (lost),
     * so it is not counted against the copies still on the shelf.
     */
    public function adjustStock(int $bookId, int $change, string $reason, ?int $staffId, string $notes = '', bool $fromLoan = false): int
    {
        $book = $this->books->find($bookId);
        if (! $book) {
            throw new RuntimeException('Book not found.');
        }
        if ($change === 0) {
            throw new RuntimeException('Enter a number of copies other than zero.');
        }

        $new = (int) $book['stock_count'] + $change;
        if ($new < 0) {
            throw new RuntimeException('"' . $book['title'] . '" only has ' . $book['stock_count'] . ' copies.');
        }
        if ($change < 0 && ! $fromLoan) {
            $free = $this->books->availableCopies($bookId);
            if (-$change > $free) {
                throw new RuntimeException('Only ' . $free . ' copy(ies) of "' . $book['title'] . '" are on the shelf. Copies on loan or on hold cannot be removed.');
            }
        }

        $this->books->update($bookId, ['stock_count' => $new]);
        (new InventoryLogModel())->insert([
            'book_id'     => $bookId,
            'change'      => $change,
            'reason'      => $reason,
            'stock_after' => $new,
            'notes'       => $notes !== '' ? mb_substr($notes, 0, 255) : null,
            'user_id'     => $staffId,
        ]);

        if ($change > 0) {
            $this->promoteQueue($bookId);
        }

        return $new;
    }

    private function borrowerOrFail(int $userId): array
    {
        $user = $this->users->find($userId);
        if (! $user || $user['role'] !== UserModel::STUDENT) {
            throw new RuntimeException('Student not found.');
        }
        if ($user['status'] !== 'active') {
            throw new RuntimeException($user['name'] . '\'s account is suspended.');
        }

        return $user;
    }
}
