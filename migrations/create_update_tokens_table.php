<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS update_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token VARCHAR(64) NOT NULL UNIQUE,
        staff_id INT NOT NULL,
        generated_by INT NOT NULL,
        expires_at DATETIME NOT NULL,
        is_used TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (token),
        INDEX (staff_id)
    )");
    echo "Table 'update_tokens' created successfully.\n";
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
