<?php

namespace App\Controllers;

use App\Libraries\Circulation as CirculationService;
use App\Models\BookModel;
use App\Models\BorrowingModel;
use App\Models\CategoryModel;
use App\Models\ReservationModel;
use App\Models\UserModel;
use RuntimeException;

/**
 * "Books" in the public menu and "Browse Books" for students.
 * Visitors can browse; logged-in students can also reserve.
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
            'mine'       => $this->myOpenBookIds(),
        ]);
    }

    public function show(int $id)
    {
        $book = (new BookModel())->findWithAvailability($id);
        if (! $book) {
            return redirect()->to('/catalog')->with('error', 'Book not found.');
        }

        return view('catalog/show', ['book' => $book, 'layout' => $this->layout(), 'mine' => $this->myOpenBookIds()]);
    }

    /** Students only (route filter). */
    public function reserve(int $id)
    {
        try {
            $r = (new CirculationService())->reserve($id, (int) session()->get('userId'), (int) session()->get('userId'));
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to('/my/reservations')->with('success', $r['status'] === 'ready'
            ? 'Reserved! A copy is waiting for you at the desk. Pick it up by ' . fmt_date($r['expires_at'], true) . '.'
            : 'Reserved! You\'ll see "Ready for pickup" here when a copy comes back.');
    }

    private function layout(): string
    {
        return current_role() === UserModel::STUDENT ? 'layouts/app' : 'layouts/public';
    }

    /** book_id => 'loan' | 'pending' | 'ready' for the logged-in student. */
    private function myOpenBookIds(): array
    {
        if (current_role() !== UserModel::STUDENT) {
            return [];
        }
        $userId = (int) session()->get('userId');
        $out    = [];
        foreach ((new ReservationModel())->where('user_id', $userId)->whereIn('status', ReservationModel::OPEN)->findAll() as $r) {
            $out[(int) $r['book_id']] = $r['status'];
        }
        foreach ((new BorrowingModel())->where('user_id', $userId)->where('status', 'borrowed')->findAll() as $l) {
            $out[(int) $l['book_id']] = 'loan';
        }

        return $out;
    }
}
