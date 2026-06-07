<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please log in to access your dashboard.'];
    redirect(SITE_URL . '/login.php');
}

$db     = getDB();
$userId = (int)$_SESSION['user_id'];
$tab    = $_GET['tab'] ?? 'overview';

// Get user info
$user = $db->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$userId]);
$user = $user->fetch();

// Stats
$favCount = $db->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
$favCount->execute([$userId]);
$favCount = (int)$favCount->fetchColumn();

$histCount = $db->prepare("SELECT COUNT(*) FROM watch_history WHERE user_id = ?");
$histCount->execute([$userId]);
$histCount = (int)$histCount->fetchColumn();

// Favorites
$favorites = $db->prepare("
    SELECT d.*, c.name as category_name, f.created_at as fav_at
    FROM favorites f
    JOIN documentaries d ON f.documentary_id = d.id
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE f.user_id = ? AND d.is_active = 1
    ORDER BY f.created_at DESC
");
$favorites->execute([$userId]);
$favorites = $favorites->fetchAll();

// Watch history
$history = $db->prepare("
    SELECT d.*, c.name as category_name, wh.watch_progress, wh.last_watched
    FROM watch_history wh
    JOIN documentaries d ON wh.documentary_id = d.id
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE wh.user_id = ? AND d.is_active = 1
    ORDER BY wh.last_watched DESC
");
$history->execute([$userId]);
$history = $history->fetchAll();

// Recent favorites for overview
$recentFavs = array_slice($favorites, 0, 6);
$recentHistory = array_slice($history, 0, 6);

// Mark is_favorited
$favIds = array_column($favorites, 'id');
foreach ($favorites as &$doc) { $doc['is_favorited'] = true; }
foreach ($history as &$doc) { $doc['is_favorited'] = in_array($doc['id'], $favIds); }
foreach ($recentFavs as &$doc) { $doc['is_favorited'] = true; }
foreach ($recentHistory as &$doc) { $doc['is_favorited'] = in_array($doc['id'], $favIds); }
unset($doc);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = trim($_POST['full_name'] ?? '');
    if (!empty($fullName)) {
        $db->prepare("UPDATE users SET full_name = ? WHERE id = ?")->execute([$fullName, $userId]);
        $_SESSION['full_name'] = $fullName;
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Profile updated!'];
        redirect(SITE_URL . '/dashboard.php?tab=profile');
    }
}

$pageTitle = 'Dashboard';
include 'includes/header.php';
?>

<!-- Dashboard Header -->
<div class="dh-dashboard-header">
    <div class="container-fluid px-3 px-lg-5">
        <div class="d-flex align-items-center gap-4">
            <div class="dh-avatar-lg"><?= strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1)) ?></div>
            <div>
                <h2 class="mb-1"><?= e($user['full_name'] ?: $user['username']) ?></h2>
                <p class="text-muted mb-0 small">@<?= e($user['username']) ?> &bull; Member since <?= date('M Y', strtotime($user['created_at'])) ?></p>
            </div>
        </div>
        <div class="row g-3 mt-3" style="max-width:500px">
            <div class="col-6 col-sm-4">
                <div class="dh-stat-box">
                    <div class="stat-num"><?= $histCount ?></div>
                    <div class="stat-label">Watched</div>
                </div>
            </div>
            <div class="col-6 col-sm-4">
                <div class="dh-stat-box">
                    <div class="stat-num"><?= $favCount ?></div>
                    <div class="stat-label">Watchlist</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-3 px-lg-5 py-4">

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item"><a class="nav-link <?= $tab === 'overview' ? 'active' : '' ?>" href="?tab=overview"><i class="bi bi-grid me-2"></i>Overview</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab === 'history' ? 'active' : '' ?>" href="?tab=history"><i class="bi bi-clock-history me-2"></i>Watch History</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab === 'favorites' ? 'active' : '' ?>" href="?tab=favorites"><i class="bi bi-bookmark me-2"></i>Watchlist</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab === 'profile' ? 'active' : '' ?>" href="?tab=profile"><i class="bi bi-person me-2"></i>Profile</a></li>
    </ul>

    <!-- ---- OVERVIEW TAB ---- -->
    <?php if ($tab === 'overview'): ?>
    <?php if (!empty($recentHistory)): ?>
    <section class="dh-section">
        <div class="dh-section-header">
            <h4 class="dh-section-title">Continue Watching</h4>
            <a href="?tab=history" class="dh-see-all">See All <i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="dh-scroll-row">
            <?php foreach ($recentHistory as $doc): ?>
            <div class="dh-card-wrap">
                <a href="<?= SITE_URL ?>/watch.php?id=<?= $doc['id'] ?>" class="text-decoration-none">
                    <div class="dh-card">
                        <div class="dh-card-thumb">
                            <img src="<?= e($doc['thumbnail'] ?: getYouTubeThumbnail($doc['youtube_video_id'])) ?>"
                                 alt="<?= e($doc['title']) ?>" loading="lazy">
                            <div class="dh-card-overlay"><div class="dh-play-btn"><i class="bi bi-play-fill"></i></div></div>
                            <div class="dh-progress-bar-wrap">
                                <div class="dh-progress-bar-fill" style="width:<?= min(100, ($doc['watch_progress']/3600)*100) ?>%"></div>
                            </div>
                        </div>
                        <div class="dh-card-body">
                            <h6 class="dh-card-title"><?= e($doc['title']) ?></h6>
                            <div class="dh-card-meta text-muted small"><?= timeAgo($doc['last_watched']) ?></div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($recentFavs)): ?>
    <section class="dh-section">
        <div class="dh-section-header">
            <h4 class="dh-section-title">Your Watchlist</h4>
            <a href="?tab=favorites" class="dh-see-all">See All <i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="dh-cards-grid">
            <?php foreach ($recentFavs as $doc): include 'includes/card.php'; endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (empty($recentHistory) && empty($recentFavs)): ?>
    <div class="dh-empty">
        <i class="bi bi-collection-play"></i>
        <h5>Nothing here yet</h5>
        <p>Start watching documentaries to build your history and watchlist.</p>
        <a href="<?= SITE_URL ?>" class="btn dh-btn-accent mt-2">Browse Documentaries</a>
    </div>
    <?php endif; ?>

    <!-- ---- HISTORY TAB ---- -->
    <?php elseif ($tab === 'history'): ?>
    <h4 class="mb-3">Watch History <span class="text-muted fs-6 fw-400">(<?= count($history) ?> items)</span></h4>
    <?php if (empty($history)): ?>
    <div class="dh-empty"><i class="bi bi-clock-history"></i><h5>No watch history</h5><p>Documentaries you watch will appear here.</p></div>
    <?php else: ?>
    <div class="dh-cards-grid">
        <?php foreach ($history as $doc): include 'includes/card.php'; endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ---- FAVORITES TAB ---- -->
    <?php elseif ($tab === 'favorites'): ?>
    <h4 class="mb-3">Watchlist <span class="text-muted fs-6 fw-400">(<?= count($favorites) ?> items)</span></h4>
    <?php if (empty($favorites)): ?>
    <div class="dh-empty"><i class="bi bi-bookmark"></i><h5>Your watchlist is empty</h5><p>Click the bookmark icon on any documentary to add it.</p></div>
    <?php else: ?>
    <div class="dh-cards-grid">
        <?php foreach ($favorites as $doc): include 'includes/card.php'; endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ---- PROFILE TAB ---- -->
    <?php elseif ($tab === 'profile'): ?>
    <div class="row">
        <div class="col-lg-5">
            <div class="dh-form-card">
                <h5 class="text-white mb-4"><i class="bi bi-person-circle me-2 text-accent"></i>Edit Profile</h5>
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="mb-3">
                        <label class="dh-form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control dh-form-control" value="<?= e($user['full_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="dh-form-label">Username</label>
                        <input type="text" class="form-control dh-form-control" value="<?= e($user['username']) ?>" disabled>
                    </div>
                    <div class="mb-4">
                        <label class="dh-form-label">Email</label>
                        <input type="email" class="form-control dh-form-control" value="<?= e($user['email']) ?>" disabled>
                    </div>
                    <button type="submit" class="btn dh-btn-accent px-4">Save Changes</button>
                </form>
            </div>
        </div>
        <div class="col-lg-4 mt-3 mt-lg-0">
            <div class="dh-form-card">
                <h5 class="text-white mb-3"><i class="bi bi-shield-check me-2 text-accent"></i>Account Info</h5>
                <div class="text-muted small">
                    <p><strong class="text-white">Member since:</strong><br><?= date('F j, Y', strtotime($user['created_at'])) ?></p>
                    <p><strong class="text-white">Last login:</strong><br><?= $user['last_login'] ? date('F j, Y H:i', strtotime($user['last_login'])) : 'N/A' ?></p>
                    <p><strong class="text-white">Account type:</strong><br><?= ucfirst($user['role']) ?></p>
                </div>
                <a href="logout.php" class="btn btn-outline-danger btn-sm mt-2 w-100">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
