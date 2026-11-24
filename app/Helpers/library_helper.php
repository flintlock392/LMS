<?php

/**
 * Library helper: ISBN utilities and stock badge rendering.
 * Load with: helper('library');
 */

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
            $check = (10 - ($sum % 10)) % 10;

            return $check === (int) $isbn[12];
        }

        return false;
    }
}

if (! function_exists('stock_badge')) {
    /** Bootstrap badge describing the stock level of a book. */
    function stock_badge(int $stock): string
    {
        $low = \App\Models\BookModel::LOW_STOCK;

        if ($stock <= 0) {
            return '<span class="badge text-bg-danger">Out of stock</span>';
        }
        if ($stock <= $low) {
            return '<span class="badge text-bg-warning">Low: ' . $stock . '</span>';
        }

        return '<span class="badge text-bg-success">' . $stock . ' in stock</span>';
    }
}
