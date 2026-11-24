<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 *
 * Filter alias "role" must be registered in app/Config/Filters.php (see README).
 *   role                   -> any logged-in user
 *   role:admin,librarian   -> staff only
 *   role:admin             -> admins only
 */

// ---- Public -----------------------------------------------------------
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attempt');
$routes->get('register', 'Auth::register');
$routes->post('register', 'Auth::store');
$routes->get('logout', 'Auth::logout');

// ---- Any logged-in user (member, librarian, admin) -------------------
$routes->group('', ['filter' => 'role'], static function ($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->get('books', 'Books::index');
});

// ---- Staff: librarian + admin ----------------------------------------
// Declared before books/(:num) so "books/new" is never treated as an id.
$routes->group('', ['filter' => 'role:admin,librarian'], static function ($routes) {
    $routes->get('books/new', 'Books::new');
    $routes->post('books/create', 'Books::create');
    $routes->get('books/(:num)/edit', 'Books::edit/$1');
    $routes->post('books/(:num)/update', 'Books::update/$1');
    $routes->post('books/(:num)/delete', 'Books::delete/$1');
    $routes->post('books/(:num)/stock', 'Books::adjustStock/$1');

    $routes->get('categories', 'Categories::index');
    $routes->get('categories/new', 'Categories::new');
    $routes->post('categories/create', 'Categories::create');
    $routes->get('categories/(:num)/edit', 'Categories::edit/$1');
    $routes->post('categories/(:num)/update', 'Categories::update/$1');
    $routes->post('categories/(:num)/delete', 'Categories::delete/$1');
});

$routes->group('', ['filter' => 'role'], static function ($routes) {
    $routes->get('books/(:num)', 'Books::show/$1');
});

// ---- Admin only -------------------------------------------------------
$routes->group('', ['filter' => 'role:admin'], static function ($routes) {
    $routes->get('users', 'Users::index');
    $routes->get('users/new', 'Users::new');
    $routes->post('users/create', 'Users::create');
    $routes->get('users/(:num)/edit', 'Users::edit/$1');
    $routes->post('users/(:num)/update', 'Users::update/$1');
    $routes->post('users/(:num)/delete', 'Users::delete/$1');
});
