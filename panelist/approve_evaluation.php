<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

// Auth Check (Panelist Chairman)
if (!isset($_SESSION['panelist_id'])) {
    header("Location: login.php");
    exit;
}

$appId = $_GET['application_id'] ?? null;
if (!$appId) {
    die("Application ID required.");
}

// Check Chairman Access for this Job
$jobId = $pdo->query("SELECT job_id FROM applications WHERE id = $appId")->fetchColumn();
$stmt = $pdo->prepare("SELECT is_chairman FROM panelist_jobs WHERE panelist_id = ? AND job_id = ? AND is_chairman = 1");
$stmt->execute([$_SESSION['panelist_id'], $jobId]);
if (!$stmt->fetch()) {
    die("Access Denied: You are not the chairman for this job.");
}

// Fetch Application & Job Info
$stmt = $pdo->prepare("
    SELECT a.*, j.title as job_title, j.id as job_id,
           u.first_name, u.last_name
    FROM applications a 
    JOIN job_postings j ON a.job_id = j.id
    LEFT JOIN candidates c ON a.candidate_id = c.id
    LEFT JOIN users u ON c.user_id = u.id -- Candidates link to users
    WHERE a.id = ?
");
$stmt->execute([$appId]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) die("Application not found.");

// ... (Top of file remains previously fetched logic, extending it below)

// -------------------------------------------------------------
// NEW LOGIC FOR GRID LAYOUT
// -------------------------------------------------------------

// 1. Fetch All Criteria (Rows) - Global or Assigned
// Since criteria are global, we fetch via assignment or just all generic if defined globally.
// Using DISTINCT via assignment linkage to be safe:
$criteriaStmt = $pdo->prepare("
    SELECT DISTINCT c.* 
    FROM evaluation_criteria c
    JOIN job_criteria_assignments jca ON c.id = jca.criteria_id
    WHERE jca.job_id = ?
    ORDER BY c.id ASC
");
$criteriaStmt->execute([$jobId]);
$allCriteria = $criteriaStmt->fetchAll(PDO::FETCH_ASSOC);

// Fallback: If no assignments found (e.g. legacy or direct global usage), fetch all global criteria
if (empty($allCriteria)) {
    $criteriaStmt = $pdo->query("SELECT * FROM evaluation_criteria ORDER BY id ASC");
    $allCriteria = $criteriaStmt->fetchAll(PDO::FETCH_ASSOC);
}

// 2. Fetch All Evaluations/Panelists (Columns)
$evalStmt = $pdo->prepare("
    SELECT e.*, p.name as panelist_name, p.id as panelist_id, p.role as panelist_role
    FROM evaluations e
    JOIN panelists p ON e.panelist_id = p.id
    WHERE e.application_id = ?
    ORDER BY p.id ASC
");
$evalStmt->execute([$appId]);
$evaluations = $evalStmt->fetchAll(PDO::FETCH_ASSOC);

// Index Panelists
$panelists = [];
foreach ($evaluations as $e) {
    $panelists[$e['panelist_id']] = $e;
}

// 3. Ensure Chairman Column Exists
$chairmanId = $_SESSION['panelist_id'];
// Get current Chairman details if not in evaluations
if (!isset($panelists[$chairmanId])) {
    $stmt = $pdo->prepare("SELECT * FROM panelists WHERE id = ?");
    $stmt->execute([$chairmanId]);
    $chairmanData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Create a dummy/placeholder entry for the Chairman
    $panelists[$chairmanId] = [
        'panelist_id' => $chairmanId,
        'panelist_name' => $chairmanData['name'],
        'panelist_role' => $chairmanData['role'] ?? 'Chairman',
        'is_chairman_column' => true,
        'total_score' => 0, // Placeholder
        'id' => null // No evaluation ID yet
    ];
} else {
    $panelists[$chairmanId]['is_chairman_column'] = true;
}

// Move Chairman to the end? Or specific position? 
// The design has Panelist A, Panelist B, Chairman. 
// Let's sort so Chairman is last.
uasort($panelists, function($a, $b) use ($chairmanId) {
    if ($a['panelist_id'] == $chairmanId) return 1;
    if ($b['panelist_id'] == $chairmanId) return -1;
    return $a['panelist_id'] <=> $b['panelist_id'];
});

// 4. Fetch All Scores (Data Points)
$scoreStmt = $pdo->prepare("
    SELECT es.*, e.panelist_id
    FROM evaluation_scores es
    JOIN evaluations e ON es.evaluation_id = e.id
    WHERE e.application_id = ?
");
$scoreStmt->execute([$appId]);
$allScores = $scoreStmt->fetchAll(PDO::FETCH_ASSOC);

// Build Score Matrix: [criteria_id][panelist_id] = score
$matrix = [];
foreach ($allScores as $s) {
    $matrix[$s['criteria_id']][$s['panelist_id']] = $s['score'];
}

// Calculate Panel Score (Summation of all submitted scores)
$totalSubmitted = 0;
foreach ($evaluations as $e) {
    // Ensure we are adding the total_score from the evaluation record
    $totalSubmitted += $e['total_score'];
}
$panelScore = $totalSubmitted;

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Assessment Comparison - <?php echo htmlspecialchars($app['first_name'] ?? 'Candidate'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#313881",
                        "background-light": "#f9fafa",
                        "background-dark": "#21242c",
                        "accent-coral": "#E89178",
                        "accent-teal": "#48947C",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .comparison-grid {
            display: grid;
            /* Grid Template Cols: Criteria Name + N Panelists */
            grid-template-columns: 240px repeat(<?php echo count($panelists); ?>, minmax(140px, 1fr));
        }
        .sticky-col {
            position: sticky;
            left: 0;
            z-index: 10;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #d5d6e2;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-[#111118] dark:text-white min-h-screen flex flex-col">
    <!-- Include Sidebar (Hidden on mobile usually, implies layout change) 
         Standard layout: Sidebar on left, Main on right.
    -->
    <div class="flex h-screen overflow-hidden">
        <?php include __DIR__ . '/../includes/panelist_sidebar.php'; ?>

        <div class="flex-1 flex flex-col min-w-0">
             <?php include __DIR__ . '/../includes/panelist_header.php'; ?>

             <!-- Main Content -->
             <main class="flex-1 overflow-hidden flex flex-col relative">
                
                <!-- Header / Breadcrumbs -->
                <div class="px-8 py-6 flex justify-between items-end border-b border-[#eaeaf0] dark:border-[#3b3b4a] bg-white dark:bg-background-dark shrink-0">
                    <div>
                        <div class="flex items-center gap-2 text-sm text-[#5e6287] mb-2">
                             <a href="candidates.php" class="hover:text-primary">Candidates</a>
                             <span class="material-symbols-outlined text-xs">chevron_right</span>
                             <span class="font-bold text-primary">Assessment Comparison</span>
                        </div>
                        <h1 class="text-3xl font-black text-[#111118] dark:text-white"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></h1>
                        <p class="text-[#5e6287] font-medium text-lg">Role: <?php echo htmlspecialchars($app['job_title']); ?></p>
                    </div>
                    <div class="flex gap-3">
                        <button class="flex items-center gap-2 px-4 h-10 rounded-lg bg-white border border-[#d5d6e2] text-[#111118] text-sm font-bold shadow-sm hover:bg-slate-50">
                            <span class="material-symbols-outlined text-lg">download</span>
                            Export Report
                        </button>
                    </div>
                </div>

                <!-- Comparison Grid Scroller -->
                <div class="flex-1 overflow-auto custom-scrollbar px-8 py-8 pb-32">
                    <div class="bg-white dark:bg-[#2b2e3a] rounded-xl shadow-lg border border-[#eaeaf0] dark:border-[#3b3b4a] inline-block min-w-full">
                        
                        <!-- Grid Header -->
                        <div class="comparison-grid border-b border-[#eaeaf0] dark:border-[#3b3b4a] bg-[#fcfcfd] dark:bg-[#323544]">
                            <div class="sticky-col bg-[#fcfcfd] dark:bg-[#323544] p-6 font-bold text-[#5e6287] text-xs uppercase tracking-widest border-r border-[#eaeaf0] dark:border-[#3b3b4a] flex items-center shadow-[4px_0_24px_rgba(0,0,0,0.02)]">
                                Evaluation Criteria
                            </div>
                            <?php foreach ($panelists as $p): ?>
                                <div class="p-6 text-center border-r border-[#eaeaf0] dark:border-[#3b3b4a] <?php echo isset($p['is_chairman_column']) ? 'bg-primary/5' : ''; ?>">
                                    <div class="flex flex-col items-center">
                                        <div class="size-10 rounded-full flex items-center justify-center font-bold text-sm mb-2 <?php echo isset($p['is_chairman_column']) ? 'bg-primary text-white' : 'bg-blue-100 text-blue-700'; ?>">
                                            <?php echo strtoupper(substr($p['panelist_name'], 0, 2)); ?>
                                        </div>
                                        <span class="font-bold block text-sm truncate max-w-[120px]" title="<?php echo htmlspecialchars($p['panelist_name']); ?>">
                                            <?php echo htmlspecialchars($p['panelist_name']); ?>
                                        </span>
                                        <span class="text-xs text-[#5e6287] truncate max-w-[120px]">
                                            <?php echo htmlspecialchars($p['panelist_role']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Rows: Criteria -->
                        <?php foreach ($allCriteria as $c): ?>
                            <div class="comparison-grid border-b border-[#eaeaf0] dark:border-[#3b3b4a] hover:bg-[#f9fafa] dark:hover:bg-[#3b3b4a] group transition-colors">
                                <!-- Sticky Criteria Name -->
                                <div class="sticky-col bg-white dark:bg-[#2b2e3a] group-hover:bg-[#f9fafa] dark:group-hover:bg-[#3b3b4a] p-6 border-r border-[#eaeaf0] dark:border-[#3b3b4a] font-semibold text-sm flex items-center shadow-[4px_0_24px_rgba(0,0,0,0.02)] transition-colors">
                                    <?php echo htmlspecialchars($c['name']); ?>
                                    <span class="ml-auto text-xs text-[#5e6287] bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded">/<?php echo $c['max_score']; ?></span>
                                </div>

                                <?php foreach ($panelists as $pid => $p): ?>
                                    <?php 
                                        $score = $matrix[$c['id']][$pid] ?? null; 
                                        $isChairman = isset($p['is_chairman_column']);
                                    ?>
                                    <div class="p-6 flex justify-center items-center border-r border-[#eaeaf0] dark:border-[#3b3b4a] <?php echo $isChairman ? 'bg-primary/5' : ''; ?>">
                                        <?php if ($isChairman): ?>
                                            <!-- Chairman Input Mode -->
                                            <input type="number" 
                                                   class="chairman-score-input w-20 text-center font-bold text-sm text-primary border border-primary/20 bg-white rounded focus:ring-primary focus:border-primary"
                                                   value="<?php echo $score; ?>" 
                                                   min="0" 
                                                   max="<?php echo $c['max_score']; ?>" 
                                                   placeholder="Score"
                                                   data-criteria-id="<?php echo $c['id']; ?>"
                                            >
                                        <?php else: ?>
                                            <!-- Read Only for Others -->
                                            <?php if ($score !== null): ?>
                                                <span class="px-3 py-1 rounded bg-[#48947C]/10 text-[#48947C] font-bold text-sm">
                                                    <?php echo $score; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-400 italic">N/A</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>

                        <!-- Qualitative / Notes Row -->
                        <div class="comparison-grid">
                            <div class="sticky-col bg-white dark:bg-[#2b2e3a] p-6 border-r border-[#eaeaf0] dark:border-[#3b3b4a] font-semibold text-sm shadow-[4px_0_24px_rgba(0,0,0,0.02)]">
                                Qualitative Notes
                            </div>
                            <?php foreach ($panelists as $pid => $p): ?>
                                <?php $isChairman = isset($p['is_chairman_column']); ?>
                                <div class="p-6 border-r border-[#eaeaf0] dark:border-[#3b3b4a] <?php echo $isChairman ? 'bg-primary/5' : ''; ?>">
                                    <?php if ($isChairman): ?>
                                        <!-- Chairman Note Input -->
                                        <?php $myNote = $p['notes'] ?? ''; ?>
                                        <textarea id="chairmanNotes" class="w-full h-32 rounded border-[#d5d6e2] text-sm p-3 focus:ring-primary focus:border-primary resize-none" placeholder="Enter final observations..."><?php echo htmlspecialchars($myNote); ?></textarea>
                                    <?php else: ?>
                                         <div class="text-xs leading-relaxed text-[#5e6287] italic max-h-32 overflow-y-auto">
                                            "<?php echo htmlspecialchars($p['notes'] ?? 'No notes provided.'); ?>"
                                         </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    </div>
                </div>

                <!-- Footer Console -->
                <footer class="absolute bottom-0 left-0 right-0 bg-white dark:bg-[#1a1c23] border-t-4 border-primary p-6 shadow-[0_-10px_30px_rgba(0,0,0,0.1)] z-20">
                    <div class="max-w-7xl mx-auto flex flex-col lg:flex-row items-center justify-between gap-6">
                        <div class="flex items-center gap-8">
                             <div class="flex flex-col">
                                <span class="text-xs font-bold uppercase text-[#5e6287] mb-1">Panel Score</span>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-3xl font-black text-primary"><?php echo $panelScore; ?></span>
                                    <span class="text-[#5e6287] font-bold">total</span>
                                </div>
                            </div>
                            <div class="h-10 w-[1px] bg-[#d5d6e2]"></div>
                             <!-- Discrepancy logic could go here -->
                        </div>

                        <div class="flex flex-1 max-w-2xl gap-4 items-end">
                            <div class="flex-1">
                                <label class="block text-xs font-bold uppercase text-[#5e6287] mb-2">Chairman's Verdict</label>
                                <select id="finalVerdict" class="w-full h-12 rounded-lg border-[#d5d6e2] font-bold text-sm focus:ring-primary focus:border-primary">
                                    <option value="">Select Action...</option>
                                    <option class="text-accent-teal" value="offered">Proceed to Offer (Hire)</option>
                                    <option value="shortlisted">Keep on Hold (Shortlist)</option>
                                    <option class="text-accent-coral" value="rejected">Reject Candidate</option>
                                </select>
                            </div>
                            <button onclick="saveFinalDecision()" class="h-12 px-10 bg-primary text-white font-black rounded-lg hover:bg-primary/90 transition-all shadow-lg active:scale-95 whitespace-nowrap flex items-center justify-center">
                                SAVE DECISION
                            </button>
                        </div>
                    </div>
                </footer>

             </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const applicationId = <?php echo $appId; ?>;
        const jobId = <?php echo $jobId; ?>;

        function saveFinalDecision() {
            const verdict = document.getElementById('finalVerdict').value;
            const notes = document.getElementById('chairmanNotes').value;
            
            // Collect Chairman Scores
            const scores = [];
            document.querySelectorAll('.chairman-score-input').forEach(input => {
                if (input.value) {
                    scores.push({
                        criteria_id: input.getAttribute('data-criteria-id'),
                        score: input.value
                    });
                }
            });

            if (!verdict) {
                Swal.fire('Verdict Required', 'Please select a final decision.', 'warning');
                return;
            }

            Swal.fire({
                title: 'Confirm Decision',
                text: "This will finalize the evaluation and update the candidate status.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Submit',
                confirmButtonColor: '#313881',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                   return fetch('../api/admin_evaluation_actions.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            action: 'chairman_decision',
                            application_id: applicationId,
                            job_id: jobId,
                            verdict: verdict,
                            notes: notes,
                            scores: scores
                        })
                    }).then(res => res.json())
                    .then(data => {
                        if (!data.success) throw new Error(data.message);
                        return data;
                    })
                    .catch(error => Swal.showValidationMessage(error));
                }
            }).then((result) => {
                if (result.isConfirmed) {
                     Swal.fire('Success', 'Decision saved successfully.', 'success').then(() => {
                         window.location.href = 'candidates.php';
                     });
                }
            });
        }
    </script>
</body>
</html>
