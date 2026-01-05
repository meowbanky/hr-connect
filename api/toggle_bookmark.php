<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$jobId = $_POST['job_id'] ?? 0;

if (!$jobId) {
    echo json_encode(['success' => false, 'message' => 'Invalid Job ID']);
    exit;
}

try {
    // Check if already saved
    $stmt = $pdo->prepare("SELECT id FROM saved_jobs WHERE user_id = ? AND job_id = ?");
    $stmt->execute([$userId, $jobId]);
    $exists = $stmt->fetch();

    if ($exists) {
        // Remove bookmark
        $stmt = $pdo->prepare("DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?");
        $stmt->execute([$userId, $jobId]);
        echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Job removed from saved list']);
    } else {
        // Add bookmark
        $stmt = $pdo->prepare("INSERT INTO saved_jobs (user_id, job_id) VALUES (?, ?)");
        $stmt->execute([$userId, $jobId]);
        echo json_encode(['success' => true, 'action' => 'saved', 'message' => 'Job saved successfully']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
