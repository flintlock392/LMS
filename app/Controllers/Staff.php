<?php

namespace App\Controllers;

use App\Models\UserModel;

/** Staff Management (Head Librarian only): librarian staff and head librarian accounts. */
class Staff extends BaseController
{
    protected UserModel $users;

    public function __construct()
    {
        $this->users = new UserModel();
    }

    public function index()
    {
        $q    = trim((string) $this->request->getGet('q'));
        $rows = $this->users->search(UserModel::STAFF_ROLES, $q)
            ->select('users.*')
            ->select("(SELECT COUNT(*) FROM borrowings b WHERE b.issued_by = users.id) AS issued", false)
            ->findAll();

        return view('staff/index', ['rows' => $rows, 'q' => $q]);
    }

    public function new()
    {
        return view('staff/form', ['user' => null]);
    }

    public function create()
    {
        if (! $this->validate($this->rules(null))) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data             = $this->collect();
        $data['status']   = 'active';
        $data['password'] = (string) $this->request->getPost('password');
        $newId = (int) $this->users->insert($data, true);
        log_activity('staff', 'added', 'Added ' . role_label($data['role']) . ' ' . $data['name'] . ' (' . $data['id_number'] . ')', 'user', $newId);

        return redirect()->to('/staff')->with('success', $data['name'] . ' was added as ' . role_label($data['role']) . '. They log in with Staff ID ' . $data['id_number'] . '.');
    }

    public function edit(int $id)
    {
        $user = $this->staffMember($id);
        if (! $user) {
            return redirect()->to('/staff')->with('error', 'Staff member not found.');
        }

        return view('staff/form', ['user' => $user]);
    }

    public function update(int $id)
    {
        if (! $this->staffMember($id)) {
            return redirect()->to('/staff')->with('error', 'Staff member not found.');
        }
        if (! $this->validate($this->rules($id))) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = $this->collect();
        if ($id === $this->me() && $data['role'] !== UserModel::HEAD) {
            return redirect()->back()->withInput()->with('error', 'You can\'t remove your own Head Librarian access.');
        }
        if ($data['role'] !== UserModel::HEAD && $this->isLastHead($id)) {
            return redirect()->back()->withInput()->with('error', 'There must always be at least one Head Librarian.');
        }

        $password = (string) $this->request->getPost('password');
        if ($password !== '') {
            $data['password'] = $password;
        }
        $before = $this->users->find($id);
        $this->users->update($id, $data);
        log_activity('staff', 'edited', 'Edited staff account ' . $data['name'] . ' (' . $data['id_number'] . ')'
            . ($before['role'] !== $data['role'] ? ' · user level changed to ' . role_label($data['role']) : '')
            . ($password !== '' ? ' · password reset' : ''), 'user', $id);

        return redirect()->to('/staff')->with('success', 'Staff account updated' . ($password !== '' ? ' and password reset.' : '.'));
    }

    public function toggleStatus(int $id)
    {
        $user = $this->staffMember($id);
        if (! $user) {
            return redirect()->to('/staff')->with('error', 'Staff member not found.');
        }
        if ($id === $this->me()) {
            return redirect()->back()->with('error', 'You can\'t suspend your own account.');
        }
        if ($user['status'] === 'active' && $this->isLastHead($id)) {
            return redirect()->back()->with('error', 'There must always be at least one active Head Librarian.');
        }

        $new = $user['status'] === 'active' ? 'suspended' : 'active';
        $this->users->update($id, ['status' => $new]);
        log_activity('staff', $new, ($new === 'suspended' ? 'Suspended ' : 'Reactivated ') . 'staff account ' . $user['name'] . ' (' . $user['id_number'] . ')', 'user', $id);

        return redirect()->back()->with('success', $user['name'] . ' is now ' . $new . '.');
    }

    public function delete(int $id)
    {
        $user = $this->staffMember($id);
        if (! $user) {
            return redirect()->to('/staff')->with('error', 'Staff member not found.');
        }
        if ($id === $this->me()) {
            return redirect()->back()->with('error', 'You can\'t delete your own account.');
        }
        if ($this->isLastHead($id)) {
            return redirect()->back()->with('error', 'There must always be at least one Head Librarian.');
        }

        // Their past circulation records stay; the "issued by" link is cleared by the foreign key.
        $this->users->delete($id);
        log_activity('staff', 'deleted', 'Deleted staff account ' . $user['name'] . ' (' . $user['id_number'] . ')', 'user', $id);

        return redirect()->to('/staff')->with('success', $user['name'] . '\'s staff account was deleted.');
    }

    private function staffMember(int $id): ?array
    {
        $user = $this->users->find($id);

        return ($user && in_array($user['role'], UserModel::STAFF_ROLES, true)) ? $user : null;
    }

    private function me(): int
    {
        return (int) session()->get('userId');
    }

    /** True when $id is the only active Head Librarian. */
    private function isLastHead(int $id): bool
    {
        $user = $this->users->find($id);
        if (! $user || $user['role'] !== UserModel::HEAD) {
            return false;
        }

        return (new UserModel())->where('role', UserModel::HEAD)->where('status', 'active')->where('id !=', $id)->countAllResults() === 0;
    }

    private function rules(?int $id): array
    {
        $ignore = $id !== null ? ',id,' . $id : '';

        return [
            'id_number' => ['rules' => 'required|regex_match[/^[A-Za-z0-9-]{3,30}$/]|is_unique[users.id_number' . $ignore . ']', 'errors' => ['is_unique' => 'That Staff ID is already in use.', 'regex_match' => 'Staff ID may only contain letters, numbers and dashes.']],
            'name'      => 'required|min_length[2]|max_length[100]',
            'email'     => ['rules' => 'required|valid_email|max_length[150]|is_unique[users.email' . $ignore . ']', 'errors' => ['is_unique' => 'That email is already in use.']],
            'phone'     => 'permit_empty|max_length[30]',
            'position'  => 'permit_empty|max_length[100]',
            'role'      => 'required|in_list[' . implode(',', UserModel::STAFF_ROLES) . ']',
            'password'  => $id === null ? 'required|min_length[8]|max_length[72]' : 'permit_empty|min_length[8]|max_length[72]',
        ];
    }

    private function collect(): array
    {
        return [
            'id_number' => strtoupper(trim((string) $this->request->getPost('id_number'))),
            'name'      => trim((string) $this->request->getPost('name')),
            'email'     => strtolower(trim((string) $this->request->getPost('email'))),
            'phone'     => trim((string) $this->request->getPost('phone')) ?: null,
            'position'  => trim((string) $this->request->getPost('position')) ?: null,
            'role'      => (string) $this->request->getPost('role'),
        ];
    }
}
