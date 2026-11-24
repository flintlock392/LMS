<?php

namespace App\Controllers;

use App\Libraries\Circulation as CirculationService;
use App\Models\BookModel;
use App\Models\ReservationModel;
use App\Models\UserModel;
use RuntimeException;

/** Reservations desk (Head Librarian and Librarian Staff). */
class Reservations extends BaseController
{
    public function index()
    {
        (new CirculationService())->housekeep();

        $status  = (string) $this->request->getGet('status');
        $filters = [
            'status' => in_array($status, ['open', 'pending', 'ready', 'closed', 'all'], true) ? $status : 'open',
            'q'      => trim((string) $this->request->getGet('q')),
        ];

        $model = new ReservationModel();
        $rows  = $model->filtered($filters)->paginate(15);
        foreach ($rows as &$r) {
            $r['position'] = $r['status'] === 'pending' ? (new ReservationModel())->queuePosition($r) : 0;
        }
        unset($r);

        return view('reservations/index', [
            'rows'     => $rows,
            'pager'    => $model->pager,
            'filters'  => $filters,
            'counts'   => (new ReservationModel())->counts(),
            'students' => (new UserModel())->where('role', UserModel::STUDENT)->activeBorrowers(),
            'books'    => (new BookModel())->forSelect(),
        ]);
    }

    /** Staff places a reservation on behalf of a student (e.g. over the counter). */
    public function create()
    {
        try {
            $r = (new CirculationService())->reserve((int) $this->request->getPost('book_id'), (int) $this->request->getPost('user_id'), (int) session()->get('userId'));
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to('/reservations')->with('success', $r['status'] === 'ready'
            ? 'Reserved. A copy is available now and is being held for pickup.'
            : 'Reserved. The student has been added to the waiting list.');
    }

    /** Hand a held book to the student: turns the reservation into a loan. */
    public function issue(int $id)
    {
        $r = (new ReservationModel())->find($id);
        if (! $r || $r['status'] !== 'ready') {
            return redirect()->back()->with('error', 'Only reservations that are ready for pickup can be issued.');
        }

        try {
            (new CirculationService())->checkout((int) $r['book_id'], (int) $r['user_id'], (int) session()->get('userId'));
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Book issued. Due back ' . fmt_date(date('Y-m-d', strtotime('+' . (int) setting('loan_days') . ' days'))) . '.');
    }

    public function cancel(int $id)
    {
        try {
            (new CirculationService())->cancelReservation($id, (int) session()->get('userId'));
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Reservation cancelled.');
    }
}
