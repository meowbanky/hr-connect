<?php
require_once __DIR__ . '/../config/db.php';

try {
    echo "Adding reset_token and reset_expires to panelists table...\n";

    // Check if column exists
    $check = $pdo->query("SHOW COLUMNS FROM panelists LIKE 'reset_token'");
    if (!$check->fetch()) {
        $pdo->exec("ALTER TABLE panelists ADD COLUMN reset_token VARCHAR(255) NULL AFTER status");
        echo "Added reset_token column.\n";
    } else {
        echo "reset_token column already exists.\n";
    }

    $check2 = $pdo->query("SHOW COLUMNS FROM panelists LIKE 'reset_expires'");
    if (!$check2->fetch()) {
        $pdo->exec("ALTER TABLE panelists ADD COLUMN reset_expires DATETIME NULL AFTER reset_token");
        echo "Added reset_expires column.\n";
    } else {
        echo "reset_expires column already exists.\n";
    }
    
    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
