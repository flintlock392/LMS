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
            (new CirculationService())->approveBorrowRequest($id, (int) session()->get('userId'));
        } catch (RuntimeException $e) {
            $tip = str_contains($e->getMessage(), 'no longer waiting') ? '' : ' You can decline the request instead.';

            return redirect()->back()->with('error', $e->getMessage() . $tip);
        }

        return redirect()->back()->with('success', 'Request approved. The book is issued and due back '
            . fmt_date(date('Y-m-d', strtotime('+' . (int) setting('loan_days') . ' days'))) . '.');
    }

    /** Return request: receive the book, with its condition (good / damaged / lost). */
    public function receive(int $id)
    {
        $fee = $this->request->getPost('damage_fee');

        try {
            $message = (new CirculationService())->approveReturnRequest(
                $id,
                (int) session()->get('userId'),
                (string) $this->request->getPost('condition'),
                ($fee === null || $fee === '') ? null : max(0, (float) $fee),
                trim((string) $this->request->getPost('notes'))
            );
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', $message);
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
