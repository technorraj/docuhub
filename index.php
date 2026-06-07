<?php
require_once 'includes/config.php';
$pageTitle = 'Home';
$db = getDB();

// Featured documentaries (for hero carousel)
$featured = $db->query("
    SELECT d.*, c.name as category_name
    FROM documentaries d
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE d.is_featured = 1 AND d.is_active = 1
    ORDER BY d.views DESC LIMIT 5
")->fetchAll();

// Trending
$trending = $db->query("
    SELECT d.*, c.name as category_name
    FROM documentaries d
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE d.is_trending = 1 AND d.is_active = 1
    ORDER BY d.views DESC LIMIT 12
")->fetchAll();

// New uploads
$newUploads = $db->query("
    SELECT d.*, c.name as category_name
    FROM documentaries d
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE d.is_active = 1
    ORDER BY d.created_at DESC LIMIT 12
")->fetchAll();

// Popular (most views)
$popular = $db->query("
    SELECT d.*, c.name as category_name
    FROM documentaries d
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE d.is_active = 1
    ORDER BY d.views DESC LIMIT 12
")->fetchAll();

// Categories for quick browse
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Continue Watching (logged-in users)
$continueWatching = [];
if (isLoggedIn()) {
    $stmt = $db->prepare("
        SELECT d.*, c.name as category_name, wh.watch_progress, wh.last_watched
        FROM watch_history wh
        JOIN documentaries d ON wh.documentary_id = d.id
        LEFT JOIN categories c ON d.category_id = c.id
        WHERE wh.user_id = ? AND d.is_active = 1
        ORDER BY wh.last_watched DESC LIMIT 8
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $continueWatching = $stmt->fetchAll();
}

// Get user favorites for this page
$userFavorites = [];
if (isLoggedIn()) {
    $stmt = $db->prepare("SELECT documentary_id FROM favorites WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userFavorites = array_column($stmt->fetchAll(), 'documentary_id');
}

// Mark favorites on docs
function markFavorites(&$docs, $favIds) {
    foreach ($docs as &$doc) {
        $doc['is_favorited'] = in_array($doc['id'], $favIds);
    }
}
markFavorites($featured, $userFavorites);
markFavorites($trending, $userFavorites);
markFavorites($newUploads, $userFavorites);
markFavorites($popular, $userFavorites);
markFavorites($continueWatching, $userFavorites);


$categoryDocs = [];

foreach ($categories as $cat) {
    $stmt = $db->prepare("
        SELECT d.*, c.name as category_name
        FROM documentaries d
        LEFT JOIN categories c ON d.category_id = c.id
        WHERE d.category_id = ? AND d.is_active = 1
        ORDER BY d.views DESC
        LIMIT 12
    ");

    $stmt->execute([$cat['id']]);
    $categoryDocs[$cat['id']] = $stmt->fetchAll();

    markFavorites($categoryDocs[$cat['id']], $userFavorites);
}

include 'includes/header.php';
?>

<!-- ============================================================
     HERO CAROUSEL
     ============================================================ -->
<?php if (!empty($featured)): ?>
<div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
        <?php foreach ($featured as $i => $doc): ?>
        <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
            <div class="dh-hero">
                <!-- BG Image -->
                <div class="dh-hero-bg" style="background-image: url('<?= e($doc['thumbnail'] ?: getYouTubeThumbnail($doc['youtube_video_id'])) ?>')"></div>
                <div class="container-fluid px-3 px-lg-5 w-100">
                    <div class="dh-hero-content">
                        <span class="dh-hero-badge">
                            <i class="bi bi-star-fill me-1"></i>Featured
                        </span>
                        <h1 class="dh-hero-title"><?= e($doc['title']) ?></h1>
                        <p class="dh-hero-desc"><?= e($doc['description']) ?></p>
                        <div class="dh-hero-meta">
                            <?php if ($doc['rating']): ?>
                            <span class="rating"><i class="bi bi-star-fill me-1"></i><?= e($doc['rating']) ?></span>
                            <?php endif; ?>
                            <?php if ($doc['year']): ?>
                            <span><?= e($doc['year']) ?></span>
                            <?php endif; ?>
                            <?php if ($doc['duration']): ?>
                            <span><i class="bi bi-clock me-1"></i><?= e($doc['duration']) ?></span>
                            <?php endif; ?>
                            <?php if ($doc['category_name']): ?>
                            <span><i class="bi bi-tag me-1"></i><?= e($doc['category_name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="<?= SITE_URL ?>/watch.php?id=<?= $doc['id'] ?>" class="btn dh-btn-accent px-4">
                                <i class="bi bi-play-fill me-2"></i>Watch Now
                            </a>
                            <a href="<?= SITE_URL ?>/watch.php?id=<?= $doc['id'] ?>" class="btn dh-btn-ghost px-4">
                                <i class="bi bi-info-circle me-2"></i>More Info
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Carousel Controls -->
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>

    <!-- Carousel Indicators -->
    <div class="dh-hero-indicators">
        <?php foreach ($featured as $i => $doc): ?>
        <button class="dh-hero-dot <?= $i === 0 ? 'active' : '' ?>" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $i ?>"></button>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================
     MAIN CONTENT
     ============================================================ -->
<div class="container-fluid px-3 px-lg-5 mt-4">

    <!-- Continue Watching -->
    <?php if (!empty($continueWatching)): ?>
    <section class="dh-section">
        <div class="dh-section-header">
            <h2 class="dh-section-title"><i class="bi bi-play-circle me-2 text-accent"></i>Continue Watching</h2>
            <a href="<?= SITE_URL ?>/dashboard.php?tab=history" class="dh-see-all">View All <i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="dh-scroll-row">
            <?php foreach ($continueWatching as $doc): ?>
            <div class="dh-card-wrap">
                <a href="<?= SITE_URL ?>/watch.php?id=<?= $doc['id'] ?>" class="text-decoration-none">
                    <div class="dh-card">
                        <div class="dh-card-thumb">
                            <img src="<?= e($doc['thumbnail'] ?: getYouTubeThumbnail($doc['youtube_video_id'])) ?>"
                                 alt="<?= e($doc['title']) ?>" loading="lazy"
                                 onerror="this.src='https://img.youtube.com/vi/<?= e($doc['youtube_video_id']) ?>/hqdefault.jpg'">
                            <div class="dh-card-overlay"><div class="dh-play-btn"><i class="bi bi-play-fill"></i></div></div>
                            <!-- Progress bar -->
                            <?php if ($doc['watch_progress'] > 0): ?>
                            <div class="dh-progress-bar-wrap">
                                <div class="dh-progress-bar-fill" style="width: <?= min(100, ($doc['watch_progress'] / 3600) * 100) ?>%"></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="dh-card-body">
                            <h6 class="dh-card-title"><?= e($doc['title']) ?></h6>
                            <div class="dh-card-meta"><span class="text-muted small"><?= timeAgo($doc['last_watched']) ?></span></div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Trending -->
    <?php if (!empty($trending)): ?>
    <section class="dh-section">
        <div class="dh-section-header">
            <h2 class="dh-section-title"><i class="bi bi-fire me-2 text-accent"></i>Trending Now</h2>
            <a href="<?= SITE_URL ?>/search.php?sort=trending" class="dh-see-all">See All <i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="dh-scroll-row">
            <?php foreach ($trending as $doc): include 'includes/card.php'; endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Browse Categories -->
    <section class="dh-section">
        <div class="dh-section-header">
            <h2 class="dh-section-title">Browse by Category</h2>
            <a href="<?= SITE_URL ?>/categories.php" class="dh-see-all">All Categories <i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="row g-3">
            <?php foreach (array_slice($categories, 0, 8) as $cat): ?>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <a href="<?= SITE_URL ?>/categories.php?cat=<?= e($cat['slug']) ?>" class="text-decoration-none">
                    <div class="dh-category-card">
                        <i class="bi <?= e($cat['icon']) ?>"></i>
                        <h6><?= e($cat['name']) ?></h6>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- New Uploads -->
    <?php if (!empty($newUploads)): ?>
    <section class="dh-section">
        <div class="dh-section-header">
            <h2 class="dh-section-title"><i class="bi bi-plus-circle me-2 text-accent"></i>New Uploads</h2>
            <a href="<?= SITE_URL ?>/search.php?sort=newest" class="dh-see-all">See All <i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="dh-cards-grid">
            <?php foreach (array_slice($newUploads, 0, 8) as $doc): include 'includes/card.php'; endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Popular -->
    <?php if (!empty($popular)): ?>
    <section class="dh-section">
        <div class="dh-section-header">
            <h2 class="dh-section-title"><i class="bi bi-graph-up-arrow me-2 text-accent"></i>Most Popular</h2>
            <a href="<?= SITE_URL ?>/search.php?sort=popular" class="dh-see-all">See All <i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="dh-cards-grid">
            <?php foreach (array_slice($popular, 0, 8) as $doc): include 'includes/card.php'; endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php foreach ($categories as $cat): ?>

    <?php if (!empty($categoryDocs[$cat['id']])): ?>

    <section class="dh-section">
        <div class="dh-section-header">
            <h2 class="dh-section-title">
                <i class="bi <?= e($cat['icon']) ?> me-2 text-accent"></i>
                <?= e($cat['name']) ?>
            </h2>

            <a href="<?= SITE_URL ?>/categories.php?cat=<?= e($cat['slug']) ?>"
               class="dh-see-all">
                See All <i class="bi bi-chevron-right"></i>
            </a>
        </div>

        <div class="dh-scroll-row">
            <?php foreach ($categoryDocs[$cat['id']] as $doc): ?>
                <?php include 'includes/card.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <?php endif; ?>

<?php endforeach; ?>

</div><!-- /container -->

<?php include 'includes/footer.php'; ?>
