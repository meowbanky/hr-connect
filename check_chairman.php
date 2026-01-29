<?php
require_once __DIR__ . '/config/db.php';

$name = 'Abiodun Bankole';
$stmt = $pdo->prepare("SELECT id FROM panelists WHERE name LIKE ?");
$stmt->execute(['%' . $name . '%']);
$panelist = $stmt->fetch();

if (!$panelist) {
    echo "Panelist not found.\n";
    exit;
}

echo "Panelist ID: " . $panelist['id'] . "\n";

$stmt = $pdo->prepare("
    SELECT pj.job_id, j.title, pj.is_chairman, pj.status 
    FROM panelist_jobs pj 
    JOIN job_postings j ON pj.job_id = j.id 
    WHERE pj.panelist_id = ?
");
$stmt->execute([$panelist['id']]);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($jobs as $job) {
    echo "Job: {$job['title']} (ID: {$job['job_id']}) - Chairman: {$job['is_chairman']} - Status: {$job['status']}\n";
}
