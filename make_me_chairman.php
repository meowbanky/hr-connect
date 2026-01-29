<?php
require_once __DIR__ . '/config/db.php';

$name = 'Abiodun Bankole';

// Get Panelist ID
$stmt = $pdo->prepare("SELECT id FROM panelists WHERE name LIKE ?");
$stmt->execute(['%' . $name . '%']);
$id = $stmt->fetchColumn();

if (!$id) {
    die("User not found.\n");
}

echo "Setting Abiodun Bankole (ID: $id) as Chairman for ALL assigned jobs...\n";

// Update all jobs for this user to be chairman
$stmt = $pdo->prepare("UPDATE panelist_jobs SET is_chairman = 1 WHERE panelist_id = ?");
$stmt->execute([$id]);

echo "Success! checks:\n";

// Verify
$stmt = $pdo->prepare("
    SELECT j.title, pj.is_chairman 
    FROM panelist_jobs pj 
    JOIN job_postings j ON pj.job_id = j.id 
    WHERE pj.panelist_id = ?
");
$stmt->execute([$id]);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($jobs as $job) {
    echo "Job: {$job['title']} - Chairman: {$job['is_chairman']}\n";
}
