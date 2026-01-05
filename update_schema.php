<?php
require_once __DIR__ . '/config/db.php';

try {
    echo "Applying schema updates...\n";

    // Add experience_level
    try {
        $pdo->exec("ALTER TABLE job_postings ADD COLUMN experience_level ENUM('Entry Level', 'Mid Level', 'Senior Level', 'Director') DEFAULT 'Mid Level'");
        echo "Added experience_level column.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column") === false) {
             echo "Info: experience_level column might already exist or error: " . $e->getMessage() . "\n";
        }
    }

    // Add min_salary and max_salary
    try {
        $pdo->exec("ALTER TABLE job_postings ADD COLUMN min_salary DECIMAL(10,2) DEFAULT NULL");
        echo "Added min_salary column.\n";
    } catch (PDOException $e) {
         // Ignore if exists
    }

    try {
        $pdo->exec("ALTER TABLE job_postings ADD COLUMN max_salary DECIMAL(10,2) DEFAULT NULL");
        echo "Added max_salary column.\n";
    } catch (PDOException $e) {
        // Ignore if exists
    }
    
    echo "Schema update complete.\n";

} catch (PDOException $e) {
    echo "Schema update failed: " . $e->getMessage();
}
