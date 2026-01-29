<?php
require_once __DIR__ . '/../config/db.php';

try {
    echo "Starting Recruitment Cycles Migration...\n";

    // 1. Create job_templates table
    // It mirrors job_postings structure but for static definitions
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS job_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            department_id INT,
            employment_type_id INT,
            employment_type VARCHAR(50), -- Enum cache
            location VARCHAR(255),
            min_salary DECIMAL(15,2),
            max_salary DECIMAL(15,2),
            salary_range VARCHAR(100),
            description TEXT,
            requirements TEXT,
            experience_level VARCHAR(50),
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
            FOREIGN KEY (employment_type_id) REFERENCES employment_types(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Created job_templates table.\n";

    // 2. Add columns to job_postings if they don't exist
    // job_template_id (FK)
    // cycle_label (e.g. 'Initial Cycle', 'Spring 2026')
    
    // Check if column exists
    $cols = $pdo->query("SHOW COLUMNS FROM job_postings LIKE 'job_template_id'")->fetchAll();
    if (count($cols) == 0) {
        $pdo->exec("ALTER TABLE job_postings ADD COLUMN job_template_id INT AFTER id");
        $pdo->exec("ALTER TABLE job_postings ADD CONSTRAINT fk_jp_template FOREIGN KEY (job_template_id) REFERENCES job_templates(id) ON DELETE SET NULL");
        echo "Added job_template_id to job_postings.\n";
    }

    $cols2 = $pdo->query("SHOW COLUMNS FROM job_postings LIKE 'cycle_label'")->fetchAll();
    if (count($cols2) == 0) {
        $pdo->exec("ALTER TABLE job_postings ADD COLUMN cycle_label VARCHAR(100) AFTER job_template_id");
        echo "Added cycle_label to job_postings.\n";
    }

    // 3. Migrate Data: Convert existing Postings to Templates
    // For each existing job posting that doesn't have a template_id, create one.
    $stmt = $pdo->query("SELECT * FROM job_postings WHERE job_template_id IS NULL");
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($jobs) > 0) {
        echo "Found " . count($jobs) . " existing jobs to migrate.\n";
        
        $insertTemplate = $pdo->prepare("
            INSERT INTO job_templates (
                title, department_id, employment_type_id, employment_type,
                location, min_salary, max_salary, salary_range,
                description, requirements, experience_level,
                created_by, created_at
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?
            )
        ");

        $updateJob = $pdo->prepare("UPDATE job_postings SET job_template_id = ?, cycle_label = ? WHERE id = ?");

        foreach ($jobs as $job) {
            // Create Template
            $insertTemplate->execute([
                $job['title'], $job['department_id'], $job['employment_type_id'], $job['employment_type'],
                $job['location'], $job['min_salary'], $job['max_salary'], $job['salary_range'],
                $job['description'], $job['requirements'], $job['experience_level'],
                $job['created_by'], $job['created_at']
            ]);
            
            $templateId = $pdo->lastInsertId();
            
            // Generate Cycle Label (e.g. '2026 Recruitment', or just 'Initial Cycle')
            $year = date('Y', strtotime($job['created_at']));
            $label = $year . " Recruitment Cycle";

            // Link back
            $updateJob->execute([$templateId, $label, $job['id']]);
            
            echo "Migrated Job #[{$job['id']}] '{$job['title']}' -> Template #[{$templateId}]\n";
        }
    } else {
        echo "No unlinked jobs found.\n";
    }

    echo "Migration Completed Successfully.\n";

} catch (PDOException $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
}
?>
