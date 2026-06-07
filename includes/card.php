<?php
/**
 * Documentary Card Component
 * Usage: include with $doc array containing documentary data
 * Optional: $showCategory = true/false
 */
?>
<div class="dh-card-wrap">
    <a href="<?= SITE_URL ?>/watch.php?id=<?= $doc['id'] ?>" class="text-decoration-none">
        <div class="dh-card">
            <!-- Thumbnail -->
            <div class="dh-card-thumb">
                <img
                    src="<?= e($doc['thumbnail'] ?: getYouTubeThumbnail($doc['youtube_video_id'])) ?>"
                    alt="<?= e($doc['title']) ?>"
                    loading="lazy"
                    onerror="this.src='https://img.youtube.com/vi/<?= e($doc['youtube_video_id']) ?>/hqdefault.jpg'"
                >
                <!-- Overlay -->
                <div class="dh-card-overlay">
                    <div class="dh-play-btn">
                        <i class="bi bi-play-fill"></i>
                    </div>
                </div>
                <!-- Duration Badge -->
                <?php if (!empty($doc['duration'])): ?>
                <span class="dh-duration-badge"><?= e($doc['duration']) ?></span>
                <?php endif; ?>
                <!-- Featured/Trending Badges -->
                <?php if (!empty($doc['is_featured'])): ?>
                <span class="dh-featured-badge"><i class="bi bi-star-fill me-1"></i>Featured</span>
                <?php endif; ?>
            </div>
            <!-- Card Body -->
            <div class="dh-card-body">
                <h6 class="dh-card-title"><?= e($doc['title']) ?></h6>
                <div class="dh-card-meta d-flex align-items-center gap-2">
                    <?php if (!empty($doc['rating'])): ?>
                    <span class="dh-rating"><i class="bi bi-star-fill"></i> <?= number_format($doc['rating'], 1) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($doc['year'])): ?>
                    <span class="text-muted small"><?= e($doc['year']) ?></span>
                    <?php endif; ?>
                    <span class="text-muted small ms-auto"><i class="bi bi-eye me-1"></i><?= formatViews($doc['views']) ?></span>
                </div>
                <?php if (!empty($doc['category_name']) && ($showCategory ?? true)): ?>
                <div class="mt-1">
                    <span class="dh-cat-badge"><?= e($doc['category_name']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </a>
    <!-- Quick Action Buttons -->
    <?php if (isLoggedIn()): ?>
    <div class="dh-card-actions">
        <button class="btn dh-action-btn fav-toggle <?= !empty($doc['is_favorited']) ? 'active' : '' ?>"
                data-id="<?= $doc['id'] ?>" title="<?= !empty($doc['is_favorited']) ? 'Remove from Watchlist' : 'Add to Watchlist' ?>">
            <i class="bi bi-bookmark<?= !empty($doc['is_favorited']) ? '-fill' : '' ?>"></i>
        </button>
    </div>
    <?php endif; ?>
</div>
