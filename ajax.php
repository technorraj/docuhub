<?php
require_once 'includes/config.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'toggle_favorite') {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'redirect' => SITE_URL . '/login.php']);
        exit;
    }
    $docId  = (int)($_POST['id'] ?? 0);
    $userId = (int)$_SESSION['user_id'];
    $db     = getDB();

    // Check if already favorited
    $stmt = $db->prepare("SELECT id FROM favorites WHERE user_id = ? AND documentary_id = ?");
    $stmt->execute([$userId, $docId]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Remove
        $stmt = $db->prepare("DELETE FROM favorites WHERE user_id = ? AND documentary_id = ?");
        $stmt->execute([$userId, $docId]);
        echo json_encode(['success' => true, 'added' => false]);
    } else {
        // Add
        $stmt = $db->prepare("INSERT INTO favorites (user_id, documentary_id) VALUES (?, ?)");
        $stmt->execute([$userId, $docId]);
        echo json_encode(['success' => true, 'added' => true]);
    }
    exit;
}

if ($action === 'record_view') {
    $docId = (int)($_POST['id'] ?? 0);
    $db    = getDB();
    // Increment view count
    $stmt = $db->prepare("UPDATE documentaries SET views = views + 1 WHERE id = ?");
    $stmt->execute([$docId]);
    // Record in watch history if logged in
    if (isLoggedIn()) {
        $userId = (int)$_SESSION['user_id'];
        $stmt = $db->prepare("INSERT INTO watch_history (user_id, documentary_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE last_watched = NOW()");
        $stmt->execute([$userId, $docId]);
    }
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
