<?php
require_once 'auth.php';
$adminTitle = 'Manage Users';
$db = getDB();

// Handle toggle active
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $uid  = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND role != 'admin'");
    $stmt->execute([$uid]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'User status updated.'];
    redirect(SITE_URL . '/admin/users.php');
}

$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = $search ? "WHERE (username LIKE ? OR email LIKE ? OR full_name LIKE ?)" : "";
$params = $search ? ["%$search%", "%$search%", "%$search%"] : [];

$cntStmt = $db->prepare("SELECT COUNT(*) FROM users $where");
$cntStmt->execute($params);
$total      = (int)$cntStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

$stmt = $db->prepare("SELECT * FROM users $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Quick stats
$activeUsers = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_active=1 AND role='user'")->fetchColumn();
$adminCount  = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h2 class="mb-1" style="font-family:var(--font-brand);letter-spacing:.5px">Users</h2>
        <p class="text-muted small mb-0"><?= number_format($total) ?> total &bull; <?= $activeUsers ?> active &bull; <?= $adminCount ?> admin(s)</p>
    </div>
</div>

<!-- Search -->
<form method="GET" class="dh-form-card mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-6">
            <label class="dh-form-label">Search Users</label>
            <input type="text" name="search" class="form-control dh-form-control"
                   value="<?= e($search) ?>" placeholder="Name, username, or email...">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn dh-btn-accent w-100">Search</button>
        </div>
        <div class="col-md-3">
            <a href="users.php" class="btn dh-btn-ghost w-100">Reset</a>
        </div>
    </div>
</form>

<div class="dh-table">
    <table class="table table-borderless mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>User</th>
                <th>Email</th>
                <th>Role</th>
                <th>Joined</th>
                <th>Last Login</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-people d-block mb-2" style="font-size:2rem"></i>No users found.
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($users as $i => $u): ?>
            <tr>
                <td class="text-muted small"><?= $offset + $i + 1 ?></td>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <div class="dh-avatar-sm" style="background:<?= $u['role'] === 'admin' ? 'var(--gold)' : 'var(--accent)' ?>;color:<?= $u['role'] === 'admin' ? '#1a1a1a' : 'white' ?>">
                            <?= strtoupper(substr($u['full_name'] ?: $u['username'], 0, 1)) ?>
                        </div>
                        <div>
                            <div style="font-weight:600;color:white;font-size:.85rem"><?= e($u['full_name'] ?: '—') ?></div>
                            <div style="font-size:.75rem;color:var(--text-muted)">@<?= e($u['username']) ?></div>
                        </div>
                    </div>
                </td>
                <td style="font-size:.82rem;color:rgba(255,255,255,.7)"><?= e($u['email']) ?></td>
                <td>
                    <?php if ($u['role'] === 'admin'): ?>
                    <span style="background:rgba(245,166,35,.15);color:var(--gold);font-size:.72rem;font-weight:700;padding:3px 8px;border-radius:4px">
                        <i class="bi bi-shield-fill me-1"></i>Admin
                    </span>
                    <?php else: ?>
                    <span style="background:rgba(0,168,225,.1);color:var(--accent);font-size:.72rem;font-weight:700;padding:3px 8px;border-radius:4px">
                        User
                    </span>
                    <?php endif; ?>
                </td>
                <td class="text-muted small"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                <td class="text-muted small">
                    <?= $u['last_login'] ? timeAgo($u['last_login']) : 'Never' ?>
                </td>
                <td>
                    <?php if ($u['is_active']): ?>
                    <span style="color:var(--success);font-size:.78rem"><i class="bi bi-circle-fill me-1" style="font-size:.45rem"></i>Active</span>
                    <?php else: ?>
                    <span style="color:var(--danger);font-size:.78rem"><i class="bi bi-circle-fill me-1" style="font-size:.45rem"></i>Suspended</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($u['role'] !== 'admin'): ?>
                    <a href="users.php?toggle=<?= $u['id'] ?>"
                       class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?> py-1 px-2"
                       title="<?= $u['is_active'] ? 'Suspend' : 'Activate' ?>">
                        <i class="bi bi-<?= $u['is_active'] ? 'pause' : 'play' ?>-fill"></i>
                    </a>
                    <a href="delete.php?type=user&id=<?= $u['id'] ?>"
                       class="btn btn-sm btn-outline-danger py-1 px-2 btn-delete-confirm ms-1" title="Delete">
                        <i class="bi bi-trash"></i>
                    </a>
                    <?php else: ?>
                    <span class="text-muted small">Protected</span>
                    <?php endif; ?>
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
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"><i class="bi bi-chevron-left"></i></a>
        </li>
        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
        <li class="page-item <?= $p == $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"><i class="bi bi-chevron-right"></i></a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php include 'footer.php'; ?>
