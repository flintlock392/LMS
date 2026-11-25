<?php
/**
 * User Management sub-modules: Students and Staff are kept in separate tables.
 * The Staff tab is for the Head Librarian only.
 *
 * @var string $active  students | staff
 */
$db       = db_connect();
$students = $db->table('users')->where('role', 'student')->where('archived_at', null)->countAllResults();
$staff    = $db->table('users')->whereIn('role', ['head_librarian', 'librarian'])->where('archived_at', null)->countAllResults();
$archived = $db->query('SELECT (SELECT COUNT(*) FROM users WHERE archived_at IS NOT NULL) + (SELECT COUNT(*) FROM books WHERE archived_at IS NOT NULL) + (SELECT COUNT(*) FROM categories WHERE archived_at IS NOT NULL) AS n')->getRow()->n;
?>
<?php if (is_head()): ?>
<nav class="module-tabs mb-4" aria-label="User Management sections">
    <a class="<?= $active === 'students' ? 'active' : '' ?>" href="<?= site_url('members') ?>"<?= $active === 'students' ? ' aria-current="page"' : '' ?>>
        <i class="bi bi-mortarboard"></i>Students <span class="tab-n"><?= $students ?></span>
    </a>
    <a class="<?= $active === 'staff' ? 'active' : '' ?>" href="<?= site_url('staff') ?>"<?= $active === 'staff' ? ' aria-current="page"' : '' ?>>
        <i class="bi bi-person-badge"></i>Staff <span class="tab-n"><?= $staff ?></span>
    </a>
    <a class="<?= $active === 'archive' ? 'active' : '' ?>" href="<?= site_url('archive') ?>"<?= $active === 'archive' ? ' aria-current="page"' : '' ?>>
        <i class="bi bi-archive"></i>Archive <span class="tab-n"><?= (int) $archived ?></span>
    </a>
</nav>
<?php endif; ?>
