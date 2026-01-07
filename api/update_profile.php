<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Update User Record (First/Last Name, Phone)
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    // We shouldn't update email here usually for security unless we have verification, 
    // but the form has it readonly anyway.

    $stmtUser = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone_number = ? WHERE id = ?");
    $stmtUser->execute([$firstName, $lastName, $phone, $user_id]);

    // 2. Candidate Record Logic
    // Check existence
    $stmtCand = $pdo->prepare("SELECT id FROM candidates WHERE user_id = ?");
    $stmtCand->execute([$user_id]);
    $candidate = $stmtCand->fetch();
    $candidate_id = $candidate['id'] ?? null;

    $dob = $_POST['dob'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $linkedin = $_POST['linkedin'] ?? '';
    $portfolio = $_POST['portfolio'] ?? '';
    $bio = $_POST['bio'] ?? '';
    
    $state = $_POST['state_of_origin'] ?? '';
    $lga = $_POST['lga'] ?? '';
    $qualification = $_POST['highest_qualification'] ?? '';
    $experience = $_POST['years_of_experience'] ?? 0;

    // Resume
    $resume_path = null;
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/resumes/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileName = time() . '_' . basename($_FILES['resume']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['resume']['tmp_name'], $targetPath)) {
            $resume_path = 'uploads/resumes/' . $fileName;
        }
    }

    if ($candidate_id) {
        // Update
        $sql = "UPDATE candidates SET date_of_birth = ?, gender = ?, linkedin_profile = ?, portfolio_url = ?, address = ?, state_of_origin = ?, lga = ?, highest_qualification = ?, years_of_experience = ? WHERE id = ?";
        $params = [$dob, $gender, $linkedin, $portfolio, $bio, $state, $lga, $qualification, $experience, $candidate_id];
        $pdo->prepare($sql)->execute($params);

        if ($resume_path) {
            $pdo->prepare("UPDATE candidates SET resume_path = ? WHERE id = ?")->execute([$resume_path, $candidate_id]);
        }
    } else {
        // Create
        $sql = "INSERT INTO candidates (user_id, date_of_birth, gender, linkedin_profile, portfolio_url, address, state_of_origin, lga, highest_qualification, years_of_experience, resume_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$user_id, $dob, $gender, $linkedin, $portfolio, $bio, $state, $lga, $qualification, $experience, $resume_path]);
        $candidate_id = $pdo->lastInsertId();
    }

    // 3. Education
    // Replace all simplified
    $pdo->prepare("DELETE FROM candidate_education WHERE candidate_id = ?")->execute([$candidate_id]);

    if (isset($_POST['edu_school']) && is_array($_POST['edu_school'])) {
        $stmtEdu = $pdo->prepare("INSERT INTO candidate_education (candidate_id, school_name, qualification, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
        
        for ($i = 0; $i < count($_POST['edu_school']); $i++) {
            $school = $_POST['edu_school'][$i];
            $degree = $_POST['edu_degree'][$i] ?? '';
            $start = $_POST['edu_start'][$i] ?? '';
            $end = $_POST['edu_end'][$i] ?? null;
            
            if (!empty($school) && !empty($degree)) {
                $stmtEdu->execute([$candidate_id, $school, $degree, $start, $end ?: null]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'reload' => ($resume_path ? true : false)]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Profile Update Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred.']);
}
?>
