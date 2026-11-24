<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    /**
     * One login form for all three user levels. The username is the
     * Student ID number (students) or Staff ID (librarians); email also works.
     * The account's user level decides which modules they see afterwards.
     */
    public function attempt()
    {
        $throttler = service('throttler');
        $key       = 'login-' . md5((string) $this->request->getIPAddress());
        if (! $throttler->check($key, 5, MINUTE)) {
            return redirect()->to('/login')->withInput()
                ->with('error', 'Too many login attempts. Please wait a minute and try again.');
        }

        if (! $this->validate(['login' => 'required|max_length[150]', 'password' => 'required|max_length[72]'])) {
            return redirect()->to('/login')->withInput()->with('error', 'Enter your ID number and password.');
        }

        $users = new UserModel();
        $user  = $users->findByLogin((string) $this->request->getPost('login'));

        if (! $user || ! password_verify((string) $this->request->getPost('password'), $user['password'])) {
            return redirect()->to('/login')->withInput()->with('error', 'Incorrect ID number or password.');
        }
        if ($user['status'] !== 'active') {
            return redirect()->to('/login')->withInput()
                ->with('error', 'Your account is suspended. Please visit the library desk.');
        }

        $users->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        session()->regenerate(true);
        session()->set([
            'isLoggedIn' => true,
            'userId'     => (int) $user['id'],
            'idNumber'   => $user['id_number'],
            'name'       => $user['name'],
            'role'       => $user['role'],
        ]);

        return redirect()->to('/dashboard')->with('success', 'Welcome back, ' . $user['name'] . '! You are signed in as ' . role_label($user['role']) . '.');
    }

    public function signup()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/signup');
    }

    /** Public sign-up always creates a Student account. Staff accounts are made by the Head Librarian. */
    public function store()
    {
        $rules = [
            'id_number'  => [
                'rules'  => 'required|regex_match[/^[A-Za-z0-9-]{4,30}$/]|is_unique[users.id_number]',
                'errors' => [
                    'regex_match' => 'Student number may only contain letters, numbers and dashes.',
                    'is_unique'   => 'That student number is already registered.',
                ],
            ],
            'name'       => 'required|min_length[2]|max_length[100]',
            'email'      => ['rules' => 'required|valid_email|max_length[150]|is_unique[users.email]', 'errors' => ['is_unique' => 'That email is already registered.']],
            'course'     => 'required|max_length[100]',
            'year_level' => 'required|max_length[20]',
            'password'   => 'required|min_length[8]|max_length[72]',
            'confirm'    => ['rules' => 'required|matches[password]', 'errors' => ['matches' => 'The passwords do not match.']],
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        (new UserModel())->insert([
            'id_number'  => strtoupper(trim((string) $this->request->getPost('id_number'))),
            'name'       => trim((string) $this->request->getPost('name')),
            'email'      => strtolower(trim((string) $this->request->getPost('email'))),
            'course'     => trim((string) $this->request->getPost('course')),
            'year_level' => (string) $this->request->getPost('year_level'),
            'password'   => (string) $this->request->getPost('password'),
            'role'       => UserModel::STUDENT,
            'status'     => 'active',
        ]);

        return redirect()->to('/login')->with('success', 'Account created! Log in with your student number.');
    }

    public function forgot()
    {
        return view('auth/forgot');
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login')->with('success', 'You have been logged out.');
    }
}
