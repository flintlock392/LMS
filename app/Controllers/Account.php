<?php

namespace App\Controllers;

use App\Models\UserModel;

/** My Account: every user can update their contact details and change their password. */
class Account extends BaseController
{
    public function index()
    {
        return view('account/index', ['user' => (new UserModel())->find((int) session()->get('userId'))]);
    }

    public function profile()
    {
        $id    = (int) session()->get('userId');
        $rules = [
            'email' => ['rules' => 'required|valid_email|max_length[150]|is_unique[users.email,id,' . $id . ']', 'errors' => ['is_unique' => 'That email is already in use.']],
            'phone' => 'permit_empty|max_length[30]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        (new UserModel())->update($id, [
            'email' => strtolower(trim((string) $this->request->getPost('email'))),
            'phone' => trim((string) $this->request->getPost('phone')) ?: null,
        ]);

        log_activity('auth', 'profile_updated', session()->get('name') . ' updated their contact details', 'user', $id);

        return redirect()->to('/account')->with('success', 'Contact details saved.');
    }

    public function password()
    {
        $rules = [
            'current'  => 'required',
            'password' => password_rule(),
            'confirm'  => ['rules' => 'required|matches[password]', 'errors' => ['matches' => 'The new passwords do not match.']],
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $users = new UserModel();
        $user  = $users->find((int) session()->get('userId'));
        if (! password_verify((string) $this->request->getPost('current'), $user['password'])) {
            return redirect()->back()->with('error', 'Your current password is incorrect.');
        }

        $users->update($user['id'], ['password' => (string) $this->request->getPost('password')]);
        log_activity('auth', 'password_changed', $user['name'] . ' changed their own password', 'user', (int) $user['id']);

        return redirect()->to('/account')->with('success', 'Password changed.');
    }
}
