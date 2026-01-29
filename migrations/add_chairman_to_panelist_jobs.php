<?php
require_once __DIR__ . '/../config/db.php';

try {
    echo "Adding is_chairman column to panelist_jobs table...\n";

    $cols = $pdo->query("SHOW COLUMNS FROM panelist_jobs")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('is_chairman', $cols)) {
        $pdo->exec("ALTER TABLE panelist_jobs ADD COLUMN is_chairman TINYINT(1) DEFAULT 0 AFTER status");
        echo "Added is_chairman column.\n";
    } else {
        echo "Column is_chairman already exists.\n";
    }

    echo "Migration complete.\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
