<?php
require_once 'auth.php';
$adminTitle = 'Manage Documentaries';
$db = getDB();

// Filters
$search  = trim($_GET['search'] ?? '');
$catId   = (int)($_GET['cat'] ?? 0);
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$conditions = [];
$params     = [];

if ($search !== '') {
    $conditions[] = '(d.title LIKE ? OR d.source LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($catId > 0) {
    $conditions[] = 'd.category_id = ?';
    $params[] = $catId;
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$total      = (int)$db->prepare("SELECT COUNT(*) FROM documentaries d LEFT JOIN categories c ON d.category_id=c.id $where")->execute($params) ? $db->prepare("SELECT COUNT(*) FROM documentaries d $where")->execute($params) : 0;
// Proper count
$cntStmt = $db->prepare("SELECT COUNT(*) FROM documentaries d LEFT JOIN categories c ON d.category_id=c.id $where");
$cntStmt->execute($params);
$total      = (int)$cntStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

$stmt = $db->prepare("
    SELECT d.*, c.name as category_name
    FROM documentaries d
    LEFT JOIN categories c ON d.category_id = c.id
    $where
    ORDER BY d.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$docs = $stmt->fetchAll();

$categories = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h2 class="mb-1" style="font-family:var(--font-brand);letter-spacing:.5px">Documentaries</h2>
        <p class="text small mb-0"><?= number_format($total) ?> total entries</p>
    </div>
    <a href="add-documentary.php" class="btn dh-btn-accent"><i class="bi bi-plus-lg me-2"></i>Add New</a>
</div>

<!-- Filters -->
<form method="GET" class="dh-form-card mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="dh-form-label">Search</label>
            <input type="text" name="search" class="form-control dh-form-control"
                   value="<?= e($search) ?>" placeholder="Search by title or source...">
        </div>
        <div class="col-md-3">
            <label class="dh-form-label">Category</label>
            <select name="cat" class="form-select dh-form-control">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $catId == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn dh-btn-accent w-100">Filter</button>
        </div>
        <div class="col-md-2">
            <a href="documentaries.php" class="btn dh-btn-ghost w-100">Reset</a>
        </div>
    </div>
</form>

<!-- Table -->
<div class="dh-table">
    <table class="table table-borderless mb-0">
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th>Documentary</th>
                <th>Category</th>
                <th>Year</th>
                <th>Views</th>
                <th>Flags</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($docs)): ?>
            <tr>
                <td colspan="8" class="text-center py-5 text">
                    <i class="bi bi-camera-video-off d-block mb-2" style="font-size:2rem"></i>
                    No documentaries found.
                    <a href="add-documentary.php" class="text-accent ms-1">Add one?</a>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($docs as $i => $d): ?>
            <tr>
                <td class="text small"><?= $offset + $i + 1 ?></td>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <img src="https://img.youtube.com/vi/<?= e($d['youtube_video_id']) ?>/default.jpg"
                             style="width:64px;height:40px;object-fit:cover;border-radius:4px;flex-shrink:0" alt="">
                        <div style="min-width:0">
                            <div style="font-size:.85rem;font-weight:600;color:black;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px">
                                <?= e($d['title']) ?>
                            </div>
                            <div style="font-size:.72rem;color:var(--text )">
                                <code style="color:var(--accent)"><?= e($d['youtube_video_id']) ?></code>
                                &bull; <?= e($d['source'] ?? '') ?>
                            </div>
                        </div>
                    </div>
                </td>
                <td><span class="dh-cat-badge"><?= e($d['category_name'] ?? '—') ?></span></td>
                <td class="text-black small"><?= e($d['year'] ?? '—') ?></td>
                <td class="text-black small"><?= formatViews($d['views']) ?></td>
                <td>
                    <?php if ($d['is_featured']): ?><span class="dh-badge-featured d-inline-block mb-1">Featured</span><br><?php endif; ?>
                    <?php if ($d['is_trending']): ?><span class="dh-badge-trending">Trending</span><?php endif; ?>
                    <?php if (!$d['is_featured'] && !$d['is_trending']): ?><span class="text small">—</span><?php endif; ?>
                </td>
                <td>
                    <?php if ($d['is_active']): ?>
                    <span style="color:var(--success);font-size:.78rem"><i class="bi bi-circle-fill me-1" style="font-size:.5rem"></i>Active</span>
                    <?php else: ?>
                    <span style="color:var(--danger);font-size:.78rem"><i class="bi bi-circle-fill me-1" style="font-size:.5rem"></i>Hidden</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="<?= SITE_URL ?>/watch.php?id=<?= $d['id'] ?>" target="_blank"
                           class="btn btn-sm dh-btn py-1 px-2" title="Preview"><i class="bi bi-eye"></i></a>
                        <a href="edit-documentary.php?id=<?= $d['id'] ?>"
                           class="btn btn-sm dh-btn py-1 px-2" title="Edit"><i class="bi bi-pencil"></i></a>
                        <a href="delete.php?type=documentary&id=<?= $d['id'] ?>"
                           class="btn btn-sm btn-outline-danger py-1 px-2 btn-delete-confirm" title="Delete"><i class="bi bi-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-4 d-flex justify-content-center">
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

<?php include 'footer.php'; ?>
