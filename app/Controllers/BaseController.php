<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @param CLIRequest|IncomingRequest $request
     *
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        $this->helpers = ['form', 'url', 'library'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');

        // Hosted MySQL can be stricter than XAMPP's MariaDB (e.g. Aiven's ANSI / ONLY_FULL_GROUP_BY).
        // DB_SQL_MODE (set on Render) makes it behave like the local database.
        $sqlMode = getenv('DB_SQL_MODE');
        if (is_string($sqlMode) && $sqlMode !== '') {
            $db = db_connect();
            $db->query('SET SESSION sql_mode = ' . $db->escape($sqlMode));
        }

        // Overdue books earn late credits every day, not only when they come back.
        // Runs once per day, on the first page a signed-in user opens.
        if (session()->get('isLoggedIn')) {
            $key = 'overdue-credits-' . date('Ymd');
            if (! cache($key)) {
                cache()->save($key, 1, DAY + HOUR);
                (new \App\Libraries\Circulation())->accrueOverdueCredits();
            }
        }
    }
}
