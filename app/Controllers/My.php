<?php

namespace App\Controllers;

use App\Libraries\Circulation as CirculationService;
use App\Models\BookRequestModel;
use App\Models\BorrowingModel;
use App\Models\FineModel;
use RuntimeException;

/** Student self-service pages. */
class My extends BaseController
{
    private function me(): int
    {
        return (int) session()->get('userId');
    }

    public function loans()
    {
        $circ  = new CirculationService();
        $model = new BorrowingModel();
        $rows  = $model->filtered(['status' => 'all', 'user_id' => $this->me()])->paginate(15);
        foreach ($rows as &$l) {
            [$l['days_late'], $l['est_fine']] = $l['status'] === 'borrowed' ? $circ->overdueFine($l['due_date'], date('Y-m-d')) : [0, 0];
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

        return view('my/loans', ['rows' => $rows, 'pager' => $model->pager, 'requests' => $requests, 'pendingReturns' => $pendingReturns]);
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

    public function fines()
    {
        return view('my/fines', [
            'rows'   => (new FineModel())->filtered(['status' => 'all', 'user_id' => $this->me()])->findAll(),
            'unpaid' => (new FineModel())->unpaidTotalForUser($this->me()),
        ]);
    }
}
