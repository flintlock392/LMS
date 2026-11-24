<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Users<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Users</h1>
    <a class="btn btn-primary" href="<?= site_url('users/new') ?>">+ Add user</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            <?php
            $colors = ['admin' => 'danger', 'librarian' => 'primary', 'member' => 'secondary'];
            foreach ($users as $u): ?>
                <tr>
                    <td><?= esc($u['name']) ?></td>
                    <td><?= esc($u['email']) ?></td>
                    <td><span class="badge text-bg-<?= $colors[$u['role']] ?? 'secondary' ?>"><?= esc($u['role']) ?></span></td>
                    <td class="text-muted small"><?= esc(substr((string) $u['created_at'], 0, 10)) ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('users/' . $u['id'] . '/edit') ?>">Edit</a>
                        <?php if ((int) $u['id'] !== (int) session()->get('userId')): ?>
                            <form class="d-inline" method="post" action="<?= site_url('users/' . $u['id'] . '/delete') ?>"
                                  onsubmit="return confirm('Delete this user?');">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
