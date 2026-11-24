<?php

namespace App\Controllers;

use App\Models\BookModel;
use App\Models\BorrowingModel;
use App\Models\UserModel;

/**
 * Librarian Staff: "Reports" (period summary, overdue list, CSV exports).
 * Head Librarian: "Reports & Analytics" (all of the above plus trends and rankings).
 */
class Reports extends BaseController
{
    public function index()
    {
        [$from, $to] = $this->range();
        $db          = db_connect();
        $start       = $from . ' 00:00:00';
        $end         = $to . ' 23:59:59';

        $one = static fn (string $sql, array $binds = []) => (float) ($db->query($sql, $binds)->getRowArray()['v'] ?? 0);

        $summary = [
            'issued'        => (int) $one('SELECT COUNT(*) v FROM borrowings WHERE borrowed_at BETWEEN ? AND ?', [$start, $end]),
            'returned'      => (int) $one("SELECT COUNT(*) v FROM borrowings WHERE status = 'returned' AND returned_at BETWEEN ? AND ?", [$start, $end]),
            'late_returns'  => (int) $one("SELECT COUNT(*) v FROM borrowings WHERE status = 'returned' AND returned_at BETWEEN ? AND ? AND DATE(returned_at) > due_date", [$start, $end]),
            'lost'          => (int) $one("SELECT COUNT(*) v FROM borrowings WHERE status = 'lost' AND returned_at BETWEEN ? AND ?", [$start, $end]),
            'new_students'  => (int) $one("SELECT COUNT(*) v FROM users WHERE role = 'student' AND created_at BETWEEN ? AND ?", [$start, $end]),
            'credits_added'   => (int) $one('SELECT COALESCE(SUM(`change`),0) v FROM credit_logs WHERE `change` > 0 AND created_at BETWEEN ? AND ?', [$start, $end]),
            'credits_reduced' => (int) -$one('SELECT COALESCE(SUM(`change`),0) v FROM credit_logs WHERE `change` < 0 AND created_at BETWEEN ? AND ?', [$start, $end]),
            'borrowers'     => (int) $one('SELECT COUNT(DISTINCT user_id) v FROM borrowings WHERE borrowed_at BETWEEN ? AND ?', [$start, $end]),
        ];

        $topBooks = $db->query(
            'SELECT books.id, books.title, books.author, COUNT(*) n FROM borrowings
             JOIN books ON books.id = borrowings.book_id
             WHERE borrowed_at BETWEEN ? AND ? GROUP BY books.id ORDER BY n DESC, books.title LIMIT 10',
            [$start, $end]
        )->getResultArray();

        $data = [
            'from'     => $from,
            'to'       => $to,
            'summary'  => $summary,
            'topBooks' => $topBooks,
            'overdue'  => (new BorrowingModel())->filtered(['status' => 'overdue'])->findAll(50),
        ];

        if (is_head()) {
            $data += $this->analytics($start, $end);
        }

        return view('reports/index', $data);
    }

    /** Head Librarian extras: trends and rankings. */
    private function analytics(string $start, string $end): array
    {
        $db = db_connect();

        // Last 6 calendar months, including empty months.
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $key          = date('Y-m', strtotime("first day of -{$i} months"));
            $months[$key] = ['label' => date('M', strtotime($key . '-01')), 'loans' => 0, 'credits' => 0];
        }
        $since = array_key_first($months) . '-01 00:00:00';
        foreach ($db->query("SELECT DATE_FORMAT(borrowed_at, '%Y-%m') m, COUNT(*) n FROM borrowings WHERE borrowed_at >= ? GROUP BY m", [$since])->getResultArray() as $r) {
            if (isset($months[$r['m']])) {
                $months[$r['m']]['loans'] = (int) $r['n'];
            }
        }
        foreach ($db->query("SELECT DATE_FORMAT(created_at, '%Y-%m') m, SUM(`change`) n FROM credit_logs WHERE `change` > 0 AND created_at >= ? GROUP BY m", [$since])->getResultArray() as $r) {
            if (isset($months[$r['m']])) {
                $months[$r['m']]['credits'] = (int) $r['n'];
            }
        }

        $stats = (new BookModel())->stats();

        return [
            'months'     => $months,
            'categories' => $db->query(
                "SELECT COALESCE(c.name, 'Uncategorized') name, COUNT(*) n FROM borrowings b
                 JOIN books ON books.id = b.book_id LEFT JOIN categories c ON c.id = books.category_id
                 WHERE b.borrowed_at BETWEEN ? AND ? GROUP BY name ORDER BY n DESC",
                [$start, $end]
            )->getResultArray(),
            'topReaders' => $db->query(
                "SELECT u.id, u.name, u.id_number, COUNT(*) n FROM borrowings b JOIN users u ON u.id = b.user_id
                 WHERE b.borrowed_at BETWEEN ? AND ? GROUP BY u.id ORDER BY n DESC LIMIT 5",
                [$start, $end]
            )->getResultArray(),
            'staffWork'  => $db->query(
                "SELECT u.name, u.role,
                    (SELECT COUNT(*) FROM borrowings b WHERE b.issued_by = u.id AND b.borrowed_at BETWEEN ? AND ?) issued,
                    (SELECT COUNT(*) FROM borrowings b WHERE b.received_by = u.id AND b.returned_at BETWEEN ? AND ?) received
                 FROM users u WHERE u.role IN ('head_librarian', 'librarian') ORDER BY issued DESC",
                [$start, $end, $start, $end]
            )->getResultArray(),
            'utilization' => $stats['copies'] > 0 ? round($stats['on_loan'] / $stats['copies'] * 100) : 0,
            'neverBorrowed' => (int) $db->query('SELECT COUNT(*) v FROM books WHERE NOT EXISTS (SELECT 1 FROM borrowings b WHERE b.book_id = books.id)')->getRowArray()['v'],
            'students'   => (new UserModel())->where('role', UserModel::STUDENT)->where('status', 'active')->countAllResults(),
        ];
    }

    /** CSV downloads: loans, overdue, credits, inventory, students (head). */
    public function export(string $type)
    {
        [$from, $to] = $this->range();
        $db          = db_connect();
        $range       = [$from . ' 00:00:00', $to . ' 23:59:59'];

        switch ($type) {
            case 'loans':
                $header = ['Borrowed', 'Due', 'Returned', 'Status', 'Condition', 'Title', 'ISBN', 'Student no.', 'Student', 'Issued by'];
                $rows   = $db->query(
                    "SELECT b.borrowed_at, b.due_date, b.returned_at, b.status, b.condition, books.title, books.isbn, u.id_number, u.name, s.name staff
                     FROM borrowings b JOIN books ON books.id = b.book_id JOIN users u ON u.id = b.user_id LEFT JOIN users s ON s.id = b.issued_by
                     WHERE b.borrowed_at BETWEEN ? AND ? ORDER BY b.borrowed_at", $range
                )->getResultArray();
                break;
            case 'overdue':
                $header = ['Due', 'Days late', 'Title', 'Student no.', 'Student', 'Email', 'Phone'];
                $rows   = $db->query(
                    "SELECT b.due_date, DATEDIFF(CURDATE(), b.due_date) days, books.title, u.id_number, u.name, u.email, u.phone
                     FROM borrowings b JOIN books ON books.id = b.book_id JOIN users u ON u.id = b.user_id
                     WHERE b.status = 'borrowed' AND b.due_date < CURDATE() ORDER BY b.due_date"
                )->getResultArray();
                break;
            case 'credits':
                $header = ['Date', 'Student no.', 'Student', 'Change', 'Balance after', 'Reason', 'Notes', 'By'];
                $rows   = $db->query(
                    'SELECT c.created_at, u.id_number, u.name, c.`change`, c.balance_after, c.reason, c.notes, s.name AS staff
                     FROM credit_logs c JOIN users u ON u.id = c.user_id LEFT JOIN users s ON s.id = c.created_by
                     WHERE c.created_at BETWEEN ? AND ? ORDER BY c.created_at', $range
                )->getResultArray();
                break;
            case 'inventory':
                $header = ['Title', 'ISBN', 'Author', 'Category', 'Shelf', 'Owned', 'On loan', 'Available'];
                $rows   = array_map(static fn ($b) => [
                    $b['title'], $b['isbn'], $b['author'], $b['category_name'], $b['shelf_location'],
                    $b['stock_count'], $b['on_loan'], $b['available'],
                ], (new BookModel())->withAvailability()->orderBy('books.title')->findAll());
                break;
            case 'students':
                if (! is_head()) {
                    return redirect()->to('/reports')->with('error', 'Only the Head Librarian can export student records.');
                }
                $header = ['Student no.', 'Name', 'Email', 'Phone', 'Course', 'Year', 'Status', 'Registered'];
                $rows   = $db->query("SELECT id_number, name, email, phone, course, year_level, status, created_at FROM users WHERE role = 'student' ORDER BY name")->getResultArray();
                break;
            default:
                return redirect()->to('/reports')->with('error', 'Unknown report.');
        }

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows ₱ and accents correctly
        fputcsv($out, $header);
        foreach ($rows as $row) {
            // Neutralise spreadsheet formulas in user-entered text.
            fputcsv($out, array_map(static fn ($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, array_values($row)));
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $this->response->download($type . '-report-' . date('Y-m-d') . '.csv', $csv)->setContentType('text/csv');
    }

    /** Validated date range from the query string; defaults to the last 30 days. */
    private function range(): array
    {
        $from = (string) $this->request->getGet('from');
        $to   = (string) $this->request->getGet('to');
        $ok   = static fn ($d) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;

        $from = $ok($from) ? $from : date('Y-m-d', strtotime('-29 days'));
        $to   = $ok($to) ? $to : date('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }
}
