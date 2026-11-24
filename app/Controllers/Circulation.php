<?php

namespace App\Controllers;

use App\Libraries\Circulation as CirculationService;
use App\Models\ActivityLogModel;
use App\Models\BookModel;
use App\Models\BorrowingModel;
use App\Models\UserModel;
use RuntimeException;

/** Issue and return actions used by the Library page (Head Librarian and Librarian Staff). */
class Circulation extends BaseController
{
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

        return redirect()->to('/library?tab=borrowed')->with('success', '"' . $loan['title'] . '" issued to ' . $loan['borrower_name'] . '. Due back ' . fmt_date($loan['due_date']) . '.');
    }

    public function receive(int $id)
    {
        $condition = (string) $this->request->getPost('condition');
        $extra     = $this->request->getPost('damage_credits');
        $extra     = ($extra === null || $extra === '') ? null : max(0, min(20, (int) $extra));

        try {
            $message = (new CirculationService())->checkin($id, (int) session()->get('userId'), $condition, $extra, trim((string) $this->request->getPost('notes')));
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', $message);
    }
}
