<?php

namespace App\Controllers;

use App\Models\BorrowingModel;
use App\Models\CreditLogModel;
use App\Models\UserModel;

/**
 * Student accounts.
 * Head Librarian: "User Management" (register, edit, suspend, reset password, archive).
 * Librarian Staff: "User Registration" (register and look up students).
 */
class Members extends BaseController
{
    protected UserModel $users;

    public function __construct()
    {
        $this->users = new UserModel();
    }

    public function index()
    {
        $q      = trim((string) $this->request->getGet('q'));
        $status = (string) $this->request->getGet('status');

        $rows = $this->users->search([UserModel::STUDENT], $q, $status)
            ->select('users.*')
            ->select("(SELECT COUNT(*) FROM borrowings b WHERE b.user_id = users.id AND b.status = 'borrowed') AS loans", false)
            ->paginate(15);

        return view('members/index', ['rows' => $rows, 'pager' => $this->users->pager, 'q' => $q, 'status' => $status]);
    }

    public function show(int $id)
    {
        $user = $this->student($id);
        if (! $user) {
            return redirect()->to('/members')->with('error', 'Student not found.');
        }

        return view('members/show', [
            'user'         => $user,
            'loans'        => (new BorrowingModel())->filtered(['status' => 'all', 'user_id' => $id])->findAll(20),
            'creditLog'    => (new CreditLogModel())->filtered(['user_id' => $id])->findAll(20),
        ]);
    }

    public function new()
    {
        return view('members/form', ['user' => null]);
    }

    public function create()
    {
        $rules = $this->rules(null);
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data             = $this->collect();
        $data['role']     = UserModel::STUDENT;
        $data['status']   = 'active';
        $data['password'] = (string) $this->request->getPost('password');
        $id               = (int) $this->users->insert($data, true);
        log_activity('user', 'registered', 'Registered student ' . $data['name'] . ' (' . $data['id_number'] . ')', 'user', $id);

        return redirect()->to('/members/' . $id)->with('success', $data['name'] . ' is registered. They can log in with student number ' . $data['id_number'] . '.');
    }

    public function edit(int $id)
    {
        $user = $this->student($id);
        if (! $user) {
            return redirect()->to('/members')->with('error', 'Student not found.');
        }

        return view('members/form', ['user' => $user]);
    }

    public function update(int $id)
    {
        if (! $this->student($id)) {
            return redirect()->to('/members')->with('error', 'Student not found.');
        }
        if (! $this->validate($this->rules($id))) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data     = $this->collect();
        $password = (string) $this->request->getPost('password');
        if ($password !== '') {
            $data['password'] = $password;
        }
        $this->users->update($id, $data);
        log_activity('user', $password !== '' ? 'password_reset' : 'edited', ($password !== '' ? 'Edited and reset the password of ' : 'Edited the details of ')
            . $data['name'] . ' (' . $data['id_number'] . ')', 'user', $id);

        return redirect()->to('/members/' . $id)->with('success', 'Student details saved' . ($password !== '' ? ' and password reset.' : '.'));
    }

    public function toggleStatus(int $id)
    {
        $user = $this->student($id);
        if (! $user) {
            return redirect()->to('/members')->with('error', 'Student not found.');
        }
        $new = $user['status'] === 'active' ? 'suspended' : 'active';
        $this->users->update($id, ['status' => $new]);
        log_activity('user', $new, ($new === 'suspended' ? 'Suspended ' : 'Reactivated ') . 'student ' . $user['name'] . ' (' . $user['id_number'] . ')', 'user', $id);

        return redirect()->back()->with('success', $user['name'] . ' is now ' . $new . '.');
    }

    /** Archived students can't log in and are hidden from lists; their records are kept. */
    public function archive(int $id)
    {
        $user = $this->student($id);
        if (! $user) {
            return redirect()->to('/members')->with('error', 'Student not found.');
        }
        if ((new BorrowingModel())->where('user_id', $id)->where('status', 'borrowed')->countAllResults() > 0) {
            return redirect()->back()->with('error', $user['name'] . ' still has books on loan. Archive the account once they are returned.');
        }

        $this->users->delete($id);   // soft delete: sets archived_at
        log_activity('archive', 'archived', 'Archived student account ' . $user['name'] . ' (' . $user['id_number'] . ')', 'user', $id);

        return redirect()->to('/members')->with('success', $user['name'] . '\'s account was archived. You can restore it from the Archive page.');
    }

    private function student(int $id): ?array
    {
        $user = $this->users->find($id);

        return ($user && $user['role'] === UserModel::STUDENT) ? $user : null;
    }

    private function rules(?int $id): array
    {
        $ignore = $id !== null ? ',id,' . $id : '';

        return [
            'id_number'  => ['rules' => 'required|regex_match[/^[A-Za-z0-9-]{4,30}$/]|is_unique[users.id_number' . $ignore . ']', 'errors' => ['is_unique' => 'That student number is already registered.', 'regex_match' => 'Student number may only contain letters, numbers and dashes.']],
            'name'       => 'required|min_length[2]|max_length[100]',
            'email'      => ['rules' => 'required|valid_email|max_length[150]|is_unique[users.email' . $ignore . ']', 'errors' => ['is_unique' => 'That email is already registered.']],
            'phone'      => 'permit_empty|max_length[30]',
            ...course_rules(),           // drop-down + "Other" text box
            'year_level' => $id === null ? 'required|in_list[1st Year,2nd Year,3rd Year,4th Year,5th Year]' : 'required|max_length[20]',
            'password'   => password_rule($id === null),
        ];
    }

    private function collect(): array
    {
        return [
            'id_number'  => strtoupper(trim((string) $this->request->getPost('id_number'))),
            'name'       => trim((string) $this->request->getPost('name')),
            'email'      => strtolower(trim((string) $this->request->getPost('email'))),
            'phone'      => trim((string) $this->request->getPost('phone')) ?: null,
            'course'     => posted_course(),
            'year_level' => (string) $this->request->getPost('year_level'),
        ];
    }
}
