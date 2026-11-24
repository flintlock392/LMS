<?php

namespace App\Controllers;

use App\Libraries\Circulation as CirculationService;
use App\Models\FineModel;
use App\Models\UserModel;

/**
 * Head Librarian: "Fine Management" (record payments, add manual fines, waive, delete).
 * Librarian Staff: "Fine Processing" (record payments).
 */
class Fines extends BaseController
{
    public function index()
    {
        $status  = (string) $this->request->getGet('status');
        $filters = [
            'status' => in_array($status, ['unpaid', 'paid', 'waived', 'all'], true) ? $status : 'unpaid',
            'q'      => trim((string) $this->request->getGet('q')),
        ];

        $model = new FineModel();

        return view('fines/index', [
            'rows'     => $model->filtered($filters)->paginate(15),
            'pager'    => $model->pager,
            'filters'  => $filters,
            'totals'   => (new FineModel())->totals(),
            'students' => is_head() ? (new UserModel())->where('role', UserModel::STUDENT)->activeBorrowers() : [],
        ]);
    }

    public function pay(int $id)
    {
        $model = new FineModel();
        $fine  = $model->find($id);
        if (! $fine || $fine['status'] !== 'unpaid') {
            return redirect()->back()->with('error', 'That fine is not awaiting payment.');
        }

        $model->update($id, ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s'), 'processed_by' => session()->get('userId')]);

        return redirect()->back()->with('success', 'Payment of ' . money($fine['amount']) . ' recorded.');
    }

    /** Head Librarian only. */
    public function waive(int $id)
    {
        $model = new FineModel();
        $fine  = $model->find($id);
        if (! $fine || $fine['status'] !== 'unpaid') {
            return redirect()->back()->with('error', 'Only unpaid fines can be waived.');
        }
        $reason = trim((string) $this->request->getPost('reason'));
        $notes  = trim(($fine['notes'] ? $fine['notes'] . ' · ' : '') . 'Waived' . ($reason !== '' ? ': ' . $reason : ''));

        $model->update($id, ['status' => 'waived', 'processed_by' => session()->get('userId'), 'notes' => mb_substr($notes, 0, 255)]);

        return redirect()->back()->with('success', 'Fine of ' . money($fine['amount']) . ' waived.');
    }

    /** Head Librarian only: a manual charge (e.g. damaged equipment, replacement card). */
    public function create()
    {
        $rules = [
            'user_id' => 'required|is_natural_no_zero|is_not_unique[users.id]',
            'reason'  => 'required|in_list[' . implode(',', array_keys(FineModel::REASONS)) . ']',
            'amount'  => 'required|decimal|greater_than[0]|less_than_equal_to[100000]',
            'notes'   => 'permit_empty|max_length[255]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        (new CirculationService())->addFine(
            (int) $this->request->getPost('user_id'),
            null,
            (string) $this->request->getPost('reason'),
            (float) $this->request->getPost('amount'),
            (int) session()->get('userId'),
            trim((string) $this->request->getPost('notes'))
        );

        return redirect()->to('/fines')->with('success', 'Fine added.');
    }

    /** Head Librarian only: remove a fine entered by mistake. */
    public function delete(int $id)
    {
        $model = new FineModel();
        if (! $model->find($id)) {
            return redirect()->back()->with('error', 'Fine not found.');
        }
        $model->delete($id);

        return redirect()->back()->with('success', 'Fine deleted.');
    }
}
