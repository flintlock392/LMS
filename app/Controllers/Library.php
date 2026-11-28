<?php

namespace App\Controllers;

use App\Libraries\Circulation as CirculationService;
use App\Models\ActivityLogModel;
use App\Models\BookModel;
use App\Models\BookRequestModel;
use App\Models\BorrowingModel;
use App\Models\CategoryModel;
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
    /** Rows per page on every Library tab; the rest continue on the next page. */
    public const PER_PAGE = 10;

    public const TABS = ['all', 'requested', 'collections', 'borrowed', 'overdue', 'returned', 'lost'];

    /** What the date range filters on, per tab (shown to the user). */
    public const DATE_MEANING = [
        'all'         => 'borrowed',
        'requested'   => 'requested',
        'borrowed'    => 'borrowed',
        'overdue'     => 'borrowed',
        'returned'    => 'borrowed',
        'lost'        => 'borrowed',
        'collections' => 'added to the catalog',
    ];

    public function index()
    {
        $service = new CirculationService();

        $tab = (string) $this->request->getGet('tab');
        $tab = $tab === 'books' ? 'collections' : $tab;   // the old "Books" tab now lives in Collections
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
            'course'     => (string) $this->request->getGet('course'),
            'shelf'      => trim((string) $this->request->getGet('shelf')),   // "section:F" or one shelf like "F-604"
            'shelves'    => shelf_options(),
            'courses'    => UserModel::COURSES,  // the four programs (plus "Other" in the view)
            // For the "Issue book" dialog.
            'students'   => (new UserModel())->where('role', UserModel::STUDENT)->activeBorrowers(),
            'bookList'   => (new BookModel())->forSelect(),
            // For the "Update Stock" dialog (Head Librarian), available on every tab.
            'allBooks'   => (new BookModel())->forSelect(),   // Update Stock title list (Head Librarian and Librarian Staff)
        ];

        if ($tab === 'requested') {
            // Students' waiting "Request to borrow" / "Request return" (oldest first).
            $model = new BookRequestModel();
            $rows  = $model->pendingFiltered([
                'q' => $q, 'from' => $from, 'to' => $to, 'course' => $data['course'], 'category_id' => $data['category'], 'shelf' => $data['shelf'],
            ])->paginate(self::PER_PAGE);
            foreach ($rows as &$r) {
                [$r['days_late'], $r['est_credits']] = $r['type'] === 'return' && $r['due_date']
                    ? $service->overdueCredits($r['due_date'], date('Y-m-d')) : [0, 0];
            }
            unset($r);
            $data['rows']  = $rows;
            $data['pager'] = $model->pager;
        } elseif ($tab === 'collections') {
            // Unavailable titles first, then the ones with the fewest copies left (A–Z within each).
            $model = new BookModel();
            $data['rows'] = $model->filtered([
                'q'           => $q,
                'category_id' => $data['category'],
                'course'      => $data['course'],
                'shelf'       => $data['shelf'],
                'added_from'  => $from,
                'added_to'    => $to,
                'sort'        => 'available',
                'dir'         => 'asc',
            ])->paginate(self::PER_PAGE);
            $data['pager'] = $model->pager;
            $data['stats'] = (new BookModel())->stats();
        } else {
            $status = ['all' => 'all', 'borrowed' => 'active', 'overdue' => 'overdue', 'returned' => 'returned', 'lost' => 'lost'][$tab];
            $model  = new BorrowingModel();
            $rows   = $model->filtered(['status' => $status, 'q' => $q, 'from' => $from, 'to' => $to, 'course' => $data['course'], 'category_id' => $data['category'], 'shelf' => $data['shelf']])->paginate(self::PER_PAGE);
            foreach ($rows as &$r) {
                [$r['days_late'], $r['est_credits']] = $r['status'] === 'borrowed' ? $service->overdueCredits($r['due_date'], date('Y-m-d')) : [0, 0];
            }
            unset($r);
            $data['rows']  = $rows;
            $data['pager'] = $model->pager;
        }

        $data['summary'] = $this->summary($tab, $q, $from, $to, $data['pager'] ? $data['pager']->getTotal() : 0);
        // Mention the chosen shelf, e.g. "… on shelf F-604 · 1 result" / "… in section F · 5 results".
        if ($data['shelf'] !== '') {
            $where = str_starts_with($data['shelf'], 'section:') ? 'in shelf section ' . substr($data['shelf'], 8) : 'on shelf ' . $data['shelf'];
            $data['summary'] = preg_replace('/ · (?=[^·]*$)/', ' ' . esc($where) . ' · ', $data['summary'], 1);
        }
        // Mention the chosen course, e.g. "… for Bachelor of Science in Education · 2 results".
        if (in_array($data['course'], [...UserModel::COURSES, '__general'], true)) {
            $label = match ($data['course']) {
                '__general'             => 'general reading (including other courses)',
                default                 => $data['course'],
            };
            $data['summary'] = preg_replace('/ · /', ' for ' . $label . ' · ', str_replace('for every title', 'for titles', $data['summary']), 1);
        }
        // Mention the chosen category, e.g. "… in Programming · 3 results".
        if ($data['category'] !== '') {
            $names    = array_column($data['categories'], 'name', 'id');
            $catLabel = $data['category'] === 'none' ? 'uncategorized books' : ($names[(int) $data['category']] ?? null);
            if ($catLabel !== null) {
                $data['summary'] = preg_replace('/ · /', ' in ' . $catLabel . ' · ', str_replace('for every title', 'for titles', $data['summary']), 1);
            }
        }

        // Head Librarian: with both dates set, also list every action in the system in that range.
        $data['activity']      = null;
        $data['activityTotal'] = 0;
        if (is_head() && $from !== '' && $to !== '') {
            $act                   = new ActivityLogModel();
            $data['activity']      = $act->between(['from' => $from, 'to' => $to])->paginate(self::PER_PAGE, 'activity');
            $data['activityPager'] = $act->pager;
            $data['activityTotal'] = $act->pager->getTotal('activity');
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
            'inventory' => 'collections',
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
            'requested'   => (new BookRequestModel())->pendingCount(),
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
            'requested'   => 'requests waiting for approval',
            'collections' => 'titles in the collection, unavailable and fewest copies first',
            'borrowed'    => 'books currently borrowed',
            'overdue'     => 'overdue books',
            'returned'    => 'returned books',
            'lost'        => 'books reported lost',
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

        return 'Showing ' . $what . $when . ($q !== '' ? ', matching "' . $q . '"' : '') . ' · ' . $total . ' result' . ($total === 1 ? '' : 's');
    }
}
