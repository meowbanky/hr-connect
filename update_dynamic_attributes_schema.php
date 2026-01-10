<?php
require_once __DIR__ . '/config/db.php';

try {
    echo "Starting Dynamic Attributes Migration...\n";

    // 1. Create employment_types table
    $sql1 = "CREATE TABLE IF NOT EXISTS `employment_types` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sql1);
    echo "Created table 'employment_types'.\n";

    // 2. Populate default employment types
    $defaults = ['Full-time', 'Part-time', 'Contract', 'Internship'];
    $stmt = $pdo->prepare("INSERT IGNORE INTO `employment_types` (name) VALUES (?)");
    foreach ($defaults as $type) {
        $stmt->execute([$type]);
    }
    echo "Populated 'employment_types' with defaults.\n";

    // 3. Add employment_type_id to job_postings if not exists
    // Check if column exists first to avoid error
    $checkCol = $pdo->query("SHOW COLUMNS FROM `job_postings` LIKE 'employment_type_id'");
    if ($checkCol->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `job_postings` ADD COLUMN `employment_type_id` int(11) DEFAULT NULL AFTER `department_id`");
        echo "Added 'employment_type_id' to 'job_postings'.\n";
        
        // Add Foreign Key
        $pdo->exec("ALTER TABLE `job_postings` ADD CONSTRAINT `fk_jobs_employment_type` FOREIGN KEY (`employment_type_id`) REFERENCES `employment_types` (`id`) ON DELETE SET NULL");
        echo "Added Foreign Key constraint.\n";
    }

    // 4. Migrate existing data (ENUM -> FK)
    // Map current ENUM values to IDs
    echo "Migrating existing job data...\n";
    
    // Get all employment types map
    $typesFn = $pdo->query("SELECT id, name FROM employment_types")->fetchAll(PDO::FETCH_KEY_PAIR);
    // name => id
    // Adjust mapping if case differs (enum is 'Full-time', table is 'Full-time')
    
    foreach ($typesFn as $name => $id) {
        // Update jobs where enum matches name
        $updateStmt = $pdo->prepare("UPDATE `job_postings` SET `employment_type_id` = ? WHERE `employment_type` = ?");
        $updateStmt->execute([$id, $name]);
    }
    echo "Data migration complete.\n";

    echo "Migration Successful!\n";

} catch (PDOException $e) {
    die("Migration Failed: " . $e->getMessage() . "\n");
}
