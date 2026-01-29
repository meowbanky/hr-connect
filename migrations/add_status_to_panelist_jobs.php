<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Add status column to panelist_jobs
    $sql = "ALTER TABLE panelist_jobs ADD COLUMN status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending'";
    $pdo->exec($sql);
    echo "Added 'status' column to 'panelist_jobs'.\n";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column 'status' already exists in 'panelist_jobs'.\n";
    } else {
        die("DB Error: " . $e->getMessage() . "\n");
    }
}
?>
