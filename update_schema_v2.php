<?php
require_once __DIR__ . '/config/db.php';

try {
    // 1. Create candidate_education table
    $sql_edu = "CREATE TABLE IF NOT EXISTS `candidate_education` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `candidate_id` int(11) NOT NULL,
        `school_name` varchar(255) NOT NULL,
        `qualification` varchar(255) NOT NULL,
        `start_date` date NOT NULL,
        `end_date` date DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `candidate_id` (`candidate_id`),
        CONSTRAINT `fk_edu_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql_edu);
    echo "Table 'candidate_education' checked/created successfully.\n";

    // 2. Create candidate_documents table
    $sql_docs = "CREATE TABLE IF NOT EXISTS `candidate_documents` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `candidate_id` int(11) NOT NULL,
        `document_name` varchar(255) NOT NULL,
        `file_path` varchar(255) NOT NULL,
        `uploaded_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `candidate_id` (`candidate_id`),
        CONSTRAINT `fk_docs_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql_docs);
    echo "Table 'candidate_documents' checked/created successfully.\n";

} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
}
?>
