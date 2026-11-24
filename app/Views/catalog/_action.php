<?php
/**
 * What a student / visitor can do with one book in Browse Books.
 *   - has it on loan        → "Request return" (or "Return requested" + Cancel)
 *   - asked to borrow it    → "Requested" + Cancel
 *   - a copy is on the shelf → "Request to borrow"
 *
 * @var array $book
 * @var array $mine  ['loans' => [book_id => borrowing_id], 'requests' => [book_id => ['borrow' => id, 'return' => id]]]
 */
$bookId    = (int) $book['id'];
$loanId    = $mine['loans'][$bookId] ?? null;
$requested = $mine['requests'][$bookId] ?? [];
$cancel    = static fn (int $requestId, string $what) => '<form method="post" action="' . site_url('my/requests/' . $requestId . '/cancel') . '" class="m-0 d-inline" data-confirm="Cancel your ' . $what . ' request for &quot;' . esc($book['title']) . '&quot;?">'
    . csrf_field() . '<button class="btn btn-sm btn-link p-0 ms-1">Cancel</button></form>';
?>
<?php if (current_role() === 'student'): ?>
    <div class="d-flex flex-wrap align-items-center justify-content-end gap-1">
        <?php if ($loanId !== null && isset($requested['return'])): ?>
            <span class="pill pill-warning"><i class="bi bi-hourglass-split me-1"></i>Return requested</span><?= $cancel($requested['return'], 'return') ?>
        <?php elseif ($loanId !== null): ?>
            <span class="pill pill-info">You have this book</span>
            <form method="post" action="<?= site_url('my/loans/' . $loanId . '/return-request') ?>" class="m-0"
                  data-confirm="Request to return &quot;<?= esc($book['title']) ?>&quot;? Please bring the book to the library desk.">
                <?= csrf_field() ?><button class="btn btn-sm btn-outline-nook"><i class="bi bi-box-arrow-in-down-left me-1"></i>Request return</button>
            </form>
        <?php elseif (isset($requested['borrow'])): ?>
            <span class="pill pill-warning"><i class="bi bi-hourglass-split me-1"></i>Requested</span><?= $cancel($requested['borrow'], 'borrow') ?>
        <?php elseif ((int) $book['available'] > 0): ?>
            <form method="post" action="<?= site_url('catalog/' . $bookId . '/request') ?>" class="m-0"
                  data-confirm="Request to borrow &quot;<?= esc($book['title']) ?>&quot;? The library staff will prepare it for you.">
                <?= csrf_field() ?><button class="btn btn-sm btn-nook"><i class="bi bi-bookmark-plus me-1"></i>Request to borrow</button>
            </form>
        <?php endif; ?>
    </div>
<?php elseif (! session()->get('isLoggedIn') && (int) $book['available'] > 0): ?>
    <a class="btn btn-sm btn-outline-nook" href="<?= site_url('login') ?>">Log in to request</a>
<?php endif; ?>
