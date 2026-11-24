<?php
/**
 * Bootstrap pagination that keeps the current filters in the query string.
 * Expects $pager (CodeIgniter\Pager\Pager).
 */
$total   = $pager->getPageCount();
$current = $pager->getCurrentPage();
if ($total > 1):
    $query = $_GET;
    $link  = static function (int $page) use ($query): string {
        $query['page'] = $page;

        return site_url('books') . '?' . http_build_query($query);
    };
    $start = max(1, $current - 2);
    $end   = min($total, $current + 2);
    ?>
    <nav aria-label="Pagination">
        <ul class="pagination justify-content-center mb-0">
            <li class="page-item <?= $current <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= esc($link(max(1, $current - 1))) ?>">Previous</a>
            </li>
            <?php if ($start > 1): ?>
                <li class="page-item"><a class="page-link" href="<?= esc($link(1)) ?>">1</a></li>
                <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
            <?php endif; ?>
            <?php for ($p = $start; $p <= $end; $p++): ?>
                <li class="page-item <?= $p === $current ? 'active' : '' ?>">
                    <a class="page-link" href="<?= esc($link($p)) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($end < $total): ?>
                <?php if ($end < $total - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                <li class="page-item"><a class="page-link" href="<?= esc($link($total)) ?>"><?= $total ?></a></li>
            <?php endif; ?>
            <li class="page-item <?= $current >= $total ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= esc($link(min($total, $current + 1))) ?>">Next</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>
