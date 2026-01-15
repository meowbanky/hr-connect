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
$job_id = 999; // Placeholder or passed from form. Ideally passed as hidden input.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Create/Update Candidate Record
    // Check if candidate exists for this user
    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $candidate = $stmt->fetch();
    
    $dob = $_POST['dob'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $linkedin = $_POST['linkedin'] ?? '';
    $bio = $_POST['bio'] ?? '';
    
    // Resume Upload Handling
    $resume_path = null;
    $use_profile_resume = isset($_POST['use_profile_resume']) && $_POST['use_profile_resume'] == '1';

    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/resumes/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileName = time() . '_' . basename($_FILES['resume']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['resume']['tmp_name'], $targetPath)) {
            $resume_path = 'uploads/resumes/' . $fileName;
        }
    } elseif ($use_profile_resume && $candidate) {
        // Fallback to existing resume
        $resume_path = $candidate['resume_path'];
    }

    if ($candidate) {
        $candidate_id = $candidate['id'];
        // Update existing profile fields
        // Only update resume_path if a NEW one was uploaded.
        // If we used profile resume, we don't need to change anything.
        $sql = "UPDATE candidates SET date_of_birth = ?, gender = ?, linkedin_profile = ? WHERE id = ?";
        $pdo->prepare($sql)->execute([$dob, $gender, $linkedin, $candidate_id]);
        
        // If a new resume was uploaded, update the record
        if ($resume_path && !$use_profile_resume) {
             $pdo->prepare("UPDATE candidates SET resume_path = ? WHERE id = ?")->execute([$resume_path, $candidate_id]);
        }
    } else {
        // Create new candidate
        $sql = "INSERT INTO candidates (user_id, date_of_birth, gender, linkedin_profile, resume_path) VALUES (?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$user_id, $dob, $gender, $linkedin, $resume_path]);
        $candidate_id = $pdo->lastInsertId();
    }
    
    // 2. Create Application Record
    // Note: Assuming job_id is fixed or passed. For now we skip job_id FK constraint if using placeholder,
    // BUT better validation would require a real job_id. 
    // Let's assume passed via POST or a default for testing.
    $job_id = $_POST['job_id'] ?? 1; // Default to ID 1 if not set
    
    // Check for duplicate application
    $checkApp = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND candidate_id = ?");
    $checkApp->execute([$job_id, $candidate_id]);
    
    // Prepare Snapshot Data
    // Education Snapshot
    $educationSnapshot = [];
    if (isset($_POST['edu_school']) && is_array($_POST['edu_school'])) {
        for ($i = 0; $i < count($_POST['edu_school']); $i++) {
            if (!empty($_POST['edu_school'][$i])) {
                $educationSnapshot[] = [
                    'school' => $_POST['edu_school'][$i],
                    'degree' => $_POST['edu_degree'][$i] ?? '',
                    'start_date' => $_POST['edu_start'][$i] ?? '',
                    'end_date' => $_POST['edu_end'][$i] ?? ''
                ];
            }
        }
    }
    
    // Certificates Snapshot (We can't get paths easily before main loop, but we can store metadata)
    // Actually, we can process files first if we want full path in snapshot, or just rely on the relational table.
    // Ideally snapshot should be self-contained.
    // For simplicity, let's snapshot the Personal + Education + Resume Path here.
    // Document paths will be added to the relational table, and maybe we can update snapshot or just query them historically?
    // Requirement says: "snapshot of the candidate details". 
    // Let's stick to Personal, Contact, Education, Resume.
    
    $snapshotData = [
        'personal' => [
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '', // Assuming these are posted or fetched? Form posts them.
            'dob' => $dob,
            'gender' => $gender,
            'email' => $email,
            'phone' => $phone,
            'bio' => $bio
        ],
        'professional' => [
            'linkedin' => $linkedin,
            'resume_path' => $resume_path
            // Portfolio?
        ],
        'education' => $educationSnapshot,
        'submitted_at' => date('Y-m-d H:i:s')
    ];
    
    $snapshotJson = json_encode($snapshotData);

    if ($checkApp->rowCount() == 0) {
         $cover_letter_text = ''; 
         // Cover letter file handling (if implemented as text, or we save path)
         // Our form has cover_letter file input.
         $cover_letter_path = null;
         if (isset($_FILES['cover_letter']) && $_FILES['cover_letter']['error'] === UPLOAD_ERR_OK) {
            $clDir = __DIR__ . '/../uploads/resumes/'; // Group with resumes
            $clName = 'cl_' . time() . '_' . basename($_FILES['cover_letter']['name']);
            if (move_uploaded_file($_FILES['cover_letter']['tmp_name'], $clDir . $clName)) {
                $cover_letter_path = 'uploads/resumes/' . $clName;
            }
         }
         
         $sqlApp = "INSERT INTO applications (job_id, candidate_id, cover_letter, status) VALUES (?, ?, ?, 'pending')";
         // Storing path in cover_letter column for now (schema says text)
         $pdo->prepare($sqlApp)->execute([$job_id, $candidate_id, $cover_letter_path ?: $bio]); 
    }

    // 3. Process Education History
    // Clear old education to avoid duplicates if re-submitting? Or just append?
    // Let's delete old for simplicity on update, or just insert.
    $pdo->prepare("DELETE FROM candidate_education WHERE candidate_id = ?")->execute([$candidate_id]);
    
    if (isset($_POST['edu_school']) && is_array($_POST['edu_school'])) {
        $stmtEdu = $pdo->prepare("INSERT INTO candidate_education (candidate_id, school_name, qualification, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
        
        for ($i = 0; $i < count($_POST['edu_school']); $i++) {
            $school = $_POST['edu_school'][$i];
            $degree = $_POST['edu_degree'][$i] ?? '';
            $start = $_POST['edu_start'][$i] ?? '';
            $end = $_POST['edu_end'][$i] ?? null;
            
            if (!empty($school)) {
                $stmtEdu->execute([$candidate_id, $school, $degree, $start, $end ?: null]);
            }
        }
    }

    // 4. Process Certificate Uploads
    // Don't delete old Docs, append new ones.
    if (isset($_POST['cert_name']) && is_array($_POST['cert_name'])) {
        $stmtDoc = $pdo->prepare("INSERT INTO candidate_documents (candidate_id, document_name, file_path) VALUES (?, ?, ?)");
        $docDir = __DIR__ . '/../uploads/certificates/';
        if (!is_dir($docDir)) mkdir($docDir, 0777, true);
        
        for ($i = 0; $i < count($_POST['cert_name']); $i++) {
            $name = $_POST['cert_name'][$i];
            
            // File upload logic for array inputs is tricky in PHP: $_FILES['cert_file']['name'][$i]
            if (isset($_FILES['cert_file']['name'][$i]) && $_FILES['cert_file']['error'][$i] === UPLOAD_ERR_OK) {
                 $fName = time() . '_' . $i . '_' . basename($_FILES['cert_file']['name'][$i]);
                 $fPath = $docDir . $fName;
                 
                 if (move_uploaded_file($_FILES['cert_file']['tmp_name'][$i], $fPath)) {
                     $webPath = 'uploads/certificates/' . $fName;
                     $stmtDoc->execute([$candidate_id, $name, $webPath]);
                 }
            }
        }
    }

    // 5. Notifications & Email
    require_once __DIR__ . '/../includes/MailHelper.php';
    require_once __DIR__ . '/../includes/NotificationHelper.php';

    // Get job details for the notification
    $jobStmt = $pdo->prepare("SELECT title FROM job_postings WHERE id = ?");
    $jobStmt->execute([$job_id]);
    $jobTitle = $jobStmt->fetchColumn() ?: 'Unknown Position';

    // Send In-App Notification
    NotificationHelper::create(
        $user_id,
        'application', // Type
        'Application Submitted', // Title
        "Your application for the position of <strong>$jobTitle</strong> has been successfully submitted.", // Message
        "/job-details?id=$job_id" // Action URL
    );

    // Send Email
    // We need the candidate's name. We have $candidate_id, let's query specific fields if not in $_POST?
    // Actually we have $_POST['fullname']? No, register has fullname. Submit usually relies on profile or session.
    // Let's rely on User table for email if not passed, but let's check what we have.
    // In code above: $email = $_POST['email'] ?? '';
    // Let's assume user session has name or we fetch it.
    
    // Fetch user details for email if needed, or use posted email
    if (empty($email)) {
         $uStmt = $pdo->prepare("SELECT email, first_name FROM users WHERE id = ?");
         $uStmt->execute([$user_id]);
         $user = $uStmt->fetch();
         $email = $user['email'];
         $name = $user['first_name'];
    } else {
        $name = "Candidate"; // Fallback if we don't query name
    }

    // Send Candidate Email
    MailHelper::sendApplicationReceived($email, $name, $jobTitle);

    // Send Admin Email
    $adminEmail = get_setting('admin_email', 'admin@hrconnect.com');
    if ($adminEmail) {
        MailHelper::sendAdminNewApplication($adminEmail, $name, $jobTitle);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Application submitted successfully!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Submission Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>
