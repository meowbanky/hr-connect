<?php
require_once __DIR__ . '/config/db.php';

try {
    // Check if 'technical_review' is already in the ENUM
    $stmt = $pdo->query("SHOW COLUMNS FROM applications LIKE 'status'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        $type = $row['Type']; // e.g., enum('pending','reviewed',...)
        
        if (strpos($type, "'technical_review'") === false) {
            // Add 'technical_review' to the enum
            // Current list based on schema: 'pending','reviewed','shortlisted','interviewed','offered','hired','rejected'
            // We want to insert it optimally, maybe after 'reviewed' or 'shortlisted'
            
            $newDefinition = "ENUM('pending','reviewed','technical_review','shortlisted','interviewed','offered','hired','rejected')";
            
            $pdo->exec("ALTER TABLE applications MODIFY COLUMN status $newDefinition DEFAULT 'pending'");
            echo "Successfully updated 'status' column to include 'technical_review'.\n";
        } else {
            echo "'status' column already includes 'technical_review'.\n";
        }
    } else {
        echo "Could not find 'status' column.\n";
    }

} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
}
?>
