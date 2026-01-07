<?php
require_once __DIR__ . '/config/db.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM applications LIKE 'profile_snapshot'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE applications ADD COLUMN profile_snapshot JSON DEFAULT NULL AFTER cover_letter");
        echo "Column 'profile_snapshot' added successfully.\n";
    } else {
        echo "Column 'profile_snapshot' already exists.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
