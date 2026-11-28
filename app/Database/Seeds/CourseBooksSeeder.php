<?php

namespace App\Database\Seeds;

use App\Models\UserModel;
use CodeIgniter\Database\Seeder;

/**
 * Books for each course, plus general reading, with their edition and cover.
 * Covers come from Open Library (https://openlibrary.org), stored as "ol:<cover id>".
 * Editions, publishers and years were checked against Open Library by ISBN.
 *
 * Safe to run more than once: books already in the catalog (same ISBN) are not
 * added again; only a missing edition or cover is filled in.
 *
 * Run:  php spark db:seed CourseBooksSeeder
 */
class CourseBooksSeeder extends Seeder
{
    private const IT    = 'Bachelor of Science in Information Technology';
    private const CS    = 'Bachelor of Science in Computer Science';
    private const EDUC  = 'Bachelor of Science in Education';
    private const HOSP  = 'Bachelor of Science in Hospitality Management';

    /** New categories (name => description). */
    private const CATEGORIES = [
        'Information Technology' => 'Networking, databases, operating systems and web development.',
        'Computer Science'       => 'Algorithms, computer systems, theory and artificial intelligence.',
        'Education'              => 'Teaching methods, learning and educational psychology.',
        'Hospitality & Culinary' => 'Hospitality service, restaurant management and cooking.',
        'Personal Growth'        => 'Habits, thinking and finding meaning — good reading for every course.',
    ];

    /**
     * [title, isbn, author, edition, category, course (null = general reading), publisher, year, shelf, copies, cover id, description]
     */
    private const BOOKS = [
        // ---- BS Information Technology --------------------------------------------------
        ['Computer Networking: A Top-Down Approach', '9780133594140', 'James F. Kurose, Keith W. Ross', '7th edition', 'Information Technology', self::IT, 'Pearson', 2016, 'F-601', 4, 8509058,
            'How the Internet works, layer by layer: applications, transport, routing, links, wireless networks and security.'],
        ['Database System Concepts', '9780078022159', 'Abraham Silberschatz, Henry F. Korth, S. Sudarshan', '7th edition', 'Information Technology', self::IT, 'McGraw-Hill Education', 2019, 'F-602', 3, 10385525,
            'Relational databases, SQL, database design, storage, transactions and query processing.'],
        ['Head First HTML and CSS', '9780596159900', 'Elisabeth Robson, Eric Freeman', '2nd edition', 'Information Technology', self::IT, "O'Reilly Media", 2012, 'F-603', 3, 7799824,
            'A friendly, visual introduction to building web pages with HTML and styling them with CSS.'],
        ['Learning Web Design', '9781491960202', 'Jennifer Niederst Robbins', '5th edition', 'Information Technology', self::IT, "O'Reilly Media", 2018, 'F-604', 3, 8508652,
            'A beginner\'s guide to HTML, CSS, JavaScript and web graphics.'],
        ['Operating System Concepts', '9781118063330', 'Abraham Silberschatz, Peter B. Galvin, Greg Gagne', '9th edition', 'Information Technology', self::IT, 'Wiley', 2013, 'F-605', 3, 7298708,
            'Processes, threads, scheduling, memory, file systems and protection in modern operating systems.'],

        // ---- BS Computer Science -----------------------------------------------------------
        ['Introduction to Algorithms', '9780262046305', 'Thomas H. Cormen, Charles E. Leiserson, Ronald L. Rivest, Clifford Stein', '4th edition', 'Computer Science', self::CS, 'MIT Press', 2022, 'G-701', 3, 13768952,
            'The standard reference on algorithms and data structures, with analysis of running time.'],
        ['Structure and Interpretation of Computer Programs', '9780262510875', 'Harold Abelson, Gerald Jay Sussman', '2nd edition', 'Computer Science', self::CS, 'MIT Press', 1996, 'G-702', 2, 9325174,
            'Classic introduction to programming and abstraction, using the Scheme language.'],
        ["Computer Systems: A Programmer's Perspective", '9780134092669', "Randal E. Bryant, David R. O'Hallaron", '3rd edition', 'Computer Science', self::CS, 'Pearson', 2015, 'G-703', 2, 14858059,
            'How programs run on real machines: data representation, machine code, memory, linking and concurrency.'],
        ['Artificial Intelligence: A Modern Approach', '9780134610993', 'Stuart Russell, Peter Norvig', '4th edition', 'Computer Science', self::CS, 'Pearson', 2020, 'G-704', 2, 13530046,
            'Intelligent agents, search, logic, planning, probabilistic reasoning and machine learning.'],
        ['Discrete Mathematics and Its Applications', '9781259676512', 'Kenneth H. Rosen', '8th edition', 'Computer Science', self::CS, 'McGraw-Hill Education', 2018, 'G-705', 4, 10375823,
            'Logic, proofs, sets, counting, relations and graphs — the mathematics behind computing.'],

        // ---- BS Education ------------------------------------------------------------------
        ['Pedagogy of the Oppressed', '9780826412768', 'Paulo Freire', '30th anniversary edition', 'Education', self::EDUC, 'Continuum', 2000, 'H-801', 3, 630082,
            'A foundational work on critical pedagogy and the relationship between teacher and learner.'],
        ['Teach Like a Champion 2.0', '9781118901854', 'Doug Lemov', '2nd edition', 'Education', self::EDUC, 'Jossey-Bass', 2014, 'H-802', 3, 7657958,
            '62 practical classroom techniques for engaging students and building a strong learning culture.'],
        ['Mindset: The New Psychology of Success', '9780345472328', 'Carol S. Dweck', 'Ballantine paperback edition', 'Education', self::EDUC, 'Ballantine Books', 2007, 'H-803', 3, 1185470,
            'How a growth mindset shapes learning, effort and achievement.'],
        ['How Children Learn', '9780201484045', 'John Holt', 'Revised edition', 'Education', self::EDUC, 'Addison-Wesley', 1995, 'H-804', 2, 134943,
            'Observations of how children learn naturally through play, talk, reading and experiment.'],
        ['Educational Psychology', '9780134774329', 'Anita Woolfolk', '14th edition', 'Education', self::EDUC, 'Pearson', 2018, 'H-805', 3, 15226175,
            'Learning, development, motivation, classroom management and assessment for future teachers.'],
        ['Understanding by Design', '9781416600350', 'Grant Wiggins, Jay McTighe', 'Expanded 2nd edition', 'Education', self::EDUC, 'ASCD', 2005, 'H-806', 2, 760475,
            'Backward design: planning lessons and units from the understanding students should reach.'],

        // ---- BS Hospitality Management -------------------------------------------------------
        ['Kitchen Confidential', '9780060899226', 'Anthony Bourdain', 'Updated edition', 'Hospitality & Culinary', self::HOSP, 'Harper Perennial', 2007, 'J-901', 3, 39600,
            'An insider\'s account of life and work in restaurant kitchens.'],
        ['Setting the Table', '9780060742768', 'Danny Meyer', 'Paperback edition', 'Hospitality & Culinary', self::HOSP, 'Harper Perennial', 2008, 'J-902', 3, 1067561,
            'The restaurateur behind Union Square Cafe on putting hospitality at the heart of a business.'],
        ['The Professional Chef', '9780470421352', 'The Culinary Institute of America', '9th edition', 'Hospitality & Culinary', self::HOSP, 'Wiley', 2011, 'J-903', 2, 15112149,
            'The Culinary Institute of America\'s complete guide to kitchen techniques, ingredients and recipes.'],
        ['Unreasonable Hospitality', '9780593418574', 'Will Guidara', '1st edition', 'Hospitality & Culinary', self::HOSP, 'Optimism Press', 2022, 'J-904', 2, 14553904,
            'How going above and beyond for guests transformed Eleven Madison Park.'],
        ['Salt, Fat, Acid, Heat', '9781476753836', 'Samin Nosrat', '1st edition', 'Hospitality & Culinary', self::HOSP, 'Simon & Schuster', 2017, 'J-905', 3, 8315567,
            'The four elements of good cooking, explained with illustrations and recipes.'],

        // ---- General reading (all courses) -------------------------------------------------
        ['Atomic Habits', '9780735211292', 'James Clear', '1st edition', 'Personal Growth', null, 'Avery', 2018, 'K-101', 4, 12886417,
            'Small, practical changes that build good habits and break bad ones.'],
        ['Thinking, Fast and Slow', '9780374533557', 'Daniel Kahneman', '1st paperback edition', 'Personal Growth', null, 'Farrar, Straus and Giroux', 2013, 'K-102', 3, 7889800,
            'The two systems that drive the way we think, and the mistakes they lead us to.'],
        ["Man's Search for Meaning", '9780807014295', 'Viktor E. Frankl', 'Beacon Press edition', 'Personal Growth', null, 'Beacon Press', 2006, 'K-103', 3, 8513458,
            'A psychiatrist\'s memoir of the concentration camps and his ideas about finding purpose.'],
        ['The Alchemist', '9780062315007', 'Paulo Coelho', '25th anniversary edition', 'Fiction', null, 'HarperOne', 2014, 'K-104', 4, 15091614,
            'A shepherd boy travels from Spain to Egypt in search of treasure and his personal legend.'],
        ['Noli Me Tángere', '9780143039693', 'José Rizal (trans. Harold Augenbraum)', 'Penguin Classics edition', 'Fiction', null, 'Penguin Classics', 2006, 'K-105', 4, 14844974,
            'Rizal\'s landmark novel of colonial Philippine society, in an English translation.'],
        ['The Little Prince', '9780156012195', 'Antoine de Saint-Exupéry (trans. Richard Howard)', 'Harcourt edition', 'Fiction', null, 'Harcourt', 2000, 'K-106', 3, 115508,
            'A pilot stranded in the desert meets a small prince from another planet.'],
    ];

    /** Editions and covers for the books that were already in the demo catalog (isbn => [edition, Open Library cover id, or a bundled cover file]). */
    private const EXISTING = [
        '9780132350884' => ['1st edition', 15126503],              // Clean Code
        '9780134685991' => ['3rd edition', 12420356],              // Effective Java
        '9780596517748' => ['1st edition', 2536428],               // JavaScript: The Good Parts
        '9780135957059' => ['20th anniversary edition', 'pragmatic-programmer-20th.png'],   // bundled in public/uploads/covers
        '9780743273565' => ['Scribner paperback edition', 13028546],
        '9780061120084' => ['Harper Perennial Modern Classics edition', 15162569],
        '9780141439518' => ['Penguin Classics edition', 12645114],
        '9780547928227' => ['75th anniversary edition', 12003329],
        '9780451524935' => ['Signet Classics edition', 15279182],
        '9781451673319' => ['60th anniversary edition', 12460599],
        '9780553380163' => ['Updated and expanded 10th anniversary edition', 14589690],
        '9780062316097' => ['1st U.S. edition', 8594920],
    ];

    public function run()
    {
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');
        $by  = $db->table('users')->where('role', UserModel::HEAD)->where('archived_at', null)->orderBy('id')->get()->getRow()->id ?? null;

        // Categories
        $categoryIds = array_column($db->table('categories')->select('id, name')->get()->getResultArray(), 'id', 'name');
        foreach (self::CATEGORIES as $name => $description) {
            if (! isset($categoryIds[$name])) {
                $db->table('categories')->insert(['name' => $name, 'description' => $description, 'created_at' => $now, 'updated_at' => $now]);
                $categoryIds[$name] = $db->insertID();
            }
        }

        // New books
        $added = 0;
        foreach (self::BOOKS as [$title, $isbn, $author, $edition, $category, $course, $publisher, $year, $shelf, $copies, $coverId, $description]) {
            $existing = $db->table('books')->where('isbn', $isbn)->get()->getRowArray();
            if ($existing) {
                $this->fillIn($existing, $edition, $coverId);

                continue;
            }

            $db->table('books')->insert([
                'title' => $title, 'isbn' => $isbn, 'author' => $author, 'edition' => $edition,
                'category_id' => $categoryIds[$category] ?? null, 'course' => $course,
                'publisher' => $publisher, 'publication_year' => $year, 'shelf_location' => $shelf,
                'stock_count' => $copies, 'description' => $description, 'cover' => 'ol:' . $coverId,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $id = $db->insertID();
            $db->table('inventory_logs')->insert([
                'book_id' => $id, 'change' => $copies, 'reason' => 'initial', 'stock_after' => $copies,
                'notes' => 'Added to catalog', 'user_id' => $by, 'created_at' => $now,
            ]);
            $db->table('activity_logs')->insert([
                'user_id' => $by, 'category' => 'book', 'action' => 'added',
                'description' => mb_substr('Added "' . $title . '" (' . $edition . ') to the catalog with ' . $copies . ' copies', 0, 255),
                'subject_type' => 'book', 'subject_id' => $id, 'created_at' => $now,
            ]);
            $added++;
        }

        // Editions and covers for the original demo books
        foreach (self::EXISTING as $isbn => [$edition, $cover]) {
            $book = $db->table('books')->where('isbn', $isbn)->get()->getRowArray();
            if ($book) {
                $this->fillIn($book, $edition, $cover);
            }
        }

        if (is_cli()) {
            echo "Added {$added} book(s).\n";
        }
    }

    /** Fill in a missing edition / cover without overwriting what staff entered. */
    private function fillIn(array $book, string $edition, int|string $cover): void
    {
        $set = [];
        if (empty($book['edition'])) {
            $set['edition'] = $edition;
        }
        if (empty($book['cover']) && $cover !== 0) {
            $set['cover'] = is_int($cover) ? 'ol:' . $cover : $cover;   // Open Library id, or a file in public/uploads/covers
        }
        if ($set) {
            $this->db->table('books')->where('id', $book['id'])->update($set);
        }
    }
}
