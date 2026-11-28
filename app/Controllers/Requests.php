<?php

namespace App\Controllers;

use App\Libraries\Circulation as CirculationService;
use RuntimeException;

/**
 * Library → Requested (Head Librarian and Librarian Staff):
 * approve or decline students' "Request to borrow" / "Request return".
 */
class Requests extends BaseController
{
    /** Borrow request: issue the book (all borrowing rules are checked again). */
    public function approve(int $id)
    {
        try {
            $loanId = (new CirculationService())->approveBorrowRequest($id, (int) session()->get('userId'));
        } catch (RuntimeException $e) {
            $tip = str_contains($e->getMessage(), 'no longer waiting') ? '' : ' You can decline the request instead.';

            return redirect()->back()->with('error', $e->getMessage() . $tip);
        }

        $due = (new \App\Models\BorrowingModel())->find($loanId)['due_date'];

        return redirect()->back()->with('success', 'Request approved. The book is issued and due back ' . fmt_date($due) . '.');
    }

    /** Return request: receive the book, with its condition (good / damaged / lost). */
    public function receive(int $id)
    {
        $extra = $this->request->getPost('damage_credits');

        try {
            $message = (new CirculationService())->approveReturnRequest(
                $id,
                (int) session()->get('userId'),
                (string) $this->request->getPost('condition'),
                ($extra === null || $extra === '') ? null : max(0, min(20, (int) $extra)),
                trim((string) $this->request->getPost('notes'))
            );
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * "View requests" pop-up: approve the ticked borrow requests of one student at once.
     * Each book is checked on its own (shelf, credits, loan limit); the rest still go through if one can't.
     */
    public function approveMany(int $userId)
    {
        $picked = $this->pickedRequests($userId);
        if (! $picked) {
            return redirect()->back()->with('error', 'Tick at least one book to approve.');
        }

        $service = new CirculationService();
        $issued  = $problems = [];
        foreach ($picked as $r) {
            if ($r['type'] !== 'borrow') {
                $problems[] = '"' . $r['title'] . '" is a return request; use Receive return to record its condition.';
                continue;
            }
            try {
                $service->approveBorrowRequest((int) $r['id'], (int) session()->get('userId'));
                $issued[] = '"' . $r['title'] . '"';
            } catch (RuntimeException $e) {
                $problems[] = '"' . $r['title'] . '": ' . $e->getMessage();
            }
        }

        $redirect = redirect()->back();
        if ($issued) {
            $redirect = $redirect->with('success', 'Approved and issued ' . count($issued) . ' book' . (count($issued) === 1 ? '' : 's') . ' to ' . $picked[0]['borrower_name'] . ': ' . implode(', ', $issued)
                . '. Due back ' . fmt_date(date('Y-m-d', strtotime('+' . (int) setting('loan_days') . ' days'))) . '.');
        }

        return $problems ? $redirect->with('error', 'Not approved: ' . implode(' ', $problems)) : $redirect;
    }

    /** "View requests" pop-up: decline the ticked requests of one student at once, with one reason. */
    public function declineMany(int $userId)
    {
        $picked = $this->pickedRequests($userId);
        if (! $picked) {
            return redirect()->back()->with('error', 'Tick at least one book to decline.');
        }

        $service = new CirculationService();
        $reason  = trim((string) $this->request->getPost('reason'));
        $done    = 0;
        foreach ($picked as $r) {
            try {
                $service->declineRequest((int) $r['id'], (int) session()->get('userId'), $reason);
                $done++;
            } catch (RuntimeException) {
                // already handled by someone else: skip it
            }
        }

        return redirect()->back()->with('success', 'Declined ' . $done . ' request' . ($done === 1 ? '' : 's') . ' from ' . $picked[0]['borrower_name'] . '. The student will see it under My Loans.');
    }

    /** The ticked requests (ids[]) that belong to this student and are still waiting. */
    private function pickedRequests(int $userId): array
    {
        $ids = array_values(array_filter(array_map('intval', (array) $this->request->getPost('ids'))));
        if (! $ids) {
            return [];
        }

        return (new \App\Models\BookRequestModel())->detailed()
            ->where('book_requests.status', 'pending')->where('book_requests.user_id', $userId)
            ->whereIn('book_requests.id', $ids)->orderBy('book_requests.created_at', 'ASC')->findAll();
    }

    public function decline(int $id)
    {
        try {
            (new CirculationService())->declineRequest($id, (int) session()->get('userId'), trim((string) $this->request->getPost('reason')));
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Request declined. The student will see it under My Loans.');
    }
}
