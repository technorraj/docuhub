<?php
require_once dirname(__DIR__) . '/includes/config.php';

if (isAdmin()) redirect(SITE_URL . '/admin/');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin' AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            redirect(SITE_URL . '/admin/');
        } else {
            $error = 'Invalid admin credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="dh-auth-wrap">
    <div class="dh-auth-box">
        <div class="text-center mb-4">
            <div class="mb-2"><i class="bi bi-shield-fill-check" style="font-size:2.5rem;color:var(--gold)"></i></div>
            <div class="dh-logo" style="font-size:1.6rem"><i class="bi bi-camera-reels-fill text-accent me-2"></i>DocumentaryHub</div>
        </div>
        <h2>Admin Login</h2>
        <p class="subtitle">Sign in with your administrator credentials</p>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="dh-form-label">Admin Email</label>
                <input type="email" name="email" class="form-control dh-form-control"
                       value="<?= e($_POST['email'] ?? '') ?>" placeholder="admin@documentaryhub.com" required autofocus>
            </div>
            <div class="mb-4">
                <label class="dh-form-label">Password</label>
                <input type="password" name="password" class="form-control dh-form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn dh-btn-accent w-100 py-3 fw-600">
                <i class="bi bi-shield-lock me-2"></i>Login to Admin
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="<?= SITE_URL ?>/login.php" class="text-muted small">← Back to regular login</a>
        </div>

        <div class="mt-4 p-3 rounded" style="background:rgba(245,166,35,0.1);border:1px solid rgba(245,166,35,0.2)">
            <small class="text-warning"><i class="bi bi-info-circle me-1"></i>
            Default: admin@documentaryhub.com / password (change in production!)</small>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
