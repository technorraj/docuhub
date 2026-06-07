<?php
require_once 'includes/config.php';
$db = getDB();

$id  = (int)($_GET['id'] ?? 0);
$doc = null;

if ($id > 0) {
    $stmt = $db->prepare("
        SELECT d.*, c.name as category_name, c.slug as category_slug
        FROM documentaries d
        LEFT JOIN categories c ON d.category_id = c.id
        WHERE d.id = ? AND d.is_active = 1
    ");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();
}

if (!$doc) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Documentary not found.'];
    redirect(SITE_URL);
}

// Related documentaries
$related = $db->prepare("
    SELECT d.*, c.name as category_name
    FROM documentaries d
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE d.category_id = ? AND d.id != ? AND d.is_active = 1
    ORDER BY d.views DESC LIMIT 8
");
$related->execute([$doc['category_id'], $id]);
$related = $related->fetchAll();

// If not enough related, fill with popular
if (count($related) < 4) {
    $moreStmt = $db->prepare("
        SELECT d.*, c.name as category_name
        FROM documentaries d
        LEFT JOIN categories c ON d.category_id = c.id
        WHERE d.id != ? AND d.is_active = 1
        ORDER BY d.views DESC LIMIT 6
    ");
    $moreStmt->execute([$id]);
    $related = array_merge($related, $moreStmt->fetchAll());
}

// Is favorited?
$isFavorited = false;
if (isLoggedIn()) {
    $fStmt = $db->prepare("SELECT id FROM favorites WHERE user_id = ? AND documentary_id = ?");
    $fStmt->execute([$_SESSION['user_id'], $id]);
    $isFavorited = (bool)$fStmt->fetch();
}

$pageTitle = $doc['title'];
$pageDesc  = substr($doc['description'] ?? '', 0, 160);

include 'includes/header.php';
?>

<div class="container-fluid px-3 px-lg-5 py-4">
    <div class="row g-4">

        <!-- Main Content -->
        <div class="col-lg-8">

            <!-- Video Player -->
            <div class="dh-video-wrap mb-4">
                <iframe
                    id="docPlayer"
                    data-doc-id="<?= $doc['id'] ?>"
                    src="https://www.youtube.com/embed/<?= e($doc['youtube_video_id']) ?>?rel=0&modestbranding=1&autoplay=0"
                    title="<?= e($doc['title']) ?>"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen>
                </iframe>
            </div>

            <!-- Documentary Info -->
            <div class="dh-doc-info">

                <!-- Category breadcrumb -->
                <?php if ($doc['category_name']): ?>
                <div class="mb-2">
                    <a href="<?= SITE_URL ?>/categories.php?cat=<?= e($doc['category_slug']) ?>" class="dh-cat-badge">
                        <?= e($doc['category_name']) ?>
                    </a>
                </div>
                <?php endif; ?>

                <h1 class="dh-doc-title-big"><?= e($doc['title']) ?></h1>

                <!-- Stats Row -->
                <div class="dh-doc-stats">
                    <?php if ($doc['rating']): ?>
                    <div class="dh-doc-stat">
                        <i class="bi bi-star-fill text-warning"></i>
                        <strong><?= number_format($doc['rating'], 1) ?></strong>
                        <span>/10</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($doc['year']): ?>
                    <div class="dh-doc-stat">
                        <i class="bi bi-calendar3"></i>
                        <strong><?= e($doc['year']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if ($doc['duration']): ?>
                    <div class="dh-doc-stat">
                        <i class="bi bi-clock"></i>
                        <strong><?= e($doc['duration']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="dh-doc-stat">
                        <i class="bi bi-eye"></i>
                        <strong><?= formatViews($doc['views']) ?></strong>
                        <span>views</span>
                    </div>
                    <?php if ($doc['source']): ?>
                    <div class="dh-doc-stat">
                        <i class="bi bi-youtube text-danger"></i>
                        <strong><?= e($doc['source']) ?></strong>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 flex-wrap mb-4">
                    <?php if (isLoggedIn()): ?>
                    <button id="watchLaterBtn" class="btn dh-btn-outline <?= $isFavorited ? 'active' : '' ?>" data-id="<?= $doc['id'] ?>">
                        <i class="bi <?= $isFavorited ? 'bi-bookmark-check-fill' : 'bi-bookmark-plus' ?> me-2"></i>
                        <?= $isFavorited ? 'In Watchlist' : 'Watch Later' ?>
                    </button>
                    <?php else: ?>
                    <a href="<?= SITE_URL ?>/login.php" class="btn dh-btn-outline">
                        <i class="bi bi-bookmark-plus me-2"></i>Watch Later
                    </a>
                    <?php endif; ?>
                    <a href="https://www.youtube.com/watch?v=<?= e($doc['youtube_video_id']) ?>" target="_blank" class="btn dh-btn-ghost">
                        <i class="bi bi-youtube me-2 text-danger"></i>Watch on YouTube
                    </a>
                    <button class="btn dh-btn-ghost" onclick="shareDoc()">
                        <i class="bi bi-share me-2"></i>Share
                    </button>
                </div>

                <!-- Description -->
                <?php if ($doc['description']): ?>
                <div class="dh-form-card mb-4">
                    <h5 class="text-white mb-3"><i class="bi bi-info-circle me-2 text-accent"></i>About This Documentary</h5>
                    <p class="text-muted mb-0" style="line-height: 1.7"><?= nl2br(e($doc['description'])) ?></p>
                </div>
                <?php endif; ?>

                <!-- Tags -->
                <div class="mb-2">
                    <?php if ($doc['category_name']): ?>
                    <a href="<?= SITE_URL ?>/categories.php?cat=<?= e($doc['category_slug']) ?>" class="dh-tag"><?= e($doc['category_name']) ?></a>
                    <?php endif; ?>
                    <?php if ($doc['source']): ?>
                    <span class="dh-tag"><?= e($doc['source']) ?></span>
                    <?php endif; ?>
                    <?php if ($doc['year']): ?>
                    <span class="dh-tag"><?= e($doc['year']) ?></span>
                    <?php endif; ?>
                </div>

            </div>
        </div><!-- /col-lg-8 -->

        <!-- Sidebar: Related Documentaries -->
        <div class="col-lg-4">
            <h5 class="dh-section-title mb-3">Related Documentaries</h5>

            <?php if (empty($related)): ?>
            <div class="dh-empty">
                <i class="bi bi-collection-play"></i>
                <p>No related documentaries found.</p>
            </div>
            <?php else: ?>
            <?php foreach (array_slice($related, 0, 8) as $rel): ?>
            <a href="<?= SITE_URL ?>/watch.php?id=<?= $rel['id'] ?>" class="text-decoration-none">
                <div class="dh-related-item">
                    <div class="dh-related-thumb">
                        <img src="<?= e($rel['thumbnail'] ?: getYouTubeThumbnail($rel['youtube_video_id'])) ?>"
                             alt="<?= e($rel['title']) ?>" loading="lazy"
                             onerror="this.src='https://img.youtube.com/vi/<?= e($rel['youtube_video_id']) ?>/hqdefault.jpg'">
                    </div>
                    <div class="dh-related-info">
                        <h6><?= e($rel['title']) ?></h6>
                        <span><?= e($rel['category_name'] ?? '') ?></span><br>
                        <span><i class="bi bi-eye me-1"></i><?= formatViews($rel['views']) ?> views</span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
function shareDoc() {
    if (navigator.share) {
        navigator.share({
            title: '<?= addslashes(e($doc['title'])) ?>',
            url: window.location.href
        });
    } else {
        navigator.clipboard.writeText(window.location.href)
            .then(() => showToast('Link copied to clipboard!', 'success'));
    }
}
</script>

<?php include 'includes/footer.php'; ?>
