<?php
require_once __DIR__ . '/config/db.php';

echo "Attempting to force add columns...\n";

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL");
    echo "SUCCESS: reset_token column added.\n";
} catch (Exception $e) {
    echo "ERROR adding reset_token: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN reset_expires_at DATETIME NULL");
    echo "SUCCESS: reset_expires_at column added.\n";
} catch (Exception $e) {
    echo "ERROR adding reset_expires_at: " . $e->getMessage() . "\n";
}

echo "Force migration finished.\n";
?>
