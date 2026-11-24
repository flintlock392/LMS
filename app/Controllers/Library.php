<?php

namespace App\Controllers;

use App\Libraries\Circulation as CirculationService;
use App\Models\ActivityLogModel;
use App\Models\BookModel;
use App\Models\BorrowingModel;
use App\Models\CategoryModel;
use App\Models\InventoryLogModel;
use App\Models\UserModel;

/**
 * "Library": one page for the catalog, borrowing/returning and stock.
 *
 * Tabs:  All · Collections · Borrowed · Overdue · Returned · Lost · Books
 *   All / Borrowed / Overdue / Returned / Lost  -> loan records
 *   Collections                                 -> the catalog (titles)
 *   Books                                       -> stock levels (formerly "Inventory")
 *
 * Under the tabs: a search box and a Start date → End date range.
 */
class Library extends BaseController
{
    public const TABS = ['all', 'collections', 'borrowed', 'overdue', 'returned', 'lost', 'books'];

    /** What the date range filters on, per tab (shown to the user). */
    public const DATE_MEANING = [
        'all'         => 'borrowed',
        'borrowed'    => 'borrowed',
        'overdue'     => 'borrowed',
        'returned'    => 'borrowed',
        'lost'        => 'borrowed',
        'collections' => 'added to the catalog',
        'books'       => 'stock changes made',
    ];

    public function index()
    {
        $service = new CirculationService();
        $service->housekeep();

        $tab = (string) $this->request->getGet('tab');
        $tab = in_array($tab, self::TABS, true) ? $tab : 'all';

        [$from, $to, $rangeError] = $this->dateRange();
        $q = trim((string) $this->request->getGet('q'));

        $data = [
            'tab'        => $tab,
            'q'          => $q,
            'from'       => $from,
            'to'         => $to,
            'rangeError' => $rangeError,
            'counts'     => $this->counts(),
            'rows'       => [],
            'pager'      => null,
            'log'        => [],
            'categories' => (new CategoryModel())->orderBy('name')->findAll(),
            'category'   => (string) $this->request->getGet('category_id'),
            // For the "Issue book" dialog.
            'students'   => (new UserModel())->where('role', UserModel::STUDENT)->activeBorrowers(),
            'bookList'   => (new BookModel())->forSelect(),
        ];

        if ($tab === 'collections' || $tab === 'books') {
            $model = new BookModel();
            $data['rows'] = $model->filtered([
                'q'           => $q,
                'category_id' => $data['category'],
                'added_from'  => $tab === 'collections' ? $from : '',
                'added_to'    => $tab === 'collections' ? $to : '',
                'sort'        => $tab === 'books' ? 'available' : (string) $this->request->getGet('sort'),
                'dir'         => $tab === 'books' ? 'asc' : (string) $this->request->getGet('dir'),
            ])->paginate(15);
            $data['pager'] = $model->pager;

            if ($tab === 'books') {
                $log = (new InventoryLogModel())
                    ->select('inventory_logs.*, books.title, users.name AS user_name')
                    ->join('books', 'books.id = inventory_logs.book_id')
                    ->join('users', 'users.id = inventory_logs.user_id', 'left')
                    ->orderBy('inventory_logs.id', 'DESC');
                if ($from !== '') {
                    $log->where('inventory_logs.created_at >=', $from . ' 00:00:00');
                }
                if ($to !== '') {
                    $log->where('inventory_logs.created_at <=', $to . ' 23:59:59');
                }
                if ($q !== '') {
                    $log->like('books.title', $q);
                }
                $data['log']      = $log->findAll(30);
                $data['stats']    = (new BookModel())->stats();
                $data['allBooks'] = is_head() ? (new BookModel())->forSelect() : [];
            }
        } else {
            $status = ['all' => 'all', 'borrowed' => 'active', 'overdue' => 'overdue', 'returned' => 'returned', 'lost' => 'lost'][$tab];
            $model  = new BorrowingModel();
            $rows   = $model->filtered(['status' => $status, 'q' => $q, 'from' => $from, 'to' => $to])->paginate(15);
            foreach ($rows as &$r) {
                [$r['days_late'], $r['est_fine']] = $r['status'] === 'borrowed' ? $service->overdueFine($r['due_date'], date('Y-m-d')) : [0, 0];
            }
            unset($r);
            $data['rows']  = $rows;
            $data['pager'] = $model->pager;
        }

        $data['summary'] = $this->summary($tab, $q, $from, $to, $data['pager'] ? $data['pager']->getTotal() : 0);

        // Head Librarian: with both dates set, also list every action in the system in that range.
        $data['activity']      = null;
        $data['activityTotal'] = 0;
        if (is_head() && $from !== '' && $to !== '') {
            $data['activity']      = (new ActivityLogModel())->between(['from' => $from, 'to' => $to])->findAll(15);
            $data['activityTotal'] = (new ActivityLogModel())->between(['from' => $from, 'to' => $to])->countAllResults();
        }

        return view('library/index', $data);
    }

    /**
     * Old addresses (/books, /circulation, /inventory) open the matching tab,
     * keeping any search or date filters.
     */
    public function legacy(string $page)
    {
        $query = $this->request->getGet();
        $tab   = match ($page) {
            'books'     => 'collections',
            'inventory' => 'books',
            default     => ['active' => 'borrowed', 'overdue' => 'overdue', 'returned' => 'returned', 'lost' => 'lost', 'all' => 'all'][$query['status'] ?? 'active'] ?? 'borrowed',
        };
        unset($query['status']);

        return redirect()->to(site_url('library') . '?' . http_build_query(['tab' => $tab] + $query));
    }

    private function counts(): array
    {
        $loans = (new BorrowingModel())->counts();
        $stats = (new BookModel())->stats();

        return [
            'all'         => (new BorrowingModel())->countAllResults(),
            'collections' => $stats['titles'],
            'borrowed'    => $loans['active'],
            'overdue'     => $loans['overdue'],
            'returned'    => (new BorrowingModel())->where('status', 'returned')->countAllResults(),
            'lost'        => (new BorrowingModel())->where('status', 'lost')->countAllResults(),
            'books'       => $stats['copies'],
        ];
    }

    /** [from, to, error]: valid Y-m-d dates, never with the end before the start. */
    private function dateRange(): array
    {
        $valid = static function (string $v): bool {
            $d = \DateTime::createFromFormat('Y-m-d', $v);

            return $d !== false && $d->format('Y-m-d') === $v;
        };

        $from  = (string) $this->request->getGet('from');
        $to    = (string) $this->request->getGet('to');
        $error = null;

        if (($from !== '' && ! $valid($from)) || ($to !== '' && ! $valid($to))) {
            return ['', '', 'That date wasn\'t recognised, so the date filter was cleared.'];
        }
        if ($from !== '' && $to !== '' && $from > $to) {
            return ['', '', 'The end date (' . fmt_date($to) . ') is before the start date (' . fmt_date($from) . '). Pick an end date on or after the start date. Showing all dates for now.'];
        }

        return [$from, $to, $error];
    }

    /** One plain sentence describing exactly what the list shows. */
    private function summary(string $tab, string $q, string $from, string $to, int $total): string
    {
        $what = [
            'all'         => 'all borrowing records',
            'collections' => 'titles in the collection',
            'borrowed'    => 'books currently borrowed',
            'overdue'     => 'overdue books',
            'returned'    => 'returned books',
            'lost'        => 'books reported lost',
            'books'       => 'stock levels for every title',
        ][$tab];

        $verb = self::DATE_MEANING[$tab];
        $when = match (true) {
            $from !== '' && $to !== '' => $from === $to
                ? ' ' . $verb . ' on ' . fmt_date($from)
                : ' ' . $verb . ' from ' . fmt_date($from) . ' to ' . fmt_date($to) . ' (both days included)',
            $from !== '' => ' ' . $verb . ' on or after ' . fmt_date($from),
            $to !== ''   => ' ' . $verb . ' on or before ' . fmt_date($to),
            default      => '',
        };
        if ($tab === 'books' && $when !== '') {
            $when = '; the stock history below lists' . $when;
        }

        return 'Showing ' . $what . $when . ($q !== '' ? ', matching "' . $q . '"' : '') . ' · ' . $total . ' result' . ($total === 1 ? '' : 's');
    }
}
