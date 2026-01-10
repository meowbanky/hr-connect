<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$name = trim($_POST['name'] ?? '');

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Department name is required']);
    exit;
}

try {
    // Check for duplicate
    $stmt = $pdo->prepare("SELECT id FROM departments WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Department already exists']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO departments (name) VALUES (?)");
    $stmt->execute([$name]);
    $newId = $pdo->lastInsertId();

    // Fetch all for dropdown reload
    $all = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Department created successfully',
        'new_id' => $newId,
        'departments' => $all
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
