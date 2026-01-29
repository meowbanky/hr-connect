<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

// Check Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get Input
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    exit;
}

$panelistId = $data['id'];

try {
    $pdo->beginTransaction();

    // Delete associated jobs first (if foreign key cascade isn't set, just to be safe)
    $stmt = $pdo->prepare("DELETE FROM panelist_jobs WHERE panelist_id = ?");
    $stmt->execute([$panelistId]);

    // Delete panelist
    $stmt = $pdo->prepare("DELETE FROM panelists WHERE id = ?");
    $stmt->execute([$panelistId]);

    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
