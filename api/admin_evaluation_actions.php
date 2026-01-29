<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

// Auth Check (Panelist Chairman)
if (!isset($_SESSION['panelist_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$appId = $input['application_id'] ?? null;
$evalId = $input['evaluation_id'] ?? null;

// Helper to get Job ID from App or Eval
if ($appId) {
    $jobId = $pdo->query("SELECT job_id FROM applications WHERE id = $appId")->fetchColumn();
} elseif ($evalId) {
    $jobId = $pdo->query("SELECT application_id FROM evaluations WHERE id = $evalId")->fetchColumn();
    $jobId = $pdo->query("SELECT job_id FROM applications WHERE id = $jobId")->fetchColumn();
} else {
    echo json_encode(['success' => false, 'message' => 'ID required']);
    exit;
}

// Verify Chairman Status
$stmt = $pdo->prepare("SELECT is_chairman FROM panelist_jobs WHERE panelist_id = ? AND job_id = ? AND is_chairman = 1");
$stmt->execute([$_SESSION['panelist_id'], $jobId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Forbidden: Only Chairman can access this.']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($action === 'update_score') {
        // Validation
        $evalId = $input['evaluation_id'] ?? null;
        $criteriaId = $input['criteria_id'] ?? null;
        $newScore = $input['score'] ?? null;

        if (!$evalId || !$criteriaId || !isset($newScore)) {
            throw new Exception("Missing parameters");
        }

        // 1. Fetch current state
        $stmt = $pdo->prepare("SELECT total_score, original_total_score FROM evaluations WHERE id = ?");
        $stmt->execute([$evalId]);
        $eval = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$eval) throw new Exception("Evaluation not found");

        // 2. Track Original Score if first time editing
        if ($eval['original_total_score'] === null) {
            $upd = $pdo->prepare("UPDATE evaluations SET original_total_score = total_score WHERE id = ?");
            $upd->execute([$evalId]);
        }

        // 3. Update the specific criteria score
        $updScore = $pdo->prepare("UPDATE evaluation_scores SET score = ? WHERE evaluation_id = ? AND criteria_id = ?");
        $updScore->execute([$newScore, $evalId, $criteriaId]);

        // 4. Recalculate Total Score for this evaluation
        $sumStmt = $pdo->prepare("SELECT SUM(score) FROM evaluation_scores WHERE evaluation_id = ?");
        $sumStmt->execute([$evalId]);
        $newTotal = $sumStmt->fetchColumn();

        // 5. Update Evaluation Header
        $updEval = $pdo->prepare("UPDATE evaluations SET total_score = ? WHERE id = ?");
        $updEval->execute([$newTotal, $evalId]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Score updated successfully']);

    } elseif ($action === 'approve_all') {
        $appId = $input['application_id'] ?? null;
        $notes = $input['notes'] ?? '';

        if (!$appId) throw new Exception("Application ID required");

        // Approve ALL evaluations for this application
        $stmt = $pdo->prepare("
            UPDATE evaluations 
            SET is_approved = 1, 
                chairman_notes = ?,
                updated_at = NOW()
            WHERE application_id = ?
        ");
        $stmt->execute([$notes, $appId]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Evaluations approved']);

    } elseif ($action === 'chairman_decision') {
        // Redesigned Flow: Save Scores + Approve + Verdict
        $appId = $input['application_id'] ?? null;
        $verdict = $input['verdict'] ?? null;
        $scores = $input['scores'] ?? [];
        $notes = $input['notes'] ?? '';
        
        if (!$appId || !$verdict) throw new Exception("Missing Application ID or Verdict");

        // 1. Save/Update Chairman's Score (if scores provided)
        if (!empty($scores)) {
            // Check if eval exists
            $stmt = $pdo->prepare("SELECT id FROM evaluations WHERE application_id = ? AND panelist_id = ?");
            $stmt->execute([$appId, $_SESSION['panelist_id']]);
            $evalId = $stmt->fetchColumn();

            if (!$evalId) {
                // Create new evaluation
                $stmt = $pdo->prepare("INSERT INTO evaluations (application_id, job_id, panelist_id, total_score, recommendation, notes, is_approved) VALUES (?, ?, ?, 0, 'Chairman Verdict', ?, 1)");
                $stmt->execute([$appId, $jobId, $_SESSION['panelist_id'], $notes]);
                $evalId = $pdo->lastInsertId();
            } else {
                // Update existing
                $pdo->prepare("UPDATE evaluations SET notes = ?, is_approved = 1, recommendation = 'Chairman Verdict' WHERE id = ?")->execute([$notes, $evalId]);
            }

            // Upsert Scores
            $upsert = $pdo->prepare("INSERT INTO evaluation_scores (evaluation_id, criteria_id, score) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE score = VALUES(score)");
            foreach ($scores as $s) {
                if (isset($s['score']) && $s['score'] !== '') {
                   $upsert->execute([$evalId, $s['criteria_id'], $s['score']]);
                }
            }

            // Recalculate Total
            $total = $pdo->query("SELECT SUM(score) FROM evaluation_scores WHERE evaluation_id = $evalId")->fetchColumn();
            $pdo->prepare("UPDATE evaluations SET total_score = ? WHERE id = ?")->execute([$total, $evalId]);
        }

        // 2. Approve ALL Evaluations
        $pdo->prepare("UPDATE evaluations SET is_approved = 1 WHERE application_id = ?")->execute([$appId]);

        // 3. Update Application Status
        if (in_array($verdict, ['offered', 'shortlisted', 'rejected'])) { 
             $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([$verdict, $appId]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true]);

    } else {
        throw new Exception("Invalid action");
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
