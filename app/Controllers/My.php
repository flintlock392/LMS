<?php

namespace App\Controllers;

use App\Libraries\Circulation as CirculationService;
use App\Models\BookRequestModel;
use App\Models\BorrowingModel;
use App\Models\CreditAppealModel;
use App\Models\CreditLogModel;
use App\Models\UserModel;
use RuntimeException;

/** Student self-service pages. */
class My extends BaseController
{
    /** My Loans status filter: key => [label, icon, message when empty] */
    private const LOAN_FILTERS = [
        'all'       => ['All', 'list-ul', 'You haven\'t borrowed any books yet.'],
        'active'    => ['On loan', 'book', 'You have no books on loan right now.'],
        'due_today' => ['Due today', 'calendar-event', 'Nothing is due today.'],
        'overdue'   => ['Overdue', 'alarm', 'No overdue books. Well done!'],
        'returned'  => ['Returned', 'check2-circle', 'You haven\'t returned any books yet.'],
    ];

    private function me(): int
    {
        return (int) session()->get('userId');
    }

    public function loans()
    {
        $circ  = new CirculationService();
        $model = new BorrowingModel();
        // Status filter: all | active (on loan) | due_today | overdue | returned
        $status = (string) $this->request->getGet('status');
        if (! array_key_exists($status, self::LOAN_FILTERS)) {
            $status = 'all';
        }
        $rows = $model->filtered(['status' => $status, 'user_id' => $this->me()])->paginate(15);
        foreach ($rows as &$l) {
            [$l['days_late'], $l['est_credits']] = $l['status'] === 'borrowed' ? $circ->overdueCredits($l['due_date'], date('Y-m-d')) : [0, 0];
        }
        unset($l);

        // Waiting requests, plus the latest few that were declined (so the student sees why).
        $requests = (new BookRequestModel())->detailed()
            ->where('book_requests.user_id', $this->me())
            ->groupStart()
                ->where('book_requests.status', 'pending')
                ->orGroupStart()->where('book_requests.status', 'declined')->where('book_requests.handled_at >=', date('Y-m-d H:i:s', strtotime('-14 days')))->groupEnd()
            ->groupEnd()
            ->orderBy("book_requests.status = 'pending'", 'DESC', false)->orderBy('book_requests.created_at', 'DESC')
            ->findAll(10);

        $pendingReturns = [];
        foreach ($requests as $r) {
            if ($r['status'] === 'pending' && $r['type'] === 'return') {
                $pendingReturns[(int) $r['borrowing_id']] = (int) $r['id'];
            }
        }

        $today  = date('Y-m-d');
        $mine   = fn () => (new BorrowingModel())->where('user_id', $this->me());
        $counts = [
            'all'       => $mine()->countAllResults(),
            'active'    => $mine()->where('status', 'borrowed')->countAllResults(),
            'due_today' => $mine()->where('status', 'borrowed')->where('due_date', $today)->countAllResults(),
            'overdue'   => $mine()->where('status', 'borrowed')->where('due_date <', $today)->countAllResults(),
            'returned'  => $mine()->where('status', 'returned')->countAllResults(),
        ];

        return view('my/loans', [
            'rows' => $rows, 'pager' => $model->pager, 'requests' => $requests, 'pendingReturns' => $pendingReturns,
            'status' => $status, 'counts' => $counts, 'filters' => self::LOAN_FILTERS,
        ]);
    }

    /** "Request return" for a book the student has. */
    public function requestReturn(int $borrowingId)
    {
        try {
            (new CirculationService())->requestReturn($borrowingId, $this->me());
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Return request sent. Please bring the book to the library desk.');
    }

    /** Withdraw a waiting borrow / return request. */
    public function cancelRequest(int $requestId)
    {
        try {
            (new CirculationService())->cancelRequest($requestId, $this->me());
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Request cancelled.');
    }

    /** My Credits: balance, standing, how to reduce credits, appeals and history. */
    public function credits()
    {
        $user = (new UserModel())->find($this->me());

        return view('my/credits', [
            'credits'  => (int) $user['credits'],
            'loans'    => (new BorrowingModel())->activeCountForUser($this->me()),
            'maxLoans' => (int) setting('max_loans'),
            'loanDays' => (int) setting('loan_days'),
            'history'  => (new CreditLogModel())->filtered(['user_id' => $this->me()])->findAll(30),
            'appeals'  => (new CreditAppealModel())->detailed()->where('credit_appeals.user_id', $this->me())
                ->where('credit_appeals.status !=', 'cancelled')->orderBy('credit_appeals.created_at', 'DESC')->findAll(10),
        ]);
    }

    /** Appeal to the Head Librarian to reduce credits (or report library service done). */
    public function appeal()
    {
        $rules = [
            'type'    => 'required|in_list[appeal,library_service]',
            'message' => ['label' => 'Message', 'rules' => 'required|min_length[10]|max_length[2000]', 'errors' => ['min_length' => 'Please explain a little more (at least 10 characters).']],
            'hours'   => 'permit_empty|decimal|greater_than[0]|less_than_equal_to[100]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            (new CirculationService())->submitAppeal(
                $this->me(),
                (string) $this->request->getPost('type'),
                (string) $this->request->getPost('message'),
                $this->request->getPost('hours') !== '' ? (float) $this->request->getPost('hours') : null
            );
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to('/my/credits')->with('success', 'Sent! The Head Librarian will review it. You\'ll see the answer here.');
    }

    public function cancelAppeal(int $id)
    {
        try {
            (new CirculationService())->cancelAppeal($id, $this->me());
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Appeal cancelled.');
    }
}
