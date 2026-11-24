<?php

namespace App\Controllers;

use App\Models\BookModel;
use App\Models\BorrowingModel;
use App\Models\CategoryModel;
use App\Models\InventoryLogModel;
use App\Models\ReservationModel;

/**
 * Head Librarian: "Book Management" (full control, including delete + categories).
 * Librarian Staff: "Book Collection" (browse, add and edit titles).
 * Copy counts change through Inventory, so every change is logged.
 */
class Books extends BaseController
{
    protected BookModel $books;
    protected CategoryModel $categories;

    public function __construct()
    {
        $this->books      = new BookModel();
        $this->categories = new CategoryModel();
    }

    public function show(int $id)
    {
        $book = $this->books->findWithAvailability($id);
        if (! $book) {
            return redirect()->to('/library?tab=collections')->with('error', 'Book not found.');
        }

        return view('books/show', [
            'book'         => $book,
            'loans'        => (new BorrowingModel())->filtered(['status' => 'all'])->where('borrowings.book_id', $id)->findAll(15),
            'reservations' => (new ReservationModel())->filtered(['status' => 'open'])->where('reservations.book_id', $id)->findAll(),
            'log'          => (new InventoryLogModel())->recent(15, $id),
        ]);
    }

    public function new()
    {
        return view('books/form', ['book' => null, 'categories' => $this->categories->orderBy('name')->findAll()]);
    }

    public function create()
    {
        return $this->persist(null);
    }

    public function edit(int $id)
    {
        $book = $this->books->find($id);
        if (! $book) {
            return redirect()->to('/library?tab=collections')->with('error', 'Book not found.');
        }

        return view('books/form', ['book' => $book, 'categories' => $this->categories->orderBy('name')->findAll()]);
    }

    public function update(int $id)
    {
        if (! $this->books->find($id)) {
            return redirect()->to('/library?tab=collections')->with('error', 'Book not found.');
        }

        return $this->persist($id);
    }

    /** Head Librarian only. Titles with circulation history are kept for the records. */
    public function delete(int $id)
    {
        $book = $this->books->find($id);
        if (! $book) {
            return redirect()->to('/library?tab=collections')->with('error', 'Book not found.');
        }
        if ((new BorrowingModel())->where('book_id', $id)->countAllResults() > 0) {
            return redirect()->back()->with('error', '"' . $book['title'] . '" has borrowing history, so it can\'t be deleted. Remove its copies in Inventory Management instead.');
        }

        $this->books->delete($id);
        log_activity('book', 'deleted', 'Deleted "' . $book['title'] . '" (ISBN ' . $book['isbn'] . ') from the catalog', 'book', $id);

        return redirect()->to('/library?tab=collections')->with('success', '"' . $book['title'] . '" was deleted.');
    }

    private function persist(?int $id)
    {
        $input         = $this->request->getPost();
        $input['isbn'] = isbn_normalize((string) ($input['isbn'] ?? ''));

        $rules = [
            'title'            => 'required|min_length[2]|max_length[255]',
            'isbn'             => 'required|max_length[20]',
            'author'           => 'required|max_length[150]',
            'category_id'      => 'permit_empty|is_natural_no_zero|is_not_unique[categories.id]',
            'publisher'        => 'permit_empty|max_length[150]',
            'publication_year' => 'permit_empty|integer|greater_than_equal_to[1000]|less_than_equal_to[' . ((int) date('Y') + 1) . ']',
            'shelf_location'   => 'permit_empty|max_length[50]',
            'course'           => 'permit_empty|max_length[100]',
            'description'      => 'permit_empty|max_length[2000]',
        ];
        if ($id === null) {
            $rules['stock_count'] = 'required|is_natural|less_than_equal_to[10000]';
        }

        $errors = $this->validateData($input, $rules) ? [] : $this->validator->getErrors();

        if (! isset($errors['isbn'])) {
            if (! isbn_is_valid($input['isbn'])) {
                $errors['isbn'] = 'Enter a valid ISBN-10 or ISBN-13 (the check digit must match).';
            } elseif ($this->books->isbnExists($input['isbn'], $id)) {
                $errors['isbn'] = 'A book with this ISBN already exists.';
            }
        }
        if ($errors) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $data = [
            'title'            => trim($input['title']),
            'isbn'             => $input['isbn'],
            'author'           => trim($input['author']),
            'category_id'      => ($input['category_id'] ?? '') !== '' ? (int) $input['category_id'] : null,
            'publisher'        => trim((string) ($input['publisher'] ?? '')) ?: null,
            'publication_year' => ($input['publication_year'] ?? '') !== '' ? (int) $input['publication_year'] : null,
            'shelf_location'   => trim((string) ($input['shelf_location'] ?? '')) ?: null,
            'course'           => trim((string) ($input['course'] ?? '')) ?: null,
            'description'      => trim((string) ($input['description'] ?? '')) ?: null,
        ];

        if ($id === null) {
            $data['stock_count'] = (int) $input['stock_count'];
            $newId               = (int) $this->books->insert($data, true);
            if ($data['stock_count'] > 0) {
                (new InventoryLogModel())->insert([
                    'book_id' => $newId, 'change' => $data['stock_count'], 'reason' => 'initial',
                    'stock_after' => $data['stock_count'], 'notes' => 'Added to catalog', 'user_id' => session()->get('userId'),
                ]);
            }

            log_activity('book', 'added', 'Added "' . $data['title'] . '" (ISBN ' . $data['isbn'] . ') to the catalog with ' . $data['stock_count'] . ' cop' . ($data['stock_count'] === 1 ? 'y' : 'ies'), 'book', $newId);

            return redirect()->to('/books/' . $newId)->with('success', '"' . $data['title'] . '" was added to the collection.');
        }

        $this->books->update($id, $data);
        log_activity('book', 'edited', 'Edited the details of "' . $data['title'] . '"', 'book', $id);

        return redirect()->to('/books/' . $id)->with('success', 'Book details updated.');
    }
}
