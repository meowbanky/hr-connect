<?php
require_once __DIR__ . '/../config/db.php';

try {
    echo "Creating evaluations table...\n";

    $sql = "CREATE TABLE IF NOT EXISTS evaluations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT NOT NULL,
        panelist_id INT NOT NULL,
        technical_score INT,
        communication_score INT,
        cultural_score INT,
        total_score INT,
        notes TEXT,
        recommendation ENUM('Strong Hire', 'Hire', 'Neutral', 'No Hire') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_eval (application_id, panelist_id),
        FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
        FOREIGN KEY (panelist_id) REFERENCES panelists(id) ON DELETE CASCADE
    )";

    $pdo->exec($sql);
    echo "Evaluations table created successfully.\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
