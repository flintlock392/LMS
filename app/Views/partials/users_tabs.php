<?php
/**
 * User Management sub-modules: Students and Staff are kept in separate tables.
 * The Staff tab is for the Head Librarian only.
 *
 * @var string $active  students | staff
 */
$db       = db_connect();
$students = $db->table('users')->where('role', 'student')->countAllResults();
$staff    = $db->table('users')->whereIn('role', ['head_librarian', 'librarian'])->countAllResults();
?>
<?php if (is_head()): ?>
<nav class="module-tabs mb-4" aria-label="User Management sections">
    <a class="<?= $active === 'students' ? 'active' : '' ?>" href="<?= site_url('members') ?>"<?= $active === 'students' ? ' aria-current="page"' : '' ?>>
        <i class="bi bi-mortarboard"></i>Students <span class="tab-n"><?= $students ?></span>
    </a>
    <a class="<?= $active === 'staff' ? 'active' : '' ?>" href="<?= site_url('staff') ?>"<?= $active === 'staff' ? ' aria-current="page"' : '' ?>>
        <i class="bi bi-person-badge"></i>Staff <span class="tab-n"><?= $staff ?></span>
    </a>
</nav>
<?php endif; ?>
