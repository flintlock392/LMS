<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 *
 * User levels (filter "role", see app/Filters/RoleFilter.php):
 *   role                           -> any logged-in user
 *   role:head_librarian,librarian  -> library staff
 *   role:head_librarian            -> head librarian only
 *   role:student                   -> students only
 */

$staff = 'role:head_librarian,librarian';
$head  = 'role:head_librarian';

// ---- Public -----------------------------------------------------------
$routes->get('/', 'Home::index');
$routes->get('about', 'Home::about');
$routes->get('login', 'Home::index');
$routes->post('login', 'Auth::attempt');
$routes->post('login/confirm', 'Auth::confirm');   // "Go to dashboard" from the home page asks for the password again
$routes->get('forgot-password', 'Auth::forgot');
$routes->post('logout', 'Auth::logout');
$routes->get('catalog', 'Catalog::index');
$routes->get('catalog/suggest', 'Catalog::suggest');   // Browse Books search suggestions (JSON)
$routes->get('catalog/(:num)', 'Catalog::show/$1');

// ---- Any logged-in user -------------------------------------------------
$routes->group('', ['filter' => 'role'], static function ($routes) {
    $routes->get('dashboard', 'Dashboard::index');
    $routes->get('account', 'Account::index');
    $routes->post('account/profile', 'Account::profile');
    $routes->post('account/password', 'Account::password');
});

// ---- Students -------------------------------------------------------------
$routes->group('', ['filter' => 'role:student'], static function ($routes) {
    $routes->get('my/loans', 'My::loans');
    // Request & return (Browse Books / My Loans)
    $routes->post('catalog/(:num)/request', 'Catalog::request/$1');
    $routes->post('my/loans/(:num)/return-request', 'My::requestReturn/$1');
    $routes->post('my/requests/(:num)/cancel', 'My::cancelRequest/$1');
    $routes->get('my/credits', 'My::credits');
    $routes->post('my/credits/appeal', 'My::appeal');
    $routes->post('my/credits/appeals/(:num)/cancel', 'My::cancelAppeal/$1');
});

// ---- Head librarian only (declared before the (:num) routes below) ---------
$routes->group('', ['filter' => $head], static function ($routes) {
    $routes->post('books/(:num)/archive', 'Books::archive/$1');

    // Archive: archived books, categories and accounts, with Restore
    $routes->get('archive', 'Archive::index');
    $routes->post('archive/(:segment)/(:num)/restore', 'Archive::restore/$1/$2');

    $routes->get('categories', 'Categories::index');
    $routes->post('categories/create', 'Categories::create');
    $routes->post('categories/(:num)/update', 'Categories::update/$1');
    $routes->post('categories/(:num)/archive', 'Categories::archive/$1');

    $routes->get('members/(:num)/edit', 'Members::edit/$1');
    $routes->post('members/(:num)/update', 'Members::update/$1');
    $routes->post('members/(:num)/status', 'Members::toggleStatus/$1');
    $routes->post('members/(:num)/archive', 'Members::archive/$1');


    // Credit Management: add / reduce credits, handle appeals (Head Librarian only)
    $routes->post('credits/adjust', 'Credits::adjust');
    $routes->post('credits/appeals/(:num)/approve', 'Credits::approveAppeal/$1');
    $routes->post('credits/appeals/(:num)/decline', 'Credits::declineAppeal/$1');

    $routes->get('staff', 'Staff::index');
    $routes->get('staff/new', 'Staff::new');
    $routes->post('staff/create', 'Staff::create');
    $routes->get('staff/(:num)/edit', 'Staff::edit/$1');
    $routes->post('staff/(:num)/update', 'Staff::update/$1');
    $routes->post('staff/(:num)/status', 'Staff::toggleStatus/$1');
    $routes->post('staff/(:num)/archive', 'Staff::archive/$1');

    $routes->get('settings', 'Settings::index');
    $routes->post('settings', 'Settings::save');

    // Book & Return → Activity Log (actions between two dates)
    $routes->get('activity', 'Activity::index');
    $routes->get('activity/export', 'Activity::export');
});

// ---- Library staff (head librarian + librarian staff) -------------------
$routes->group('', ['filter' => $staff], static function ($routes) {
    $routes->get('library', 'Library::index');
    $routes->get('books', 'Library::legacy/books');
    $routes->get('books/new', 'Books::new');
    $routes->post('books/create', 'Books::create');
    $routes->get('books/(:num)', 'Books::show/$1');
    $routes->get('books/(:num)/edit', 'Books::edit/$1');
    $routes->post('books/(:num)/update', 'Books::update/$1');

    $routes->get('circulation', 'Library::legacy/circulation');
    $routes->post('circulation/issue', 'Circulation::issue');

    // Library → Requested: students' borrow / return requests
    $routes->post('requests/(:num)/approve', 'Requests::approve/$1');
    $routes->post('requests/(:num)/receive', 'Requests::receive/$1');
    $routes->post('requests/(:num)/decline', 'Requests::decline/$1');
    $routes->post('circulation/(:num)/return', 'Circulation::receive/$1');
    $routes->post('inventory/adjust', 'Inventory::adjust');   // Update Stock: add / remove copies (Head Librarian and Librarian Staff)

    $routes->get('suggest', 'Suggest::index');                       // search-box suggestions (JSON)

    $routes->get('members', 'Members::index');
    $routes->get('members/new', 'Members::new');
    $routes->post('members/create', 'Members::create');
    $routes->get('members/(:num)', 'Members::show/$1');

    $routes->get('inventory', 'Library::legacy/inventory');

    // Credit Management (view: head + staff)
    $routes->get('credits', 'Credits::index');

    $routes->get('reports', 'Reports::index');
    $routes->get('reports/export/(:segment)', 'Reports::export/$1');
});
