<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/MailHelper.php';

session_start();

// Admin Auth Check
if (!isset($_SESSION['user_role']) || 
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff' && $_SESSION['user_role'] !== 'superadmin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? 'panelist';
    $job_ids = $_POST['job_ids'] ?? []; // Array of job IDs

    if (empty($name) || empty($email)) {
        throw new Exception("Name and Email are required");
    }

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM panelists WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception("Panelist with this email already exists.");
    }

    // Generate Credentials
    $username = strtolower(explode('@', $email)[0]) . rand(100, 999);
    // Ensure unique username
    while(true) {
        $check = $pdo->prepare("SELECT id FROM panelists WHERE username = ?");
        $check->execute([$username]);
        if(!$check->fetch()) break;
        $username .= rand(1, 9);
    }

    $raw_password = bin2hex(random_bytes(4)); // 8 chars random
    $password_hash = password_hash($raw_password, PASSWORD_DEFAULT);

    // Insert Panelist
    $pdo->beginTransaction();

    $sql = "INSERT INTO panelists (name, email, role, username, password_hash, status) VALUES (?, ?, ?, ?, ?, 'active')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $email, $role, $username, $password_hash]);
    $panelist_id = $pdo->lastInsertId();

    // Assign Jobs
    $jobAssignments = [];
    if (!empty($job_ids) && is_array($job_ids)) {
        $jobQuery = "INSERT INTO panelist_jobs (panelist_id, job_id, invite_token, status) VALUES (?, ?, ?, 'pending')";
        $jobStmt = $pdo->prepare($jobQuery);
        
        // Fetch Job Titles for Email
        $titleStmt = $pdo->prepare("SELECT title FROM job_postings WHERE id = ?");

        foreach ($job_ids as $job_id) {
            $token = bin2hex(random_bytes(32)); // Unique token
            $jobStmt->execute([$panelist_id, $job_id, $token]);
            
            $titleStmt->execute([$job_id]);
            $jobTitle = $titleStmt->fetchColumn();
            
            $jobAssignments[] = [
                'title' => $jobTitle,
                'token' => $token
            ];
        }
    }

    $pdo->commit();

    // Send Email
    $loginUrl = "https://" . $_SERVER['HTTP_HOST'] . "/panelist/login.php";
    
    $mailHelper = new MailHelper();
    // Pass $jobAssignments to the email function (make sure to update MailHelper next!)
    $mailSent = $mailHelper->sendPanelistInvite($email, $name, $username, $raw_password, $loginUrl, $jobAssignments);

    if ($mailSent) {
        echo json_encode(['success' => true, 'message' => 'Panelist invited successfully and email sent.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Panelist created, but email failed to send. Check server logs.', 'email_failed' => true]);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
