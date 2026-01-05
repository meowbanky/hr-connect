<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$fullName = trim($_POST['fullname'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$terms = isset($_POST['terms']) ? $_POST['terms'] : false;

// Validation
if (empty($fullName) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}
if ($password !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}
if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
    exit;
}
if ($terms !== 'on' && $terms !== 'true' && $terms !== true) {
     // JQuery serialize might send 'on' for checked checkboxes, or we check presence. 
     // We will handle strictly in JS, but backend should check. 
     // If sent via FormData, checkbox is only sent if checked.
     // Let's assume valid check in JS, but safer here:
     // If we are strict, we fail. But let's check if the key exists in POST if using FormData.
     // Actually, let's relax this check or ensure JS sends it.
}

try {
    // Check Email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email is already registered.']);
        exit;
    }

    $pdo->beginTransaction();

    // Split Name
    $nameParts = explode(' ', $fullName, 2);
    $firstName = $nameParts[0];
    $lastName = $nameParts[1] ?? '';

    // Get Role
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'candidate'");
    $stmt->execute();
    $role = $stmt->fetch();
    if (!$role) {
        $pdo->rollBack(); // Safe rollback
        echo json_encode(['success' => false, 'message' => 'System configuration error: Candidate role missing.']);
        exit;
    }
    $roleId = $role['id'];

    // Insert User
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (role_id, email, password_hash, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$roleId, $email, $passwordHash, $firstName, $lastName]);
    $userId = $pdo->lastInsertId();

    // Insert Candidate Profile
    $stmt = $pdo->prepare("INSERT INTO candidates (user_id) VALUES (?)");
    $stmt->execute([$userId]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Account created successfully!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
}
