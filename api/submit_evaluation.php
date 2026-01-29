<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['panelist_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$appId = $_POST['application_id'] ?? null;
$jobId = $_POST['job_id'] ?? null; // Not strictly needed for table but good for validation
$notes = $_POST['notes'] ?? '';
$recommendation = $_POST['recommendation'] ?? '';

if (!$appId || !$recommendation) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Process Criteria Scores - ONLY Assigned Ones
    // Validate that panelist is assigned these criteria for this job
    $stmt = $pdo->prepare("
        SELECT c.id, c.max_score 
        FROM evaluation_criteria c
        JOIN job_criteria_assignments jca ON c.id = jca.criteria_id
        WHERE jca.job_id = ? AND jca.panelist_id = ?
    ");
    $stmt->execute([$jobId, $_SESSION['panelist_id']]);
    $criteriaList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($criteriaList)) {
        throw new Exception("You are not assigned any criteria for this job evaluation.");
    }

    $totalScore = 0;
    $scoresToInsert = [];

    foreach ($criteriaList as $criteria) {
        $cid = $criteria['id'];
        $inputName = "criteria_" . $cid;
        
        $score = isset($_POST[$inputName]) ? (int)$_POST[$inputName] : 0;
        
        // Validate score max
        if ($score > $criteria['max_score']) {
            throw new Exception("Score for criteria ID $cid exceeds maximum allowed.");
        }
        if ($score < 0) {
            throw new Exception("Score cannot be negative.");
        }

        $totalScore += $score;
        $scoresToInsert[] = [
            'criteria_id' => $cid,
            'score' => $score
        ];
    }

    // 2. Insert Evaluation Header
    // Note: We leave legacy static score columns NULL
    $stmt = $pdo->prepare("
        INSERT INTO evaluations (application_id, panelist_id, total_score, notes, recommendation) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            total_score = VALUES(total_score),
            notes = VALUES(notes),
            recommendation = VALUES(recommendation),
            updated_at = NOW()
    ");
    $stmt->execute([
        $appId,
        $_SESSION['panelist_id'],
        $totalScore,
        $notes,
        $recommendation
    ]);

    // Get evaluation ID (handle Insert vs Update logic)
    // For simplicity with ON DUPLICATE KEY UPDATE, lastInsertId might not work reliably if updated.
    // So we fetch the ID back.
    $stmt = $pdo->prepare("SELECT id FROM evaluations WHERE application_id = ? AND panelist_id = ?");
    $stmt->execute([$appId, $_SESSION['panelist_id']]);
    $evalId = $stmt->fetchColumn();

    // 3. Update Detail Scores
    // Clear old scores for this evaluation if any (in case of update)
    $pdo->prepare("DELETE FROM evaluation_scores WHERE evaluation_id = ?")->execute([$evalId]);

    // Insert new scores
    $insertScore = $pdo->prepare("INSERT INTO evaluation_scores (evaluation_id, criteria_id, score) VALUES (?, ?, ?)");
    foreach ($scoresToInsert as $s) {
        $insertScore->execute([$evalId, $s['criteria_id'], $s['score']]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Evaluation submitted successfully']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Evaluation Submit Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error saving evaluation: ' . $e->getMessage()]);
}
