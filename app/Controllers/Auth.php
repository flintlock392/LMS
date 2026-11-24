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
            log_activity('auth', 'login_failed', 'Failed login attempt for "' . mb_substr(trim((string) $this->request->getPost('login')), 0, 60) . '"', 'user', $user['id'] ?? null, 0);

            return redirect()->to('/login')->withInput()->with('error', 'Incorrect ID number or password.');
        }
        if ($user['status'] !== 'active') {
            return redirect()->to('/login')->withInput()
                ->with('error', 'Your account is suspended. Please visit the library desk.');
        }

        $users->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
        log_activity('auth', 'login', $user['name'] . ' signed in as ' . role_label($user['role']), 'user', (int) $user['id'], (int) $user['id']);

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

    public function forgot()
    {
        return view('auth/forgot');
    }

    public function logout()
    {
        if (session()->get('isLoggedIn')) {
            log_activity('auth', 'logout', session()->get('name') . ' signed out', 'user', (int) session()->get('userId'));
        }
        session()->destroy();

        return redirect()->to('/login')->with('success', 'You have been logged out.');
    }
}
