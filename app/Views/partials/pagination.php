<?php
/**
 * Pager template (set in app/Config/Pager.php as 'nook').
 *
 * @var \CodeIgniter\Pager\PagerRenderer $pager
 */
$pager->setSurroundCount(2);
?>
<?php if ($pager->getPageCount() > 1): ?>
<nav aria-label="Pages" class="mt-3">
    <ul class="pagination pagination-sm justify-content-end mb-0">
        <?php if ($pager->hasPreviousPage()): ?>
            <li class="page-item"><a class="page-link" href="<?= $pager->getPreviousPage() ?>" aria-label="Previous">&lsaquo;</a></li>
        <?php endif; ?>
        <?php foreach ($pager->links() as $link): ?>
            <li class="page-item<?= $link['active'] ? ' active' : '' ?>">
                <a class="page-link" href="<?= $link['uri'] ?>"<?= $link['active'] ? ' aria-current="page"' : '' ?>><?= $link['title'] ?></a>
            </li>
        <?php endforeach; ?>
        <?php if ($pager->hasNextPage()): ?>
            <li class="page-item"><a class="page-link" href="<?= $pager->getNextPage() ?>" aria-label="Next">&rsaquo;</a></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>
