<?php

namespace App\Controllers;

use App\Libraries\Circulation;
use App\Models\BookModel;
use App\Models\BorrowingModel;
use App\Models\FineModel;
use App\Models\UserModel;

class Dashboard extends BaseController
{
    public function index()
    {
        return current_role() === UserModel::STUDENT ? $this->student() : $this->staff();
    }

    /** Head Librarian and Librarian Staff share one dashboard; head-only cards are toggled in the view. */
    private function staff()
    {
        $books      = new BookModel();
        $borrowings = new BorrowingModel();
        $users      = new UserModel();

        return view('dashboard/staff', [
            'bookStats'  => $books->stats(),
            'loans'      => $borrowings->counts(),
            'fineTotals' => (new FineModel())->totals(),
            'requests'   => (new \App\Models\BookRequestModel())->pendingCount(),
            'students'   => $users->where('role', UserModel::STUDENT)->where('status', 'active')->countAllResults(),
            'staffCount' => $users->whereIn('role', UserModel::STAFF_ROLES)->countAllResults(),
            'overdue'    => (new BorrowingModel())->filtered(['status' => 'overdue'])->findAll(6),
            'dueSoon'    => (new BorrowingModel())->detailed()->where('borrowings.status', 'borrowed')
                ->where('borrowings.due_date >=', date('Y-m-d'))
                ->where('borrowings.due_date <=', date('Y-m-d', strtotime('+2 days')))
                ->orderBy('borrowings.due_date')->findAll(6),
            'recent'     => (new BorrowingModel())->detailed()->orderBy('borrowings.updated_at', 'DESC')->findAll(6),
            'lowStock'   => (new BookModel())->filtered(['availability' => 'out_of_stock'])->findAll(5),
        ]);
    }

    private function student()
    {
        $userId     = (int) session()->get('userId');
        $circ       = new Circulation();
        $loans      = (new BorrowingModel())->filtered(['status' => 'active', 'user_id' => $userId])->findAll();
        $estimated  = 0.0;
        foreach ($loans as &$loan) {
            [$days, $fine]     = $circ->overdueFine($loan['due_date'], date('Y-m-d'));
            $loan['days_late'] = $days;
            $loan['est_fine']  = $fine;
            $estimated += $fine;
        }
        unset($loan);


        return view('dashboard/student', [
            'loans'        => $loans,
            'unpaid'       => (new FineModel())->unpaidTotalForUser($userId),
            'estimated'    => $estimated,
            'history'      => (new BorrowingModel())->where('user_id', $userId)->where('status !=', 'borrowed')->countAllResults(),
            'newBooks'     => (new BookModel())->withAvailability()->orderBy('books.created_at', 'DESC')->findAll(4),
        ]);
    }
}
