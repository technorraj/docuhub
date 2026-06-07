<?php
// Admin authentication guard
// Include at the top of every admin page
require_once dirname(__DIR__) . '/includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Admin access required.'];
    redirect(SITE_URL . '/admin/login.php');
}
