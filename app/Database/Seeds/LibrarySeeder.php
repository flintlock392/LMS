<?php

namespace App\Database\Seeds;

use App\Models\BookModel;
use App\Models\BorrowingModel;
use App\Models\CategoryModel;
use App\Models\FineModel;
use App\Models\InventoryLogModel;
use App\Models\ReservationModel;
use App\Models\UserModel;
use CodeIgniter\Database\Seeder;

/**
 * Demo data for local development.
 *
 * Run:  php spark migrate  &&  php spark db:seed LibrarySeeder
 *
 * DEMO ACCOUNTS (log in with the ID number, or the email):
 *   Head Librarian   ID: HL-0001      password: Head@12345
 *   Librarian Staff  ID: LS-0001      password: Staff@12345
 *   Student          ID: 2024-00001   password: Student@12345
 *   (more students: 2024-00002 ... 2024-00006, same student password)
 *
 * CHANGE THESE PASSWORDS before any real use.
 */
class LibrarySeeder extends Seeder
{
    public function run()
    {
        $users = new UserModel();

        $accounts = [
            ['id_number' => 'HL-0001', 'name' => 'Maria Santos', 'email' => 'head@cozynook.test', 'password' => 'Head@12345', 'role' => UserModel::HEAD, 'position' => 'Head Librarian', 'phone' => '0917 000 0001'],
            ['id_number' => 'LS-0001', 'name' => 'Paolo Reyes', 'email' => 'staff@cozynook.test', 'password' => 'Staff@12345', 'role' => UserModel::LIBRARIAN, 'position' => 'Circulation Librarian', 'phone' => '0917 000 0002'],
            ['id_number' => 'LS-0002', 'name' => 'Ana Cruz', 'email' => 'staff2@cozynook.test', 'password' => 'Staff@12345', 'role' => UserModel::LIBRARIAN, 'position' => 'Cataloguing Assistant'],
            ['id_number' => '2024-00001', 'name' => 'Juan dela Cruz', 'email' => 'juan@student.test', 'password' => 'Student@12345', 'role' => UserModel::STUDENT, 'course' => 'BS Information Technology', 'year_level' => '2nd Year'],
            ['id_number' => '2024-00002', 'name' => 'Bea Villanueva', 'email' => 'bea@student.test', 'password' => 'Student@12345', 'role' => UserModel::STUDENT, 'course' => 'BS Computer Science', 'year_level' => '1st Year'],
            ['id_number' => '2024-00003', 'name' => 'Carlo Mendoza', 'email' => 'carlo@student.test', 'password' => 'Student@12345', 'role' => UserModel::STUDENT, 'course' => 'BS Education', 'year_level' => '3rd Year'],
            ['id_number' => '2024-00004', 'name' => 'Diane Garcia', 'email' => 'diane@student.test', 'password' => 'Student@12345', 'role' => UserModel::STUDENT, 'course' => 'BS Psychology', 'year_level' => '4th Year'],
            ['id_number' => '2024-00005', 'name' => 'Enzo Ramos', 'email' => 'enzo@student.test', 'password' => 'Student@12345', 'role' => UserModel::STUDENT, 'course' => 'BS Information Technology', 'year_level' => '2nd Year'],
            ['id_number' => '2024-00006', 'name' => 'Faye Torres', 'email' => 'faye@student.test', 'password' => 'Student@12345', 'role' => UserModel::STUDENT, 'course' => 'BA Communication', 'year_level' => '1st Year', 'status' => 'suspended'],
        ];
        $userIds = [];
        foreach ($accounts as $account) {
            $existing = $users->where('id_number', $account['id_number'])->first();
            $userIds[$account['id_number']] = $existing ? (int) $existing['id'] : (int) $users->insert($account, true);
        }

        // Only build sample circulation data on an empty library.
        if ((new BookModel())->countAllResults() > 0) {
            return;
        }

        $categories    = new CategoryModel();
        $categoryNames = [
            'Programming'     => 'Software development and computer science.',
            'Fiction'         => 'Novels and short stories.',
            'Fantasy'         => 'Fantasy and speculative fiction.',
            'Science Fiction' => 'Science fiction and dystopian works.',
            'Science'         => 'Popular and general science.',
            'History'         => 'World and Philippine history.',
        ];
        $categoryIds = [];
        foreach ($categoryNames as $name => $description) {
            $existing           = $categories->where('name', $name)->first();
            $categoryIds[$name] = $existing ? $existing['id'] : $categories->insert(['name' => $name, 'description' => $description], true);
        }

        $samples = [
            ['Clean Code', '9780132350884', 'Robert C. Martin', 'Programming', 'Prentice Hall', 2008, 'A-101', 4],
            ['Effective Java', '9780134685991', 'Joshua Bloch', 'Programming', 'Addison-Wesley', 2018, 'A-102', 2],
            ['JavaScript: The Good Parts', '9780596517748', 'Douglas Crockford', 'Programming', "O'Reilly Media", 2008, 'A-103', 1],
            ['The Pragmatic Programmer', '9780135957059', 'David Thomas', 'Programming', 'Addison-Wesley', 2019, 'A-104', 3],
            ['The Great Gatsby', '9780743273565', 'F. Scott Fitzgerald', 'Fiction', 'Scribner', 2004, 'B-201', 5],
            ['To Kill a Mockingbird', '9780061120084', 'Harper Lee', 'Fiction', 'Harper Perennial', 2006, 'B-202', 3],
            ['Pride and Prejudice', '9780141439518', 'Jane Austen', 'Fiction', 'Penguin Classics', 2002, 'B-203', 4],
            ['The Hobbit', '9780547928227', 'J.R.R. Tolkien', 'Fantasy', 'Mariner Books', 2012, 'C-301', 3],
            ['1984', '9780451524935', 'George Orwell', 'Science Fiction', 'Signet Classics', 1961, 'C-302', 2],
            ['Fahrenheit 451', '9781451673319', 'Ray Bradbury', 'Science Fiction', 'Simon & Schuster', 2012, 'C-303', 2],
            ['A Brief History of Time', '9780553380163', 'Stephen Hawking', 'Science', 'Bantam', 1998, 'D-401', 2],
            ['Sapiens', '9780062316097', 'Yuval Noah Harari', 'History', 'Harper', 2015, 'E-501', 3],
        ];

        $books   = new BookModel();
        $logs    = new InventoryLogModel();
        $bookIds = [];
        foreach ($samples as [$title, $isbn, $author, $category, $publisher, $year, $shelf, $stock]) {
            $id = (int) $books->insert([
                'title' => $title, 'isbn' => $isbn, 'author' => $author,
                'category_id' => $categoryIds[$category], 'publisher' => $publisher,
                'publication_year' => $year, 'shelf_location' => $shelf, 'stock_count' => $stock,
            ], true);
            $bookIds[$isbn] = $id;
            $logs->insert(['book_id' => $id, 'change' => $stock, 'reason' => 'initial', 'stock_after' => $stock, 'notes' => 'Opening inventory', 'user_id' => $userIds['HL-0001']]);
        }

        // ---- Sample circulation ---------------------------------------------
        $staff = $userIds['LS-0001'];
        $loans = new BorrowingModel();
        $fines = new FineModel();
        $day   = static fn (int $offset, string $fmt = 'Y-m-d H:i:s') => date($fmt, strtotime(($offset >= 0 ? '+' : '') . $offset . ' days'));

        // Active, on time.
        $loans->insert(['book_id' => $bookIds['9780132350884'], 'user_id' => $userIds['2024-00001'], 'issued_by' => $staff, 'borrowed_at' => $day(-3), 'due_date' => $day(4, 'Y-m-d'), 'status' => 'borrowed']);
        $loans->insert(['book_id' => $bookIds['9780547928227'], 'user_id' => $userIds['2024-00001'], 'issued_by' => $staff, 'borrowed_at' => $day(-6), 'due_date' => $day(1, 'Y-m-d'), 'status' => 'borrowed']);
        $loans->insert(['book_id' => $bookIds['9780596517748'], 'user_id' => $userIds['2024-00002'], 'issued_by' => $staff, 'borrowed_at' => $day(-2), 'due_date' => $day(5, 'Y-m-d'), 'status' => 'borrowed']);
        // Overdue.
        $loans->insert(['book_id' => $bookIds['9780451524935'], 'user_id' => $userIds['2024-00003'], 'issued_by' => $staff, 'borrowed_at' => $day(-12), 'due_date' => $day(-5, 'Y-m-d'), 'status' => 'borrowed']);
        $loans->insert(['book_id' => $bookIds['9780134685991'], 'user_id' => $userIds['2024-00004'], 'issued_by' => $staff, 'borrowed_at' => $day(-10), 'due_date' => $day(-3, 'Y-m-d'), 'status' => 'borrowed']);

        // Returned history across the last few months (for reports).
        $history = [
            ['9780743273565', '2024-00001', -95, 0], ['9780061120084', '2024-00002', -80, 2], ['9780141439518', '2024-00003', -64, 0],
            ['9780547928227', '2024-00004', -50, 0], ['9780132350884', '2024-00005', -41, 3], ['9780743273565', '2024-00002', -33, 0],
            ['9781451673319', '2024-00001', -25, 0], ['9780062316097', '2024-00005', -18, 1], ['9780132350884', '2024-00003', -15, 0],
            ['9780553380163', '2024-00004', -9, 0],  ['9780135957059', '2024-00001', -20, 0], ['9780451524935', '2024-00002', -28, 0],
        ];
        foreach ($history as [$isbn, $student, $start, $late]) {
            $id = (int) $loans->insert([
                'book_id' => $bookIds[$isbn], 'user_id' => $userIds[$student], 'issued_by' => $staff,
                'borrowed_at' => $day($start), 'due_date' => $day($start + 7, 'Y-m-d'),
                'returned_at' => $day($start + 7 + $late), 'received_by' => $staff,
                'status' => 'returned', 'condition' => 'good',
            ], true);
            if ($late > 0) {
                $fines->insert([
                    'user_id' => $userIds[$student], 'borrowing_id' => $id, 'reason' => 'overdue',
                    'amount' => $late * 5, 'status' => $late === 3 ? 'unpaid' : 'paid', 'notes' => $late . ' day(s) late',
                    'created_by' => $staff, 'processed_by' => $late === 3 ? null : $staff,
                    'paid_at' => $late === 3 ? null : $day($start + 8 + $late),
                ]);
            }
        }

        // Reservations: one waiting in the queue, one ready at the desk.
        $reservations = new ReservationModel();
        $reservations->insert(['book_id' => $bookIds['9780596517748'], 'user_id' => $userIds['2024-00001'], 'status' => 'pending', 'reserved_at' => $day(-1)]);
        $reservations->insert(['book_id' => $bookIds['9780062316097'], 'user_id' => $userIds['2024-00002'], 'status' => 'ready', 'reserved_at' => $day(-2), 'ready_at' => $day(-1), 'expires_at' => $day(1)]);

        // Turn the sample history above into Activity Log entries.
        $this->call('ActivityBackfillSeeder');
    }
}
