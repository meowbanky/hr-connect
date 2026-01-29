<?php
require_once __DIR__ . '/config/db.php';
try {
    $stmt = $pdo->query("SELECT * FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Roles:\n";
    print_r($roles);

    $stmt2 = $pdo->query("SELECT id, email, role_id FROM users LIMIT 5");
    $users = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo "Users:\n";
    print_r($users);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
