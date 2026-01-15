<?php
require_once __DIR__ . '/config/db.php';

try {
    echo "Checking schema...\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('reset_token', $columns)) {
        echo "Adding reset_token column...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL");
    } else {
        echo "reset_token already exists.\n";
    }

    if (!in_array('reset_expires_at', $columns)) {
        echo "Adding reset_expires_at column...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN reset_expires_at DATETIME NULL");
    } else {
        echo "reset_expires_at already exists.\n";
    }
    
    echo "Migration complete.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
