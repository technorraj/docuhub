<?php
require_once 'includes/config.php';

if (isLoggedIn()) redirect(SITE_URL . '/dashboard.php');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username must be at least 3 characters and contain only letters, numbers, underscores.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db = getDB();

        // Check duplicates
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $error = 'Email or username is already taken.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hashed, $fullName]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Account created! Please log in.'];
            redirect(SITE_URL . '/login.php');
        }
    }
}

$pageTitle = 'Register';
include 'includes/header.php';
?>

<div class="dh-auth-wrap">
    <div class="dh-auth-box" style="max-width:480px">
        <div class="text-center mb-4">
            <div class="dh-logo mb-2" style="font-size:1.8rem">
                <i class="bi bi-camera-reels-fill text-accent me-2"></i>DocumentaryHub
            </div>
        </div>
        <h2>Create Account</h2>
        <p class="subtitle">Join free and start exploring documentaries</p>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="mb-3">
                <label class="dh-form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control dh-form-control"
                       value="<?= e($_POST['full_name'] ?? '') ?>" placeholder="John Doe" required>
            </div>
            <div class="mb-3">
                <label class="dh-form-label">Username</label>
                <input type="text" name="username" class="form-control dh-form-control"
                       value="<?= e($_POST['username'] ?? '') ?>" placeholder="johndoe" required>
            </div>
            <div class="mb-3">
                <label class="dh-form-label">Email Address</label>
                <input type="email" name="email" class="form-control dh-form-control"
                       value="<?= e($_POST['email'] ?? '') ?>" placeholder="you@example.com" required>
            </div>
            <div class="mb-3">
                <label class="dh-form-label">Password</label>
                <input type="password" name="password" class="form-control dh-form-control"
                       placeholder="Min. 6 characters" required>
            </div>
            <div class="mb-4">
                <label class="dh-form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control dh-form-control"
                       placeholder="Re-enter password" required>
            </div>
            <button type="submit" class="btn dh-btn-accent w-100 py-3 fw-600">
                <i class="bi bi-person-plus me-2"></i>Create Account
            </button>
        </form>

        <hr class="border-secondary my-4">
        <p class="text-center text-muted small mb-0">
            Already have an account?
            <a href="login.php" class="text-accent fw-600">Sign in</a>
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
