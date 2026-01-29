<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Check if role exists
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'superadmin'");
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        $pdo->exec("INSERT INTO roles (name, description) VALUES ('superadmin', 'Super Administrator with elevated privileges')");
        echo "Role 'superadmin' created successfully.\n";
    } else {
        echo "Role 'superadmin' already exists.\n";
    }
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
