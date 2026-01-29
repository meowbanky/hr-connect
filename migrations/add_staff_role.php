<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Check if 'staff' role exists
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'staff'");
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        $pdo->exec("INSERT INTO roles (name, description) VALUES ('staff', 'Standard Staff Member')");
        echo "Created 'staff' role successfully.\n";
    } else {
        echo "'staff' role already exists.\n";
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
?>
