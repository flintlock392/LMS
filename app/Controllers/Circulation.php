<?php

namespace App\Controllers;

use App\Libraries\Circulation as CirculationService;
use App\Models\BookModel;
use App\Models\BorrowingModel;
use App\Models\UserModel;
use RuntimeException;

/** Borrowing & Returning (Head Librarian and Librarian Staff). */
class Circulation extends BaseController
{
    public function index()
    {
        $service = new CirculationService();
        $service->housekeep();

        $filters = [
            'status' => in_array($this->request->getGet('status'), ['active', 'overdue', 'returned', 'lost', 'all'], true)
                ? (string) $this->request->getGet('status') : 'active',
            'q'    => trim((string) $this->request->getGet('q')),
            'from' => (string) $this->request->getGet('from'),
            'to'   => (string) $this->request->getGet('to'),
        ];

        $model = new BorrowingModel();
        $loans = $model->filtered($filters)->paginate(15);
        foreach ($loans as &$loan) {
            [$days, $fine]     = $loan['status'] === 'borrowed' ? $service->overdueFine($loan['due_date'], date('Y-m-d')) : [0, 0];
            $loan['days_late'] = $days;
            $loan['est_fine']  = $fine;
        }
        unset($loan);

        return view('circulation/index', [
            'loans'    => $loans,
            'pager'    => $model->pager,
            'filters'  => $filters,
            'counts'   => (new BorrowingModel())->counts(),
            'students' => (new UserModel())->where('role', UserModel::STUDENT)->activeBorrowers(),
            'books'    => (new BookModel())->forSelect(),
        ]);
    }

    public function issue()
    {
        $bookId = (int) $this->request->getPost('book_id');
        $userId = (int) $this->request->getPost('user_id');
        if (! $bookId || ! $userId) {
            return redirect()->back()->withInput()->with('error', 'Choose a student and a book.');
        }

        try {
            $id   = (new CirculationService())->checkout($bookId, $userId, (int) session()->get('userId'));
            $loan = (new BorrowingModel())->detailed()->where('borrowings.id', $id)->first();
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to('/circulation')->with('success', '"' . $loan['title'] . '" issued to ' . $loan['borrower_name'] . '. Due back ' . fmt_date($loan['due_date']) . '.');
    }

    public function receive(int $id)
    {
        $condition = (string) $this->request->getPost('condition');
        $fee       = $this->request->getPost('damage_fee');
        $fee       = ($fee === null || $fee === '') ? null : max(0, (float) $fee);

        try {
            $message = (new CirculationService())->checkin($id, (int) session()->get('userId'), $condition, $fee, trim((string) $this->request->getPost('notes')));
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', $message);
    }
}
