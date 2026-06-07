<?php
// Prevent direct access without config
if (!defined('DB_HOST')) {
    require_once dirname(__DIR__) . '/includes/config.php';
}

// Fetch categories for nav
$db = getDB();
$navCategories = $db->query("SELECT id, name, slug FROM categories ORDER BY name LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' . SITE_NAME : SITE_NAME . ' - Watch Documentaries Online' ?></title>
    <meta name="description" content="<?= isset($pageDesc) ? e($pageDesc) : SITE_DESCRIPTION ?>">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark dh-navbar sticky-top">
    <div class="container-fluid px-3 px-lg-4">

        <!-- Logo -->
        <a class="navbar-brand dh-logo" href="<?= SITE_URL ?>">
            <i class="bi bi-camera-reels-fill text-accent me-2"></i>DocumentaryHub
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <!-- Left Nav Links -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-3">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="<?= SITE_URL ?>">Home</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>" href="#" data-bs-toggle="dropdown">
                        Categories
                    </a>
                    <ul class="dropdown-menu dh-dropdown">
                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/categories.php">All Categories</a></li>
                        <li><hr class="dropdown-divider border-secondary"></li>
                        <?php foreach ($navCategories as $cat): ?>
                        <li>
                            <a class="dropdown-item" href="<?= SITE_URL ?>/categories.php?cat=<?= e($cat['slug']) ?>">
                                <?= e($cat['name']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/search.php">Search</a>
                </li>
            </ul>

            <!-- Search Bar -->
            <form class="d-flex me-3 dh-search-form" action="<?= SITE_URL ?>/search.php" method="GET">
                <div class="input-group">
                    <input class="form-control dh-search-input" type="search" name="q"
                           placeholder="Search documentaries..." value="<?= e($_GET['q'] ?? '') ?>">
                    <button class="btn dh-search-btn" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

            <!-- Right Nav -->
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/dashboard.php">
                        <i class="bi bi-grid-fill me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                        <div class="dh-avatar-sm">
                            <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
                        </div>
                        <?= e($_SESSION['username'] ?? '') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dh-dropdown">
                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/dashboard.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/dashboard.php?tab=favorites"><i class="bi bi-bookmark me-2"></i>Watchlist</a></li>
                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/dashboard.php?tab=history"><i class="bi bi-clock-history me-2"></i>History</a></li>
                        <?php if (isAdmin()): ?>
                        <li><hr class="dropdown-divider border-secondary"></li>
                        <li><a class="dropdown-item text-accent" href="<?= SITE_URL ?>/admin/"><i class="bi bi-shield-check me-2"></i>Admin Panel</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider border-secondary"></li>
                        <li><a class="dropdown-item text-danger" href="<?= SITE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/login.php">Login</a>
                </li>
                <li class="nav-item">
                    <a class="btn dh-btn-accent btn-sm ms-2 px-3" href="<?= SITE_URL ?>/register.php">Register</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Flash Messages -->
<?php if (isset($_SESSION['flash'])): ?>
<div class="container-fluid px-3 px-lg-4 mt-2">
    <div class="alert alert-<?= $_SESSION['flash']['type'] == 'error' ? 'danger' : $_SESSION['flash']['type'] ?> alert-dismissible fade show" role="alert">
        <?= e($_SESSION['flash']['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php unset($_SESSION['flash']); endif; ?>
