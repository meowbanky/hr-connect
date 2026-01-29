<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

if (!isset($_SESSION['panelist_id'])) {
    header("Location: login.php");
    exit;
}

$panelistId = $_SESSION['panelist_id'];
$panelistName = $_SESSION['panelist_name'];

// Fetch Assigned Jobs
$sql = "SELECT j.id, j.title, pj.status as invite_status, pj.is_chairman,
       (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id AND a.status IN ('shortlisted', 'interviewed')) as candidate_count
       FROM job_postings j
       JOIN panelist_jobs pj ON j.id = pj.job_id
       WHERE pj.panelist_id = ?
       ORDER BY j.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$panelistId]);
$allJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$activeJobs = [];
$pendingInvites = [];

foreach ($allJobs as $job) {
    if ($job['invite_status'] === 'active' || $job['invite_status'] === 'accepted') { // Assuming 'active' legacy or 'accepted' new
        $activeJobs[] = $job;
    } elseif ($job['invite_status'] === 'pending') {
        $pendingInvites[] = $job;
    }
}

$jobs = $activeJobs; // Keep $jobs for legacy compatibility if needed, or use activeJobs

// Calculate Stats
$totalInterviews = 0;
foreach ($activeJobs as $job) {
    $totalInterviews += $job['candidate_count'];
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Panelist Dashboard | HR Connect</title>
    <link rel="icon" href="../assets/images/favicon.png" type="image/png">
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
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen flex overflow-hidden">
<!-- Persistent Left Sidebar -->
<?php include __DIR__ . '/../includes/panelist_sidebar.php'; ?>

<!-- Main Workspace -->
<main class="flex-1 flex flex-col h-screen overflow-y-auto bg-background-light dark:bg-background-dark">
    <!-- Header Section -->
    <?php include __DIR__ . '/../includes/panelist_header.php'; ?>

    <div class="p-8 max-w-7xl mx-auto w-full">
        <!-- Welcome Heading -->
        <div class="mb-8">
            <h1 class="text-[#111019] dark:text-white text-3xl font-black leading-tight tracking-tight">Welcome back, <?php echo htmlspecialchars($panelistName); ?></h1>
            <p class="text-slate-500 dark:text-slate-400 text-base mt-1">Today is <?php echo date('l, M jS'); ?> • You have <?php echo count($activeJobs); ?> active interview panels</p>
        </div>

        <?php foreach ($pendingInvites as $invite): ?>
        <div class="mb-8 bg-indigo-50 dark:bg-indigo-900/10 border border-indigo-100 dark:border-indigo-800 p-6 rounded-xl flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="size-12 bg-indigo-100 dark:bg-indigo-800 rounded-full flex items-center justify-center text-indigo-600 dark:text-indigo-300">
                    <span class="material-symbols-outlined">mail</span>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-slate-900 dark:text-white">Panel Invitation</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">You have been invited to join the panel for <strong><?php echo htmlspecialchars($invite['title']); ?></strong>.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <button onclick="respondInvite(<?php echo $invite['id']; ?>, 'decline')" class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-400 font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Decline</button>
                <button onclick="respondInvite(<?php echo $invite['id']; ?>, 'accept')" class="px-4 py-2 bg-primary text-white font-bold rounded-lg shadow-lg hover:opacity-90 transition-all">Accept Invite</button>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Stats Overview Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-slate-500 text-sm font-medium">Assigned Candidates</p>
                    <p class="text-2xl font-bold mt-1"><?php echo $totalInterviews; ?></p>
                </div>
                <div class="size-12 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400">
                    <span class="material-symbols-outlined">groups</span>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-slate-500 text-sm font-medium">Pending Evaluations</p>
                    <p class="text-2xl font-bold mt-1"><?php echo $totalInterviews; ?></p>
                </div>
                <div class="size-12 rounded-full bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center text-amber-600 dark:text-amber-400">
                    <span class="material-symbols-outlined">pending_actions</span>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-slate-500 text-sm font-medium">Active Panels</p>
                    <p class="text-2xl font-bold mt-1"><?php echo count($activeJobs); ?></p>
                </div>
                <div class="size-12 rounded-full bg-green-50 dark:bg-green-900/20 flex items-center justify-center text-green-600 dark:text-green-400">
                    <span class="material-symbols-outlined">task_alt</span>
                </div>
            </div>
        </div>

        <!-- Bento Layout Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Today's Interviews Section -->
            <div class="lg:col-span-2 space-y-6">
                <h2 class="text-xl font-bold tracking-tight">Priority Focus</h2>
                
                <?php if (!empty($activeJobs)): 
                    $activeJob = $activeJobs[0];
                ?>
                <!-- Primary Active Session Card -->
                <div class="relative group overflow-hidden bg-primary text-white p-8 rounded-2xl shadow-xl shadow-primary/20 border border-primary/10">
                    <div class="absolute top-0 right-0 p-4">
                        <span class="flex items-center gap-1.5 px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-xs font-bold uppercase tracking-wider">
                            <span class="size-2 bg-white rounded-full animate-pulse"></span>
                            Active Cycle
                        </span>
                    </div>
                    <div class="relative z-10">
                        <p class="text-primary-100/80 text-sm font-medium mb-4 uppercase tracking-widest">Active Panel</p>
                        <div class="flex items-center gap-5 mb-8">
                            <div class="size-20 rounded-2xl bg-white/10 border border-white/20 p-1 flex items-center justify-center text-3xl font-bold">
                                <?php echo substr($activeJob['title'], 0, 1); ?>
                            </div>
                            <div>
                                <h3 class="text-xl md:text-3xl font-bold truncate max-w-md"><?php echo htmlspecialchars($activeJob['title']); ?></h3>
                                <p class="text-primary-100/90 text-sm md:text-lg">Job ID: <?php echo 1000 + $activeJob['id']; ?></p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-4 mb-8">
                            <div class="flex items-center gap-2 bg-white/10 px-4 py-2 rounded-lg backdrop-blur-sm">
                                <span class="material-symbols-outlined text-sm">groups</span>
                                <span class="text-sm"><?php echo $activeJob['candidate_count']; ?> Candidates</span>
                            </div>
                            <div class="flex items-center gap-2 bg-white/10 px-4 py-2 rounded-lg backdrop-blur-sm">
                                <span class="material-symbols-outlined text-sm">event_available</span>
                                <span class="text-sm">Open for Evaluation</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <a href="candidates.php?job_id=<?php echo $activeJob['id']; ?>" class="bg-white text-primary px-8 py-4 rounded-xl font-bold text-base shadow-lg hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center gap-2">
                                View Candidates <span class="material-symbols-outlined">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                    <!-- Abstract background texture -->
                    <div class="absolute -bottom-12 -right-12 size-64 bg-white/5 rounded-full blur-3xl"></div>
                    <div class="absolute -top-12 -right-12 size-48 bg-white/5 rounded-full blur-2xl"></div>
                </div>
                <?php else: ?>
                    <div class="bg-white dark:bg-slate-900 p-8 rounded-2xl border border-dashed border-slate-300 dark:border-slate-800 text-center text-slate-500">
                        No active interview panels.
                    </div>
                <?php endif; ?>

                <!-- Upcoming Queue List -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-slate-700 dark:text-slate-300">Active Panels</h3>
                        <button class="text-primary text-sm font-semibold hover:underline">View All</button>
                    </div>
                    
                    <?php if (empty($activeJobs)): ?>
                         <p class="text-sm text-slate-500 italic">No active panels.</p>
                    <?php endif; ?>

                    <?php foreach ($activeJobs as $job): ?>
                    <!-- Panel Item -->
                    <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between group hover:border-primary/30 transition-all cursor-pointer gap-4">
                        <div class="flex items-center gap-4">
                             <div class="size-12 bg-slate-100 dark:bg-slate-800 rounded-lg overflow-hidden shrink-0 flex items-center justify-center font-bold text-slate-500">
                                <?php echo substr($job['title'], 0, 2); ?>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($job['title']); ?></h4>
                                <p class="text-xs text-slate-500">ID: <?php echo 1000 + $job['id']; ?></p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between md:justify-end gap-3 w-full md:w-auto border-t md:border-t-0 border-slate-100 dark:border-slate-800 pt-3 md:pt-0">
                            <span class="text-xs font-semibold bg-slate-100 px-2 py-1 rounded-md text-slate-600"><?php echo $job['candidate_count']; ?> Candidates</span>
                            
                            <div class="flex items-center gap-2">
                                <?php if (!empty($job['is_chairman'])): ?>
                                    <a href="assign_criteria.php?job_id=<?php echo $job['id']; ?>" class="p-2 text-amber-500 hover:text-amber-600 hover:bg-amber-50 rounded transition-colors" title="Assign Criteria (Chairman)">
                                        <span class="material-symbols-outlined text-lg">assignment_add</span>
                                    </a>
                                <?php endif; ?>

                                <a href="candidates.php?job_id=<?php echo $job['id']; ?>" class="p-2 text-slate-400 hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined">arrow_forward_ios</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Column: Sidebar Actions & Guides -->
            <div class="space-y-8">
                <!-- Panelist Tip of the Day -->
                <div class="bg-indigo-50/50 dark:bg-indigo-900/10 border border-indigo-100 dark:border-indigo-900/30 p-6 rounded-2xl relative overflow-hidden">
                    <div class="relative z-10">
                        <div class="size-10 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center text-indigo-600 dark:text-indigo-400 mb-4">
                            <span class="material-symbols-outlined text-xl">lightbulb</span>
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-white mb-2">Panelist Tip</h4>
                        <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                            Remember to ask behavioral questions using the STAR method (Situation, Task, Action, Result) for more objective evaluation.
                        </p>
                    </div>
                    <div class="absolute -bottom-6 -right-6 size-24 bg-indigo-500/5 rounded-full blur-xl"></div>
                </div>

                <!-- Quick Links/Guides -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="font-bold text-lg">Interview Resources</h3>
                    </div>
                    <div class="p-2">
                        <div class="flex items-center gap-3 p-4 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl cursor-pointer group transition-all">
                            <div class="size-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                <span class="material-symbols-outlined">menu_book</span>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold">Standard Interview Guide</p>
                                <p class="text-xs text-slate-500">Best practices and score rubric</p>
                            </div>
                            <span class="material-symbols-outlined text-slate-300 group-hover:text-primary transition-all">chevron_right</span>
                        </div>
                        <div class="flex items-center gap-3 p-4 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl cursor-pointer group transition-all">
                            <div class="size-10 rounded-lg bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center text-purple-600 dark:text-purple-400">
                                <span class="material-symbols-outlined">gavel</span>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold">Code of Conduct</p>
                                <p class="text-xs text-slate-500">Updated ethics policy 2024</p>
                            </div>
                            <span class="material-symbols-outlined text-slate-300 group-hover:text-primary transition-all">chevron_right</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function respondInvite(jobId, action) {
        const actionText = action === 'accept' ? 'Accept' : 'Decline';
        const confirmText = action === 'accept' ? 'You will be added to this panel.' : 'You will be removed from this invite.';
        
        Swal.fire({
            title: actionText + ' Invite?',
            text: confirmText,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: action === 'accept' ? '#3e34ad' : '#d33',
            confirmButtonText: 'Yes, ' + actionText
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('../api/respond_invite.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ job_id: jobId, action: action })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire('Success', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(err => Swal.fire('Error', 'Network error', 'error'));
            }
        });
    }
</script>
</body>
</html>
