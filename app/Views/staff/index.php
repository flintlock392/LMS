<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?>User Management<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= view('partials/users_tabs', ['active' => 'staff']) ?>
<div class="panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <h2 class="panel-title mb-0">Library staff</h2>
        <a class="btn btn-nook btn-sm ms-auto" href="<?= site_url('staff/new') ?>"><i class="bi bi-person-plus me-1"></i>Add staff</a>
    </div>
    <form class="filters" method="get" data-filter-form>
        <input class="form-control form-control-sm" type="search" name="q" value="<?= esc($q) ?>" placeholder="Name, Staff ID, email" aria-label="Search staff" data-suggest="staff">
        <button class="btn btn-sm btn-nook" data-apply><i class="bi bi-search me-1"></i>Search</button>
        <?php if ($q !== ''): ?><a class="btn-clear" href="<?= site_url('staff') ?>"><i class="bi bi-x-circle"></i>Clear filters</a><?php endif; ?>
    </form>
    <div class="table-responsive"><table class="table table-nook">
        <thead><tr><th>Name</th><th>Staff ID</th><th>User level</th><th>Books issued</th><th>Last login</th><th>Status</th><th class="text-end"></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $u): $self = (int) $u['id'] === (int) session()->get('userId'); ?>
            <tr>
                <td class="fw-semibold"><?= esc($u['name']) ?><?= $self ? ' <span class="pill pill-info">You</span>' : '' ?><div class="sub"><?= esc($u['position'] ?? '') ?> · <?= esc($u['email']) ?></div></td>
                <td><?= esc($u['id_number']) ?></td>
                <td><span class="pill <?= $u['role'] === 'head_librarian' ? 'pill-dark' : 'pill-secondary' ?>"><?= esc(role_label($u['role'])) ?></span></td>
                <td><?= (int) $u['issued'] ?></td>
                <td class="sub"><?= fmt_date($u['last_login_at'], true) ?></td>
                <td><?= status_badge($u['status']) ?></td>
                <td class="actions">
                    <a class="btn btn-sm btn-outline-nook" href="<?= site_url('staff/' . $u['id'] . '/edit') ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                    <?php if (! $self): ?>
                        <form class="d-inline" method="post" action="<?= site_url('staff/' . $u['id'] . '/status') ?>" data-confirm="<?= $u['status'] === 'active' ? 'Suspend' : 'Reactivate' ?> <?= esc($u['name']) ?>?">
                            <?= csrf_field() ?><button class="btn btn-sm btn-outline-warning" title="<?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>"><i class="bi bi-<?= $u['status'] === 'active' ? 'pause-circle' : 'play-circle' ?>"></i></button>
                        </form>
                        <form class="d-inline" method="post" action="<?= site_url('staff/' . $u['id'] . '/archive') ?>" data-confirm="Archive <?= esc($u['name']) ?>'s staff account? They won't be able to log in. You can restore it from the Archive page.">
                            <?= csrf_field() ?><button class="btn btn-sm btn-outline-nook" title="Archive" aria-label="Archive"><i class="bi bi-archive"></i></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?= $this->endSection() ?>
