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
$action = $input['action'] ?? $_GET['action'] ?? '';
$jobId = $input['job_id'] ?? $_GET['job_id'] ?? null;

if (!$jobId) {
    echo json_encode(['success' => false, 'message' => 'Job ID required']);
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
    if ($action === 'fetch_matrix') {
        
        // 1. Fetch Job Panelists
        $stmt = $pdo->prepare("
            SELECT p.id, p.name 
            FROM panelist_jobs pj 
            JOIN panelists p ON pj.panelist_id = p.id 
            WHERE pj.job_id = ?
        ");
        $stmt->execute([$jobId]);
        $panelists = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Fetch Global Criteria
        $criteria = $pdo->query("SELECT id, name, max_score FROM evaluation_criteria ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

        // 3. Fetch Existing Assignments
        $stmt = $pdo->prepare("SELECT panelist_id, criteria_id FROM job_criteria_assignments WHERE job_id = ?");
        $stmt->execute([$jobId]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Map assignments for easier frontend consumption: { panelist_id: [criteria_id_1, criteria_id_2] }
        $assignmentMap = [];
        foreach ($assignments as $a) {
            $assignmentMap[$a['panelist_id']][] = $a['criteria_id'];
        }

        echo json_encode([
            'success' => true,
            'panelists' => $panelists,
            'criteria' => $criteria,
            'assignments' => $assignmentMap
        ]);

    } elseif ($action === 'save_assignments') {
        $matrix = $input['matrix'] ?? []; // { panelist_id: [criteria_ids...] }

        $pdo->beginTransaction();

        // Clear existing assignments for this job
        $stmt = $pdo->prepare("DELETE FROM job_criteria_assignments WHERE job_id = ?");
        $stmt->execute([$jobId]);

        $ins = $pdo->prepare("INSERT INTO job_criteria_assignments (job_id, panelist_id, criteria_id) VALUES (?, ?, ?)");
        
        foreach ($matrix as $panelistId => $criteriaIds) {
            foreach ($criteriaIds as $cid) {
                $ins->execute([$jobId, $panelistId, $cid]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Assignments saved successfully']);

    } else {
        throw new Exception("Invalid action");
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
