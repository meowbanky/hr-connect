<?php
// api/candidate_checkin.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$userLat = $_POST['lat'] ?? null;
$userLng = $_POST['lng'] ?? null;
$interviewId = $_POST['interview_id'] ?? null;

if (!$userLat || !$userLng || !$interviewId) {
    echo json_encode(['success' => false, 'message' => 'Location data missing.']);
    exit;
}

try {
    // 1. Fetch Interview Details
    // Ensure interview belongs to a job applied by this user
    $sql = "SELECT i.*, a.user_id 
            FROM interviews i 
            JOIN applications a ON i.application_id = a.id 
            WHERE i.id = ? AND a.user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$interviewId, $_SESSION['user_id']]);
    $interview = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$interview) {
        echo json_encode(['success' => false, 'message' => 'Interview not found.']);
        exit;
    }

    // 2. Check Date/Time Validity
    $interviewDate = $interview['interview_date']; // Y-m-d
    $today = date('Y-m-d');
    
    // Allow check-in only on the interview day (strict?) or maybe +/- 1 day? strict for now.
    if ($today !== $interviewDate) {
        echo json_encode(['success' => false, 'message' => 'Check-in is only allowed on the day of the interview (' . $interviewDate . ').']);
        exit;
    }

    // 3. Geolocation Check (Haversine Formula)
    // If venue_lat/lng are null, we skip check (or fail? let's allow if admin didn't set coords)
    if (!empty($interview['venue_lat']) && !empty($interview['venue_lng'])) {
        $earthRadius = 6371000; // meters

        $lat1 = deg2rad($userLat);
        $lon1 = deg2rad($userLng);
        $lat2 = deg2rad($interview['venue_lat']);
        $lon2 = deg2rad($interview['venue_lng']);

        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;

        $a = sin($dLat/2) * sin($dLat/2) +
             cos($lat1) * cos($lat2) *
             sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;

        $maxDistance = 1000; // 1000 meters / 1km allowance (generous for GPS drift)

        if ($distance > $maxDistance) {
            echo json_encode([
                'success' => false, 
                'message' => 'You are too far from the venue. Please check in when you arrive.',
                'distance_detected' => round($distance) . 'm'
            ]);
            exit;
        }
    }

    // 4. Update Check-in
    $updateSql = "UPDATE interviews SET status = 'completed', check_in_time = NOW(), check_in_lat = ?, check_in_lng = ? WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([$userLat, $userLng, $interviewId]);

    echo json_encode(['success' => true, 'message' => 'Checked in successfully! Good luck.']);

} catch (Exception $e) {
    error_log("Check-in Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred.']);
}
?>
