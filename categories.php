<?php
require_once 'includes/config.php';
$pageTitle = 'Categories';
$db = getDB();

$catSlug = trim($_GET['cat'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

// All categories with counts
$allCats = $db->query("
    SELECT c.*, COUNT(d.id) as doc_count
    FROM categories c
    LEFT JOIN documentaries d ON c.id = d.category_id AND d.is_active = 1
    GROUP BY c.id
    ORDER BY c.name
")->fetchAll();

$currentCat = null;
$docs       = [];
$totalPages = 1;

if ($catSlug) {
    // Find category
    $stmt = $db->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$catSlug]);
    $currentCat = $stmt->fetch();

    if ($currentCat) {
        // Count docs
        $countStmt = $db->prepare("SELECT COUNT(*) FROM documentaries WHERE category_id = ? AND is_active = 1");
        $countStmt->execute([$currentCat['id']]);
        $total      = (int)$countStmt->fetchColumn();
        $totalPages = max(1, ceil($total / $perPage));

        // Fetch docs
        $stmt = $db->prepare("
            SELECT d.*, c.name as category_name
            FROM documentaries d
            LEFT JOIN categories c ON d.category_id = c.id
            WHERE d.category_id = ? AND d.is_active = 1
            ORDER BY d.views DESC
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute([$currentCat['id']]);
        $docs = $stmt->fetchAll();
    }
}

// User favorites
$userFavorites = [];
if (isLoggedIn()) {
    $fStmt = $db->prepare("SELECT documentary_id FROM favorites WHERE user_id = ?");
    $fStmt->execute([$_SESSION['user_id']]);
    $userFavorites = array_column($fStmt->fetchAll(), 'documentary_id');
}
foreach ($docs as &$doc) {
    $doc['is_favorited'] = in_array($doc['id'], $userFavorites);
}
unset($doc);

include 'includes/header.php';
?>

<div class="container-fluid px-3 px-lg-5 py-4">

    <?php if (!$catSlug || !$currentCat): ?>
    <!-- All Categories Grid -->
    <div class="dh-section-header mb-4">
        <h1 class="dh-section-title">Browse Categories</h1>
    </div>
    <div class="row g-3">
        <?php foreach ($allCats as $cat): ?>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <a href="categories.php?cat=<?= e($cat['slug']) ?>" class="text-decoration-none">
                <div class="dh-category-card">
                    <i class="bi <?= e($cat['icon']) ?>"></i>
                    <h6><?= e($cat['name']) ?></h6>
                    <span><?= $cat['doc_count'] ?> documentary<?= $cat['doc_count'] != 1 ? 'ies' : '' ?></span>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <?php else: ?>
    <!-- Category View -->
    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <a href="categories.php" class="btn dh-btn-ghost btn-sm"><i class="bi bi-arrow-left me-2"></i>All Categories</a>
        <div>
            <h1 class="dh-section-title mb-0">
                <i class="bi <?= e($currentCat['icon']) ?> me-2 text-accent"></i><?= e($currentCat['name']) ?>
            </h1>
            <?php if ($currentCat['description']): ?>
            <p class="text-muted small mt-1 mb-0"><?= e($currentCat['description']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Other category pills -->
    <div class="dh-cat-pills mb-4">
        <a href="categories.php" class="dh-cat-pill">All</a>
        <?php foreach ($allCats as $cat): ?>
        <a href="categories.php?cat=<?= e($cat['slug']) ?>"
           class="dh-cat-pill <?= $cat['slug'] === $catSlug ? 'active' : '' ?>">
            <?= e($cat['name']) ?> <span class="opacity-75">(<?= $cat['doc_count'] ?>)</span>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($docs)): ?>
    <div class="dh-empty">
        <i class="bi bi-camera-video-off"></i>
        <h5>No documentaries in this category yet</h5>
        <a href="categories.php" class="btn dh-btn-accent mt-2">Browse Other Categories</a>
    </div>
    <?php else: ?>
    <div class="dh-cards-grid">
        <?php foreach ($docs as $doc): include 'includes/card.php'; endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="mt-5 d-flex justify-content-center">
        <ul class="pagination">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?cat=<?= e($catSlug) ?>&page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a>
            </li>
            <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
            <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                <a class="page-link" href="?cat=<?= e($catSlug) ?>&page=<?= $p ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?cat=<?= e($catSlug) ?>&page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
