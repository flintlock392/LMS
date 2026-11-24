<?php $role = session()->get('role'); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->renderSection('title') ?> · Library Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-body-tertiary">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="<?= site_url('/') ?>">📚 Library</a>
        <?php if (session()->get('isLoggedIn')): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="<?= site_url('books') ?>">Books</a></li>
                    <?php if (in_array($role, ['admin', 'librarian'], true)): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= site_url('categories') ?>">Categories</a></li>
                    <?php endif; ?>
                    <?php if ($role === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= site_url('users') ?>">Users</a></li>
                    <?php endif; ?>
                </ul>
                <span class="navbar-text me-3">
                    <?= esc(session()->get('name')) ?>
                    <span class="badge text-bg-secondary"><?= esc($role) ?></span>
                </span>
                <a class="btn btn-outline-light btn-sm" href="<?= site_url('logout') ?>">Log out</a>
            </div>
        <?php endif; ?>
    </div>
</nav>

<main class="container pb-5">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger mb-3">
            <ul class="mb-0">
                <?php foreach ((array) session()->getFlashdata('errors') as $message): ?>
                    <li><?= esc($message) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?= $this->renderSection('content') ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
