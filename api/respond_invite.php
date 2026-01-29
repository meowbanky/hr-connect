<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
session_start();

if (!isset($_SESSION['panelist_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$jobId = $data['job_id'] ?? null;
$action = $data['action'] ?? null;

if (!$jobId || !in_array($action, ['accept', 'decline'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$newStatus = ($action === 'accept') ? 'accepted' : 'declined';
$panelistId = $_SESSION['panelist_id'];

try {
    $stmt = $pdo->prepare("UPDATE panelist_jobs SET status = ? WHERE panelist_id = ? AND job_id = ?");
    $stmt->execute([$newStatus, $panelistId, $jobId]);

    echo json_encode(['success' => true, 'message' => 'Invite ' . $newStatus]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
