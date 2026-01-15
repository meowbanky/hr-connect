<?php
// api/auth_reset_password.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($token) || empty($password) || empty($confirmPassword)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
    exit;
}

try {
    // 1. Verify Token
    $stmt = $pdo->prepare("SELECT id, reset_expires_at FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired reset token.']);
        exit;
    }

    // 2. Check Expiry
    if (strtotime($user['reset_expires_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Reset token has expired. Please request a new one.']);
        exit;
    }

    // 3. Update Password
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?");
    
    if ($updateStmt->execute([$hash, $user['id']])) {
        echo json_encode(['success' => true, 'message' => 'Password has been reset successfully. You can now login.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reset password.']);
    }

} catch (Exception $e) {
    error_log("Reset Password Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred.']);
}
?>
