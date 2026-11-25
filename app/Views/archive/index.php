<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?>User Management<?= $this->endSection() ?>

<?php
/**
 * Archived books, categories and accounts. Nothing here is deleted: each item
 * keeps its history and can be restored.
 */
$sections = [
    'student'  => ['Students', 'mortarboard', $students],
    'staff'    => ['Staff', 'person-badge', $staff],
    'book'     => ['Books', 'book', $books],
    'category' => ['Categories', 'tags', $categories],
];
$restore = static fn (string $type, array $row, string $name) => '<form class="d-inline" method="post" action="' . site_url('archive/' . $type . '/' . $row['id'] . '/restore') . '" data-confirm="Restore ' . esc($name, 'attr') . '?">'
    . csrf_field() . '<button class="btn btn-sm btn-outline-nook"><i class="bi bi-arrow-counterclockwise me-1"></i>Restore</button></form>';
?>
<?= $this->section('content') ?>
<?= view('partials/users_tabs', ['active' => 'archive']) ?>

<p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>
    Archived items are hidden from the library, searches and log-in, but their history is kept. Restore an item to bring it back.</p>

<?php foreach ($sections as $type => [$title, $icon, $rows]): ?>
    <div class="panel mb-4">
        <h2 class="panel-title"><i class="bi bi-<?= $icon ?>"></i> <?= $title ?> <span class="pill pill-secondary ms-1"><?= count($rows) ?></span></h2>
        <?php if (! $rows): ?>
            <p class="text-muted small mb-0">No archived <?= strtolower($title) ?>.</p>
        <?php else: ?>
            <div class="table-responsive"><table class="table table-nook mb-0">
                <thead><tr>
                    <?php if ($type === 'book'): ?><th>Title</th><th>ISBN</th><th>Copies</th>
                    <?php elseif ($type === 'category'): ?><th>Category</th><th>Description</th>
                    <?php else: ?><th>Name</th><th><?= $type === 'student' ? 'Student no.' : 'Staff ID' ?></th><th><?= $type === 'student' ? 'Course' : 'User level' ?></th><?php endif; ?>
                    <th>Archived</th><th class="text-end"></th>
                </tr></thead>
                <tbody>
                <?php foreach ($rows as $r): $name = $r['title'] ?? $r['name']; ?>
                    <tr>
                        <?php if ($type === 'book'): ?>
                            <td class="fw-semibold"><?= esc($r['title']) ?><div class="sub"><?= esc($r['author']) ?></div></td><td><?= esc($r['isbn']) ?></td><td><?= (int) $r['stock_count'] ?></td>
                        <?php elseif ($type === 'category'): ?>
                            <td class="fw-semibold"><?= esc($r['name']) ?></td><td class="sub"><?= esc($r['description'] ?? '') ?></td>
                        <?php else: ?>
                            <td class="fw-semibold"><?= esc($r['name']) ?><div class="sub"><?= esc($r['email']) ?></div></td>
                            <td><?= esc($r['id_number']) ?></td>
                            <td><?= esc($type === 'student' ? ($r['course'] ?? '') : role_label($r['role'])) ?></td>
                        <?php endif; ?>
                        <td class="sub"><?= fmt_date($r['archived_at'], true) ?></td>
                        <td class="actions"><?= $restore($type, $r, $name) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
<?= $this->endSection() ?>
