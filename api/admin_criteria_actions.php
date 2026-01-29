<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

// Auth Check (Admin or HR)
if (!isset($_SESSION['user_id']) || 
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff' && $_SESSION['user_role'] !== 'superadmin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// Helper to get total score
function getCurrentTotal($pdo) {
    $stmt = $pdo->query("SELECT SUM(max_score) as total FROM evaluation_criteria");
    return (int)$stmt->fetchColumn();
}

try {
    if ($action === 'fetch') {
        $stmt = $pdo->query("SELECT * FROM evaluation_criteria ORDER BY id ASC");
        $criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = getCurrentTotal($pdo);
        
        echo json_encode(['success' => true, 'criteria' => $criteria, 'total_score' => $total]);

    } elseif ($action === 'add') {
        $name = trim($input['name'] ?? '');
        $score = (int)($input['max_score'] ?? 0);

        if (!$name || $score <= 0) {
            echo json_encode(['success' => false, 'message' => 'Valid name and score required']);
            exit;
        }

        $currentTotal = getCurrentTotal($pdo);
        if ($currentTotal + $score > 100) {
            echo json_encode(['success' => false, 'message' => "Cannot add criteria. Total score would exceed 100%. Current total: $currentTotal%"]);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO evaluation_criteria (name, max_score) VALUES (?, ?)");
        $stmt->execute([$name, $score]);
        
        echo json_encode(['success' => true, 'message' => 'Criteria added successfully']);

    } elseif ($action === 'delete') {
        $id = $input['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Criteria ID required']);
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM evaluation_criteria WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Criteria removed']);

    } elseif ($action === 'reset_default') {
        // Clear existing
        $pdo->beginTransaction();
        $pdo->exec("TRUNCATE TABLE evaluation_criteria");
        
        // Add defaults: Professional 40, Appearance 10, General knowledge 40, Comportment 10
        $defaults = [
            ['Professional', 40],
            ['Appearance', 10],
            ['General Knowledge', 40],
            ['Comportment', 10]
        ];
        
        $ins = $pdo->prepare("INSERT INTO evaluation_criteria (name, max_score) VALUES (?, ?)");
        foreach ($defaults as $d) {
            $ins->execute([$d[0], $d[1]]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Reverted to default criteria']);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Criteria Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
