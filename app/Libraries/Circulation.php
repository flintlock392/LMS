<?php

namespace App\Libraries;

use App\Models\BookModel;
use App\Models\BookRequestModel;
use App\Models\BorrowingModel;
use App\Models\CreditLogModel;
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
    private UserModel $users;
    private SettingModel $settings;

    public function __construct()
    {
        $this->books        = new BookModel();
        $this->borrowings   = new BorrowingModel();
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

        // Students on "Final warning" get a shorter loan period (see CreditPolicy).
        $due = date('Y-m-d', strtotime('+' . $this->loanDaysFor($user) . ' days'));

        $id = (int) $this->borrowings->insert([
            'book_id'     => $bookId,
            'user_id'     => $userId,
            'issued_by'   => $staffId,
            'borrowed_at' => date('Y-m-d H:i:s'),
            'due_date'    => $due,
            'status'      => 'borrowed',
        ], true);

        log_activity('circulation', 'issued', 'Issued "' . $book['title'] . '" to ' . $user['name'] . ' (' . $user['id_number'] . '), due '
            . fmt_date($due), 'borrowing', $id, $staffId);

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

        // Credits decide how many books the student may have (see CreditPolicy).
        $credits = (int) ($user['credits'] ?? 0);
        $tier    = CreditPolicy::tier($credits);
        if (CreditPolicy::isBlocked($credits)) {
            throw new RuntimeException($who . ($forRequest ? ' are' : ' is') . ' blocked from borrowing (' . $credits . ' credits). '
                . ($forRequest ? 'Please appeal to the Head Librarian from My Credits.' : 'The Head Librarian must settle the student\'s status first.'));
        }
        $maxLoans = CreditPolicy::maxBooks($credits, (int) $this->settings->value('max_loans'));
        $count    = $this->borrowings->activeCountForUser($userId);
        if ($forRequest) {
            $count += (new BookRequestModel())->where('user_id', $userId)->where('type', 'borrow')->where('status', 'pending')->countAllResults();
        }
        if ($count >= $maxLoans) {
            throw new RuntimeException($who . ($forRequest ? ' already have' : ' already has') . ' the maximum of ' . $maxLoans . ' book(s)'
                . ($forRequest ? ' on loan or requested' : ' on loan')
                . ($tier['key'] !== 'good' ? ' allowed at "' . $tier['label'] . '" (' . $credits . ' credits).' : '.'));
        }
        if ($this->borrowings->overdueCountForUser($userId) > 0) {
            throw new RuntimeException($who . ($forRequest ? ' have' : ' has') . ' overdue books. They must be returned first.');
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
    public function approveReturnRequest(int $requestId, int $staffId, string $condition = 'good', ?int $damageCredits = null, string $notes = ''): string
    {
        $r = $this->pendingRequestOrFail($requestId, 'return');

        return $this->checkin((int) $r['borrowing_id'], $staffId, $condition, $damageCredits, $notes);   // closes the request too
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
     * Returns a summary message including any credits added.
     */
    public function checkin(int $borrowingId, int $staffId, string $condition = 'good', ?int $damageCredits = null, string $notes = ''): string
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

        // Violations add credits instead of fines.
        if ($condition === 'lost') {
            $add = (int) $this->settings->value('lost_credits');
            $this->changeCredits((int) $loan['user_id'], $add, 'lost', $staffId, 'Lost: ' . $book['title'], $borrowingId);
            $charged[] = '+' . $add . ' for a lost book';

            $this->adjustStock((int) $book['id'], -1, 'lost', $staffId, 'Reported lost by borrower', true);
        } else {
            [$days, $late] = $this->overdueCredits($loan['due_date'], date('Y-m-d'));
            if ($late > 0) {
                $this->changeCredits((int) $loan['user_id'], $late, 'overdue', $staffId, $days . ' day(s) late: ' . $book['title'], $borrowingId);
                $charged[] = '+' . $late . ' for ' . $days . ' day' . ($days === 1 ? '' : 's') . ' late';
            }
            if ($condition === 'damaged') {
                $add = $damageCredits ?? (int) $this->settings->value('damaged_credits');
                if ($add > 0) {
                    $this->changeCredits((int) $loan['user_id'], $add, 'damaged', $staffId, $notes ?: 'Returned damaged: ' . $book['title'], $borrowingId);
                    $charged[] = '+' . $add . ' for damage';
                }
            }
        }

        // A waiting "Request return" for this loan is now done.
        $this->closePendingRequests(['borrowing_id' => $borrowingId, 'type' => 'return'], $staffId);

        $db->transComplete();

        $message = '"' . $book['title'] . '" ' . ($condition === 'lost' ? 'marked as lost.' : 'returned.');
        if ($charged) {
            $now = (int) ($this->users->find($loan['user_id'])['credits'] ?? 0);
            $message .= ' Credits added: ' . implode(', ', $charged) . '. The student now has ' . $now . ' credit' . ($now === 1 ? '' : 's')
                . ' (' . CreditPolicy::tier($now)['label'] . ').';
        }

        $borrower = $this->users->find($loan['user_id']);
        log_activity(
            'circulation',
            $condition === 'lost' ? 'lost' : 'returned',
            ($condition === 'lost' ? 'Marked "' . $book['title'] . '" as lost by ' : 'Received "' . $book['title'] . '" from ')
                . $borrower['name'] . ($condition === 'damaged' ? ' (damaged)' : '') . ($charged ? ' · credits: ' . implode(', ', $charged) : ''),
            'borrowing',
            $borrowingId,
            $staffId
        );

        return $message;
    }

    /** [days late, credits] for a due date and a return date (Y-m-d). */
    public function overdueCredits(string $dueDate, string $returnDate): array
    {
        $days = (int) floor((strtotime($returnDate) - strtotime($dueDate)) / 86400);
        if ($days <= 0) {
            return [0, 0];
        }

        $credits = $days * (int) $this->settings->value('credits_per_late_day');
        $cap     = (int) $this->settings->value('max_late_credits');
        if ($cap > 0) {
            $credits = min($credits, $cap);
        }

        return [$days, $credits];
    }

    /** Loan period for this student, shortened on "Final warning". */
    public function loanDaysFor(array $user): int
    {
        return CreditPolicy::loanDays((int) ($user['credits'] ?? 0), (int) $this->settings->value('loan_days'));
    }

    // ------------------------------------------------------------------
    // Credits
    // ------------------------------------------------------------------

    /**
     * Add (positive) or reduce (negative) a student's credits, kept between 0 and 20,
     * and record it in the credit history and Activity Log. Returns the new balance.
     */
    public function changeCredits(int $userId, int $change, string $reason, ?int $staffId, string $notes = '', ?int $borrowingId = null, ?int $appealId = null): int
    {
        $user = $this->users->find($userId);
        if (! $user || $user['role'] !== UserModel::STUDENT) {
            throw new RuntimeException('Student not found.');
        }
        if ($change === 0) {
            throw new RuntimeException('Enter a number of credits other than zero.');
        }
        if (! array_key_exists($reason, CreditLogModel::LABELS)) {
            $reason = $change > 0 ? 'other' : 'correction';
        }

        $before = (int) $user['credits'];
        $after  = max(0, min(CreditPolicy::MAX, $before + $change));
        $actual = $after - $before;
        if ($actual === 0) {
            throw new RuntimeException($change > 0
                ? $user['name'] . ' already has the maximum of ' . CreditPolicy::MAX . ' credits.'
                : $user['name'] . ' has no credits to reduce.');
        }

        $this->users->update($userId, ['credits' => $after]);
        (new CreditLogModel())->insert([
            'user_id'       => $userId,
            'change'        => $actual,
            'balance_after' => $after,
            'reason'        => $reason,
            'borrowing_id'  => $borrowingId,
            'appeal_id'     => $appealId,
            'notes'         => $notes !== '' ? mb_substr($notes, 0, 255) : null,
            'created_by'    => $staffId,
        ]);

        $tierBefore = CreditPolicy::tier($before);
        $tierAfter  = CreditPolicy::tier($after);
        log_activity('credit', $actual > 0 ? 'added' : 'reduced',
            ($actual > 0 ? 'Added ' . $actual : 'Reduced ' . abs($actual)) . ' credit' . (abs($actual) === 1 ? '' : 's')
            . ($actual > 0 ? ' to ' : ' for ') . $user['name'] . ' (' . $user['id_number'] . ') · ' . CreditLogModel::LABELS[$reason]
            . ' · now ' . $after . ($tierBefore['key'] !== $tierAfter['key'] ? ' (' . $tierBefore['label'] . ' → ' . $tierAfter['label'] . ')' : '')
            . ($notes !== '' ? ' · ' . $notes : ''),
            'user', $userId, $staffId);

        return $after;
    }

    /** Student asks the Head Librarian to reduce their credits. Returns the appeal id. */
    public function submitAppeal(int $userId, string $type, string $message, ?float $hours = null): int
    {
        $user = $this->users->find($userId);
        if (! $user || $user['role'] !== UserModel::STUDENT) {
            throw new RuntimeException('Student not found.');
        }
        if ((int) $user['credits'] === 0) {
            throw new RuntimeException('You have no credits to reduce.');
        }
        $appeals = new \App\Models\CreditAppealModel();
        if ($appeals->where('user_id', $userId)->where('status', 'pending')->countAllResults() > 0) {
            throw new RuntimeException('You already have an appeal waiting. The Head Librarian will review it soon.');
        }

        $id = (int) $appeals->insert([
            'user_id' => $userId,
            'type'    => array_key_exists($type, \App\Models\CreditAppealModel::TYPES) ? $type : 'appeal',
            'message' => mb_substr(trim($message), 0, 2000),
            'hours'   => $type === 'library_service' && $hours ? $hours : null,
            'status'  => 'pending',
        ], true);
        log_activity('credit', 'appeal_submitted', $user['name'] . ' (' . $user['id_number'] . ') submitted '
            . ($type === 'library_service' ? 'a library service report' . ($hours ? ' (' . $hours . ' h)' : '') : 'an appeal') . ' to reduce credits', 'user', $userId, $userId);

        return $id;
    }

    /** Head Librarian approves an appeal, reducing the student's credits by $reduceBy. */
    public function approveAppeal(int $appealId, int $staffId, int $reduceBy, string $response = ''): int
    {
        $appeals = new \App\Models\CreditAppealModel();
        $a       = $appeals->find($appealId);
        if (! $a || $a['status'] !== 'pending') {
            throw new RuntimeException('That appeal is no longer waiting.');
        }
        if ($reduceBy < 1) {
            throw new RuntimeException('Enter how many credits to remove (at least 1).');
        }

        $reason = $a['type'] === 'library_service' ? 'library_service' : 'appeal';
        $after  = $this->changeCredits((int) $a['user_id'], -$reduceBy, $reason, $staffId, $response, null, $appealId);
        $appeals->update($appealId, [
            'status' => 'approved', 'reduced_by' => $reduceBy, 'handled_by' => $staffId, 'handled_at' => date('Y-m-d H:i:s'),
            'response' => $response !== '' ? mb_substr($response, 0, 255) : null,
        ]);

        return $after;
    }

    public function declineAppeal(int $appealId, int $staffId, string $response = ''): void
    {
        $appeals = new \App\Models\CreditAppealModel();
        $a       = $appeals->find($appealId);
        if (! $a || $a['status'] !== 'pending') {
            throw new RuntimeException('That appeal is no longer waiting.');
        }
        $appeals->update($appealId, [
            'status' => 'declined', 'handled_by' => $staffId, 'handled_at' => date('Y-m-d H:i:s'),
            'response' => $response !== '' ? mb_substr($response, 0, 255) : null,
        ]);
        $user = $this->users->find($a['user_id']);
        log_activity('credit', 'appeal_declined', 'Declined ' . $user['name'] . '\'s credit appeal' . ($response !== '' ? ' · ' . $response : ''), 'user', (int) $a['user_id'], $staffId);
    }

    public function cancelAppeal(int $appealId, int $userId): void
    {
        $appeals = new \App\Models\CreditAppealModel();
        $a       = $appeals->find($appealId);
        if (! $a || (int) $a['user_id'] !== $userId || $a['status'] !== 'pending') {
            throw new RuntimeException('That appeal is no longer waiting.');
        }
        $appeals->update($appealId, ['status' => 'cancelled', 'handled_at' => date('Y-m-d H:i:s')]);
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
