<?php
require_once __DIR__ . '/../includes/settings.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS staff (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        phone VARCHAR(50),
        dob DATE,
        gender VARCHAR(50),
        
        job_title VARCHAR(100),
        department VARCHAR(100),
        grade_level VARCHAR(50),
        step VARCHAR(50),
        worker_category VARCHAR(100) COMMENT 'Medical Doctors, Nurses, Admin, Others',
        call_type VARCHAR(100) COMMENT 'Doctors, Others',
        
        start_date DATE,
        employment_type VARCHAR(50),
        location VARCHAR(100),
        salary VARCHAR(100) NULL,
        
        emergency_contact_name VARCHAR(255),
        emergency_contact_relationship VARCHAR(100),
        emergency_contact_phone VARCHAR(50),
        
        profile_image VARCHAR(255),
        status VARCHAR(50) DEFAULT 'active',
        
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "Staff table created successfully.\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
?>
