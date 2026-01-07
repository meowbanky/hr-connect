<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo->exec("ALTER TABLE notifications ADD COLUMN is_archived TINYINT(1) DEFAULT 0");
    echo "Column added successfully.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
