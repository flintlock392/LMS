<?php

namespace App\Controllers;

use App\Models\BookModel;
use App\Models\BorrowingModel;
use App\Models\CategoryModel;
use App\Models\UserModel;

/**
 * "Books" in the public menu and "Browse Books" for students.
 * Anyone can browse and see which titles are on the shelf; books are borrowed at the desk.
 */
class Catalog extends BaseController
{
    public function index()
    {
        $filters = [
            'q'            => trim((string) $this->request->getGet('q')),
            'category_id'  => (string) $this->request->getGet('category_id'),
            'availability' => (string) $this->request->getGet('availability') === 'in_stock' ? 'in_stock' : '',
            'sort'         => 'title',
        ];
        $model = new BookModel();

        return view('catalog/index', [
            'books'      => $model->filtered($filters)->paginate(12),
            'pager'      => $model->pager,
            'filters'    => $filters,
            'categories' => (new CategoryModel())->orderBy('name')->findAll(),
            'layout'     => $this->layout(),
            'mine'       => $this->myLoans(),
        ]);
    }

    public function show(int $id)
    {
        $book = (new BookModel())->findWithAvailability($id);
        if (! $book) {
            return redirect()->to('/catalog')->with('error', 'Book not found.');
        }

        return view('catalog/show', ['book' => $book, 'layout' => $this->layout(), 'mine' => $this->myLoans()]);
    }

    private function layout(): string
    {
        return current_role() === UserModel::STUDENT ? 'layouts/app' : 'layouts/public';
    }

    /** book_id => true for books the logged-in student currently has on loan. */
    private function myLoans(): array
    {
        if (current_role() !== UserModel::STUDENT) {
            return [];
        }
        $out = [];
        foreach ((new BorrowingModel())->where('user_id', (int) session()->get('userId'))->where('status', 'borrowed')->findAll() as $l) {
            $out[(int) $l['book_id']] = true;
        }

        return $out;
    }
}
