<?php
require_once __DIR__ . '/config/db.php';
$stmt = $pdo->query("DESCRIBE notifications");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo $col['Field'] . "\n";
}
?>
