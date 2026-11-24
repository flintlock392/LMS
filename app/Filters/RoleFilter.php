<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Login + role gate.
 *
 *   'filter' => 'role'                    -> any logged-in user
 *   'filter' => 'role:admin,librarian'    -> only those roles
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (! $session->get('isLoggedIn')) {
            return redirect()->to('/login')->with('error', 'Please log in to continue.');
        }

        if (! empty($arguments) && ! in_array($session->get('role'), $arguments, true)) {
            return redirect()->to('/')->with('error', 'You do not have permission to do that.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
