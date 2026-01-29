<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Add invite_token column to panelist_jobs
    $sql = "ALTER TABLE panelist_jobs ADD COLUMN invite_token VARCHAR(64) NULL";
    $pdo->exec($sql);
    echo "Added 'invite_token' column to 'panelist_jobs'.\n";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column 'invite_token' already exists in 'panelist_jobs'.\n";
    } else {
        die("DB Error: " . $e->getMessage() . "\n");
    }
}
?>
