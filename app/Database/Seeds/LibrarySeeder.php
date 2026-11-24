<?php

namespace App\Database\Seeds;

use App\Models\BookModel;
use App\Models\CategoryModel;
use App\Models\UserModel;
use CodeIgniter\Database\Seeder;

class LibrarySeeder extends Seeder
{
    public function run()
    {
        $users      = new UserModel();
        $categories = new CategoryModel();
        $books      = new BookModel();

        // Default accounts: CHANGE THESE PASSWORDS before any real use.
        $accounts = [
            ['name' => 'Admin',     'email' => 'admin@library.test',     'password' => 'Admin@12345',     'role' => 'admin'],
            ['name' => 'Librarian', 'email' => 'librarian@library.test', 'password' => 'Librarian@12345', 'role' => 'librarian'],
            ['name' => 'Member',    'email' => 'member@library.test',    'password' => 'Member@12345',    'role' => 'member'],
        ];
        foreach ($accounts as $account) {
            if (! $users->findByEmail($account['email'])) {
                $users->insert($account);
            }
        }

        $categoryNames = [
            'Programming'     => 'Software development and computer science.',
            'Fiction'         => 'Novels and short stories.',
            'Fantasy'         => 'Fantasy and speculative fiction.',
            'Science Fiction' => 'Science fiction and dystopian works.',
        ];
        $categoryIds = [];
        foreach ($categoryNames as $name => $description) {
            $existing = $categories->where('name', $name)->first();
            $categoryIds[$name] = $existing
                ? $existing['id']
                : $categories->insert(['name' => $name, 'description' => $description], true);
        }

        $samples = [
            ['Clean Code',                 '9780132350884', 'Robert C. Martin',  'Programming',     'Prentice Hall',     2008, 6],
            ['Effective Java',             '9780134685991', 'Joshua Bloch',      'Programming',     'Addison-Wesley',    2018, 3],
            ['JavaScript: The Good Parts', '9780596517748', 'Douglas Crockford', 'Programming',     "O'Reilly Media",    2008, 0],
            ['The Great Gatsby',           '9780743273565', 'F. Scott Fitzgerald', 'Fiction',       'Scribner',          2004, 8],
            ['To Kill a Mockingbird',      '9780061120084', 'Harper Lee',        'Fiction',         'Harper Perennial',  2006, 4],
            ['Pride and Prejudice',        '9780141439518', 'Jane Austen',       'Fiction',         'Penguin Classics',  2002, 10],
            ['The Hobbit',                 '9780547928227', 'J.R.R. Tolkien',    'Fantasy',         'Mariner Books',     2012, 5],
            ['1984',                       '9780451524935', 'George Orwell',     'Science Fiction', 'Signet Classics',   1961, 2],
            ['Fahrenheit 451',             '9781451673319', 'Ray Bradbury',      'Science Fiction', 'Simon & Schuster',  2012, 7],
        ];

        foreach ($samples as [$title, $isbn, $author, $category, $publisher, $year, $stock]) {
            if ($books->isbnExists($isbn)) {
                continue;
            }
            $books->insert([
                'title'            => $title,
                'isbn'             => $isbn,
                'author'           => $author,
                'category_id'      => $categoryIds[$category],
                'publisher'        => $publisher,
                'publication_year' => $year,
                'stock_count'      => $stock,
            ]);
        }
    }
}
