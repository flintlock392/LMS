<?php

namespace App\Controllers;

use App\Models\BookModel;
use App\Models\CategoryModel;

class Dashboard extends BaseController
{
    public function index()
    {
        helper('library');

        $books = new BookModel();

        return view('dashboard/index', [
            'stats'       => $books->stats() + ['categories' => (new CategoryModel())->countAllResults()],
            'lowStock'    => $books->filtered(['availability' => 'low_stock', 'sort' => 'stock_count'])->findAll(8),
            'outOfStock'  => $books->filtered(['availability' => 'out_of_stock'])->findAll(8),
        ]);
    }
}
