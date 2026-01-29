<?php
require_once __DIR__ . '/../config/db.php';

try {
    echo "Creating evaluation_criteria and evaluation_scores tables...\n";

    // 1. Create evaluation_criteria table
    $sqlCriteria = "CREATE TABLE IF NOT EXISTS evaluation_criteria (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        max_score INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (job_id) REFERENCES job_postings(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlCriteria);
    echo "evaluation_criteria table created successfully.\n";

    // 2. Create evaluation_scores table (to store actual scores for each criteria)
    $sqlScores = "CREATE TABLE IF NOT EXISTS evaluation_scores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        evaluation_id INT NOT NULL,
        criteria_id INT NOT NULL,
        score INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE,
        FOREIGN KEY (criteria_id) REFERENCES evaluation_criteria(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlScores);
    echo "evaluation_scores table created successfully.\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
