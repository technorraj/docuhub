<?php
require_once 'auth.php';
$db   = getDB();
$type = $_GET['type'] ?? '';
$id   = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect(SITE_URL . '/admin/');
}

switch ($type) {
    case 'documentary':
        $stmt = $db->prepare("DELETE FROM documentaries WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Documentary deleted.'];
        redirect(SITE_URL . '/admin/documentaries.php');

    case 'category':
        // Only if no docs assigned
        $cnt = $db->prepare("SELECT COUNT(*) FROM documentaries WHERE category_id = ?");
        $cnt->execute([$id]);
        if ((int)$cnt->fetchColumn() > 0) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cannot delete: category has documentaries assigned.'];
        } else {
            $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Category deleted.'];
        }
        redirect(SITE_URL . '/admin/categories.php');

    case 'user':
        // Protect admins and self
        $u = $db->prepare("SELECT role FROM users WHERE id = ?");
        $u->execute([$id]);
        $u = $u->fetch();
        if (!$u || $u['role'] === 'admin' || $id === (int)$_SESSION['user_id']) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cannot delete this user.'];
        } else {
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'User deleted.'];
        }
        redirect(SITE_URL . '/admin/users.php');

    default:
        redirect(SITE_URL . '/admin/');
}
