<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) redirect(SITE_URL . '/dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            // Update last login
            $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Welcome back, ' . $user['full_name'] . '!'];

            // Redirect admin to admin panel
            if ($user['role'] === 'admin') {
                redirect(SITE_URL . '/admin/');
            }
            redirect(SITE_URL . '/dashboard.php');
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login';
include 'includes/header.php';
?>

<div class="dh-auth-wrap">
    <div class="dh-auth-box">
        <div class="text-center mb-4">
            <div class="dh-logo mb-2" style="font-size:1.8rem">
                <i class="bi bi-camera-reels-fill text-accent me-2"></i>DocumentaryHub
            </div>
        </div>
        <h2>Welcome back</h2>
        <p class="subtitle">Sign in to your account to continue watching</p>

        <?php if ($error): ?>
        <div class="alert alert-danger mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="mb-3">
                <label class="dh-form-label">Email Address</label>
                <input type="email" name="email" class="form-control dh-form-control"
                       value="<?= e($_POST['email'] ?? '') ?>" placeholder="you@example.com" required autofocus>
            </div>
            <div class="mb-4">
                <label class="dh-form-label">Password</label>
                <input type="password" name="password" class="form-control dh-form-control"
                       placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn dh-btn-accent w-100 py-3 fw-600">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <hr class="border-secondary my-4">
        <p class="text-center text-muted small mb-0">
            Don't have an account?
            <a href="register.php" class="text-accent fw-600">Create one free</a>
        </p>
        <p class="text-center text-muted small mt-2">
            <small>Admin? <a href="admin/login.php" class="text-muted">Admin Login →</a></small>
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
