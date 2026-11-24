<?php

namespace App\Libraries;

/**
 * Student credits: 0 = good standing, 20 = blocked.
 * Credits are added for violations (late, damaged, lost…) and reduced by the
 * Head Librarian (approved appeal, library service, settlement).
 *
 * The more credits a student has, the fewer books they may borrow:
 *
 *   0–4    Good standing   normal limit (System Settings → Max books per student)
 *   5–9    Warning         up to 2 books
 *   10–14  Probation       1 book
 *   15–19  Final warning   1 book, loan period halved
 *   20     Blocked         no borrowing until the Head Librarian settles the student's status
 */
class CreditPolicy
{
    public const MAX = 20;

    /** [min credits, key, label, max books (null = normal limit), loan-period factor, pill tone, explanation] */
    public const TIERS = [
        [0,  'good',      'Good standing', null, 1.0, 'success', 'Can borrow the normal number of books.'],
        [5,  'warning',   'Warning',       2,    1.0, 'warning', 'Can borrow up to 2 books.'],
        [10, 'probation', 'Probation',     1,    1.0, 'warning', 'Can borrow only 1 book.'],
        [15, 'final',     'Final warning', 1,    0.5, 'danger',  'Can borrow only 1 book, for half the usual loan period.'],
        [20, 'blocked',   'Blocked',       0,    0.0, 'dark',    'Cannot borrow any books until the Head Librarian settles their status.'],
    ];

    /** @return array{min:int,key:string,label:string,maxBooks:?int,loanFactor:float,tone:string,explain:string,next:?int} */
    public static function tier(int $credits): array
    {
        $credits = max(0, min(self::MAX, $credits));
        $found   = self::TIERS[0];
        $next    = null;
        foreach (self::TIERS as $i => $t) {
            if ($credits >= $t[0]) {
                $found = $t;
                $next  = self::TIERS[$i + 1][0] ?? null;
            }
        }

        return [
            'min' => $found[0], 'key' => $found[1], 'label' => $found[2], 'maxBooks' => $found[3],
            'loanFactor' => $found[4], 'tone' => $found[5], 'explain' => $found[6], 'next' => $next,
        ];
    }

    /** How many books a student with these credits may have at once. */
    public static function maxBooks(int $credits, int $normalLimit): int
    {
        $t = self::tier($credits);

        return $t['maxBooks'] === null ? $normalLimit : min($normalLimit, $t['maxBooks']);
    }

    /** Loan period in days for a student with these credits (at least 1 day). */
    public static function loanDays(int $credits, int $normalDays): int
    {
        return max(1, (int) floor($normalDays * self::tier($credits)['loanFactor']));
    }

    public static function isBlocked(int $credits): bool
    {
        return self::tier($credits)['key'] === 'blocked';
    }
}
