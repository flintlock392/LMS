<?php

/**
 * Library helper: ISBN utilities, money, badges, roles and the role-based menu.
 * Autoloaded for every controller via BaseController.
 */

use App\Models\SettingModel;
use App\Models\UserModel;

if (! function_exists('isbn_normalize')) {
    /** Strip spaces/hyphens and upper-case (so "978-0-13-235088-4" => "9780132350884"). */
    function isbn_normalize(string $isbn): string
    {
        return strtoupper(preg_replace('/[\s-]+/', '', $isbn) ?? '');
    }
}

if (! function_exists('isbn_is_valid')) {
    /** Validate ISBN-10 or ISBN-13 including the check digit. */
    function isbn_is_valid(string $isbn): bool
    {
        $isbn = isbn_normalize($isbn);

        if (strlen($isbn) === 10) {
            if (! preg_match('/^\d{9}[\dX]$/', $isbn)) {
                return false;
            }
            $sum = 0;
            for ($i = 0; $i < 10; $i++) {
                $digit = $isbn[$i] === 'X' ? 10 : (int) $isbn[$i];
                $sum  += $digit * (10 - $i);
            }

            return $sum % 11 === 0;
        }

        if (strlen($isbn) === 13) {
            if (! ctype_digit($isbn)) {
                return false;
            }
            $sum = 0;
            for ($i = 0; $i < 12; $i++) {
                $sum += (int) $isbn[$i] * ($i % 2 === 0 ? 1 : 3);
            }

            return (10 - ($sum % 10)) % 10 === (int) $isbn[12];
        }

        return false;
    }
}

if (! function_exists('setting')) {
    function setting(string $key): string
    {
        static $model = null;
        $model ??= new SettingModel();

        return $model->value($key);
    }
}

if (! function_exists('money')) {
    function money(float|string|null $amount): string
    {
        return setting('currency') . number_format((float) $amount, 2);
    }
}

if (! function_exists('current_role')) {
    function current_role(): ?string
    {
        return session()->get('role');
    }
}

if (! function_exists('is_head')) {
    function is_head(): bool
    {
        return current_role() === UserModel::HEAD;
    }
}

if (! function_exists('is_staff')) {
    function is_staff(): bool
    {
        return in_array(current_role(), UserModel::STAFF_ROLES, true);
    }
}

if (! function_exists('role_label')) {
    function role_label(?string $role): string
    {
        return UserModel::roleLabel($role);
    }
}

if (! function_exists('fmt_date')) {
    function fmt_date(?string $value, bool $withTime = false): string
    {
        if (! $value) {
            return '—';
        }

        return date($withTime ? 'M j, Y g:i A' : 'M j, Y', strtotime($value));
    }
}

if (! function_exists('status_badge')) {
    /** Coloured pill for loan, reservation, fine and account statuses. */
    function status_badge(string $status, ?string $dueDate = null): string
    {
        if ($status === 'borrowed' && $dueDate !== null && $dueDate < date('Y-m-d')) {
            $status = 'overdue';
        }

        $map = [
            'borrowed'  => ['info', 'On loan'],
            'overdue'   => ['danger', 'Overdue'],
            'returned'  => ['success', 'Returned'],
            'lost'      => ['dark', 'Lost'],
            'pending'   => ['warning', 'In queue'],
            'ready'     => ['success', 'Ready for pickup'],
            'fulfilled' => ['secondary', 'Fulfilled'],
            'cancelled' => ['secondary', 'Cancelled'],
            'expired'   => ['secondary', 'Expired'],
            'unpaid'    => ['danger', 'Unpaid'],
            'paid'      => ['success', 'Paid'],
            'waived'    => ['secondary', 'Waived'],
            'active'    => ['success', 'Active'],
            'suspended' => ['danger', 'Suspended'],
        ];
        [$tone, $label] = $map[$status] ?? ['secondary', ucfirst($status)];

        return '<span class="pill pill-' . $tone . '">' . esc($label) . '</span>';
    }
}

if (! function_exists('availability_badge')) {
    function availability_badge(int $available, int $total): string
    {
        if ($total <= 0 || $available <= 0) {
            return '<span class="pill pill-danger">Unavailable</span>';
        }
        $tone = $available <= \App\Models\BookModel::LOW_STOCK ? 'warning' : 'success';

        return '<span class="pill pill-' . $tone . '">' . $available . ' of ' . $total . ' available</span>';
    }
}

if (! function_exists('brand_image')) {
    /**
     * URL of a branding image in public/assets/img. Uses your exported
     * PNG/JPG/WEBP if present (e.g. logo.png), otherwise the bundled SVG.
     */
    function brand_image(string $name): string
    {
        foreach (['png', 'webp', 'jpg', 'svg'] as $ext) {
            if (is_file(FCPATH . 'assets/img/' . $name . '.' . $ext)) {
                return base_url('assets/img/' . $name . '.' . $ext);
            }
        }

        return base_url('assets/img/' . $name . '.svg');
    }
}

if (! function_exists('nav_items')) {
    /**
     * Sidebar menu for each user level: [label, bootstrap-icon, path].
     * Labels follow the module names agreed for each role.
     */
    function nav_items(?string $role): array
    {
        return match ($role) {
            UserModel::HEAD => [
                ['Dashboard', 'speedometer2', 'dashboard'],
                ['Book Management', 'book', 'books'],
                ['Borrowing & Returning', 'arrow-left-right', 'circulation'],
                ['Reservations', 'bookmark-check', 'reservations'],
                ['User Management', 'people', 'members'],
                ['Inventory Management', 'box-seam', 'inventory'],
                ['Fine Management', 'cash-coin', 'fines'],
                ['Reports & Analytics', 'bar-chart-line', 'reports'],
                ['Staff Management', 'person-badge', 'staff'],
                ['System Settings', 'gear', 'settings'],
            ],
            UserModel::LIBRARIAN => [
                ['Dashboard', 'speedometer2', 'dashboard'],
                ['Book Collection', 'book', 'books'],
                ['Borrowing & Returning', 'arrow-left-right', 'circulation'],
                ['Reservations', 'bookmark-check', 'reservations'],
                ['User Registration', 'person-plus', 'members'],
                ['Inventory Monitoring', 'box-seam', 'inventory'],
                ['Fine Processing', 'cash-coin', 'fines'],
                ['Reports', 'file-earmark-bar-graph', 'reports'],
            ],
            UserModel::STUDENT => [
                ['Dashboard', 'speedometer2', 'dashboard'],
                ['Browse Books', 'search', 'catalog'],
                ['My Loans', 'journal-bookmark', 'my/loans'],
                ['My Reservations', 'bookmark-check', 'my/reservations'],
                ['My Fines', 'cash-coin', 'my/fines'],
            ],
            default => [],
        };
    }
}

if (! function_exists('module_title')) {
    /** The role-specific name of a module (e.g. "fines" => "Fine Processing" for staff). */
    function module_title(string $path): string
    {
        foreach (nav_items(current_role()) as [$label, , $p]) {
            if ($p === $path) {
                return $label;
            }
        }

        return ucfirst($path);
    }
}
