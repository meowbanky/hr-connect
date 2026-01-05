<?php
require_once __DIR__ . '/config/db.php';

try {
    echo "<h1>Database Debug</h1>";
    
    // Check Jobs Count
    $stmt = $pdo->query("SELECT COUNT(*) FROM job_postings");
    $count = $stmt->fetchColumn();
    echo "<p>Total Jobs in DB: <strong>$count</strong></p>";

    if ($count > 0) {
        $stmt = $pdo->query("SELECT * FROM job_postings LIMIT 3");
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>" . print_r($jobs, true) . "</pre>";
    } else {
        echo "<p>Database is empty.</p>";
    }
    
    // Check Schema
    echo "<h3>Schema Columns</h3>";
    $stmt = $pdo->query("DESCRIBE job_postings");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($columns, true) . "</pre>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
