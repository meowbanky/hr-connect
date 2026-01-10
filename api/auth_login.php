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
    $stmt = $pdo->prepare("SELECT u.id, u.password_hash, u.role_id, u.first_name, u.last_name, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Authentication successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role_name']; // Use role name for easier checks
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        
        $redirect = '/jobs'; // Default for candidates (Job Board)
        if ($user['role_name'] === 'admin' || $user['role_name'] === 'hr_staff') {
            $redirect = '/admin';
        }
        
        echo json_encode(['success' => true, 'redirect' => $redirect]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
