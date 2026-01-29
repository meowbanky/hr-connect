<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';


// Auth Check (Panelist Chairman)
if (!isset($_SESSION['panelist_id'])) {
    header("Location: login.php");
    exit;
}

$jobId = $_GET['job_id'] ?? null;
if (!$jobId) {
    header("Location: dashboard.php");
    exit;
}

// Verify Chairman Status
$stmt = $pdo->prepare("SELECT is_chairman FROM panelist_jobs WHERE panelist_id = ? AND job_id = ? AND is_chairman = 1");
$stmt->execute([$_SESSION['panelist_id'], $jobId]);
if (!$stmt->fetch()) {
    die("Access Denied: You are not the chairman for this job.");
}

// Fetch Job Details
$stmt = $pdo->prepare("SELECT id, title FROM job_postings WHERE id = ?");
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    die("Job not found.");
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Assign Criteria - <?php echo htmlspecialchars($job['title']); ?> - HR Connect</title>
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
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
    <?php echo get_theme_css(); ?>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-dark-custom dark:text-white min-h-screen flex">
    <?php include __DIR__ . '/../includes/panelist_sidebar.php'; ?>

    <main class="flex-1 flex flex-col md:ml-0 transition-all duration-300">
        <?php include __DIR__ . '/../includes/panelist_header.php'; ?>

        <div class="p-8 overflow-y-auto">
            <header class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-8">
                <div>
                    <a href="dashboard.php" class="flex items-center gap-2 text-sm text-slate-500 hover:text-primary mb-2 transition-colors">
                        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                        Back to Dashboard
                    </a>
                    <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">Criteria Assignment</h1>
                    <p class="text-slate-500">Distribute evaluation responsibilities for <strong><?php echo htmlspecialchars($job['title']); ?></strong>.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="saveAssignments()" class="flex items-center gap-2 px-6 py-3 bg-primary text-white font-bold rounded-lg shadow-lg hover:shadow-xl transition-all">
                        <span class="material-symbols-outlined text-[20px]">save</span>
                        Save Assignments
                    </button>
                </div>
            </header>

            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-x-auto p-6" id="matrixContainer">
                <div class="flex items-center justify-center py-12">
                    <span class="loading-spinner">Loading...</span>
                </div>
            </div>
            
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const jobId = <?php echo $jobId; ?>;
        let panelists = [];
        let criteria = [];
        let assignments = {};

        function fetchMatrix() {
            fetch(`../api/assign_criteria.php?action=fetch_matrix&job_id=${jobId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        panelists = data.panelists;
                        criteria = data.criteria;
                        assignments = data.assignments;
                        renderMatrix();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
        }

        function renderMatrix() {
            const container = document.getElementById('matrixContainer');
            
            if (panelists.length === 0) {
                container.innerHTML = '<p class="text-center text-slate-500">No panelists assigned to this job yet.</p>';
                return;
            }
            if (criteria.length === 0) {
                container.innerHTML = '<p class="text-center text-slate-500">No global criteria defined yet.</p>';
                return;
            }

            let html = `
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50">
                                <th class="p-4 border border-slate-200 dark:border-slate-700 font-bold sticky left-0 bg-slate-50 dark:bg-slate-900 z-10 w-64">Criteria / Max Score</th>
                                ${panelists.map(p => `
                                    <th class="p-4 border border-slate-200 dark:border-slate-700 text-center min-w-[150px]">
                                        <div class="font-bold text-sm">${p.name}</div>
                                    </th>
                                `).join('')}
                            </tr>
                        </thead>
                        <tbody>
            `;

            criteria.forEach(c => {
                html += `
                    <tr>
                        <td class="p-4 border border-slate-200 dark:border-slate-700 font-medium sticky left-0 bg-white dark:bg-slate-800 z-10">
                            ${c.name} 
                            <span class="ml-2 text-xs font-bold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-500">${c.max_score}%</span>
                        </td>
                        ${panelists.map(p => {
                            const isChecked = assignments[p.id] && assignments[p.id].includes(c.id) ? 'checked' : '';
                            return `
                                <td class="p-4 border border-slate-200 dark:border-slate-700 text-center hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors cursor-pointer" onclick="toggleCheck(${p.id}, ${c.id})">
                                    <input type="checkbox" id="chk_${p.id}_${c.id}" ${isChecked} 
                                           class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary pointer-events-none"
                                           onchange="updateState()">
                                </td>
                            `;
                        }).join('')}
                    </tr>
                `;
            });

            html += `
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50 dark:bg-slate-900/50 font-bold text-sm">
                                <td class="p-4 border border-slate-200 dark:border-slate-700 sticky left-0 bg-slate-50 dark:bg-slate-900 z-10" id="grandTotalCell">Total Assigned</td>
                                ${panelists.map(p => `
                                    <td class="p-4 border border-slate-200 dark:border-slate-700 text-center" id="total_${p.id}">
                                        0%
                                    </td>
                                `).join('')}
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;
            
            container.innerHTML = html;
            updateTotals();
        }

        function updateTotals() {
            let grandTotal = 0;
            panelists.forEach(p => {
                let sum = 0;
                criteria.forEach(c => {
                    const chk = document.getElementById(`chk_${p.id}_${c.id}`);
                    if (chk.checked) sum += c.max_score;
                });
                
                grandTotal += sum;
                const cell = document.getElementById(`total_${p.id}`);
                cell.innerText = sum + '%';
                
                if (sum > 100) {
                    cell.className = "p-4 border border-slate-200 dark:border-slate-700 text-center text-red-600 font-black bg-red-50 dark:bg-red-900/20";
                } else if (sum === 100) {
                    cell.className = "p-4 border border-slate-200 dark:border-slate-700 text-center text-green-600 font-black bg-green-50 dark:bg-green-900/20";
                } else {
                    cell.className = "p-4 border border-slate-200 dark:border-slate-700 text-center text-slate-500 font-bold";
                }
            });

            // Update Grand Total
            const gtCell = document.getElementById('grandTotalCell');
            if (gtCell) {
                gtCell.innerHTML = `Total Assigned: <span class="${grandTotal > 100 ? 'text-red-600' : (grandTotal === 100 ? 'text-green-600' : 'text-slate-700 dark:text-slate-300')}">${grandTotal}%</span>`;
                
                if (grandTotal > 100) gtCell.className = "p-4 border border-slate-200 dark:border-slate-700 sticky left-0 bg-red-50 dark:bg-red-900/20 z-10 font-bold";
                else if (grandTotal === 100) gtCell.className = "p-4 border border-slate-200 dark:border-slate-700 sticky left-0 bg-green-50 dark:bg-green-900/20 z-10 font-bold";
                else gtCell.className = "p-4 border border-slate-200 dark:border-slate-700 sticky left-0 bg-slate-50 dark:bg-slate-900 z-10 font-bold";
            }
        }

        function toggleCheck(pid, cid) {
            // Enforce Exclusivity: Uncheck this criteria for all other panelists
            panelists.forEach(p => {
                if (p.id !== pid) {
                    const otherChk = document.getElementById(`chk_${p.id}_${cid}`);
                    if (otherChk && otherChk.checked) {
                        otherChk.checked = false;
                    }
                }
            });

            // Toggle current
            // Note: The click event toggles the checkbox state automatically before this function runs if attached to onclick?
            // Actually, if we use onclick on TD, we need to handle the input manually OR let input bubble.
            // My previous code: onclick="toggleCheck" on TD. Input has onchange="updateState()".
            // Let's rely on standard change event for the INPUT, and handle TD click to toggle input.
            
            const checkbox = document.getElementById(`chk_${pid}_${cid}`);
            // If called from TD click, we manually toggle. If called from Input, we don't.
            // But my previous code had `onclick` on TD AND `onchange` on Input (which was pointer-events-none).
            // So TD click is the only trigger.
            
            // Wait, previous code:
            // <td onclick="toggleCheck(...)"> <input pointer-events-none ... > </td>
            // toggleCheck() { checkbox.checked = !checkbox.checked; updateTotals(); }
            
            // So yes, manual toggle.
            // Logic:
            // 1. If currently unchecked -> checking it. Need to uncheck others.
            // 2. If currently checked -> unchecking it. No side effects.
            
            const wasChecked = checkbox.checked;
            
            if (!wasChecked) {
                 // We are about to check it. Uncheck others.
                 panelists.forEach(p => {
                    if (p.id !== pid) {
                        const otherChk = document.getElementById(`chk_${p.id}_${cid}`);
                        if (otherChk) otherChk.checked = false;
                    }
                });
            }
            
            checkbox.checked = !checkbox.checked;
            updateTotals();
        }

        function saveAssignments() {
            // Build Matrix
            let matrix = {};
            panelists.forEach(p => {
                matrix[p.id] = [];
                criteria.forEach(c => {
                     const chk = document.getElementById(`chk_${p.id}_${c.id}`);
                     if (chk.checked) matrix[p.id].push(c.id);
                });
            });

            fetch('../api/assign_criteria.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'save_assignments',
                    job_id: jobId,
                    matrix: matrix
                })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    Swal.fire('Saved', 'Criteria distributed successfully.', 'success');
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }

        fetchMatrix();
    </script>
</body>
</html>
