<?php
require_once __DIR__ . '/config/db.php';

try {
    // Check if role 'admin' exists
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'admin'");
    $stmt->execute();
    $role = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$role) {
        $pdo->exec("INSERT INTO roles (name, description) VALUES ('admin', 'System Administrator')");
        $roleId = $pdo->lastInsertId();
        echo "Created 'admin' role.<br>";
    } else {
        $roleId = $role['id'];
    }

    // Check if admin user exists
    $email = 'admin@company.com';
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo "Admin user ($email) already exists.<br>";
    } else {
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (role_id, email, password_hash, first_name, last_name, is_active) VALUES (?, ?, ?, 'System', 'Admin', 1)");
        $stmt->execute([$roleId, $email, $password]);
        echo "Created admin user: $email / password123<br>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
