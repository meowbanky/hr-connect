<?php
require_once __DIR__ . '/config/db.php';

echo "Migrating interviews table...\n";

try {
    $sql = "CREATE TABLE IF NOT EXISTS interviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT NOT NULL,
        interview_date DATE NOT NULL,
        interview_time TIME NOT NULL,
        venue_name VARCHAR(255) NOT NULL,
        venue_address TEXT NOT NULL,
        venue_link TEXT,
        venue_lat DECIMAL(10, 8),
        venue_lng DECIMAL(10, 8),
        meet_link VARCHAR(255),
        status ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
        check_in_time DATETIME NULL,
        check_in_lat DECIMAL(10, 8) NULL,
        check_in_lng DECIMAL(10, 8) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "SUCCESS: interviews table created/verified.\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
