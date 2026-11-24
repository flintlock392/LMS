<?php

namespace App\Controllers;

use App\Models\BookModel;

class Home extends BaseController
{
    /** Landing page with the login card (also served at /login). */
    public function index(): string
    {
        return view('public/home');
    }

    public function about(): string
    {
        return view('public/about', ['stats' => (new BookModel())->stats()]);
    }
}
