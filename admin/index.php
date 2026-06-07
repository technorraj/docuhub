<?php
require_once 'auth.php';
$adminTitle = 'Dashboard';
$db = getDB();

// Stats
$totalDocs     = (int)$db->query("SELECT COUNT(*) FROM documentaries WHERE is_active=1")->fetchColumn();
$totalUsers    = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalCats     = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalViews    = (int)$db->query("SELECT COALESCE(SUM(views),0) FROM documentaries")->fetchColumn();
$totalFavs     = (int)$db->query("SELECT COUNT(*) FROM favorites")->fetchColumn();
$totalHistory  = (int)$db->query("SELECT COUNT(*) FROM watch_history")->fetchColumn();
$featuredCount = (int)$db->query("SELECT COUNT(*) FROM documentaries WHERE is_featured=1")->fetchColumn();
$trendingCount = (int)$db->query("SELECT COUNT(*) FROM documentaries WHERE is_trending=1")->fetchColumn();

// Recent documentaries
$recentDocs = $db->query("
    SELECT d.*, c.name as category_name
    FROM documentaries d
    LEFT JOIN categories c ON d.category_id = c.id
    ORDER BY d.created_at DESC LIMIT 8
")->fetchAll();

// Top docs by views
$topDocs = $db->query("
    SELECT d.title, d.views, d.youtube_video_id, c.name as category_name
    FROM documentaries d
    LEFT JOIN categories c ON d.category_id = c.id
    ORDER BY d.views DESC LIMIT 5
")->fetchAll();

// Recent users
$recentUsers = $db->query("
    SELECT username, email, full_name, created_at
    FROM users WHERE role='user'
    ORDER BY created_at DESC LIMIT 5
")->fetchAll();

// Docs per category
$catStats = $db->query("
    SELECT c.name, COUNT(d.id) as count
    FROM categories c
    LEFT JOIN documentaries d ON c.id = d.category_id AND d.is_active=1
    GROUP BY c.id ORDER BY count DESC LIMIT 6
")->fetchAll();

include 'header.php';
?>

<!-- Page Heading -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h2 class="mb-1" style="font-family:var(--font-brand);letter-spacing:.5px">Dashboard</h2>
        <p class="text small mb-0">Welcome back, <?= e($_SESSION['full_name'] ?? 'Admin') ?>! Here's what's happening.</p>
    </div>
    <a href="add-documentary.php" class="btn dh-btn-accent">
        <i class="bi bi-plus-lg me-2"></i>Add Documentary
    </a>
</div>

<!-- Stat Cards Row 1 -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="dh-admin-stat">
            <div class="dh-admin-stat-icon blue"><i class="bi bi-camera-video-fill"></i></div>
            <div>
                <div class="dh-admin-stat-num"><?= number_format($totalDocs) ?></div>
                <div class="dh-admin-stat-label">Total Documentaries</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="dh-admin-stat">
            <div class="dh-admin-stat-icon green"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="dh-admin-stat-num"><?= number_format($totalUsers) ?></div>
                <div class="dh-admin-stat-label">Registered Users</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="dh-admin-stat">
            <div class="dh-admin-stat-icon gold"><i class="bi bi-eye-fill"></i></div>
            <div>
                <div class="dh-admin-stat-num"><?= formatViews($totalViews) ?></div>
                <div class="dh-admin-stat-label">Total Views</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="dh-admin-stat">
            <div class="dh-admin-stat-icon red"><i class="bi bi-tag-fill"></i></div>
            <div>
                <div class="dh-admin-stat-num"><?= $totalCats ?></div>
                <div class="dh-admin-stat-label">Categories</div>
            </div>
        </div>
    </div>
</div>

<!-- Stat Cards Row 2 -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="dh-form-card text-center py-3">
            <div style="font-size:1.6rem;font-weight:700;color:var(--gold);font-family:var(--font-brand)"><?= $featuredCount ?></div>
            <div class="text small mt-1">Featured</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dh-form-card text-center py-3">
            <div style="font-size:1.6rem;font-weight:700;color:var(--danger);font-family:var(--font-brand)"><?= $trendingCount ?></div>
            <div class="text small mt-1">Trending</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dh-form-card text-center py-3">
            <div style="font-size:1.6rem;font-weight:700;color:var(--accent);font-family:var(--font-brand)"><?= number_format($totalFavs) ?></div>
            <div class="text small mt-1">Watchlist Saves</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dh-form-card text-center py-3">
            <div style="font-size:1.6rem;font-weight:700;color:var(--success);font-family:var(--font-brand)"><?= number_format($totalHistory) ?></div>
            <div class="text small mt-1">Watch Events</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Documentaries -->
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="text-white mb-0">Recent Documentaries</h5>
            <a href="documentaries.php" class="dh-see-all">Manage All <i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="dh-table">
            <table class="table table-borderless mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Views</th>
                        <th>Flags</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentDocs as $d): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <img src="https://img.youtube.com/vi/<?= e($d['youtube_video_id']) ?>/default.jpg"
                                     style="width:56px;height:36px;object-fit:cover;border-radius:4px" alt="">
                                <div>
                                    <div style="font-size:.82rem;font-weight:600;color:white;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                        <?= e($d['title']) ?>
                                    </div>
                                    <div style="font-size:.72rem;color:var(--text)"><?= e($d['source'] ?? '') ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="dh-cat-badge"><?= e($d['category_name'] ?? '—') ?></span></td>
                        <td class="text small"><?= formatViews($d['views']) ?></td>
                        <td>
                            <?php if ($d['is_featured']): ?><span class="dh-badge-featured me-1">Featured</span><?php endif; ?>
                            <?php if ($d['is_trending']): ?><span class="dh-badge-trending">Trending</span><?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="edit-documentary.php?id=<?= $d['id'] ?>" class="btn btn-sm dh-btn-success py-1 px-2" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="delete.php?type=documentary&id=<?= $d['id'] ?>"
                                   class="btn btn-sm btn-outline-danger py-1 px-2 btn-delete-confirm" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-lg-4">

        <!-- Top by Views -->
        <div class="mb-4">
            <h5 class="text-white mb-3">Top by Views</h5>
            <div class="dh-form-card p-0" style="overflow:hidden">
                <?php foreach ($topDocs as $i => $d): ?>
                <div class="d-flex align-items-center gap-3 p-3 <?= $i < count($topDocs)-1 ? 'border-bottom' : '' ?>" style="border-color:var(--border)!important">
                    <span style="font-size:1.2rem;font-weight:700;color:var(--text-dim);font-family:var(--font-brand);min-width:24px">
                        <?= $i + 1 ?>
                    </span>
                    <img src="https://img.youtube.com/vi/<?= e($d['youtube_video_id']) ?>/default.jpg"
                         style="width:48px;height:30px;object-fit:cover;border-radius:4px" alt="">
                    <div style="min-width:0;flex:1">
                        <div style="font-size:.78rem;font-weight:600;color:white;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            <?= e($d['title']) ?>
                        </div>
                        <div style="font-size:.7rem;color:var(--text)"><i class="bi bi-eye me-1"></i><?= formatViews($d['views']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Docs Per Category -->
        <div class="mb-4">
            <h5 class="text-white mb-3">Docs by Category</h5>
            <div class="dh-form-card">
                <?php
                $maxCount = max(array_column($catStats, 'count') ?: [1]);
                foreach ($catStats as $cs):
                    $pct = $maxCount > 0 ? round(($cs['count'] / $maxCount) * 100) : 0;
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.8rem;color:rgba(255,255,255,.8)"><?= e($cs['name']) ?></span>
                        <span style="font-size:.8rem;color:var(--text)"><?= $cs['count'] ?></span>
                    </div>
                    <div style="height:5px;background:var(--bg-primary);border-radius:3px">
                        <div style="height:5px;width:<?= $pct ?>%;background:var(--accent);border-radius:3px;transition:.5s"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Users -->
        <div>
            <h5 class="text-white mb-3">Recent Users</h5>
            <div class="dh-form-card p-0" style="overflow:hidden">
                <?php foreach ($recentUsers as $i => $u): ?>
                <div class="d-flex align-items-center gap-3 p-3 <?= $i < count($recentUsers)-1 ? 'border-bottom' : '' ?>" style="border-color:var(--border)!important">
                    <div class="dh-avatar-sm"><?= strtoupper(substr($u['full_name'] ?: $u['username'], 0, 1)) ?></div>
                    <div>
                        <div style="font-size:.82rem;font-weight:600;color:white"><?= e($u['full_name'] ?: $u['username']) ?></div>
                        <div style="font-size:.72rem;color:var(--text)"><?= timeAgo($u['created_at']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($recentUsers)): ?>
                <div class="p-3 text small text-center">No users yet</div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Quick Action Buttons -->
<div class="row g-3 mt-2">
    <div class="col-12">
        <h5 class="text-white mb-3">Quick Actions</h5>
    </div>
    <div class="col-6 col-md-3">
        <a href="add-documentary.php" class="dh-form-card d-flex align-items-center gap-3 text-decoration-none" style="padding:16px">
            <i class="bi bi-plus-circle-fill" style="font-size:1.4rem;color:var(--accent)"></i>
            <span style="font-weight:600;color:white;font-size:.875rem">Add Documentary</span>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="categories.php" class="dh-form-card d-flex align-items-center gap-3 text-decoration-none" style="padding:16px">
            <i class="bi bi-tag-fill" style="font-size:1.4rem;color:var(--gold)"></i>
            <span style="font-weight:600;color:white;font-size:.875rem">Manage Categories</span>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="users.php" class="dh-form-card d-flex align-items-center gap-3 text-decoration-none" style="padding:16px">
            <i class="bi bi-people-fill" style="font-size:1.4rem;color:var(--success)"></i>
            <span style="font-weight:600;color:white;font-size:.875rem">Manage Users</span>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= SITE_URL ?>" target="_blank" class="dh-form-card d-flex align-items-center gap-3 text-decoration-none" style="padding:16px">
            <i class="bi bi-box-arrow-up-right" style="font-size:1.4rem;color:var(--text)"></i>
            <span style="font-weight:600;color:white;font-size:.875rem">View Frontend</span>
        </a>
    </div>
</div>

<?php include 'footer.php'; ?>
