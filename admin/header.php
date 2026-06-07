<?php
if (!defined('DB_HOST')) {
    require_once dirname(__DIR__) . '/includes/config.php';
}
$db = getDB();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($adminTitle) ? e($adminTitle) . ' - Admin' : 'Admin Panel' ?> | <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .admin-layout { display: flex; min-height: calc(100vh - 60px); }
        .admin-main { flex: 1; overflow: auto; padding: 28px; background: var(--bg-primary); }
        @media(max-width:991px){ .admin-main { padding: 16px; } .dh-admin-sidebar { display: none !important; } }
    </style>
</head>
<body>

<!-- Admin Top Bar -->
<nav class="dh-navbar navbar navbar-dark px-3 px-lg-4" style="position:sticky;top:0;z-index:999">
    <a class="navbar-brand dh-logo" href="<?= SITE_URL ?>">
        <i class="bi bi-camera-reels-fill text-accent me-2"></i>DocumentaryHub
    </a>
    <div class="d-flex align-items-center gap-3">
        <span class="badge bg-warning text-dark fw-600 px-3 py-2">
            <i class="bi bi-shield-fill me-1"></i>Admin Panel
        </span>
        <a href="<?= SITE_URL ?>" class="btn dh-btn-ghost btn-sm">
            <i class="bi bi-house me-1"></i> Front End
        </a>
        <a href="<?= SITE_URL ?>/logout.php" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-box-arrow-right me-1"></i> Logout
        </a>
    </div>
</nav>

<div class="admin-layout">
<!-- Sidebar -->
<aside class="dh-admin-sidebar">
    <div class="py-3">
        <div class="px-4 py-2 mb-1">
            <span class="text-muted" style="font-size:0.7rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase">Main Menu</span>
        </div>
        <a href="<?= SITE_URL ?>/admin/" class="dh-admin-nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>
        <a href="<?= SITE_URL ?>/admin/documentaries.php" class="dh-admin-nav-link <?= strpos($_SERVER['PHP_SELF'], 'documentaries') !== false ? 'active' : '' ?>">
            <i class="bi bi-camera-video"></i> Documentaries
        </a>
        <a href="<?= SITE_URL ?>/admin/add-documentary.php" class="dh-admin-nav-link <?= strpos($_SERVER['PHP_SELF'], 'add-documentary') !== false ? 'active' : '' ?>">
            <i class="bi bi-plus-circle"></i> Add Documentary
        </a>
        <a href="<?= SITE_URL ?>/admin/categories.php" class="dh-admin-nav-link <?= strpos($_SERVER['PHP_SELF'], 'categories') !== false ? 'active' : '' ?>">
            <i class="bi bi-tag"></i> Categories
        </a>
        <a href="<?= SITE_URL ?>/admin/users.php" class="dh-admin-nav-link <?= strpos($_SERVER['PHP_SELF'], 'users') !== false ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Users
        </a>
        <hr class="border-secondary mx-3 my-2">
        <div class="px-4 py-2 mb-1">
            <span class="text-muted" style="font-size:0.7rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase">Quick Links</span>
        </div>
        <a href="<?= SITE_URL ?>" target="_blank" class="dh-admin-nav-link">
            <i class="bi bi-box-arrow-up-right"></i> View Site
        </a>
        <a href="<?= SITE_URL ?>/logout.php" class="dh-admin-nav-link" style="color:#e74c3c">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</aside>

<main class="admin-main">
<!-- Flash Messages -->
<?php if (isset($_SESSION['flash'])): ?>
<div class="alert alert-<?= $_SESSION['flash']['type'] == 'error' ? 'danger' : $_SESSION['flash']['type'] ?> alert-dismissible fade show mb-4" role="alert">
    <?= e($_SESSION['flash']['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['flash']); endif; ?>
