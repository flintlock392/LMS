<?php

namespace App\Controllers;

use App\Libraries\Circulation as CirculationService;
use App\Models\BookModel;
use App\Models\BookRequestModel;
use App\Models\BorrowingModel;
use App\Models\CategoryModel;
use App\Models\UserModel;
use RuntimeException;

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
            'course'       => (string) $this->request->getGet('course'),   // one of the four programs, or "__general"
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

    /**
     * Suggestions for the Browse Books search box (anyone can browse the catalog).
     * GET catalog/suggest?q=clean → [{type, label, sub, value}] (max 8)
     */
    public function suggest()
    {
        $q = trim((string) $this->request->getGet('q'));
        if ($q === '' || mb_strlen($q) > 100) {
            return $this->response->setJSON([]);
        }

        $db   = db_connect();
        $like = $db->escape($db->escapeLikeString($q) . '%');
        $rows = $db->table('books')
            ->select('title, author, isbn')
            ->groupStart()->like('title', $q)->orLike('author', $q)->orLike('isbn', str_replace('-', '', $q))->groupEnd()
            ->orderBy('(title LIKE ' . $like . ' OR author LIKE ' . $like . ')', 'DESC', false)   // "starts with" first
            ->orderBy('title')
            ->limit(8)->get()->getResultArray();

        return $this->response->setJSON(array_map(static fn ($b) => [
            'type'  => 'book',
            'label' => $b['title'],
            'sub'   => $b['author'] . ' · ISBN ' . $b['isbn'],
            'value' => $b['title'],
        ], $rows));
    }

    /** Students only (route filter): "Request to borrow". Staff approve it in Library → Requested. */
    public function request(int $id)
    {
        try {
            (new CirculationService())->requestBorrow($id, (int) session()->get('userId'));
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Request sent! The library staff will prepare the book. You\'ll see it under My Loans once it\'s issued.');
    }

    private function layout(): string
    {
        return current_role() === UserModel::STUDENT ? 'layouts/app' : 'layouts/public';
    }

    /**
     * The logged-in student's loans and waiting requests, for the buttons on each book:
     *   ['loans' => [book_id => borrowing_id], 'requests' => [book_id => ['borrow' => id, 'return' => id]]]
     */
    private function myLoans(): array
    {
        if (current_role() !== UserModel::STUDENT) {
            return ['loans' => [], 'requests' => []];
        }
        $userId = (int) session()->get('userId');
        $loans  = [];
        foreach ((new BorrowingModel())->where('user_id', $userId)->where('status', 'borrowed')->findAll() as $l) {
            $loans[(int) $l['book_id']] = (int) $l['id'];
        }

        return ['loans' => $loans, 'requests' => (new BookRequestModel())->pendingMapFor($userId)];
    }
}
