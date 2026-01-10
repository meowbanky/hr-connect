<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Security Check (Admin Only)
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$type = $_POST['type'] ?? ''; // 'department' or 'employment_type'

if (!$action || !$type) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$table = ($type === 'department') ? 'departments' : 'employment_types';

try {
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if (!$name) {
             echo json_encode(['success' => false, 'message' => 'Name required']);
             exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO `$table` (name) VALUES (?)");
        $stmt->execute([$name]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        
        if (!$id || !$name) {
             echo json_encode(['success' => false, 'message' => 'ID and Name required']);
             exit;
        }

        $stmt = $pdo->prepare("UPDATE `$table` SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
             echo json_encode(['success' => false, 'message' => 'ID required']);
             exit;
        }

        $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    }
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
         echo json_encode(['success' => false, 'message' => 'Duplicate name or item is in use.']);
    } else {
         echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
