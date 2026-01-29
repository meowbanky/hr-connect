<?php
require_once __DIR__ . '/../config/db.php';

try {
    echo "Setting up Distributed Evaluation System...\n";

    // 1. Create job_criteria_assignments table
    // Maps a specific global criteria to a specific panelist for a specific job
    $sqlAssignments = "CREATE TABLE IF NOT EXISTS job_criteria_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        panelist_id INT NOT NULL,
        criteria_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_assignment (job_id, panelist_id, criteria_id),
        FOREIGN KEY (job_id) REFERENCES job_postings(id) ON DELETE CASCADE,
        FOREIGN KEY (panelist_id) REFERENCES panelists(id) ON DELETE CASCADE,
        FOREIGN KEY (criteria_id) REFERENCES evaluation_criteria(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlAssignments);
    echo "job_criteria_assignments table created.\n";

    // 2. Update evaluations table to support Chairman Approval
    // Check if columns exist first to avoid errors
    $cols = $pdo->query("SHOW COLUMNS FROM evaluations")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('is_approved', $cols)) {
        $pdo->exec("ALTER TABLE evaluations ADD COLUMN is_approved TINYINT(1) DEFAULT 0 AFTER recommendation");
        echo "Added is_approved column.\n";
    }
    
    if (!in_array('chairman_notes', $cols)) {
        $pdo->exec("ALTER TABLE evaluations ADD COLUMN chairman_notes TEXT AFTER is_approved");
        echo "Added chairman_notes column.\n";
    }

    if (!in_array('original_total_score', $cols)) {
        $pdo->exec("ALTER TABLE evaluations ADD COLUMN original_total_score INT DEFAULT NULL AFTER total_score");
        echo "Added original_total_score column.\n";
    }

    echo "Migration complete.\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
