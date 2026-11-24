<?php

namespace App\Controllers;

use App\Models\UserModel;

/** Admin-only user and role management. */
class Users extends BaseController
{
    protected UserModel $users;

    public function __construct()
    {
        helper('form');
        $this->users = new UserModel();
    }

    public function index()
    {
        return view('users/index', ['users' => $this->users->orderBy('name')->findAll()]);
    }

    public function new()
    {
        return view('users/form', ['user' => null]);
    }

    public function create()
    {
        return $this->persist(null);
    }

    public function edit(int $id)
    {
        $user = $this->users->find($id);
        if (! $user) {
            return redirect()->to('/users')->with('error', 'User not found.');
        }

        return view('users/form', ['user' => $user]);
    }

    public function update(int $id)
    {
        if (! $this->users->find($id)) {
            return redirect()->to('/users')->with('error', 'User not found.');
        }

        return $this->persist($id);
    }

    public function delete(int $id)
    {
        $user = $this->users->find($id);
        if (! $user) {
            return redirect()->to('/users')->with('error', 'User not found.');
        }
        if ($id === (int) session()->get('userId')) {
            return redirect()->to('/users')->with('error', 'You cannot delete your own account.');
        }

        $this->users->delete($id);

        return redirect()->to('/users')->with('success', 'User deleted.');
    }

    private function persist(?int $id)
    {
        $passwordRule = $id === null
            ? 'required|min_length[8]|max_length[72]'
            : 'permit_empty|min_length[8]|max_length[72]';

        $rules = [
            'name'     => 'required|min_length[2]|max_length[100]',
            'email'    => 'required|valid_email|max_length[150]|is_unique[users.email' . ($id !== null ? ',id,' . $id : '') . ']',
            'role'     => 'required|in_list[' . implode(',', UserModel::ROLES) . ']',
            'password' => $passwordRule,
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $role = (string) $this->request->getPost('role');

        // Don't let an admin demote themselves and lock everyone out.
        if ($id !== null && $id === (int) session()->get('userId') && $role !== 'admin') {
            return redirect()->back()->withInput()->with('errors', ['role' => 'You cannot remove your own admin role.']);
        }

        $data = [
            'name'  => trim((string) $this->request->getPost('name')),
            'email' => strtolower(trim((string) $this->request->getPost('email'))),
            'role'  => $role,
        ];
        $password = (string) $this->request->getPost('password');
        if ($password !== '') {
            $data['password'] = $password;
        }

        if ($id === null) {
            $this->users->insert($data);
            $message = 'User created.';
        } else {
            $this->users->update($id, $data);
            $message = 'User updated.';
        }

        return redirect()->to('/users')->with('success', $message);
    }
}
