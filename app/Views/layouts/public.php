<?php
$path = trim(uri_string(), '/');
$active = static fn (array $paths) => in_array($path, $paths, true) ? ' active' : '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->renderSection('title') ?> · <?= esc(setting('library_name')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Abhaya+Libre:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="public-body">
<header class="public-nav">
    <div class="container d-flex align-items-center flex-wrap gap-3">
        <a class="brand" href="<?= site_url('/') ?>" aria-label="<?= esc(setting('library_name')) ?> home">
            <img src="<?= brand_image('logo') ?>" alt="<?= esc(setting('library_name')) ?>">
        </a>
        <nav class="ms-auto d-flex align-items-center flex-wrap" aria-label="Main">
            <a class="nav-link-pub<?= $active(['', 'login']) ?>" href="<?= site_url('/') ?>">Home</a>
            <a class="nav-link-pub<?= str_starts_with($path, 'catalog') ? ' active' : '' ?>" href="<?= site_url('catalog') ?>">Books</a>
            <a class="nav-link-pub<?= $active(['about']) ?>" href="<?= site_url('about') ?>">About Us</a>
            <span class="divider d-none d-sm-block"></span>
            <?php if (session()->get('isLoggedIn')): ?>
                <a class="btn-pub-login" href="<?= site_url('dashboard') ?>">Dashboard</a>
            <?php else: ?>
                <a class="btn-pub-login" href="<?= site_url('login') ?>">Login</a>
                <a class="btn-pub-signup" href="<?= site_url('signup') ?>">Sign up</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="public-main">
    <?= $this->renderSection('content') ?>
</main>

<footer class="public-footer">
    <div class="container d-flex flex-wrap justify-content-between gap-2">
        <span>&copy; <?= date('Y') ?> <?= esc(setting('library_name')) ?> Library</span>
        <span>Open Monday–Saturday, 8:00 AM – 6:00 PM</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
