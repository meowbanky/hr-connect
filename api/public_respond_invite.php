<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

$token = $_GET['token'] ?? '';
$action = $_GET['action'] ?? '';

$validActions = ['accept' => 'accepted', 'decline' => 'declined'];
$newStatus = $validActions[$action] ?? null;

$message = '';
$messageType = 'error'; // success, error

if (!$token || !$newStatus) {
    $message = "Invalid link parameters.";
} else {
    try {
        // Find assignment by token
        $stmt = $pdo->prepare("SELECT pj.id, pj.status, j.title, p.name 
                               FROM panelist_jobs pj
                               JOIN job_postings j ON pj.job_id = j.id
                               JOIN panelists p ON pj.panelist_id = p.id
                               WHERE pj.invite_token = ?");
        $stmt->execute([$token]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$assignment) {
            $message = "Invalid or expired invitation link.";
        } else {
            // Update Status
            $upd = $pdo->prepare("UPDATE panelist_jobs SET status = ?, invite_token = NULL WHERE id = ?");
            // Note: We NULL the token to prevent re-use? 
            // Or keep it? If we NULL it, they can't change their mind via email.
            // Let's NULL it to prevent spamming status updates, or keep valid?
            // Safer to invalidate token after use or simpler to just update status.
            // Decision: Let's invalidate token to ensure one-time use logic mostly, or actually, 
            // if they decline by mistake, they might want to accept?
            // Actually, usually email links should remain valid for status toggling until deadline?
            // The prompt implies "accept or decline", usually a one-time decision. 
            // Let's Keep token but update status.
            
            $upd = $pdo->prepare("UPDATE panelist_jobs SET status = ? WHERE id = ?");
            $upd->execute([$newStatus, $assignment['id']]);
            
            $message = "You have successfully <strong>" . strtoupper($action) . "ED</strong> the invitation for <strong>" . htmlspecialchars($assignment['title']) . "</strong>.";
            $messageType = 'success';
        }

    } catch (PDOException $e) {
        $message = "Database error occurred.";
        error_log($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Response</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full text-center border border-slate-100">
        <?php if ($messageType === 'success'): ?>
            <div class="size-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
        <?php else: ?>
            <div class="size-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6">
                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </div>
        <?php endif; ?>
        
        <h1 class="text-2xl font-bold mb-2 text-slate-800"><?php echo ($messageType === 'success') ? 'Response Recorded' : 'Action Failed'; ?></h1>
        <p class="text-slate-600 mb-8 leading-relaxed"><?php echo $message; ?></p>
        
        <a href="../panelist/login.php" class="inline-block w-full bg-[#5045e8] hover:bg-[#3f36c5] text-white font-bold py-3 px-6 rounded-xl transition-colors">
            Go to Panelist Portal
        </a>
    </div>
</body>
</html>
