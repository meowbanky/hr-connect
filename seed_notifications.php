<?php
// seed_notifications.php
require_once __DIR__ . '/includes/NotificationHelper.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Please login first.");
}

$userId = $_SESSION['user_id'];

// 1. Today
NotificationHelper::create($userId, 'system', 'Welcome (Today)', 'This is a test notification from today.');
NotificationHelper::create($userId, 'application', 'App Update (Today)', 'Your application was viewed today.');

// 2. Yesterday (Manual Insert to bypass NOW())
global $pdo;
$stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL 1 DAY))");
$stmt->execute([$userId, 'interview', 'Interview (Yesterday)', 'You missed an interview yesterday.']);

// 3. Earlier
$stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL 5 DAY))");
$stmt->execute([$userId, 'system', 'Old Alert (Earlier)', 'This is from 5 days ago.']);

echo "Notifications seeded for User ID: $userId";
?>
