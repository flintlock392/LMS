<?php

namespace App\Controllers;

use App\Models\BookModel;
use App\Models\CategoryModel;

class Books extends BaseController
{
    protected BookModel $books;
    protected CategoryModel $categories;

    public function __construct()
    {
        helper(['form', 'library']);
        $this->books      = new BookModel();
        $this->categories = new CategoryModel();
    }

    /** List with search, category / author / availability filters and sorting (all roles). */
    public function index()
    {
        $filters = [
            'q'            => trim((string) $this->request->getGet('q')),
            'category_id'  => (string) $this->request->getGet('category_id'),
            'author'       => trim((string) $this->request->getGet('author')),
            'availability' => (string) $this->request->getGet('availability'),
            'sort'         => (string) $this->request->getGet('sort'),
            'dir'          => (string) $this->request->getGet('dir'),
        ];

        $rows = $this->books->filtered($filters)->paginate(10);

        return view('books/index', [
            'books'      => $rows,
            'pager'      => $this->books->pager,
            'filters'    => $filters,
            'categories' => $this->categories->orderBy('name')->findAll(),
            'authors'    => $this->books->distinctAuthors(),
        ]);
    }

    public function show(int $id)
    {
        $book = $this->books->findWithCategory($id);
        if (! $book) {
            return redirect()->to('/books')->with('error', 'Book not found.');
        }

        return view('books/show', ['book' => $book]);
    }

    public function new()
    {
        return view('books/form', [
            'book'       => null,
            'categories' => $this->categories->orderBy('name')->findAll(),
        ]);
    }

    public function create()
    {
        return $this->persist(null);
    }

    public function edit(int $id)
    {
        $book = $this->books->find($id);
        if (! $book) {
            return redirect()->to('/books')->with('error', 'Book not found.');
        }

        return view('books/form', [
            'book'       => $book,
            'categories' => $this->categories->orderBy('name')->findAll(),
        ]);
    }

    public function update(int $id)
    {
        if (! $this->books->find($id)) {
            return redirect()->to('/books')->with('error', 'Book not found.');
        }

        return $this->persist($id);
    }

    public function delete(int $id)
    {
        $book = $this->books->find($id);
        if (! $book) {
            return redirect()->to('/books')->with('error', 'Book not found.');
        }

        $this->books->delete($id);

        return redirect()->to('/books')->with('success', '"' . $book['title'] . '" was deleted.');
    }

    /** Quick +/- stock adjustment from the list (librarian/admin). */
    public function adjustStock(int $id)
    {
        $book = $this->books->find($id);
        if (! $book) {
            return redirect()->to('/books')->with('error', 'Book not found.');
        }

        $delta = (int) $this->request->getPost('delta');
        $new   = max(0, (int) $book['stock_count'] + $delta);
        $this->books->update($id, ['stock_count' => $new]);

        return redirect()->back()->with('success', '"' . $book['title'] . '" stock is now ' . $new . '.');
    }

    /** Shared create/update logic. */
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
            'stock_count'      => 'required|is_natural|less_than_equal_to[100000]',
            'description'      => 'permit_empty|max_length[2000]',
        ];

        $errors = $this->validateData($input, $rules) ? [] : $this->validator->getErrors();

        if (! isset($errors['isbn'])) {
            if (! isbn_is_valid($input['isbn'])) {
                $errors['isbn'] = 'Enter a valid ISBN-10 or ISBN-13 (check digit must match).';
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
            'stock_count'      => (int) $input['stock_count'],
            'description'      => trim((string) ($input['description'] ?? '')) ?: null,
        ];

        if ($id === null) {
            $this->books->insert($data);
            $message = 'Book added.';
        } else {
            $this->books->update($id, $data);
            $message = 'Book updated.';
        }

        return redirect()->to('/books')->with('success', $message);
    }
}
