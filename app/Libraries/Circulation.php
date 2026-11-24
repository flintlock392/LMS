<?php

namespace App\Libraries;

use App\Models\BookModel;
use App\Models\BookRequestModel;
use App\Models\BorrowingModel;
use App\Models\FineModel;
use App\Models\InventoryLogModel;
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
    private FineModel $fines;
    private UserModel $users;
    private SettingModel $settings;

    public function __construct()
    {
        $this->books        = new BookModel();
        $this->borrowings   = new BorrowingModel();
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
        [$book, $user] = $this->assertCanBorrow($bookId, $userId);

        $id = (int) $this->borrowings->insert([
            'book_id'     => $bookId,
            'user_id'     => $userId,
            'issued_by'   => $staffId,
            'borrowed_at' => date('Y-m-d H:i:s'),
            'due_date'    => date('Y-m-d', strtotime('+' . (int) $this->settings->value('loan_days') . ' days')),
            'status'      => 'borrowed',
        ], true);

        log_activity('circulation', 'issued', 'Issued "' . $book['title'] . '" to ' . $user['name'] . ' (' . $user['id_number'] . '), due '
            . fmt_date(date('Y-m-d', strtotime('+' . (int) $this->settings->value('loan_days') . ' days'))), 'borrowing', $id, $staffId);

        // A waiting "Request to borrow" for this book is now fulfilled.
        $this->closePendingRequests(['user_id' => $userId, 'book_id' => $bookId, 'type' => 'borrow'], $staffId, $id);

        return $id;
    }

    /**
     * Every rule a student must pass to borrow (used at the desk and for requests).
     * $forRequest: also counts the student's other waiting borrow requests toward the limit.
     *
     * @return array{0: array, 1: array} [book, user]
     */
    private function assertCanBorrow(int $bookId, int $userId, bool $forRequest = false): array
    {
        $book = $this->books->find($bookId);
        if (! $book) {
            throw new RuntimeException('Book not found.');
        }
        $user = $this->borrowerOrFail($userId);
        $who  = $forRequest ? 'You' : $user['name'];

        $maxLoans = (int) $this->settings->value('max_loans');
        $count    = $this->borrowings->activeCountForUser($userId);
        if ($forRequest) {
            $count += (new BookRequestModel())->where('user_id', $userId)->where('type', 'borrow')->where('status', 'pending')->countAllResults();
        }
        if ($count >= $maxLoans) {
            throw new RuntimeException($who . ($forRequest ? ' already have' : ' already has') . ' the maximum of ' . $maxLoans . ' book(s)'
                . ($forRequest ? ' on loan or requested.' : ' on loan.'));
        }
        if ($this->borrowings->overdueCountForUser($userId) > 0) {
            throw new RuntimeException($who . ($forRequest ? ' have' : ' has') . ' overdue books. They must be returned first.');
        }
        if ($this->settings->value('block_unpaid_fines') === '1') {
            $unpaid = $this->fines->unpaidTotalForUser($userId);
            if ($unpaid > 0) {
                throw new RuntimeException($who . ($forRequest ? ' have' : ' has') . ' unpaid fines of ' . money($unpaid) . '. Settle them before borrowing.');
            }
        }
        if ($this->borrowings->userHasBook($userId, $bookId)) {
            throw new RuntimeException($who . ($forRequest ? ' already have' : ' already has') . ' a copy of "' . $book['title'] . '".');
        }
        if ($this->books->availableCopies($bookId) <= 0) {
            throw new RuntimeException('No copies of "' . $book['title'] . '" are on the shelf right now. All copies are on loan.');
        }

        return [$book, $user];
    }

    // ------------------------------------------------------------------
    // Student requests (Browse Books → staff approve in Library → Requested)
    // ------------------------------------------------------------------

    /** Student asks to borrow a book that is on the shelf. Returns the request id. */
    public function requestBorrow(int $bookId, int $userId): int
    {
        $requests = new BookRequestModel();
        if ($requests->findPending($userId, $bookId, 'borrow')) {
            throw new RuntimeException('You already requested this book. Please wait for the library staff.');
        }
        [$book, $user] = $this->assertCanBorrow($bookId, $userId, true);

        $id = (int) $requests->insert(['type' => 'borrow', 'user_id' => $userId, 'book_id' => $bookId, 'status' => 'pending'], true);
        log_activity('circulation', 'borrow_requested', $user['name'] . ' (' . $user['id_number'] . ') requested to borrow "' . $book['title'] . '"', 'request', $id, $userId);

        return $id;
    }

    /** Student asks to return a book they have on loan. Returns the request id. */
    public function requestReturn(int $borrowingId, int $userId): int
    {
        $loan = $this->borrowings->find($borrowingId);
        if (! $loan || (int) $loan['user_id'] !== $userId || $loan['status'] !== 'borrowed') {
            throw new RuntimeException('That book is not on loan to you.');
        }
        $requests = new BookRequestModel();
        if ($requests->where('borrowing_id', $borrowingId)->where('type', 'return')->where('status', 'pending')->first()) {
            throw new RuntimeException('You already asked to return this book. Please bring it to the library desk.');
        }

        $book = $this->books->find($loan['book_id']);
        $user = $this->users->find($userId);
        $id   = (int) $requests->insert([
            'type' => 'return', 'user_id' => $userId, 'book_id' => (int) $loan['book_id'], 'borrowing_id' => $borrowingId, 'status' => 'pending',
        ], true);
        log_activity('circulation', 'return_requested', $user['name'] . ' (' . $user['id_number'] . ') requested to return "' . $book['title'] . '"', 'request', $id, $userId);

        return $id;
    }

    /** Student withdraws their own waiting request. */
    public function cancelRequest(int $requestId, int $userId): void
    {
        $requests = new BookRequestModel();
        $r        = $requests->find($requestId);
        if (! $r || (int) $r['user_id'] !== $userId || $r['status'] !== 'pending') {
            throw new RuntimeException('That request is no longer waiting.');
        }
        $requests->update($requestId, ['status' => 'cancelled', 'handled_at' => date('Y-m-d H:i:s')]);

        $book = $this->books->find($r['book_id']);
        log_activity('circulation', 'request_cancelled', 'Cancelled own ' . $r['type'] . ' request for "' . $book['title'] . '"', 'request', $requestId, $userId);
    }

    /** Staff approve a borrow request: lends the book (all borrowing rules re-checked). */
    public function approveBorrowRequest(int $requestId, int $staffId): int
    {
        $r = $this->pendingRequestOrFail($requestId, 'borrow');

        return $this->checkout((int) $r['book_id'], (int) $r['user_id'], $staffId);   // closes the request too
    }

    /** Staff receive a book for a return request (condition / damage / lost as at the desk). */
    public function approveReturnRequest(int $requestId, int $staffId, string $condition = 'good', ?float $damageFee = null, string $notes = ''): string
    {
        $r = $this->pendingRequestOrFail($requestId, 'return');

        return $this->checkin((int) $r['borrowing_id'], $staffId, $condition, $damageFee, $notes);   // closes the request too
    }

    /** Staff decline a request, optionally saying why (the student sees the reason). */
    public function declineRequest(int $requestId, int $staffId, string $reason = ''): void
    {
        $r = (new BookRequestModel())->find($requestId);
        if (! $r || $r['status'] !== 'pending') {
            throw new RuntimeException('That request is no longer waiting.');
        }
        (new BookRequestModel())->update($requestId, [
            'status' => 'declined', 'handled_by' => $staffId, 'handled_at' => date('Y-m-d H:i:s'),
            'reason' => $reason !== '' ? mb_substr($reason, 0, 255) : null,
        ]);

        $book = $this->books->find($r['book_id']);
        $user = $this->users->find($r['user_id']);
        log_activity('circulation', 'request_declined', 'Declined ' . $user['name'] . '\'s ' . $r['type'] . ' request for "' . $book['title'] . '"'
            . ($reason !== '' ? ' · reason: ' . $reason : ''), 'request', $requestId, $staffId);
    }

    private function pendingRequestOrFail(int $requestId, string $type): array
    {
        $r = (new BookRequestModel())->find($requestId);
        if (! $r || $r['status'] !== 'pending' || $r['type'] !== $type) {
            throw new RuntimeException('That request is no longer waiting.');
        }

        return $r;
    }

    /** Mark matching waiting requests as approved (e.g. a book issued or returned at the desk). */
    private function closePendingRequests(array $where, int $staffId, ?int $borrowingId = null): void
    {
        $requests = new BookRequestModel();
        foreach ($where as $col => $val) {
            $requests->where($col, $val);
        }
        foreach ($requests->where('status', 'pending')->findAll() as $r) {
            (new BookRequestModel())->update($r['id'], array_filter([
                'status' => 'approved', 'handled_by' => $staffId, 'handled_at' => date('Y-m-d H:i:s'),
                'borrowing_id' => $borrowingId ?? $r['borrowing_id'],
            ], static fn ($v) => $v !== null));
        }
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

        // A waiting "Request return" for this loan is now done.
        $this->closePendingRequests(['borrowing_id' => $borrowingId, 'type' => 'return'], $staffId);

        $db->transComplete();

        $message = '"' . $book['title'] . '" ' . ($condition === 'lost' ? 'marked as lost.' : 'returned.');
        if ($charged) {
            $message .= ' Fines charged: ' . implode(', ', $charged) . '.';
        }

        $borrower = $this->users->find($loan['user_id']);
        log_activity(
            'circulation',
            $condition === 'lost' ? 'lost' : 'returned',
            ($condition === 'lost' ? 'Marked "' . $book['title'] . '" as lost by ' : 'Received "' . $book['title'] . '" from ')
                . $borrower['name'] . ($condition === 'damaged' ? ' (damaged)' : '') . ($charged ? ' · fines: ' . implode(', ', $charged) : ''),
            'borrowing',
            $borrowingId,
            $staffId
        );

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
                throw new RuntimeException('Only ' . $free . ' copy(ies) of "' . $book['title'] . '" are on the shelf. Copies on loan cannot be removed.');
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

        log_activity('inventory', $change > 0 ? 'added' : 'removed', ($change > 0 ? 'Added ' : 'Removed ') . abs($change) . ' cop' . (abs($change) === 1 ? 'y' : 'ies')
            . ($change > 0 ? ' to "' : ' of "') . $book['title'] . '" (' . (InventoryLogModel::LABELS[$reason] ?? $reason) . ') · now ' . $new
            . ($notes !== '' ? ' · ' . $notes : ''), 'book', $bookId, $staffId);

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
