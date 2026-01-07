<?php
require_once __DIR__ . '/config/db.php';

try {
    echo "Updating notifications table schema...\n";

    // Add 'type' column
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN type VARCHAR(50) DEFAULT 'system' AFTER user_id");
        echo "Added 'type' column.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column") !== false) {
            echo "'type' column already exists.\n";
        } else {
            throw $e;
        }
    }

    // Add 'action_url' column for "View Details" links
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN action_url VARCHAR(255) DEFAULT NULL AFTER message");
        echo "Added 'action_url' column.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column") !== false) {
            echo "'action_url' column already exists.\n";
        } else {
            throw $e;
        }
    }

    echo "Schema update completed successfully.\n";

} catch (Exception $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
    exit(1);
}
?>
