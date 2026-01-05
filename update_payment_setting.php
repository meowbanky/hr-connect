<?php
require_once 'config/db.php';

try {
    // Check if key exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = 'enable_payment'");
    $stmt->execute();
    $exists = $stmt->fetchColumn();

    if (!$exists) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('enable_payment', '0')");
        $stmt->execute();
        echo "Added 'enable_payment' setting (default: 0).<br>";
    } else {
        echo "'enable_payment' setting already exists.<br>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
