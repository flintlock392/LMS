<?php

namespace App\Controllers;

use App\Models\ActivityLogModel;
use App\Models\UserModel;

/**
 * Activity Log (Head Librarian only), a tab of Book & Return.
 * Pick a start date and an end date; every action recorded in the system
 * from the start of the first day to the end of the second day is listed.
 */
class Activity extends BaseController
{
    public function index()
    {
        [$filters, $error] = $this->filters();

        $model = new ActivityLogModel();
        $rows  = $model->between($filters)->paginate(50);

        $days = (int) round((strtotime($filters['to']) - strtotime($filters['from'])) / 86400) + 1;

        return view('activity/index', [
            'rows'    => $rows,
            'pager'   => $model->pager,
            'filters' => $filters,
            'error'   => $error,
            'days'    => $days,
            'counts'  => (new ActivityLogModel())->countsByCategory($filters['from'], $filters['to']),
            'people'  => (new UserModel())->select('id, name, role')->whereIn('role', UserModel::STAFF_ROLES)->orderBy('name')->findAll(),
        ]);
    }

    /** Same range and filters as the page, as a spreadsheet. */
    public function export()
    {
        [$filters] = $this->filters();
        $rows      = (new ActivityLogModel())->between($filters)->findAll(20000);

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Date', 'Time', 'Done by', 'User level', 'Area', 'Action', 'Description', 'IP address']);
        foreach ($rows as $r) {
            $cells = [
                date('Y-m-d', strtotime($r['created_at'])), date('H:i:s', strtotime($r['created_at'])),
                $r['user_name'] ?? 'System', $r['user_role'] ? role_label($r['user_role']) : '',
                ActivityLogModel::CATEGORIES[$r['category']][0] ?? $r['category'], $r['action'], $r['description'], $r['ip_address'],
            ];
            fputcsv($out, array_map(static fn ($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, $cells));
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $this->response->download('activity-' . $filters['from'] . '-to-' . $filters['to'] . '.csv', $csv)->setContentType('text/csv');
    }

    /** [filters, error message|null]. Defaults to the last 7 days. */
    private function filters(): array
    {
        $isDate = static function (string $v): bool {
            $d = \DateTime::createFromFormat('Y-m-d', $v);

            return $d !== false && $d->format('Y-m-d') === $v;
        };

        $from  = (string) $this->request->getGet('from');
        $to    = (string) $this->request->getGet('to');
        $error = null;

        if ($from !== '' && ! $isDate($from) || $to !== '' && ! $isDate($to)) {
            $error = 'A date wasn\'t recognised, so the last 7 days are shown.';
            $from  = $to = '';
        }
        $to   = $to !== '' ? $to : date('Y-m-d');
        $from = $from !== '' ? $from : date('Y-m-d', strtotime($to . ' -6 days'));

        if ($from > $to) {
            $error = 'The end date (' . fmt_date($to) . ') is before the start date (' . fmt_date($from) . '). '
                . 'Showing ' . fmt_date($from) . ' only. Pick an end date on or after the start date.';
            $to = $from;
        }

        $user = (string) $this->request->getGet('user_id');

        return [[
            'from'     => $from,
            'to'       => $to,
            'category' => (string) $this->request->getGet('category'),
            'user_id'  => $user === 'system' ? 'system' : (ctype_digit($user) ? $user : ''),
            'q'        => trim((string) $this->request->getGet('q')),
        ], $error];
    }
}
