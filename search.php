<?php
require_once 'includes/config.php';
$pageTitle = 'Search';
$db = getDB();

$q    = trim($_GET['q'] ?? '');
$cat  = trim($_GET['cat'] ?? '');
$sort = $_GET['sort'] ?? 'relevance';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

// Build query
$conditions = ['d.is_active = 1'];
$params     = [];

if ($q !== '') {
    $conditions[] = '(d.title LIKE ? OR d.description LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}

if ($cat !== '') {
    $conditions[] = 'c.slug = ?';
    $params[] = $cat;
}

$where = 'WHERE ' . implode(' AND ', $conditions);

// Sort
$orderBy = match ($sort) {
    'newest'   => 'ORDER BY d.created_at DESC',
    'popular'  => 'ORDER BY d.views DESC',
    'trending' => 'ORDER BY d.is_trending DESC, d.views DESC',
    'rating'   => 'ORDER BY d.rating DESC',
    default    => $q ? 'ORDER BY d.views DESC' : 'ORDER BY d.created_at DESC',
};

// Count
$countStmt = $db->prepare("
    SELECT COUNT(*) FROM documentaries d
    LEFT JOIN categories c ON d.category_id = c.id
    $where
");
$countStmt->execute($params);
$totalResults = (int)$countStmt->fetchColumn();
$totalPages   = max(1, ceil($totalResults / $perPage));

// Fetch results
$stmt = $db->prepare("
    SELECT d.*, c.name as category_name, c.slug as category_slug
    FROM documentaries d
    LEFT JOIN categories c ON d.category_id = c.id
    $where
    $orderBy
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$results = $stmt->fetchAll();

// Categories for filter
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// User favorites
$userFavorites = [];
if (isLoggedIn()) {
    $fStmt = $db->prepare("SELECT documentary_id FROM favorites WHERE user_id = ?");
    $fStmt->execute([$_SESSION['user_id']]);
    $userFavorites = array_column($fStmt->fetchAll(), 'documentary_id');
}
foreach ($results as &$doc) {
    $doc['is_favorited'] = in_array($doc['id'], $userFavorites);
}
unset($doc);

include 'includes/header.php';
?>

<!-- Search Hero -->
<div class="dh-search-hero">
    <div class="container-fluid px-3 px-lg-5">
        <h1 class="dh-section-title mb-3">
            <?= $q ? 'Search Results' : 'Browse Documentaries' ?>
        </h1>
        <form class="dh-search-big d-flex" action="search.php" method="GET">
            <?php if ($cat): ?>
            <input type="hidden" name="cat" value="<?= e($cat) ?>">
            <?php endif; ?>
            <input type="text" class="form-control" name="q" value="<?= e($q) ?>"
                   placeholder="Search by title, topic, keyword...">
            <button class="btn dh-btn-accent px-4" type="submit">
                <i class="bi bi-search me-1"></i> Search
            </button>
        </form>
    </div>
</div>

<div class="container-fluid px-3 px-lg-5 py-4">

    <!-- Category Filters -->
    <div class="dh-cat-pills">
        <a href="search.php?<?= $q ? 'q=' . urlencode($q) . '&' : '' ?>sort=<?= e($sort) ?>"
           class="dh-cat-pill <?= $cat === '' ? 'active' : '' ?>">All</a>
        <?php foreach ($categories as $c): ?>
        <a href="search.php?<?= $q ? 'q=' . urlencode($q) . '&' : '' ?>cat=<?= e($c['slug']) ?>&sort=<?= e($sort) ?>"
           class="dh-cat-pill <?= $cat === $c['slug'] ? 'active' : '' ?>">
            <?= e($c['name']) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Sort + Result Count -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div class="dh-result-count">
            <?php if ($q): ?>
            Showing <strong><?= $totalResults ?></strong> result<?= $totalResults != 1 ? 's' : '' ?> for "<strong><?= e($q) ?></strong>"
            <?php else: ?>
            <strong><?= $totalResults ?></strong> documentary<?= $totalResults != 1 ? 'ies' : '' ?> found
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="text-muted small">Sort by:</span>
            <?php
            $sortOptions = ['relevance' => 'Relevance', 'newest' => 'Newest', 'popular' => 'Most Popular', 'rating' => 'Rating', 'trending' => 'Trending'];
            foreach ($sortOptions as $val => $label):
                $active = $sort === $val;
                $url = 'search.php?' . ($q ? 'q=' . urlencode($q) . '&' : '') . ($cat ? 'cat=' . urlencode($cat) . '&' : '') . 'sort=' . $val;
            ?>
            <a href="<?= $url ?>" class="dh-cat-pill py-1 px-3 <?= $active ? 'active' : '' ?>" style="font-size:0.78rem">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Results Grid -->
    <?php if (empty($results)): ?>
    <div class="dh-no-results">
        <i class="bi bi-camera-video-off"></i>
        <h4>No documentaries found</h4>
        <p class="text-muted">Try a different search term or category.</p>
        <a href="search.php" class="btn dh-btn-accent mt-2">Browse All</a>
    </div>
    <?php else: ?>
    <div class="dh-cards-grid">
        <?php foreach ($results as $doc): include 'includes/card.php'; endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav class="mt-5 d-flex justify-content-center">
        <ul class="pagination">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
            <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
            <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
