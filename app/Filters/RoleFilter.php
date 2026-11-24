<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Login + user-level gate.
 *
 *   'filter' => 'role'                            -> any logged-in user
 *   'filter' => 'role:head_librarian,librarian'   -> only those user levels
 *
 * The account is re-checked on every request, so suspending or deleting a
 * user (or changing their level) takes effect immediately.
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (! $session->get('isLoggedIn')) {
            return redirect()->to('/login')->with('error', 'Please log in to continue.');
        }

        $user = (new UserModel())->select('id, name, role, status')->find((int) $session->get('userId'));
        if (! $user || $user['status'] !== 'active') {
            $session->destroy();

            return redirect()->to('/login')->with('error', 'Your account is not active. Please contact the library.');
        }
        if ($user['role'] !== $session->get('role') || $user['name'] !== $session->get('name')) {
            $session->set(['role' => $user['role'], 'name' => $user['name']]);
        }

        if (! empty($arguments) && ! in_array($user['role'], $arguments, true)) {
            return redirect()->to('/dashboard')->with('error', 'You do not have access to that page.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
