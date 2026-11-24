<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?><?= esc(module_title('members')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= view('partials/users_tabs', ['active' => 'students']) ?>
<div class="panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <h2 class="panel-title mb-0">Students</h2>
        <a class="btn btn-nook btn-sm ms-auto" href="<?= site_url('members/new') ?>"><i class="bi bi-person-plus me-1"></i>Register student</a>
    </div>
    <form class="filters" method="get">
        <input class="form-control form-control-sm" type="search" name="q" value="<?= esc($q) ?>" placeholder="Name, student no., email, course" aria-label="Search students" data-suggest="students">
        <select class="form-select form-select-sm" name="status" aria-label="Status" style="max-width:150px">
            <option value="">Any status</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
        </select>
        <button class="btn btn-sm btn-nook"><i class="bi bi-search"></i></button>
    </form>
    <?php if (! $rows): ?>
        <div class="empty"><i class="bi bi-people"></i>No students found.</div>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-nook">
            <thead><tr><th>Student</th><th>Student no.</th><th>Course</th><th>On loan</th><th>Unpaid</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $u): ?>
                <tr>
                    <td><a class="fw-semibold text-reset" href="<?= site_url('members/' . $u['id']) ?>"><?= esc($u['name']) ?></a><div class="sub"><?= esc($u['email']) ?></div></td>
                    <td><?= esc($u['id_number']) ?></td>
                    <td><?= esc($u['course'] ?? '—') ?><div class="sub"><?= esc($u['year_level'] ?? '') ?></div></td>
                    <td><?= (int) $u['loans'] ?></td>
                    <td><?= (float) $u['unpaid'] > 0 ? '<span class="text-danger fw-semibold">' . money($u['unpaid']) . '</span>' : '—' ?></td>
                    <td><?= status_badge($u['status']) ?></td>
                    <td class="actions">
                        <a class="btn btn-sm btn-outline-nook" href="<?= site_url('members/' . $u['id']) ?>" title="View"><i class="bi bi-eye"></i></a>
                        <?php if (is_head()): ?>
                            <a class="btn btn-sm btn-outline-nook" href="<?= site_url('members/' . $u['id'] . '/edit') ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?= $pager->links('default', 'nook') ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
