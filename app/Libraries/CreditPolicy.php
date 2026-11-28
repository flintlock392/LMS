<?php

namespace App\Libraries;

/**
 * Student credits: every student starts with 20 credits (good standing).
 * Credits are deducted for violations (late, damaged, lost…) and restored by the
 * Head Librarian (approved appeal, library service, settlement). 0 = blocked.
 *
 * The fewer credits a student has, the fewer books they may borrow:
 *
 *   16–20  Good standing   normal limit (System Settings → Max books per student)
 *   11–15  Warning         up to 2 books
 *   6–10   Probation       1 book
 *   1–5    Final warning   1 book, loan period halved
 *   0      Blocked         no borrowing until the Head Librarian settles the student's status
 */
class CreditPolicy
{
    /** Full credits; every student starts here. */
    public const MAX = 20;

    /** Best to worst: [lowest credits in the tier, key, label, max books (null = normal limit), loan-period factor, pill tone, explanation] */
    public const TIERS = [
        [16, 'good',      'Good standing', null, 1.0, 'success', 'Can borrow the normal number of books.'],
        [11, 'warning',   'Warning',       2,    1.0, 'warning', 'Can borrow up to 2 books.'],
        [6,  'probation', 'Probation',     1,    1.0, 'warning', 'Can borrow only 1 book.'],
        [1,  'final',     'Final warning', 1,    0.5, 'danger',  'Can borrow only 1 book, for half the usual loan period.'],
        [0,  'blocked',   'Blocked',       0,    0.0, 'dark',    'Cannot borrow any books until a librarian settles their status.'],
    ];

    /**
     * The tier for a credit balance.
     * 'worse' / 'toWorse': the next lower tier and how many more credits lost would reach it (null when blocked).
     *
     * @return array{min:int,max:int,key:string,label:string,maxBooks:?int,loanFactor:float,tone:string,explain:string,worse:?string,toWorse:?int}
     */
    public static function tier(int $credits): array
    {
        $credits = max(0, min(self::MAX, $credits));
        foreach (self::TIERS as $i => $t) {
            if ($credits >= $t[0]) {
                $worse = self::TIERS[$i + 1] ?? null;

                return [
                    'min' => $t[0], 'max' => self::tierMax($i), 'key' => $t[1], 'label' => $t[2], 'maxBooks' => $t[3],
                    'loanFactor' => $t[4], 'tone' => $t[5], 'explain' => $t[6],
                    'worse' => $worse[2] ?? null, 'toWorse' => $worse ? $credits - $t[0] + 1 : null,
                ];
            }
        }

        return self::tier(0);
    }

    /** Highest credits in tier $i (tiers are listed best to worst). */
    public static function tierMax(int $i): int
    {
        return $i === 0 ? self::MAX : self::TIERS[$i - 1][0] - 1;
    }

    /** "16–20", "0" … for tier $i. */
    public static function rangeLabel(int $i): string
    {
        $min = self::TIERS[$i][0];
        $max = self::tierMax($i);

        return $min === $max ? (string) $min : $min . '–' . $max;
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
