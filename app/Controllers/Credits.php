<?php

namespace App\Controllers;

use App\Libraries\Circulation as CirculationService;
use App\Libraries\CreditPolicy;
use App\Models\CreditAppealModel;
use App\Models\CreditLogModel;
use App\Models\UserModel;
use RuntimeException;

/**
 * Credit Management (replaces fines).
 *   Head Librarian:  "Credit Management" — add / reduce credits, approve or decline appeals.
 *   Librarian Staff: "Credit Records"    — view only.
 *
 * Tabs: students (balances) · appeals · history
 */
class Credits extends BaseController
{
    public function index()
    {
        $tab = (string) $this->request->getGet('tab');
        $tab = in_array($tab, ['students', 'appeals', 'history'], true) ? $tab : 'students';
        $q   = trim((string) $this->request->getGet('q'));

        $data = [
            'tab'     => $tab,
            'q'       => $q,
            'tiers'   => $this->tierCounts(),
            'pending' => (new CreditAppealModel())->pendingCount(),
            'maxLoans' => (int) setting('max_loans'),
        ];

        if ($tab === 'students') {
            $tier  = (string) $this->request->getGet('tier');
            $users = new UserModel();
            $users->select('users.*')
                ->select("(SELECT COUNT(*) FROM borrowings b WHERE b.user_id = users.id AND b.status = 'borrowed') AS loans", false)
                ->where('role', UserModel::STUDENT);
            if ($q !== '') {
                $users->groupStart()->like('name', $q)->orLike('id_number', $q)->orLike('course', $q)->groupEnd();
            }
            foreach (CreditPolicy::TIERS as $i => $t) {
                if ($tier === $t[1]) {
                    $users->where('credits >=', $t[0]);
                    if (isset(CreditPolicy::TIERS[$i + 1])) {
                        $users->where('credits <', CreditPolicy::TIERS[$i + 1][0]);
                    }
                }
            }
            $data['tier']  = $tier;
            $data['rows']  = $users->orderBy('credits', 'DESC')->orderBy('name', 'ASC')->paginate(15);
            $data['pager'] = $users->pager;
        } elseif ($tab === 'appeals') {
            $status  = (string) $this->request->getGet('status');
            $status  = in_array($status, ['pending', 'approved', 'declined', 'all'], true) ? $status : 'pending';
            $appeals = (new CreditAppealModel())->detailed();
            if ($status !== 'all') {
                $appeals->where('credit_appeals.status', $status);
            } else {
                $appeals->where('credit_appeals.status !=', 'cancelled');
            }
            if ($q !== '') {
                $appeals->groupStart()->like('u.name', $q)->orLike('u.id_number', $q)->groupEnd();
            }
            $data['status'] = $status;
            $data['rows']   = $appeals->orderBy('credit_appeals.created_at', $status === 'pending' ? 'ASC' : 'DESC')->paginate(15);
            $data['pager']  = $appeals->pager;
        } else {
            $dir  = (string) $this->request->getGet('direction');
            $logs = new CreditLogModel();
            $data['direction'] = in_array($dir, ['added', 'reduced'], true) ? $dir : '';
            $data['rows']      = $logs->filtered(['q' => $q, 'direction' => $data['direction']])->paginate(15);
            $data['pager']     = $logs->pager;
        }

        return view('credits/index', $data);
    }

    /** Head Librarian: add credits for a violation, or reduce them (library service, settlement, correction). */
    public function adjust()
    {
        $direction = (string) $this->request->getPost('direction');
        $amount    = (int) $this->request->getPost('amount');
        $reason    = (string) $this->request->getPost('reason');
        $userId    = (int) $this->request->getPost('user_id');
        $notes     = trim((string) $this->request->getPost('notes'));

        $valid = $direction === 'add' ? CreditLogModel::ADD_REASONS : CreditLogModel::REDUCE_REASONS;
        if (! $userId) {
            return redirect()->back()->withInput()->with('error', 'Choose a student from the suggestions.');
        }
        if (! array_key_exists($reason, $valid)) {
            return redirect()->back()->withInput()->with('error', 'Choose a reason.');
        }
        if ($amount < 1 || $amount > CreditPolicy::MAX) {
            return redirect()->back()->withInput()->with('error', 'Enter between 1 and ' . CreditPolicy::MAX . ' credits.');
        }

        try {
            $after = (new CirculationService())->changeCredits($userId, $direction === 'add' ? $amount : -$amount, $reason, (int) session()->get('userId'), $notes);
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        $user = (new UserModel())->find($userId);
        $tier = CreditPolicy::tier($after);

        return redirect()->back()->with('success', $user['name'] . ' now has ' . $after . ' credit' . ($after === 1 ? '' : 's') . ' (' . $tier['label'] . '). ' . $tier['explain']);
    }

    /** Head Librarian approves an appeal and removes credits. */
    public function approveAppeal(int $id)
    {
        try {
            $after = (new CirculationService())->approveAppeal($id, (int) session()->get('userId'), (int) $this->request->getPost('reduce_by'), trim((string) $this->request->getPost('response')));
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Appeal approved. The student now has ' . $after . ' credit' . ($after === 1 ? '' : 's') . ' (' . CreditPolicy::tier($after)['label'] . ').');
    }

    public function declineAppeal(int $id)
    {
        try {
            (new CirculationService())->declineAppeal($id, (int) session()->get('userId'), trim((string) $this->request->getPost('response')));
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Appeal declined. The student will see your reason under My Credits.');
    }

    /** Number of students at each standing level. */
    private function tierCounts(): array
    {
        $counts = [];
        foreach (CreditPolicy::TIERS as $t) {
            $counts[$t[1]] = 0;
        }
        foreach ((new UserModel())->select('credits')->where('role', UserModel::STUDENT)->findAll() as $u) {
            $counts[CreditPolicy::tier((int) $u['credits'])['key']]++;
        }

        return $counts;
    }
}
