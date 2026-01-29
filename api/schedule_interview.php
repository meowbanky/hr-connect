<?php
// api/schedule_interview.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/MailHelper.php';
require_once __DIR__ . '/../includes/NotificationHelper.php';
require_once __DIR__ . '/../includes/settings.php';

// Check Admin Auth
if (!isset($_SESSION['user_role']) || 
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff' && $_SESSION['user_role'] !== 'superadmin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Validation
$applicationId = $_POST['application_id'] ?? '';
$date = $_POST['interview_date'] ?? '';
$time = $_POST['interview_time'] ?? '';
$venueName = $_POST['venue_name'] ?? '';
$venueAddress = $_POST['venue_address'] ?? '';
$venueLink = $_POST['venue_link'] ?? '';
// Lat/Lng might be optional or calculated later, but better if passed from frontend
$venueLat = $_POST['venue_lat'] ?? null; 
$venueLng = $_POST['venue_lng'] ?? null;

if (empty($applicationId) || empty($date) || empty($time) || empty($venueName) || empty($venueAddress)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Support single or multiple IDs
    $appIds = [];
    if (strpos($applicationId, ',') !== false) {
        $appIds = explode(',', $applicationId); // Comma separated string
    } elseif (is_array($applicationId)) {
        $appIds = $applicationId;
    } else {
        $appIds = [$applicationId];
    }

    foreach ($appIds as $id) {
        $id = trim($id);
        if (empty($id)) continue;

        // 1. Insert Interview
        $sql = "INSERT INTO interviews (application_id, interview_date, interview_time, venue_name, venue_address, venue_link, venue_lat, venue_lng, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $date, $time, $venueName, $venueAddress, $venueLink, $venueLat, $venueLng]);
        
        // 2. Update Application Status
        $updateStmt = $pdo->prepare("UPDATE applications SET status = 'interviewed' WHERE id = ?");
        $updateStmt->execute([$id]);

        // 3. Fetch Candidate Info for Email
        $appStmt = $pdo->prepare("SELECT u.id as user_id, u.email, u.first_name, u.last_name, j.title as job_title 
                                  FROM applications a 
                                  JOIN candidates c ON a.candidate_id = c.id
                                  JOIN users u ON c.user_id = u.id 
                                  JOIN job_postings j ON a.job_id = j.id 
                                  WHERE a.id = ?");
        $appStmt->execute([$id]);
        $appData = $appStmt->fetch(PDO::FETCH_ASSOC);

        if ($appData) {
            $fullName = $appData['first_name'] . ' ' . $appData['last_name'];
            
            $interviewDetails = [
                'job_title' => $appData['job_title'],
                'date' => $date,
                'time' => $time,
                'venue' => $venueName,
                'address' => $venueAddress,
                'map_link' => $venueLink
            ];

            // Send Email (We need to update MailHelper next)
            if (method_exists('MailHelper', 'sendInterviewInvitation')) {
                MailHelper::sendInterviewInvitation($appData['email'], $fullName, $interviewDetails);
            }

            // Create In-App Notification
            if (class_exists('NotificationHelper')) {
                $notifTitle = "Interview Invitation: " . $appData['job_title'];
                $notifMsg = "You have been invited for an interview on " . date('M j, Y', strtotime($date)) . " at " . date('g:i A', strtotime($time)) . ". Check your application details for more info.";
                $actionUrl = "/application/" . $id; 
                NotificationHelper::create($appData['user_id'], 'interview', $notifTitle, $notifMsg, $actionUrl);
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Interview scheduled successfully.']);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Schedule Interview Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>
