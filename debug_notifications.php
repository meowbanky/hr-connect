<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/config/db.php';

echo "<h2>Notification Debugger</h2>";

// 1. Check Session
if (!isset($_SESSION['user_id'])) {
    echo "User NOT logged in.<br>";
    echo "Session ID: " . session_id() . "<br>";
} else {
    echo "User Logged In: ID <strong>" . $_SESSION['user_id'] . "</strong> (" . ($_SESSION['user_name'] ?? 'No Name') . ")<br>";
}

// 2. Check DB Connection
global $pdo;
if (isset($pdo)) {
    echo "PDO Connection: OK<br>";
} else {
    echo "PDO Connection: FAILED (variable not set)<br>";
    die("Stopping debug: No DB.");
}

// 3. Check Count
if (isset($_SESSION['user_id']) && $pdo) {
    echo "<h3>Database State</h3>";
    try {
        // Count Query
        $sql = "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0";
        $stmtBadge = $pdo->prepare($sql);
        $stmtBadge->execute([$_SESSION['user_id']]);
        $badgeCount = $stmtBadge->fetchColumn();
        echo "Query: <code>$sql</code> with ID " . $_SESSION['user_id'] . "<br>";
        echo "Unread Count in DB: <strong>$badgeCount</strong><br>";
        
        // List recent
        echo "<h3>Recent Notifications</h3>";
        $stmtList = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmtList->execute([$_SESSION['user_id']]);
        $list = $stmtList->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($list) > 0) {
            echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Title</th><th>Read?</th><th>Type</th><th>Created</th></tr>";
            foreach ($list as $row) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                echo "<td>" . ($row['is_read'] ? 'Yes' : '<b style="color:red">No</b>') . "</td>";
                echo "<td>" . ($row['type'] ?? 'N/A') . "</td>";
                echo "<td>" . $row['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No notifications found for this user.<br>";
        }

        // Test insertion if empty
        if ($badgeCount == 0 && isset($_GET['create'])) {
            echo "<h3>Creating Test Notification</h3>";
            require_once __DIR__ . '/includes/NotificationHelper.php';
            $res = NotificationHelper::create($_SESSION['user_id'], 'system', 'Test Notification', 'This is a test to verify the badge.', '/notifications');
            if ($res) echo "Created! Refresh to see badge.<br>";
            else echo "Failed to create.<br>";
        } else if ($badgeCount == 0) {
            echo "<br><a href='?create=1'>[Click here to create a test notification]</a>";
        }

    } catch (PDOException $e) {
        echo "Query Error: " . $e->getMessage() . "<br>";
    }
}
?>
