<?php
require_once __DIR__ . '/../includes/settings.php';

try {
    // Staff Education Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS staff_education (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id INT NOT NULL,
        school_name VARCHAR(255) NOT NULL,
        qualification VARCHAR(255),
        start_date DATE,
        end_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Staff Documents Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS staff_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id INT NOT NULL,
        document_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "Staff related tables (education, documents) created successfully.\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
?>
