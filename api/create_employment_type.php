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
    echo json_encode(['success' => false, 'message' => 'Employment type name is required']);
    exit;
}

try {
    // Check for duplicate
    $stmt = $pdo->prepare("SELECT id FROM employment_types WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Employment type already exists']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO employment_types (name) VALUES (?)");
    $stmt->execute([$name]);
    $newId = $pdo->lastInsertId();

    // Fetch all for dropdown reload
    $all = $pdo->query("SELECT * FROM employment_types ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Employment type created successfully',
        'new_id' => $newId,
        'employment_types' => $all
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
