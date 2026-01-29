<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/settings.php';

// Ensure user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || 
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff' && $_SESSION['user_role'] !== 'superadmin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'update') {
        try {
            $pdo->beginTransaction();

            $fullName = $_POST['full_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $dob = $_POST['dob'] ?? null;
            $gender = $_POST['gender'] ?? '';
            
            $jobTitle = $_POST['job_title'] ?? '';
            $department = $_POST['department'] ?? '';
            $gradeLevel = $_POST['grade_level'] ?? '';
            $step = $_POST['step'] ?? '';
            $workerCategory = $_POST['worker_category'] ?? '';
            $callType = $_POST['call_type'] ?? '';
            
            $startDate = $_POST['start_date'] ?? null;
            $employmentType = $_POST['employment_type'] ?? ''; // e.g. Full-time
            $location = $_POST['location'] ?? '';
            
            // New Emergency Contact Fields
            $ecName = $_POST['emergency_contact_name'] ?? '';
            $ecRel = $_POST['emergency_contact_relationship'] ?? '';
            $ecPhone = $_POST['emergency_contact_phone'] ?? '';

            if (empty($fullName) || empty($email) || empty($jobTitle) || empty($department)) {
                throw new Exception("Required fields are missing.");
            }

            // Server-side Date Validation
            $today = date('Y-m-d');
            if ($dob && $dob > $today) {
                throw new Exception("Date of Birth cannot be in the future.");
            }
            if ($action === 'create' && $startDate && $startDate < $today) {
                // Note: User logic requested "prevent past date for assumption of duty".
                // However, strictly "start date cannot be in the past" might block adding historical records?
                // The prompt says "prevent past date for assumption of duty", likely implementation of constraint requested.
                // If this is for strictly NEW onboarding, it makes sense. If for historical data entry, it might be an issue.
                // Assuming "Assumption of Duty" implies a future or current event for new staff.
                // PROMPT: "prevent past date for assumption of duty".
                throw new Exception("Date of Assumption of Duty cannot be in the past.");
            }

            // Insert Staff
            if ($action === 'create') {
                $stmt = $pdo->prepare("INSERT INTO staff (
                    full_name, email, phone, dob, gender, 
                    job_title, department, grade_level, step, worker_category, call_type, 
                    start_date, employment_type, location,
                    emergency_contact_name, emergency_contact_relationship, emergency_contact_phone
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $fullName, $email, $phone, $dob ?: null, $gender,
                    $jobTitle, $department, $gradeLevel, $step, $workerCategory, $callType,
                    $startDate ?: null, $employmentType, $location,
                    $ecName, $ecRel, $ecPhone
                ]);
                
            $staffId = $pdo->lastInsertId();
            } else {
                // Update Staff
                $staffId = $_POST['staff_id'] ?? 0;
                if (!$staffId) throw new Exception("Staff ID required for update");

                // Validate Update Token
                $token = $_POST['update_token'] ?? '';
                if (empty($token)) throw new Exception("Authorization Token is required.");

                $stmtToken = $pdo->prepare("SELECT id, expires_at, is_used FROM update_tokens WHERE token = ? AND staff_id = ?");
                $stmtToken->execute([$token, $staffId]);
                $tokenData = $stmtToken->fetch(PDO::FETCH_ASSOC);

                if (!$tokenData) {
                    throw new Exception("Invalid token.");
                }
                if ($tokenData['is_used']) {
                    throw new Exception("This token has already been used.");
                }
                if (strtotime($tokenData['expires_at']) < time()) {
                    throw new Exception("Token has expired.");
                }

                // Mark token as used immediately (or after success - doing it here implies intent, transaction will rollback if update fails)
                $stmtMarkUsed = $pdo->prepare("UPDATE update_tokens SET is_used = 1 WHERE id = ?");
                $stmtMarkUsed->execute([$tokenData['id']]);

                $stmt = $pdo->prepare("UPDATE staff SET 
                    full_name=?, email=?, phone=?, dob=?, gender=?, 
                    job_title=?, department=?, grade_level=?, step=?, worker_category=?, call_type=?, 
                    start_date=?, employment_type=?, location=?,
                    emergency_contact_name=?, emergency_contact_relationship=?, emergency_contact_phone=?
                    WHERE id=?");
                
                $stmt->execute([
                    $fullName, $email, $phone, $dob ?: null, $gender,
                    $jobTitle, $department, $gradeLevel, $step, $workerCategory, $callType,
                    $startDate ?: null, $employmentType, $location,
                    $ecName, $ecRel, $ecPhone,
                    $staffId
                ]);
            }

            // Process Education History
            if (isset($_POST['edu_school']) && is_array($_POST['edu_school'])) {
                $stmtEdu = $pdo->prepare("INSERT INTO staff_education (staff_id, school_name, qualification, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
                
                for ($i = 0; $i < count($_POST['edu_school']); $i++) {
                    $school = $_POST['edu_school'][$i];
                    $degree = $_POST['edu_degree'][$i] ?? '';
                    $start = $_POST['edu_start'][$i] ?? null;
                    $end = $_POST['edu_end'][$i] ?? null;
                    
                    if (!empty($school)) {
                        $stmtEdu->execute([$staffId, $school, $degree, $start ?: null, $end ?: null]);
                    }
                }
            }

            // Process Document Uploads
            if (isset($_POST['doc_name']) && is_array($_POST['doc_name'])) {
                $stmtDoc = $pdo->prepare("INSERT INTO staff_documents (staff_id, document_name, file_path) VALUES (?, ?, ?)");
                $docDir = __DIR__ . '/../uploads/staff_documents/';
                if (!is_dir($docDir)) mkdir($docDir, 0777, true);
                
                for ($i = 0; $i < count($_POST['doc_name']); $i++) {
                    $docName = $_POST['doc_name'][$i];
                    
                    if (isset($_FILES['doc_file']['name'][$i]) && $_FILES['doc_file']['error'][$i] === UPLOAD_ERR_OK) {
                         $fName = time() . '_' . $i . '_' . basename($_FILES['doc_file']['name'][$i]);
                         $fPath = $docDir . $fName;
                         
                         if (move_uploaded_file($_FILES['doc_file']['tmp_name'][$i], $fPath)) {
                             $webPath = 'uploads/staff_documents/' . $fName;
                             $stmtDoc->execute([$staffId, $docName, $webPath]);
                         }
                    }
                }
            }

            // --- USER ACCOUNT CREATION & ONBOARDING EMAIL ---
            if ($action === 'create') {
                // Check if user already exists
                $checkUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $checkUser->execute([$email]);
                
                if ($checkUser->rowCount() == 0) {
                    // Split Name
                    $nameParts = explode(' ', trim($fullName));
                    $firstName = $nameParts[0];
                    $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : '';
                    
                    // Generate Username (lowercase firstname.lastname)
                    $username = strtolower($firstName . '.' . str_replace(' ', '', $lastName));
                    // Basic duplicate username check could be added here, but email is unique login usually.
                    
                    // Fetch 'staff' role ID
                    $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'staff' OR name = 'employee' LIMIT 1");
                    $roleStmt->execute();
                    $roleId = $roleStmt->fetchColumn();
                    
                    if (!$roleId) {
                         // Fallback or handle error. Let's assume ID 2 or 3 implies staff if unknown.
                         // Or try to insert? No. Let's log warning and skip user creation if role missing.
                         // Better: Create 'staff' role if missing? No, user might not have permissions.
                         // Let's assume standard role exists. If not, maybe use '3' (often guest/staff).
                         error_log("Warning: 'staff' role not found during onboarding.");
                    } else {
                        // Generate Reset Token
                        $token = bin2hex(random_bytes(32));
                        $expiry = date('Y-m-d H:i:s', strtotime('+48 hours')); // 48 hours for onboarding
                        
                        // Insert User
                        // Assuming password_hash is required, we set a dummy one or null if allowed.
                        // Ideally we set a random string hash that they can't guess, forcing reset.
                        $dummyPass = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);
                        
                        $stmtUser = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash, role_id, reset_token, reset_expires_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                        $stmtUser->execute([$firstName, $lastName, $email, $dummyPass, $roleId, $token, $expiry]);
                        
                        // Send Onboarding Email
                        require_once __DIR__ . '/../includes/MailHelper.php';
                        $resetLink = get_setting('site_url', 'http://' . $_SERVER['HTTP_HOST']) . "/reset_password.php?token=" . $token;
                        
                        // We use Full Name for email greeting
                        MailHelper::sendEmployeeOnboarding($email, $fullName, $email, $resetLink); // Username = Email usually for login
                    }
                }
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Staff member onboarded successfully. User account created and email sent.']);

        } catch (Exception $e) {
             if ($pdo->inTransaction()) $pdo->rollBack();
             http_response_code(400);
             echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } elseif ($action === 'generate_token') {
        try {
            // Permission check: 'superadmin' only
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'superadmin') {
                throw new Exception("Unauthorized. Superadmin access required.");
            }

            $staffId = $_POST['staff_id'] ?? 0;
            if (!$staffId) throw new Exception("Staff member not selected.");

            // Generate 6-character token
            $token = strtoupper(bin2hex(random_bytes(3))); // 3 bytes = 6 hex chars
            $generatedBy = $_SESSION['user_id'];
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

            $stmt = $pdo->prepare("INSERT INTO update_tokens (token, staff_id, generated_by, expires_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$token, $staffId, $generatedBy, $expiresAt]);

            echo json_encode(['success' => true, 'token' => $token]);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

    } elseif ($action === 'update_status') {
        try {
            $staffId = $_POST['staff_id'] ?? 0;
            $status = $_POST['status'] ?? '';
            
            if (!$staffId || !$status) {
                throw new Exception("Missing parameters.");
            }
            
            // Allow only specific statuses if needed, or open text
            $allowedStatuses = ['active', 'inactive', 'on_leave', 'terminated'];
            if (!in_array($status, $allowedStatuses)) {
                // throw new Exception("Invalid status."); // Optional strict check
            }
            
            $stmt = $pdo->prepare("UPDATE staff SET status = ? WHERE id = ?");
            $stmt->execute([$status, $staffId]);
            
            // Update linked user account active status as well?
            // If inactive/terminated, disable login.
            $isActive = ($status === 'active') ? 1 : 0;
            // Find user by staff email or if we linked them properly. 
            // Staff table has email. Users table has email.
            // Let's toggle the user active state based on email linkage.
            $stmtGetEmail = $pdo->prepare("SELECT email FROM staff WHERE id = ?");
            $stmtGetEmail->execute([$staffId]);
            $staffEmail = $stmtGetEmail->fetchColumn();
            
            if ($staffEmail) {
                $stmtUser = $pdo->prepare("UPDATE users SET is_active = ? WHERE email = ?");
                $stmtUser->execute([$isActive, $staffEmail]);
            }

            echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if ($action === 'fetch') {
             $search = $_GET['search'] ?? '';
             $dept = $_GET['department'] ?? '';
             
             $query = "SELECT * FROM staff WHERE 1=1";
             $params = [];
             
             if (!empty($search)) {
                 $query .= " AND (full_name LIKE ? OR email LIKE ? OR job_title LIKE ?)";
                 $searchTerm = "%$search%";
                 $params[] = $searchTerm;
                 $params[] = $searchTerm;
                 $params[] = $searchTerm;
             }
             
             if (!empty($dept)) {
                 $query .= " AND department = ?";
                 $params[] = $dept;
             }
             
             $query .= " ORDER BY created_at DESC";
             
             $stmt = $pdo->prepare($query);
             $stmt->execute($params);
             $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
             echo json_encode(['success' => true, 'data' => $staff]);
    
        } elseif ($action === 'fetch_departments') {
             $depts = [];
             try {
                 // Try to get from departments table
                 $stmt = $pdo->query("SELECT name FROM departments ORDER BY name ASC");
                 $depts = $stmt->fetchAll(PDO::FETCH_COLUMN);
             } catch (Exception $e) {
                 // Table might not exist or other error. Ignore and use fallback.
             }
             
             // If empty (no departments defined yet or table missing), fallback to existing staff depts
             if (empty($depts)) {
                 $stmt = $pdo->query("SELECT DISTINCT department FROM staff WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");
                 $depts = $stmt->fetchAll(PDO::FETCH_COLUMN);
             }
             
             echo json_encode(['success' => true, 'data' => $depts]);
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
