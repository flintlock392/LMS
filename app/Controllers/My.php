<?php

namespace App\Controllers;

use App\Libraries\Circulation as CirculationService;
use App\Models\BorrowingModel;
use App\Models\FineModel;
use App\Models\ReservationModel;
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

        return view('my/loans', ['rows' => $rows, 'pager' => $model->pager]);
    }

    public function reservations()
    {
        (new CirculationService())->housekeep();

        $rows = (new ReservationModel())->filtered(['status' => 'all', 'user_id' => $this->me()])->findAll(30);
        foreach ($rows as &$r) {
            $r['position'] = $r['status'] === 'pending' ? (new ReservationModel())->queuePosition($r) : 0;
        }
        unset($r);

        return view('my/reservations', ['rows' => $rows]);
    }

    public function cancelReservation(int $id)
    {
        try {
            (new CirculationService())->cancelReservation($id, $this->me(), $this->me());
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to('/my/reservations')->with('success', 'Reservation cancelled.');
    }

    public function fines()
    {
        return view('my/fines', [
            'rows'   => (new FineModel())->filtered(['status' => 'all', 'user_id' => $this->me()])->findAll(),
            'unpaid' => (new FineModel())->unpaidTotalForUser($this->me()),
        ]);
    }
}
