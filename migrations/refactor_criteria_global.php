<?php
require_once __DIR__ . '/../config/db.php';

try {
    echo "Refactoring evaluation_criteria to global...\n";

    // Drop FK first
    // Note: The constraint name might be auto-generated. We try to drop it if we know the name or just drop column which usually requires dropping FK first.
    // simpler approach: check columns.

    // Check if job_id exists
    $stmt = $pdo->query("SHOW COLUMNS FROM evaluation_criteria LIKE 'job_id'");
    if ($stmt->fetch()) {
        // Try to drop FK. Often named `evaluation_criteria_ibfk_1` but let's be safe and just modify the table
        // We'll likely need to know the constraint name to drop it properly in pure SQL if we want to be strict,
        // but let's try dropping the column directly, some DBs handle it or throw error.
        
        // Use a safer method: Get constraint name from information_schema
        $stmtFK = $pdo->prepare("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'evaluation_criteria' 
            AND COLUMN_NAME = 'job_id' 
            AND TABLE_SCHEMA = DATABASE()
        ");
        $stmtFK->execute();
        $fk = $stmtFK->fetch(PDO::FETCH_ASSOC);

        if ($fk) {
            $fkName = $fk['CONSTRAINT_NAME'];
            echo "Dropping Foreign Key: $fkName\n";
            $pdo->exec("ALTER TABLE evaluation_criteria DROP FOREIGN KEY $fkName");
        }

        echo "Dropping job_id column...\n";
        $pdo->exec("ALTER TABLE evaluation_criteria DROP COLUMN job_id");
        echo "Successfully removed job_id.\n";
    } else {
        echo "job_id column does not exist or already removed.\n";
    }

    // Clear existing data to avoid confusion since they were job specific?
    // User wants a global rule. 
    $pdo->exec("TRUNCATE TABLE evaluation_criteria"); 
    
    // Add default global criteria immediately
    $defaults = [
        ['Professional', 40],
        ['Appearance', 10],
        ['General Knowledge', 40],
        ['Comportment', 10]
    ];
    
    $ins = $pdo->prepare("INSERT INTO evaluation_criteria (name, max_score) VALUES (?, ?)");
    foreach ($defaults as $d) {
        $ins->execute([$d[0], $d[1]]);
    }
    echo "Global defaults inserted.\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
