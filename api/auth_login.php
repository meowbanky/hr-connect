<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please enter both email and password.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, password_hash, role_id, first_name, last_name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Authentication successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role_id'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        
        echo json_encode(['success' => true, 'redirect' => '../job_board_&_candidate_portal/index.php']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
