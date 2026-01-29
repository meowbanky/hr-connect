<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

// Check Auth
if (!isset($_SESSION['panelist_id'])) {
    header("Location: login.php");
    exit;
}

$panelistId = $_SESSION['panelist_id'];
$jobId = $_GET['job_id'] ?? null;

// Fetch Candidates Logic
$query = "SELECT a.id as app_id, a.status as app_status, a.application_date,
                 u.first_name, u.last_name, u.email, u.profile_image,
                 j.title as job_title, j.id as job_id, pj.is_chairman,
                 ev.id as evaluation_id, ev.total_score, ev.is_approved,
                 i.interview_date, i.interview_time,
                 (SELECT COUNT(*) FROM panelist_jobs pj2 WHERE pj2.job_id = a.job_id) as required_evals,
                 (SELECT COUNT(*) FROM evaluations ev2 WHERE ev2.application_id = a.id) as submitted_evals
          FROM applications a
          JOIN candidates c ON a.candidate_id = c.id
          JOIN users u ON c.user_id = u.id
          JOIN job_postings j ON a.job_id = j.id
          JOIN panelist_jobs pj ON j.id = pj.job_id
          LEFT JOIN evaluations ev ON a.id = ev.application_id AND ev.panelist_id = ?
          LEFT JOIN interviews i ON a.id = i.application_id
          WHERE pj.panelist_id = ? 
            AND (a.status IN ('shortlisted', 'interviewed', 'offered') OR (a.status = 'rejected' AND i.id IS NOT NULL))";

$params = [$panelistId, $panelistId];

if ($jobId) {
    $query .= " AND j.id = ?";
    $params[] = $jobId;
}

$query .= " ORDER BY a.application_date ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats Calculation
$totalCandidates = count($candidates);
$completed = 0;
$pending = 0;

foreach ($candidates as $c) {
    $isChairman = !empty($c['is_chairman']);
    $evaluated = !empty($c['evaluation_id']);
    $appStatus = $c['app_status'];
    
    // If Final Decision Made (Offered/Rejected), it is always Completed
    if (in_array($appStatus, ['offered', 'rejected'])) {
        $completed++;
        continue;
    }
    
    if ($isChairman) {
        $req = $c['required_evals'] ?? 1;
        $sub = $c['submitted_evals'] ?? 0;
        $isReady = ($sub >= $req) && ($sub > 0);
        
        if ($isReady) {
            $completed++;
        } else {
            $pending++;
        }
    } else {
        if ($evaluated) {
            $completed++;
        } else {
            $pending++;
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Panelist My Candidates Roster</title>
    <link rel="icon" href="../assets/images/favicon.svg" type="image/svg+xml">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#3e34ad",
                        "background-light": "#f8fafc",
                        "background-dark": "#020818",
                    },
                    fontFamily: {
                        "display": ["Inter"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        
        .sort-asc::after { content: ' ▲'; font-size: 0.7em; }
        .sort-desc::after { content: ' ▼'; font-size: 0.7em; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 font-display">
<div class="flex h-screen overflow-hidden">
    
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/panelist_sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <!-- Top Navigation Bar -->
        <?php include __DIR__ . '/../includes/panelist_header.php'; ?>

        <!-- Content Body -->
        <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
            <!-- Page Heading -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
                <div>
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Candidate Roster</h2>
                    <p class="text-slate-500 text-sm mt-1">Manage and score your assigned candidate interviews.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-500 hover:text-primary transition-all" onclick="location.reload()">
                        <span class="material-symbols-outlined text-[20px]">refresh</span>
                    </button>
                </div>
            </div>

            <!-- Table Filters -->
            <div class="flex flex-wrap items-center gap-2 mb-4">
                <button onclick="filterStatus('all')" class="filter-btn active flex items-center gap-1.5 px-3 py-1.5 bg-primary/10 text-primary text-xs font-bold rounded-full border border-primary/20" data-filter="all">
                    All Candidates
                    <span class="bg-primary text-white text-[10px] px-1.5 rounded-full"><?php echo $totalCandidates; ?></span>
                </button>
                 <button onclick="filterStatus('completed')" class="filter-btn flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 text-xs font-semibold rounded-full border border-slate-200 dark:border-slate-800 hover:border-slate-300" data-filter="completed">
                    Completed
                    <span class="bg-emerald-100 text-emerald-700 text-[10px] px-1.5 rounded-full"><?php echo $completed; ?></span>
                </button>
                 <button onclick="filterStatus('pending')" class="filter-btn flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 text-xs font-semibold rounded-full border border-slate-200 dark:border-slate-800 hover:border-slate-300" data-filter="pending">
                    Pending
                    <span class="bg-amber-100 text-amber-700 text-[10px] px-1.5 rounded-full"><?php echo $pending; ?></span>
                </button>
            </div>

            <!-- Mobile Card View (md:hidden) -->
            <div class="md:hidden space-y-4">
                <?php if (empty($candidates)): ?>
                    <div class="text-center p-8 text-slate-500 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800">
                        No candidates assigned yet.
                    </div>
                <?php endif; ?>

                <?php foreach ($candidates as $c): 
                     // Logic replicated for mobile view
                     $name = htmlspecialchars($c['first_name'] . ' ' . $c['last_name']);
                     $initial = strtoupper(substr($c['first_name'], 0, 1));
                     $role = htmlspecialchars($c['job_title']);
                     
                     $isEvaluated = !empty($c['evaluation_id']);
                     $completedIsChairman = !empty($c['is_chairman']);
                     
                     $appStatus = $c['app_status'];
                     $req = $c['required_evals'] ?? 1; 
                     $sub = $c['submitted_evals'] ?? 0;
                     $isReadyForReview = ($sub >= $req) && ($sub > 0);

                     // Determine Status Display (Mobile)
                     $statusLabel = 'Pending';
                     $statusColor = 'text-amber-600 dark:text-amber-400';
                     $statusBg = 'bg-amber-50 dark:bg-amber-900/20';
                     $statusIcon = 'pending';
                     $filterStatus = 'pending';

                     if (in_array($appStatus, ['offered', 'rejected'])) {
                         $statusLabel = ucfirst($appStatus);
                         $filterStatus = 'completed';
                         if ($appStatus === 'offered') {
                             $statusColor = 'text-blue-600 dark:text-blue-400';
                             $statusBg = 'bg-blue-50 dark:bg-blue-900/20';
                             $statusIcon = 'verified';
                         } else {
                             $statusColor = 'text-red-600 dark:text-red-400';
                             $statusBg = 'bg-red-50 dark:bg-red-900/20';
                             $statusIcon = 'cancel';
                         }
                     } else {
                         if ($completedIsChairman) {
                             $statusLabel = $isReadyForReview ? 'Ready for Decision' : 'Pending Panel';
                             $filterStatus = $isReadyForReview ? 'completed' : 'pending';
                             $statusColor = $isReadyForReview ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400';
                             $statusBg = $isReadyForReview ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-amber-50 dark:bg-amber-900/20';
                             $statusIcon = $isReadyForReview ? 'gavel' : 'group';
                         } else {
                             $statusLabel = $isEvaluated ? 'Completed' : 'Pending';
                             $filterStatus = $isEvaluated ? 'completed' : 'pending';
                             $statusColor = $isEvaluated ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400';
                             $statusBg = $isEvaluated ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-amber-50 dark:bg-amber-900/20';
                             $statusIcon = $isEvaluated ? 'check_circle' : 'pending';
                         }
                     }
                     
                     $scoreDisplay = $isEvaluated ? 'Score: ' . $c['total_score'] : 'Not Scored';
                     $dateStr = $c['interview_date'] ? date('M d, Y', strtotime($c['interview_date'])) : 'Not Scheduled';
                ?>
                <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm candidate-row" data-status="<?php echo $filterStatus; ?>" data-name="<?php echo strtolower($name); ?>" data-role="<?php echo strtolower($role); ?>">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                             <div class="size-12 rounded-full bg-slate-100 overflow-hidden ring-2 ring-white dark:ring-slate-900 flex items-center justify-center font-bold text-slate-500 text-sm">
                                <?php if($c['profile_image']): ?>
                                    <img src="<?php echo htmlspecialchars($c['profile_image']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <?php echo $initial; ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white"><?php echo $name; ?></h4>
                                <p class="text-xs text-slate-500 mb-1"><?php echo htmlspecialchars($c['email']); ?></p>
                                <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 text-[10px] font-bold rounded uppercase inline-block">
                                    <?php echo $role; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between border-t border-slate-100 dark:border-slate-800 pt-4 mt-2">
                        <div class="flex flex-col gap-1">
                             <span class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide <?php echo $statusColor; ?>">
                                <span class="material-symbols-outlined text-[16px]"><?php echo $statusIcon; ?></span>
                                <?php echo $statusLabel; ?>
                            </span>
                            <?php if($isEvaluated): ?>
                                <span class="text-[10px] text-slate-400 ml-6"><?php echo $scoreDisplay; ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center gap-2">
                            <?php if ($completedIsChairman): ?>
                                <a href="approve_evaluation.php?application_id=<?php echo $c['app_id']; ?>" class="p-2 bg-amber-50 text-amber-600 rounded-lg">
                                    <span class="material-symbols-outlined">gavel</span>
                                </a>
                            <?php endif; ?>

                            <?php 
                                $isFinalized = in_array($appStatus, ['offered', 'rejected']) || !empty($c['is_approved']);
                            ?>
                            
                            <?php if ($isEvaluated || $isFinalized): ?>
                                <a href="view_evaluation.php?application_id=<?php echo $c['app_id']; ?>" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-600 font-bold text-xs rounded-lg">
                                    View
                                </a>
                            <?php else: ?>
                                <a href="evaluate.php?application_id=<?php echo $c['app_id']; ?>" class="px-4 py-2 bg-primary text-white font-bold text-xs rounded-lg shadow-lg shadow-primary/20">
                                    Score
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Desktop Table View (hidden on mobile) -->
            <div class="hidden md:block bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse" id="rosterTable">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider w-12 cursor-pointer" onclick="sortTable(1)">Candidate Name</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(2)">Job Role</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(3)">Interview Date</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(4)">Scoring Status</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php if (empty($candidates)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                    <p class="font-medium">No candidates assigned yet.</p>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($candidates as $c): 
                            $name = htmlspecialchars($c['first_name'] . ' ' . $c['last_name']);
                            $initial = strtoupper(substr($c['first_name'], 0, 1));
                            $role = htmlspecialchars($c['job_title']);
                            
                            $isEvaluated = !empty($c['evaluation_id']);
                            $completedIsChairman = !empty($c['is_chairman']);
                            
                            $appStatus = $c['app_status'];
                            $req = $c['required_evals'] ?? 1; 
                            $sub = $c['submitted_evals'] ?? 0;
                            $isReadyForReview = ($sub >= $req) && ($sub > 0);

                            // Determine Status Display
                            $statusLabel = 'Pending';
                            $statusColor = 'text-amber-600 dark:text-amber-400';
                            $statusBg = 'bg-amber-50 dark:bg-amber-900/20';
                            $statusIcon = 'pending';
                            $filterStatus = 'pending';

                            if (in_array($appStatus, ['offered', 'rejected'])) {
                                // Final Status
                                $statusLabel = ucfirst($appStatus); // Offered or Rejected
                                $filterStatus = 'completed';
                                if ($appStatus === 'offered') {
                                    $statusColor = 'text-blue-600 dark:text-blue-400';
                                    $statusBg = 'bg-blue-50 dark:bg-blue-900/20';
                                    $statusIcon = 'verified';
                                } else {
                                    $statusColor = 'text-red-600 dark:text-red-400';
                                    $statusBg = 'bg-red-50 dark:bg-red-900/20';
                                    $statusIcon = 'cancel';
                                }
                            } else {
                                if ($completedIsChairman) {
                                    // For Chairman: Status reflects GLOBAL panel completion
                                    $statusLabel = $isReadyForReview ? 'Ready for Decision' : 'Pending Panel';
                                    $filterStatus = $isReadyForReview ? 'completed' : 'pending';
                                    
                                    $statusColor = $isReadyForReview ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400';
                                    $statusBg = $isReadyForReview ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-amber-50 dark:bg-amber-900/20';
                                    $statusIcon = $isReadyForReview ? 'gavel' : 'group';
                                } else {
                                    // For Regular Panelist: Status reflects PERSONAL completion
                                    $statusLabel = $isEvaluated ? 'Completed' : 'Pending';
                                    $filterStatus = $isEvaluated ? 'completed' : 'pending';
                                    
                                    $statusColor = $isEvaluated ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400';
                                    $statusBg = $isEvaluated ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-amber-50 dark:bg-amber-900/20';
                                    $statusIcon = $isEvaluated ? 'check_circle' : 'pending';
                                }
                            }

                            
                            $scoreDisplay = $isEvaluated ? 'Score: ' . $c['total_score'] : 'Not Scored';

                            // Format Date
                            $dateStr = $c['interview_date'] ? date('M d, Y', strtotime($c['interview_date'])) : 'Not Scheduled';
                            $timeStr = $c['interview_time'] ? date('h:i A', strtotime($c['interview_time'])) : '';
                        ?>
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group candidate-row" data-status="<?php echo $filterStatus; ?>" data-name="<?php echo strtolower($name); ?>" data-role="<?php echo strtolower($role); ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="size-10 rounded-full bg-slate-100 overflow-hidden ring-2 ring-white dark:ring-slate-900 flex items-center justify-center font-bold text-slate-500 text-sm">
                                        <?php if($c['profile_image']): ?>
                                            <img src="<?php echo htmlspecialchars($c['profile_image']); ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <?php echo $initial; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900 dark:text-white"><?php echo $name; ?></p>
                                        <p class="text-xs text-slate-500"><?php echo htmlspecialchars($c['email']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 text-[10px] font-bold rounded uppercase truncate max-w-[150px] inline-block">
                                    <?php echo $role; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <p class="text-sm text-slate-700 dark:text-slate-300 font-medium"><?php echo $dateStr; ?></p>
                                    <p class="text-xs text-slate-500"><?php echo $timeStr; ?></p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide w-fit <?php echo $statusBg . ' ' . $statusColor; ?>">
                                    <span class="material-symbols-outlined text-[14px]"><?php echo $statusIcon; ?></span>
                                    <?php echo $statusLabel; ?>
                                </span>
                                <?php if($isEvaluated): ?>
                                    <span class="text-[10px] text-slate-400 ml-1"><?php echo $scoreDisplay; ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <?php if ($completedIsChairman): ?>
                                    <!-- Always allow Chairman to Review/Override -->
                                    <a href="approve_evaluation.php?application_id=<?php echo $c['app_id']; ?>" 
                                       class="inline-flex items-center gap-1 overflow-hidden px-3 py-1.5 border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/10 text-amber-700 dark:text-amber-500 text-xs font-bold rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-all"
                                       title="Chairman Review"
                                    >
                                        <span class="material-symbols-outlined text-[16px]">gavel</span>
                                        Review
                                    </a>
                                <?php endif; ?>

                                <?php 
                                    $isFinalized = in_array($appStatus, ['offered', 'rejected']) || !empty($c['is_approved']);
                                ?>

                                <?php if ($isEvaluated): ?>
                                    <?php if ($isFinalized): ?>
                                        <!-- View Only (Verdict Given) -->
                                        <a href="view_evaluation.php?application_id=<?php echo $c['app_id']; ?>" 
                                           class="p-2 text-slate-400 hover:text-primary transition-colors tooltip inline-block" 
                                           title="View Evaluation"
                                        >
                                            <span class="material-symbols-outlined">visibility</span>
                                        </a>
                                    <?php else: ?>
                                        <!-- Edit Allowed -->
                                        <a href="evaluate.php?application_id=<?php echo $c['app_id']; ?>" 
                                           class="p-2 text-slate-400 hover:text-primary transition-colors tooltip inline-block" 
                                           title="Edit Evaluation"
                                        >
                                            <span class="material-symbols-outlined">edit_note</span>
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($isFinalized): ?>
                                         <!-- View Only (Even if not scored personally) -->
                                        <a href="view_evaluation.php?application_id=<?php echo $c['app_id']; ?>" 
                                           class="p-2 text-slate-400 hover:text-primary transition-colors tooltip inline-block" 
                                           title="View Evaluation Result"
                                        >
                                            <span class="material-symbols-outlined">visibility</span>
                                        </a>
                                    <?php else: ?>
                                        <a href="evaluate.php?application_id=<?php echo $c['app_id']; ?>" class="inline-block px-4 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                                            Score
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Stats Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                <div class="p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center gap-4">
                    <div class="size-12 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">check_circle</span>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Completed</p>
                        <p class="text-2xl font-black"><?php echo $completed; ?></p>
                    </div>
                </div>
                <div class="p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center gap-4">
                    <div class="size-12 rounded-lg bg-amber-50 dark:bg-amber-900/20 text-amber-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">pending</span>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Pending</p>
                        <p class="text-2xl font-black"><?php echo $pending; ?></p>
                    </div>
                </div>
                 <div class="p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center gap-4">
                    <div class="size-12 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">event</span>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Total Assigned</p>
                        <p class="text-2xl font-black"><?php echo $totalCandidates; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    // Unified Filter & Search Logic
    let currentFilter = 'all';
    let searchTerm = '';

    function updateTable() {
        const rows = document.querySelectorAll('.candidate-row');
        rows.forEach(row => {
            const status = row.dataset.status;
            const name = row.dataset.name;
            const role = row.dataset.role;
            
            // Check Filter
            const matchesFilter = (currentFilter === 'all') || (status === currentFilter);
            
            // Check Search
            const matchesSearch = name.includes(searchTerm) || role.includes(searchTerm);
            
            if (matchesFilter && matchesSearch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function filterStatus(status) {
        currentFilter = status;
        
        // Update Buttons
        const btns = document.querySelectorAll('.filter-btn');
        btns.forEach(btn => {
            if (btn.dataset.filter === status) {
                btn.classList.add('bg-primary/10', 'text-primary', 'border-primary/20');
                btn.classList.remove('bg-white', 'text-slate-600', 'border-slate-200');
            } else {
                btn.classList.remove('bg-primary/10', 'text-primary', 'border-primary/20');
                btn.classList.add('bg-white', 'text-slate-600', 'border-slate-200');
            }
        });

        updateTable();
    }

    document.getElementById('searchInput').addEventListener('keyup', function(e) {
        searchTerm = e.target.value.toLowerCase();
        updateTable();
    });

    // Sorting Logic remains same
    let sortDir = 1; 
    function sortTable(colIndex) {
        const table = document.getElementById("rosterTable");
        const tbody = table.querySelector("tbody");
        const rows = Array.from(tbody.querySelectorAll("tr"));
        
        rows.sort((a, b) => {
            const aText = a.cells[colIndex].innerText.toLowerCase();
            const bText = b.cells[colIndex].innerText.toLowerCase();
            return aText.localeCompare(bText) * sortDir;
        });
        
        sortDir *= -1;
        const headers = table.querySelectorAll("th");
        headers.forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
        headers[colIndex].classList.add(sortDir === 1 ? 'sort-asc' : 'sort-desc');
        rows.forEach(row => tbody.appendChild(row));
    }
</script>
</body>
</html>
