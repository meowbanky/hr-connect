<?php
require_once __DIR__ . '/config/db.php';

// Configuration
$numberOfCandidates = 20;
$applicationsPerCandidate = 2; // Average

echo "🌱 Seeding Dummy Data...\n";

try {
    // 1. Get Candidate Role ID
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'candidate'");
    $stmt->execute();
    $roleId = $stmt->fetchColumn();
    
    if (!$roleId) {
        die("Error: Candidate role not found. Please run database_schema.sql first.\n");
    }

    // 2. Get Job IDs
    $stmt = $pdo->query("SELECT id FROM job_postings WHERE status = 'published'");
    $jobIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($jobIds)) {
        die("Error: No published jobs found. Please seed jobs first (seed_jobs.php).\n");
    }

    // names for randomization
    $firstNames = ['John', 'Jane', 'Michael', 'Emily', 'David', 'Sarah', 'Chris', 'Anna', 'James', 'Laura', 'Robert', 'Emma', 'William', 'Olivia', 'Daniel', 'Sophia', 'Matthew', 'Ava', 'Joseph', 'Isabella'];
    $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin'];
    $qualifications = ['B.Sc Computer Science', 'M.Sc Engineering', 'Ph.D Physics', 'HND Accounting', 'B.A English', 'MBA', 'B.Eng Electrical'];

    $candidatesCreated = 0;
    $applicationsCreated = 0;

    for ($i = 0; $i < $numberOfCandidates; $i++) {
        // A. Create User
        $fname = $firstNames[array_rand($firstNames)];
        $lname = $lastNames[array_rand($lastNames)];
        $email = strtolower($fname . '.' . $lname . rand(100, 9999) . '@example.com');
        $password = password_hash('password123', PASSWORD_BCRYPT); // Default password
        
        // Check if email exists (simple partial fix for collision)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            continue; // Skip if exists
        }

        $stmt = $pdo->prepare("INSERT INTO users (role_id, email, password_hash, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$roleId, $email, $password, $fname, $lname]);
        $userId = $pdo->lastInsertId();

        // B. Create Candidate Profile
        $exp = rand(1, 15); // 1 to 15 years exp
        $qual = $qualifications[array_rand($qualifications)];
        
        $stmt = $pdo->prepare("INSERT INTO candidates (user_id, years_of_experience, highest_qualification) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $exp, $qual]);
        $candidateId = $pdo->lastInsertId();
        
        $candidatesCreated++;

        // C. Create Applications
        // Apply to random jobs
        $keys = (array)array_rand($jobIds, rand(1, 3)); // Apply to 1-3 jobs
        
        foreach ($keys as $key) {
            $jobId = $jobIds[$key];
            
            // Check for duplicate application
            $stmt = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND candidate_id = ?");
            $stmt->execute([$jobId, $candidateId]);
            if ($stmt->fetch()) continue;
            
            // Random Status
            $statuses = ['pending', 'pending', 'pending', 'reviewed', 'reviewed', 'technical_review', 'shortlisted', 'rejected'];
            $status = $statuses[array_rand($statuses)];
            
            $stmt = $pdo->prepare("INSERT INTO applications (job_id, candidate_id, status) VALUES (?, ?, ?)");
            $stmt->execute([$jobId, $candidateId, $status]);
            $applicationsCreated++;
        }
    }

    echo "✅ Success!\n";
    echo "   - Created $candidatesCreated new candidates.\n";
    echo "   - Created $applicationsCreated new applications.\n";

} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}
?>
