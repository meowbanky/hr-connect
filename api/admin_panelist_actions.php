<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/MailHelper.php';
require_once __DIR__ . '/../includes/settings.php';

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_role']) || 
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff' && $_SESSION['user_role'] !== 'superadmin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$panelistId = $_POST['panelist_id'] ?? null;
$jobId = $_POST['job_id'] ?? null;

if (!$panelistId) {
    echo json_encode(['success' => false, 'message' => 'Missing panelist ID']);
    exit;
}

try {
    if ($action === 'resend_invite') {
        // Fetch Panelist
        $stmt = $pdo->prepare("SELECT * FROM panelists WHERE id = ?");
        $stmt->execute([$panelistId]);
        $panelist = $stmt->fetch();

        if (!$panelist) throw new Exception("Panelist not found");

        // Fetch Specific Job (if provided) or find pending jobs
        $jobQuery = "SELECT pj.invite_token, j.title, j.id as job_id 
                     FROM panelist_jobs pj 
                     JOIN job_postings j ON pj.job_id = j.id 
                     WHERE pj.panelist_id = ? AND pj.status = 'pending'";
        
        $params = [$panelistId];
        if ($jobId) {
            $jobQuery .= " AND pj.job_id = ?";
            $params[] = $jobId;
        }

        $stmtJobs = $pdo->prepare($jobQuery);
        $stmtJobs->execute($params);
        $assignments = $stmtJobs->fetchAll(PDO::FETCH_ASSOC);

        if (empty($assignments)) {
            echo json_encode(['success' => false, 'message' => 'No pending invites to resend for this selection.']);
            exit;
        }

        // Resend Email
        $mailHelper = new MailHelper();
        // We format assignments for the mail helper: array(['title' => ..., 'token' => ...])
        $jobData = [];
        foreach ($assignments as $a) {
            $jobData[] = ['title' => $a['title'], 'token' => $a['invite_token']];
        }

        // We also need the access code (password). We can't retrieve the hash.
        // Option 1: Regenerate password (annoying).
        // Option 2: Just send the link (but login needs password).
        // Since the prompt says "Resend Invite", and we switched to "Password" terminology which implies a persistent credential, 
        // we might assume the previous email had the code.
        // However, if they lost it, we might need to reset it.
        // BUT, for now, let's just re-send the JOB INVITE email which contains LINKS.
        // The "credential" email was sent on creation.
        // If we just want to re-trigger the "You are invited to X" email:
        
        // Construct Login URL
        $siteUrl = get_setting('site_url', 'https://hr.prismtechnologies.com.ng');
        $loginUrl = $siteUrl . '/panelist/login.php';

        $mailHelper->sendPanelistInvite($panelist['email'], $panelist['name'], $panelist['username'], '*** (Unchanged) ***', $loginUrl, $jobData);
        // Note: The template displays credentials. If we pass masked password, it shows masked.
        // Ideally we should differentiate "New Account" vs "New Job Invite".
        // For simplicity, we sending the invite links is the most important part for "Resend Invite".
        
        echo json_encode(['success' => true, 'message' => 'Invite resent successfully']);

    } elseif ($action === 'add_job') {
        if (!$jobId) throw new Exception("Job ID required");
        
        $isChairman = $_POST['is_chairman'] ?? 0;

        // Check if already assigned
        // Now supporting multiple IDs "1,2,3"
        $jobIds = explode(',', $jobId);
        $jobData = [];
        $siteUrl = get_setting('site_url', 'https://hr.prismtechnologies.com.ng');
        $loginUrl = $siteUrl . '/panelist/login.php';

        foreach ($jobIds as $jid) {
            $jid = trim($jid);
            if (empty($jid)) continue;

            // Check duplicate
            $check = $pdo->prepare("SELECT id FROM panelist_jobs WHERE panelist_id = ? AND job_id = ?");
            $check->execute([$panelistId, $jid]);
            if ($check->fetch()) continue; // Skip duplicates
            
            // Single Chairman Enforcement
            if ($isChairman == 1) {
                $pdo->prepare("UPDATE panelist_jobs SET is_chairman = 0 WHERE job_id = ?")->execute([$jid]);
            }

            // Create Assignment
            $token = bin2hex(random_bytes(32));
            $ins = $pdo->prepare("INSERT INTO panelist_jobs (panelist_id, job_id, status, invite_token, is_chairman) VALUES (?, ?, 'pending', ?, ?)");
            $ins->execute([$panelistId, $jid, $token, $isChairman]);
            
            // Get Title
            $stmtJ = $pdo->prepare("SELECT title FROM job_postings WHERE id = ?");
            $stmtJ->execute([$jid]);
            $jobTitle = $stmtJ->fetchColumn();

            $jobData[] = ['title' => $jobTitle, 'token' => $token];
        }
        
        if (empty($jobData)) {
            // Either no IDs or all were duplicates
            echo json_encode(['success' => true, 'message' => 'No new jobs were assigned (duplicates skipped).']);
            exit;
        }

        // Fetch Panelist Details for Email
        $stmtP = $pdo->prepare("SELECT * FROM panelists WHERE id = ?");
        $stmtP->execute([$panelistId]);
        $panelist = $stmtP->fetch();

        // Send Email with ALL new assignments
        $mailHelper = new MailHelper();
        $mailHelper->sendPanelistInvite($panelist['email'], $panelist['name'], $panelist['username'], '[Use Existing Password]', $loginUrl, $jobData);

        echo json_encode(['success' => true, 'message' => 'Jobs assigned and invites sent']);

    } elseif ($action === 'remove_job') {
        if (!$jobId) throw new Exception("Job ID required");

        // Fetch Details Before Deletion for Email
        $stmtDetails = $pdo->prepare("
            SELECT p.email, p.name, j.title 
            FROM panelist_jobs pj
            JOIN panelists p ON pj.panelist_id = p.id
            JOIN job_postings j ON pj.job_id = j.id
            WHERE pj.panelist_id = ? AND pj.job_id = ?
        ");
        $stmtDetails->execute([$panelistId, $jobId]);
        $details = $stmtDetails->fetch(PDO::FETCH_ASSOC);

        // Delete Assignment
        $del = $pdo->prepare("DELETE FROM panelist_jobs WHERE panelist_id = ? AND job_id = ?");
        $del->execute([$panelistId, $jobId]);

        // Send Email if details were found
        if ($details) {
            $mailHelper = new MailHelper();
            $mailHelper->sendPanelistRemoval($details['email'], $details['name'], $details['title']);
        }

        echo json_encode(['success' => true, 'message' => 'Assignment removed and email sent']);

    } elseif ($action === 'set_chairman') {
        $panelistId = $_POST['panelist_id'] ?? null;
        $jobId = $_POST['job_id'] ?? null;
        $isChairman = $_POST['is_chairman'] ?? 0;

        if (!$panelistId || !$jobId) {
             echo json_encode(['success' => false, 'message' => 'Missing IDs']);
             exit;
        }

        // Only one chairman per job? Usually yes.
        // If we want to enforce single chairman:
        if ($isChairman == 1) {
            $pdo->beginTransaction();
            // Reset existing chairman for this job
            $pdo->prepare("UPDATE panelist_jobs SET is_chairman = 0 WHERE job_id = ?")->execute([$jobId]);
            // Set new chairman
            $stmt = $pdo->prepare("UPDATE panelist_jobs SET is_chairman = 1 WHERE panelist_id = ? AND job_id = ?");
            $stmt->execute([$panelistId, $jobId]);
            $pdo->commit();
        } else {
            // Just revoke
            $stmt = $pdo->prepare("UPDATE panelist_jobs SET is_chairman = 0 WHERE panelist_id = ? AND job_id = ?");
            $stmt->execute([$panelistId, $jobId]);
        }

        echo json_encode(['success' => true, 'message' => 'Chairman status updated']);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    error_log("Admin Panelist Action Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
