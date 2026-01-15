<?php
// api/fetch_admin_notifications.php
session_start();
header('Content-Type: application/json');

// 1. Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

try {
    $userId = $_SESSION['user_id'];
    $filter = $_GET['filter'] ?? 'all';
    $search = $_GET['search'] ?? '';

    // Base Query
    $sql = "SELECT * FROM notifications WHERE user_id = ?";
    $params = [$userId];

    // Apply Filter
    if ($filter === 'unread') {
        $sql .= " AND is_read = 0";
    } elseif ($filter !== 'all') {
        // Assume filter matches 'type' e.g. 'application', 'system'
        $sql .= " AND type = ?";
        $params[] = $filter;
    }

    // Apply Search
    if (!empty($search)) {
        $sql .= " AND (title LIKE ? OR message LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // Order
    $sql .= " ORDER BY created_at DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get Unread Count (Global for this user)
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $countStmt->execute([$userId]);
    $unreadCount = $countStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unreadCount
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    error_log("Fetch Notifications Error: " . $e->getMessage());
}
?>
