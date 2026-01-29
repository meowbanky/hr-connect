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
$jobId = $_GET['job_id'] ?? null;

if (!$appId || !$jobId) {
    // header("Location: dashboard.php");
    // exit;
}

// Fetch Candidate Details
$stmt = $pdo->prepare("
    SELECT a.*, j.title as job_title, 
           c.resume_path,
           u.first_name, u.last_name, u.email,
           i.interview_date, i.interview_time, i.venue_link
    FROM applications a 
    JOIN job_postings j ON a.job_id = j.id
    LEFT JOIN candidates c ON a.candidate_id = c.id
    LEFT JOIN users u ON c.user_id = u.id
    LEFT JOIN interviews i ON a.id = i.application_id
    WHERE a.id = ?
");
$stmt->execute([$appId]);
$candidate = $stmt->fetch(PDO::FETCH_ASSOC);

if ($candidate) {
    $jobId = $candidate['job_id']; 
}

if (!$candidate) {
    die("Candidate not found.");
}

// Check if already evaluated
$evalStmt = $pdo->prepare("SELECT * FROM evaluations WHERE application_id = ? AND panelist_id = ?");
$evalStmt->execute([$appId, $_SESSION['panelist_id']]);
$existingEval = $evalStmt->fetch(PDO::FETCH_ASSOC);

// Fetch Assigned Criteria for this Panelist/Job
$criteriaStmt = $pdo->prepare("
    SELECT c.* 
    FROM evaluation_criteria c
    JOIN job_criteria_assignments jca ON c.id = jca.criteria_id
    WHERE jca.job_id = ? AND jca.panelist_id = ?
    ORDER BY c.id ASC
");
$criteriaStmt->execute([$jobId, $_SESSION['panelist_id']]);
$criteria = $criteriaStmt->fetchAll(PDO::FETCH_ASSOC);

// Helper for safe output
$cFirst = $candidate['first_name'] ?? 'Candidate';
$cLast = $candidate['last_name'] ?? '';
$cFull = trim("$cFirst $cLast");
$cJob = $candidate['job_title'] ?? 'Unknown Position';
$cDate = !empty($candidate['interview_date']) ? date('M j, Y', strtotime($candidate['interview_date'])) : 'Not Scheduled';
$cTime = !empty($candidate['interview_time']) ? date('g:i A', strtotime($candidate['interview_time'])) : '';
$cResume = $candidate['resume_path'] ?? null;
$cEmail = $candidate['email'] ?? 'No Email';
$cPhone = 'N/A';
$cCover = $candidate['cover_letter'] ?? 'No cover letter provided.';

if (empty($criteria) && !$existingEval) {
    // If no criteria assigned, show message (handled in HTML)
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Evaluate <?php echo htmlspecialchars($cFull); ?> - HR Connect</title>
    <link rel="icon" href="../assets/images/favicon.svg" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#1313ec", 
                        "background-light": "#f6f6f8", 
                        "background-dark": "#121220", 
                        "slate-custom": "#535393", 
                        "dark-custom": "#0f0f1a", 
                    },
                    fontFamily: {
                        "display": ["Inter"]
                    },
                },
            },
        }
    </script>
    <?php echo get_theme_css(); ?>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .split-pane { height: calc(100vh - 60px); }
        iframe { width: 100%; height: 100%; border: none; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-dark-custom dark:text-white h-screen overflow-hidden flex flex-col">
    <!-- Mobile Overlay -->
    <div id="mobileOverlay" class="fixed inset-0 bg-slate-900/50 z-40 hidden backdrop-blur-sm md:hidden" onclick="toggleSidebar()"></div>

    <!-- Sidebar (Hidden on mobile by default, handled by toggle) -->
    <!-- Note: evaluate.php didn't have sidebar included in the view_file? Let me check lines 1-115. Ah, it didn't include sidebar! It's a full screen view. -->
    <!-- If it's full screen, it doesn't use the standard sidebar layout. It has a back button to candidates. -->
    <!-- So does it NEED the hamburger? The header in evaluate.php is different. It has a back button. -->
    <!-- Lines 116: back to candidates.php. -->
    <!-- So maybe I SHOULD NOT include panelist_header.php here because it's a special view? -->
    <!-- But the user said "make all files in the panel module mobile". -->
    <!-- If I include panelist_header.php, I lose the specific context (Candidate Name, Back button). -->
    <!-- So I should KEEP the custom header but make it responsive if needed. -->
    <!-- actually, line 114: header class="..." -->
    <!-- It doesn't have a sidebar. It's a focus mode page. -->
    <!-- So I don't need to add hamburger/sidebar here. -->
    <!-- I just need to fix the split pane. -->
    <!-- But wait, the user said "the menu on the panel module is not displaying". -->
    <!-- If this page doesn't have a menu, then it's fine. -->
    <!-- BUT, does it need to be responsive? YES. -->
    <!-- So I will fix layout to stack on mobile. -->

<body class="bg-background-light dark:bg-background-dark font-display text-dark-custom dark:text-white h-screen flex flex-col md:overflow-hidden overflow-y-auto">

    <!-- Header -->
    <header class="h-[60px] bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-4 md:px-6 shrink-0 z-20 sticky top-0">
        <div class="flex items-center gap-4">
            <a href="candidates.php?job_id=<?php echo $jobId; ?>" class="p-2 -ml-2 text-slate-400 hover:text-primary transition-colors rounded-full hover:bg-slate-50 dark:hover:bg-slate-800">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <div class="overflow-hidden">
                <h1 class="font-bold text-lg leading-tight text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($cFull); ?></h1>
                <p class="text-xs text-slate-500 uppercase font-semibold tracking-wider truncate"><?php echo htmlspecialchars($cJob); ?></p>
            </div>
        </div>
        <div class="flex items-center gap-4">
             <div class="text-right hidden md:block">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Interview Time</p>
                <p class="text-sm font-semibold"><?php echo $cDate . ($cTime ? ' • ' . $cTime : ''); ?></p>
            </div>
        </div>
    </header>

    <!-- Main Content (Split View) -->
    <main class="flex-1 flex flex-col md:flex-row overflow-hidden md:overflow-hidden h-full">
        
        <!-- Left Pane: Resume & Details -->
        <div class="w-full md:w-1/2 flex flex-col border-r border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900/50 h-[50vh] md:h-full shrink-0">
            <!-- Tabs -->
            <div class="flex border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900">
                <button onclick="switchTab('resume')" id="tab-resume" class="flex-1 py-3 text-sm font-bold text-center border-b-2 border-primary text-primary transition-colors">Resume / CV</button>
                <button onclick="switchTab('details')" id="tab-details" class="flex-1 py-3 text-sm font-bold text-center border-b-2 border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition-colors">Application Details</button>
            </div>

            <!-- Resume View -->
            <div id="view-resume" class="flex-1 relative bg-slate-200 dark:bg-slate-800">
                <?php if ($cResume && file_exists(__DIR__ . '/../' . $cResume)): ?>
                    <iframe src="../<?php echo htmlspecialchars($cResume); ?>"></iframe>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center h-full text-slate-400">
                        <span class="material-symbols-outlined text-6xl mb-4">description</span>
                        <p class="font-medium">No Resume Uploaded</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Details View (Hidden by default) -->
            <div id="view-details" class="flex-1 p-8 overflow-y-auto hidden bg-white dark:bg-slate-900">
                <div class="prose dark:prose-invert max-w-none">
                    <h3 class="text-lg font-bold mb-4">Contact Information</h3>
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <span class="text-xs text-slate-500 uppercase font-bold">Email</span>
                            <p class="font-medium"><?php echo htmlspecialchars($cEmail); ?></p>
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 uppercase font-bold">Phone</span>
                            <p class="font-medium"><?php echo htmlspecialchars($cPhone); ?></p>
                        </div>
                    </div>
                    
                    <h3 class="text-lg font-bold mb-4">Cover Letter / Statement</h3>
                    <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-lg text-sm text-slate-700 dark:text-slate-300 leading-relaxed border border-slate-100 dark:border-slate-700">
                         <?php echo nl2br(htmlspecialchars($cCover)); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Pane: Evaluation Form -->
        <div class="w-full md:w-1/2 flex flex-col bg-white dark:bg-slate-900 h-full overflow-hidden">
            <div class="flex-1 overflow-y-auto p-8 relative">
                
                <?php if ($existingEval): ?>
                    <div class="flex flex-col items-center justify-center h-full text-center">
                        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-6 text-green-600">
                            <span class="material-symbols-outlined text-5xl">check_circle</span>
                        </div>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-2">Evaluation Submitted</h2>
                        <p class="text-slate-500 mb-8 max-w-sm">Thank you for submitting your evaluation for this candidate. You can edit your response if needed.</p>
                        <button onclick="document.getElementById('evalFormContainer').classList.remove('hidden'); this.parentElement.classList.add('hidden')" class="px-6 py-3 bg-white border border-slate-200 shadow-sm rounded-lg font-bold text-slate-700 hover:bg-slate-50 transition-colors">
                            Edit Evaluation
                        </button>
                    </div>
                <?php endif; ?>

                <div id="evalFormContainer" class="<?php echo $existingEval ? 'hidden' : ''; ?>">
                    <div class="mb-8">
                        <h2 class="text-xl font-black mb-2">Evaluation Form</h2>
                        <p class="text-sm text-slate-500">Rate the candidate based on the defined criteria. Be objective and leave notes.</p>
                    </div>

                    <form id="evaluationForm" onsubmit="submitEvaluation(event)" class="space-y-8 pb-20">
                        <input type="hidden" name="application_id" value="<?php echo $appId; ?>">
                        <input type="hidden" name="job_id" value="<?php echo $jobId; ?>">
                        
                        <!-- Dynamic Criteria Loop -->
                         <?php if(empty($criteria)): ?>
                            <div class="bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 p-6 rounded-xl border border-amber-100 dark:border-amber-700/50 text-center mb-6">
                                <span class="material-symbols-outlined text-4xl mb-2">assignment_late</span>
                                <h3 class="font-bold text-lg">No Criteria Assigned</h3>
                                <p class="text-sm">You have not been assigned any specific evaluation criteria for this job yet.</p>
                            </div>
                         <?php endif; ?>

                         <?php foreach ($criteria as $c): ?>
                            <div class="bg-slate-50 dark:bg-slate-800/50 p-6 rounded-xl border border-slate-100 dark:border-slate-700/50">
                                <div class="flex items-center justify-between mb-4">
                                    <label class="font-bold text-lg text-slate-900 dark:text-white"><?php echo htmlspecialchars($c['name']); ?></label>
                                    <span class="text-xs font-bold uppercase bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-2 py-1 rounded">Max: <?php echo $c['max_score']; ?>%</span>
                                </div>
                                
                                <input type="range" name="criteria_<?php echo $c['id']; ?>" min="0" max="<?php echo $c['max_score']; ?>" value="0" 
                                       class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-primary mb-2"
                                       oninput="updateOutput(this, <?php echo $c['max_score']; ?>)">
                                
                                <div class="flex justify-between text-xs font-bold text-slate-400">
                                    <span>0</span>
                                    <span class="text-primary text-base" id="output_<?php echo $c['id']; ?>">0</span>
                                    <span><?php echo $c['max_score']; ?></span>
                                </div>
                            </div>
                         <?php endforeach; ?>

                        <!-- Standard Fields -->
                        <div>
                            <label class="block font-bold text-sm text-slate-700 dark:text-slate-300 mb-2">Recommendation</label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="border border-slate-200 dark:border-slate-700 rounded-lg p-4 flex items-center gap-3 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors has-[:checked]:border-green-500 has-[:checked]:bg-green-50 dark:has-[:checked]:bg-green-900/20">
                                    <input type="radio" name="recommendation" value="Strong Hire" class="w-5 h-5 text-green-600 focus:ring-green-500 border-gray-300">
                                    <span class="font-bold text-green-700 dark:text-green-400">Strong Hire</span>
                                </label>
                                <label class="border border-slate-200 dark:border-slate-700 rounded-lg p-4 flex items-center gap-3 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors has-[:checked]:border-red-500 has-[:checked]:bg-red-50 dark:has-[:checked]:bg-red-900/20">
                                    <input type="radio" name="recommendation" value="No Hire" class="w-5 h-5 text-red-600 focus:ring-red-500 border-gray-300">
                                    <span class="font-bold text-red-700 dark:text-red-400">Reject</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-sm text-slate-700 dark:text-slate-300 mb-2">Additional Notes</label>
                            <textarea name="notes" rows="4" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-primary focus:border-primary text-sm p-4" placeholder="Enter specific feedback, strengths, and weaknesses..."></textarea>
                        </div>
                        
                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                            <button type="submit" class="w-full bg-primary hover:bg-[#3f36c5] text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/20 transition-all text-lg flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined">send</span>
                                Submit Evaluation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function switchTab(tab) {
            document.getElementById('view-resume').classList.add('hidden');
            document.getElementById('view-details').classList.add('hidden');
            document.getElementById('tab-resume').classList.remove('border-primary', 'text-primary');
            document.getElementById('tab-details').classList.remove('border-primary', 'text-primary');
            document.getElementById('tab-details').classList.add('border-transparent');
            document.getElementById('tab-resume').classList.add('border-transparent');

            document.getElementById('view-' + tab).classList.remove('hidden');
            document.getElementById('tab-' + tab).classList.remove('border-transparent');
            document.getElementById('tab-' + tab).classList.add('border-primary', 'text-primary');
        }

        function updateOutput(input, max) {
            const output = input.nextElementSibling.children[1];
            output.innerText = input.value; 
        }

        function submitEvaluation(e) {
            e.preventDefault();
            const form = new FormData(e.target);
            
            // Basic Frontend Validation
            if (!form.get('recommendation')) {
                Swal.fire('Missing Recommendation', 'Please select a recommendation (Hire/Reject).', 'warning');
                return;
            }

            Swal.fire({
                title: 'Submit Evaluation?',
                text: "You can update this later if needed.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Submit',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch('../api/submit_evaluation.php', {
                        method: 'POST',
                        body: form
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) throw new Error(data.message);
                        return data;
                    })
                    .catch(error => Swal.showValidationMessage(error));
                }
            }).then((result) => {
                if(result.isConfirmed) {
                    Swal.fire('Success', 'Evaluation saved successfully.', 'success').then(() => {
                         window.location.href = 'candidates.php?job_id=<?php echo $jobId; ?>';
                    });
                }
            });
        }
    </script>
</body>
</html>
