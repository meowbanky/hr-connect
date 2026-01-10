<?php
require_once __DIR__ . '/config/db.php';

try {
    echo "Starting Department Deduplication...\n";

    $pdo->beginTransaction();

    // 1. Identify Duplicates
    // Get all departments ordered by ID
    $stmt = $pdo->query("SELECT id, name FROM departments ORDER BY id ASC");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $seen = [];
    $duplicates = [];

    foreach ($departments as $dept) {
        $name = trim($dept['name']);
        if (isset($seen[$name])) {
            // This is a duplicate
            $masterId = $seen[$name];
            $duplicateId = $dept['id'];
            $duplicates[] = ['master' => $masterId, 'duplicate' => $duplicateId, 'name' => $name];
        } else {
            // This is the first time we see this name, mark as master
            $seen[$name] = $dept['id'];
        }
    }

    echo "Found " . count($duplicates) . " duplicate entries.\n";

    // 2. Resolve Duplicates
    if (!empty($duplicates)) {
        foreach ($duplicates as $dup) {
            $masterId = $dup['master'];
            $dupId = $dup['duplicate'];
            $name = $dup['name'];

            echo "Resolving '$name': Keeping ID $masterId, merging ID $dupId...\n";

            // Update job_postings to point to master ID
            $updateSql = "UPDATE job_postings SET department_id = ? WHERE department_id = ?";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([$masterId, $dupId]);

            // Assuming there might be other tables, but currently only job_postings links to departments.
            // If users or other tables linked, we'd update them here too.

            // Delete the duplicate department
            $delSql = "DELETE FROM departments WHERE id = ?";
            $delStmt = $pdo->prepare($delSql);
            $delStmt->execute([$dupId]);
        }
    }

    // 3. Add Unique Constraint
    // First check if index exists to allow re-running script safely
    $indexCheck = $pdo->query("SHOW INDEX FROM departments WHERE Key_name = 'unique_name'");
    if ($indexCheck->rowCount() == 0) {
        $pdo->exec("ALTER TABLE departments ADD UNIQUE KEY `unique_name` (`name`)");
        echo "Added UNIQUE constraint on 'name' column.\n";
    } else {
        echo "UNIQUE constraint already exists.\n";
    }

    $pdo->commit();
    echo "Deduplication Complete!\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Error: " . $e->getMessage() . "\n");
}
