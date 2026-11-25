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

    /**
     * "Go to dashboard" on the home page: someone already signed in must type
     * their password again before the dashboard opens. Too many wrong tries
     * sign the account out.
     */
    public function confirm()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        $user = (new UserModel())->find((int) session()->get('userId'));
        if (! $user || $user['status'] !== 'active') {
            $this->signOut();

            return redirect()->to('/login')->with('error', 'Please log in again.');
        }

        $password = (string) $this->request->getPost('password');
        if ($password === '') {
            return redirect()->to('/')->with('error', 'Enter your password to go to the dashboard.');
        }

        if (! password_verify($password, $user['password'])) {
            $fails = (int) session()->get('confirmFails') + 1;
            log_activity('auth', 'confirm_failed', 'Wrong password when reopening the dashboard as ' . $user['name'], 'user', (int) $user['id']);
            if ($fails >= 5) {
                log_activity('auth', 'logout', $user['name'] . ' was signed out after 5 wrong passwords', 'user', (int) $user['id']);
                $this->signOut();

                return redirect()->to('/login')->with('error', 'Too many wrong passwords. You have been signed out.');
            }
            session()->set('confirmFails', $fails);

            return redirect()->to('/')->with('error', 'Incorrect password. ' . (5 - $fails) . ' tr' . (5 - $fails === 1 ? 'y' : 'ies') . ' left before you are signed out.');
        }

        session()->remove('confirmFails');

        return redirect()->to('/dashboard');
    }

    /** Ends the sign-in but keeps the session, so the message after the redirect still shows. */
    private function signOut(): void
    {
        session()->remove(['isLoggedIn', 'userId', 'idNumber', 'name', 'role', 'confirmFails']);
        session()->regenerate(true);
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
