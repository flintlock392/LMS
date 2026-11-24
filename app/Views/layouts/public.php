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
    <link href="<?= asset('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="public-body<?= in_array($path, ['', 'login'], true) ? ' is-home' : '' ?>">
<header class="public-nav">
    <div class="container d-flex align-items-center flex-wrap gap-3">
        <a class="brand" href="<?= site_url('/') ?>" aria-label="<?= esc(setting('library_name')) ?> home">
            <img src="<?= brand_image('logo') ?>" alt="<?= esc(setting('library_name')) ?>">
        </a>
        <!-- Home | Books | About Us, right beside the logo -->
        <nav class="pub-links" aria-label="Main">
            <a class="nav-link-pub<?= $active(['', 'login']) ?>" href="<?= site_url('/') ?>"<?= $active(['', 'login']) ? ' aria-current="page"' : '' ?>>Home</a>
            <a class="nav-link-pub<?= str_starts_with($path, 'catalog') ? ' active' : '' ?>" href="<?= site_url('catalog') ?>"<?= str_starts_with($path, 'catalog') ? ' aria-current="page"' : '' ?>>Books</a>
            <a class="nav-link-pub<?= $active(['about']) ?>" href="<?= site_url('about') ?>"<?= $active(['about']) ? ' aria-current="page"' : '' ?>>About Us</a>
        </nav>
        <?php if (session()->get('isLoggedIn')): ?>
            <a class="btn-pub-login ms-auto" href="<?= site_url('dashboard') ?>">Dashboard</a>
        <?php endif; ?>
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
<script src="<?= asset('assets/js/suggest.js') ?>" defer></script>
</body>
</html>
