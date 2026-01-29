<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

// Auth Check
if (!isset($_SESSION['panelist_id'])) {
    header("Location: login.php");
    exit;
}

$appId = $_GET['application_id'] ?? null;

if (!$appId) {
    die("Invalid Application ID");
}

// Fetch Candidate & Job Details
$stmt = $pdo->prepare("
    SELECT a.*, j.title as job_title, j.id as job_id,
           c.resume_path,
           u.first_name, u.last_name, u.email, u.profile_image,
           i.interview_date, i.interview_time
    FROM applications a 
    JOIN job_postings j ON a.job_id = j.id
    LEFT JOIN candidates c ON a.candidate_id = c.id
    LEFT JOIN users u ON c.user_id = u.id
    LEFT JOIN interviews i ON a.id = i.application_id
    WHERE a.id = ?
");
$stmt->execute([$appId]);
$candidate = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$candidate) {
    die("Candidate not found.");
}

$jobId = $candidate['job_id'];
$cFull = trim($candidate['first_name'] . ' ' . $candidate['last_name']);

// Fetch All Evaluations
$evalStmt = $pdo->prepare("
    SELECT e.*, p.name as panelist_name 
    FROM evaluations e 
    JOIN panelists p ON e.panelist_id = p.id 
    WHERE e.application_id = ?
");
$evalStmt->execute([$appId]);
$evaluations = $evalStmt->fetchAll(PDO::FETCH_ASSOC);

$numEvaluators = count($evaluations);

if ($numEvaluators === 0) {
    die("No evaluations found.");
}

// Fetch All Scores
$scoreStmt = $pdo->prepare("
    SELECT es.* 
    FROM evaluation_scores es
    JOIN evaluations e ON es.evaluation_id = e.id
    WHERE e.application_id = ?
");
$scoreStmt->execute([$appId]);
$allScores = $scoreStmt->fetchAll(PDO::FETCH_ASSOC);

$scoresByCriteria = [];
foreach ($allScores as $s) {
    $cid = $s['criteria_id'];
    if (!isset($scoresByCriteria[$cid])) $scoresByCriteria[$cid] = 0;
    $scoresByCriteria[$cid] += $s['score'];
}

// Fetch Criteria Details
// Refactored to use global criteria - no longer joining job_criteria_assignments
$criteriaStmt = $pdo->prepare("
    SELECT * 
    FROM evaluation_criteria
    ORDER BY id ASC
");
$criteriaStmt->execute();
$criteriaList = $criteriaStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Stats
$totalPossible = 0;
$totalEarned = 0;
foreach ($criteriaList as $c) {
    $max = $c['max_score'];
    $score = $scoresByCriteria[$c['id']] ?? 0;
    
    $totalPossible += $max;
    $totalEarned += $score;
}
$percentage = $totalPossible > 0 ? round(($totalEarned / $totalPossible) * 100) : 0;

$recColorMap = [
    'Strong Hire' => 'emerald',
    'Hire'        => 'emerald',
    'Hold'        => 'amber',
    'No Hire'     => 'red',
    'Reject'      => 'red',
];

$recIconMap = [
    'Strong Hire' => 'check_circle',
    'Hire'        => 'check_circle',
    'Hold'        => 'pause_circle',
    'No Hire'     => 'cancel',
    'Reject'      => 'cancel',
];

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Evaluation Review - <?php echo htmlspecialchars($cFull); ?></title>
    <link rel="icon" href="../assets/images/favicon.svg" type="image/svg+xml">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#3e34ad",
                        "primary-100": "#e0e7ff",
                        "background-light": "#f9fafb",
                        "background-dark": "#1d283a",
                        "slate-content": "#EBEFF3",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            display: inline-block;
            line-height: 1;
            text-transform: none;
            letter-spacing: normal;
            word-wrap: normal;
            white-space: nowrap;
            direction: ltr;
        }
        body { font-family: 'Inter', sans-serif; }
        .circular-progress {
            /* Background moved to inline style */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .circular-progress-inner {
            background-color: white;
            border-radius: 50%;
            width: 85%;
            height: 85%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .dark .circular-progress-inner {
            background-color: #1d283a; 
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 font-display">
<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/panelist_sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-y-auto">
        <!-- Top Nav -->
        <header class="h-16 border-b border-[#eaeaf1] dark:border-slate-700 bg-white/80 dark:bg-background-dark/80 backdrop-blur-md px-4 md:px-8 flex items-center justify-between sticky top-0 z-10">
            <div class="flex items-center gap-4">
                 <!-- Mobile Toggle -->
                <button class="md:hidden p-2 -ml-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg" onclick="toggleSidebar()">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <a href="candidates.php" class="text-[#5d5d89] text-sm hover:text-primary transition-colors hidden md:block">Candidates</a>
                <span class="material-symbols-outlined text-slate-300 text-sm hidden md:block">chevron_right</span>
                <span class="text-sm font-bold">Evaluation Review</span>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex gap-2">
                    <button onclick="window.print()" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-xs font-bold rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-200 transition-colors flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">print</span>
                        Print
                    </button>
                </div>
            </div>
        </header>

        <div class="p-8 max-w-6xl mx-auto w-full space-y-8">
            <!-- Page Heading -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 border-b border-slate-200 dark:border-slate-800 pb-8">
                <div class="flex gap-6 items-center">
                    <div class="size-20 rounded-2xl bg-slate-200 overflow-hidden bg-cover bg-center border-4 border-white dark:border-slate-700 shadow-xl flex items-center justify-center">
                         <?php if($candidate['profile_image']): ?>
                             <img src="<?php echo htmlspecialchars($candidate['profile_image']); ?>" class="w-full h-full object-cover">
                         <?php else: ?>
                             <span class="text-3xl font-bold text-slate-400"><?php echo strtoupper(substr($candidate['first_name'],0,1)); ?></span>
                         <?php endif; ?>
                    </div>
                    <div class="flex flex-col">
                        <h2 class="text-4xl font-black tracking-tight"><?php echo htmlspecialchars($cFull); ?></h2>
                        <div class="flex items-center gap-2 text-[#5d5d89] dark:text-slate-400 mt-1">
                            <span class="font-medium"><?php echo htmlspecialchars($candidate['job_title']); ?></span>
                            <span class="size-1 bg-slate-300 rounded-full"></span>
                            <span class="text-sm">Interviewed <?php echo date('M d, Y', strtotime($candidate['interview_date'])); ?></span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-2">
                    <span class="text-[10px] uppercase tracking-widest font-bold text-slate-400">Evaluations Completed</span>
                    <div class="flex items-center gap-2 px-6 py-3 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl">
                        <span class="material-symbols-outlined">group</span>
                        <span class="text-lg font-black"><?php echo $numEvaluators; ?> Panelists</span>
                    </div>
                </div>
            </div>

            <!-- Stats / Executive Summary -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Score Wheel Card -->
                <div class="lg:col-span-4 bg-white dark:bg-slate-900 p-8 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 flex flex-col items-center justify-center text-center">
                    <h3 class="text-sm font-bold text-slate-500 mb-6 uppercase tracking-wider">Weighted Score</h3>
                    <div class="size-48 circular-progress shadow-xl dark:shadow-none mb-6 relative" style="background: conic-gradient(#4040b5 <?php echo $percentage; ?>%, #eaeaf1 0);">
                        <div class="circular-progress-inner dark:bg-slate-900">
                            <span class="text-5xl font-black text-primary leading-none"><?php echo $totalEarned; ?></span>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-tighter mt-1">out of <?php echo $totalPossible; ?></span>
                        </div>
                    </div>
                    <p class="text-primary text-sm font-bold flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">trending_up</span> <!-- Generic icon -->
                        <?php echo $percentage; ?>% Overall Score
                    </p>
                </div>
                
                <!-- Category Quick Stats -->
                <div class="lg:col-span-8 grid grid-cols-1 md:grid-cols-2 gap-4">
                     <?php 
                        $count = 0;
                        foreach ($criteriaList as $c): 
                            if ($count >= 4) break;
                            $score = $scoresByCriteria[$c['id']] ?? 0;
                            $max = $c['max_score'];
                            $percent = $max > 0 ? ($score / $max) * 100 : 0;
                            $count++;
                        ?>
                    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 flex flex-col justify-between">
                        <div class="flex justify-between items-start">
                            <p class="text-sm font-bold text-slate-500 truncate" title="<?php echo htmlspecialchars($c['name']); ?>">
                                <?php echo htmlspecialchars($c['name']); ?>
                            </p>
                            <span class="material-symbols-outlined text-primary">analytics</span>
                        </div>
                        <div>
                            <p class="text-3xl font-black mt-2"><?php echo $score; ?><span class="text-lg font-medium text-slate-400">/<?php echo $max; ?></span></p>
                            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 mt-3 overflow-hidden">
                                <div class="bg-primary h-full rounded-full" style="width: <?php echo $percent; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Detailed Breakdowns -->
            <div class="space-y-4">
                <h4 class="text-lg font-black flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">analytics</span>
                    Detailed Assessment
                </h4>
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 divide-y divide-slate-100 dark:divide-slate-800">
                    <?php foreach ($criteriaList as $c): 
                         $score = $scoresByCriteria[$c['id']] ?? 0;
                         $max = $c['max_score'];
                         $percent = $max > 0 ? ($score / $max) * 100 : 0;
                    ?>
                    <div class="p-5 flex flex-col gap-2">
                        <div class="flex justify-between items-center">
                            <p class="text-sm font-bold"><?php echo htmlspecialchars($c['name']); ?></p>
                            <span class="text-xs font-black bg-primary/10 text-primary px-2 py-0.5 rounded"><?php echo $score; ?> / <?php echo $max; ?></span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-primary h-full" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>


            <!-- Qualitative Feedback & Individual Reviews -->
            <div class="space-y-6">
                 <h4 class="text-lg font-black flex items-center gap-2">
                    <span class="material-symbols-outlined text-slate-500">notes</span>
                    Panelist Evaluations
                </h4>
                
                <?php foreach ($evaluations as $eval): 
                    $rec = $eval['recommendation'] ?? 'Pending';
                    $rc = $recColorMap[$rec] ?? 'slate';
                    $ri = $recIconMap[$rec] ?? 'help';
                ?>
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                    <div class="px-8 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="size-10 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center font-bold text-slate-500">
                                <?php echo strtoupper(substr($eval['panelist_name'], 0, 2)); ?>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($eval['panelist_name']); ?></h4>
                                <p class="text-xs text-slate-500">Evaluated on <?php echo date('M d, Y', strtotime($eval['created_at'])); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 px-3 py-1 bg-<?php echo $rc; ?>-50 dark:bg-<?php echo $rc; ?>-900/20 text-<?php echo $rc; ?>-700 dark:text-<?php echo $rc; ?>-400 border border-<?php echo $rc; ?>-100 dark:border-<?php echo $rc; ?>-800/20 rounded-lg">
                             <span class="material-symbols-outlined text-sm"><?php echo $ri; ?></span>
                             <span class="text-sm font-bold uppercase"><?php echo htmlspecialchars($rec); ?></span>
                        </div>
                    </div>
                    <div class="p-8">
                         <div class="prose dark:prose-invert max-w-none">
                            <p class="text-[#111118] dark:text-slate-300 leading-relaxed text-base italic bg-slate-50 dark:bg-slate-800/40 p-6 rounded-xl border-l-4 border-primary">
                                "<?php echo nl2br(htmlspecialchars($eval['notes'] ?? 'No notes provided.')); ?>"
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
