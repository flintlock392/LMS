<?php
/**
 * Reserve button / status for one book.
 * @var array $book  @var array $mine
 */
$state = $mine[(int) $book['id']] ?? null;
?>
<?php if (current_role() === 'student'): ?>
    <?php if ($state === 'loan'): ?>
        <span class="pill pill-info">You have this book</span>
    <?php elseif ($state === 'ready'): ?>
        <span class="pill pill-success">Ready for you at the desk</span>
    <?php elseif ($state === 'pending'): ?>
        <span class="pill pill-warning">You're in the queue</span>
    <?php else: ?>
        <form method="post" action="<?= site_url('catalog/' . $book['id'] . '/reserve') ?>" class="m-0">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-nook"><i class="bi bi-bookmark-plus me-1"></i><?= (int) $book['available'] > 0 ? 'Reserve for pickup' : 'Join waiting list' ?></button>
        </form>
    <?php endif; ?>
<?php elseif (! session()->get('isLoggedIn')): ?>
    <a class="btn btn-sm btn-outline-nook" href="<?= site_url('login') ?>">Log in to reserve</a>
<?php endif; ?>
