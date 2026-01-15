<?php
// api/auth_forgot_password.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/MailHelper.php';
require_once __DIR__ . '/../includes/settings.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Please enter your email address.']);
    exit;
}

// AUTO-MIGRATION END

try {
    // 1. Check if user exists
    $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // 2. Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // 3. Store token in DB
        $updateStmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE id = ?");
        $updateStmt->execute([$token, $expiry, $user['id']]);

        // 4. Send Email
        $resetLink = get_setting('site_url', 'http://' . $_SERVER['HTTP_HOST']) . "/reset_password.php?token=" . $token;
        $fullName = $user['first_name'] . ' ' . $user['last_name'];
        
        if (MailHelper::sendPasswordReset($email, $fullName, $resetLink)) {
            echo json_encode(['success' => true, 'message' => 'Password reset instructions have been sent to your email.']);
        } else {
            // Log error but show generic success to prevent email enumeration? 
            // Better to show error if email fails, but for security generic is better.
            // But for UX, if email fails, user is stuck.
            // Let's return success false for internal errors.
            error_log("Failed to send reset email to $email");
            echo json_encode(['success' => false, 'message' => 'Unable to send email. Please try again later.']);
        }
    } else {
        // User not found. To prevent enumeration, we can pretend success or show specific error.
        // Standard practice: "If an account exists with this email, instructions have been sent."
        // But for this internal-ish tool, let's be explicit for now or match user preference.
        // Let's use the secure vague message.
        echo json_encode(['success' => true, 'message' => 'If an account exists with this email, reset instructions have been sent.']);
    }

} catch (Exception $e) {
    error_log("Forgot Password Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
