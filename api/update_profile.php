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

    // 1. Update User Record (Dynamic)
    $updateFields = [];
    $updateParams = [];

    if (isset($_POST['first_name'])) {
        $updateFields[] = "first_name = ?";
        $updateParams[] = $_POST['first_name'];
    }
    if (isset($_POST['last_name'])) {
        $updateFields[] = "last_name = ?";
        $updateParams[] = $_POST['last_name'];
    }
    if (isset($_POST['phone'])) {
        $updateFields[] = "phone_number = ?";
        $updateParams[] = $_POST['phone'];
    }

    // Profile Image Upload
    $profileImgPath = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/uploads/profile/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileName = 'profile_' . $user_id . '_' . time() . '.' . pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
            $profileImgPath = '/assets/uploads/profile/' . $fileName;
            $updateFields[] = "profile_image = ?";
            $updateParams[] = $profileImgPath;
            $_SESSION['profile_image'] = $profileImgPath;
        }
    }

    if (!empty($updateFields)) {
        $sqlUser = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $updateParams[] = $user_id;
        $pdo->prepare($sqlUser)->execute($updateParams);
        
        // Update session names if changed
        if (isset($_POST['first_name']) && isset($_POST['last_name'])) {
             $_SESSION['user_name'] = $_POST['first_name'] . ' ' . $_POST['last_name'];
        }
    }

    // 2. Candidate Record Logic (Only for candidates)
    $resume_path = null;
    if ($_SESSION['user_role'] === 'candidate') {
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
