<?php
// ============================================================
// DocumentaryHub - Configuration File
// ============================================================

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'documentaryhub');
define('DB_USER', 'root');         // Change to your MySQL username
define('DB_PASS', '');             // Change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_NAME', 'DocumentaryHub');
define('SITE_URL', 'http://localhost/documentaryhub');
define('SITE_DESCRIPTION', 'Your ultimate destination for quality documentaries');

// YouTube Data API Key (for cron.php auto-fetch)
define('YOUTUBE_API_KEY', 'AIzaSyB5NiGPbEBlB8lPtpYyLJpq7JaQEadYALE');

// Session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PDO Database Connection
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="background:#1a1a1a;color:#ff4444;font-family:monospace;padding:20px;margin:20px;border:1px solid #ff4444;border-radius:8px;">
                <h3>Database Connection Failed</h3>
                <p>' . htmlspecialchars($e->getMessage()) . '</p>
                <p>Please check your database configuration in <code>includes/config.php</code></p>
            </div>');
        }
    }
    return $pdo;
}

// Helper: Check if logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper: Check if admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Helper: Redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Helper: Sanitize output
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Helper: Format views
function formatViews($num) {
    if ($num >= 1000000) return round($num / 1000000, 1) . 'M';
    if ($num >= 1000) return round($num / 1000, 1) . 'K';
    return $num;
}

// Helper: Time ago
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

// Helper: Generate slug
function generateSlug($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// Helper: Get YouTube thumbnail
function getYouTubeThumbnail($videoId, $quality = 'maxresdefault') {
    return "https://img.youtube.com/vi/{$videoId}/{$quality}.jpg";
}
