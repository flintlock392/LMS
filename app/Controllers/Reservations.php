<?php

namespace App\Controllers;

use App\Libraries\Circulation as CirculationService;
use App\Models\BookModel;
use App\Models\CategoryModel;
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
            'counts'     => (new ReservationModel())->counts(),
            'books'      => (new BookModel())->forSelect(),
            'categories' => (new CategoryModel())->orderBy('name')->findAll(),
            'courses'    => course_options(),
        ]);
    }

    /**
     * Suggestions for the "Student" box: matches the name or the student number.
     * GET reservations/students?q=juan  →  JSON list (max 8).
     */
    public function students()
    {
        $q = trim((string) $this->request->getGet('q'));
        if ($q === '') {
            return $this->response->setJSON([]);
        }

        $rows = (new UserModel())
            ->select('users.id, users.name, users.id_number, users.course, users.year_level, users.status')
            ->select("(SELECT COUNT(*) FROM reservations r WHERE r.user_id = users.id AND r.status IN ('pending','ready')) AS open_reservations", false)
            ->select("(SELECT COUNT(*) FROM borrowings b WHERE b.user_id = users.id AND b.status = 'borrowed') AS loans", false)
            ->where('role', UserModel::STUDENT)
            ->groupStart()->like('name', $q)->orLike('id_number', $q)->groupEnd()
            // Names/numbers that start with what was typed come first.
            ->orderBy("(name LIKE " . db_connect()->escape($q . '%') . " OR id_number LIKE " . db_connect()->escape($q . '%') . ")", 'DESC', false)
            ->orderBy('name', 'ASC')
            ->findAll(8);

        $max = (int) setting('max_reservations');

        return $this->response->setJSON(array_map(static fn ($u) => [
            'id'        => (int) $u['id'],
            'name'      => $u['name'],
            'idNumber'  => $u['id_number'],
            'course'    => $u['course'] ?? '',
            'year'      => $u['year_level'] ?? '',
            'suspended' => $u['status'] !== 'active',
            'open'      => (int) $u['open_reservations'],
            'atLimit'   => (int) $u['open_reservations'] >= $max,
            'loans'     => (int) $u['loans'],
        ], $rows));
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
