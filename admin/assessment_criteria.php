<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

// Auth Check
if (!isset($_SESSION['user_id']) || 
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff' && $_SESSION['user_role'] !== 'superadmin')) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Assessment Criteria - Global Settings</title>
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
    <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="flex-1 flex flex-col md:ml-0 transition-all duration-300">
        <?php include __DIR__ . '/../includes/admin_header.php'; ?>

        <div class="p-8 overflow-y-auto">
        <header class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">Assessment Criteria</h1>
                <p class="text-slate-500">Define the global assessment rubric used for <strong>all candidate evaluations</strong>.</p>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="resetDefaults()" class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 font-semibold rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-all">
                    <span class="material-symbols-outlined text-[20px]">restart_alt</span>
                    Reset to Default
                </button>
            </div>
        </header>

        <!-- Stats / Progress Bar -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm mb-8">
            <div class="flex items-center justify-between mb-4">
                <div>
                     <span class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Weighting</span>
                     <p class="text-3xl font-black mt-1"><span id="totalDisplay">0</span><span class="text-lg text-slate-400">/100%</span></p>
                </div>
                <div id="statusBadge" class="px-3 py-1 bg-slate-100 text-slate-500 font-bold text-xs uppercase rounded-full tracking-wide">
                    Incomplete
                </div>
            </div>
            <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-4 overflow-hidden relative">
                 <div id="progressBar" class="bg-primary h-full rounded-full transition-all duration-500" style="width: 0%"></div>
            </div>
            <p id="remainingText" class="text-xs text-slate-500 mt-2 text-right">0% remaining</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Add Form -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-slate-800 p-6 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm sticky top-8">
                    <h3 class="font-bold text-lg mb-4">Add Criterion</h3>
                    <form id="addForm" onsubmit="addCriteria(event)" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold mb-1">Criterion Name</label>
                            <input type="text" name="name" required placeholder="e.g. Leadership Skills" class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Max Score (%)</label>
                            <input type="number" name="max_score" required min="1" max="100" placeholder="e.g. 20" class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm focus:ring-primary focus:border-primary">
                        </div>
                        <button type="submit" class="w-full bg-primary hover:bg-[#3f36c5] text-white font-bold py-3 rounded-lg shadow-lg shadow-primary/20 transition-all flex items-center justify-center gap-2">
                             <span class="material-symbols-outlined text-[20px]">add</span>
                             Add Criterion
                        </button>
                    </form>
                </div>
            </div>

            <!-- List -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Criterion</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center w-32">Weight</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-right w-20">Action</th>
                            </tr>
                        </thead>
                        <tbody id="criteriaList" class="divide-y divide-slate-100 dark:divide-slate-700">
                            <!-- JS Will Populate -->
                             <tr><td colspan="3" class="px-6 py-8 text-center text-slate-400">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let currentTotal = 0;

    function fetchCriteria() {
        fetch('../api/admin_criteria_actions.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'fetch' })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                renderList(data.criteria);
                updateStats(data.total_score);
            }
        });
    }

    function renderList(list) {
        const tbody = document.getElementById('criteriaList');
        tbody.innerHTML = '';
        
        if (list.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="px-6 py-8 text-center text-slate-500">No criteria defined yet. Add one or reset to defaults.</td></tr>';
            return;
        }

        list.forEach(item => {
            tbody.innerHTML += `
                <tr class="group hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-6 py-4 font-semibold">${item.name}</td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded text-xs font-bold">${item.max_score}%</span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button onclick="deleteCriteria(${item.id})" class="text-slate-400 hover:text-red-500 transition-colors">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    function updateStats(total) {
        currentTotal = total;
        const totalDisplay = document.getElementById('totalDisplay');
        const bar = document.getElementById('progressBar');
        const badge = document.getElementById('statusBadge');
        const remainingText = document.getElementById('remainingText');

        totalDisplay.innerText = total;
        bar.style.width = total + '%';

        const remaining = 100 - total;
        remainingText.innerText = remaining > 0 ? `${remaining}% remaining` : (remaining < 0 ? 'Over Limit!' : 'Complete');

        if (total === 100) {
            bar.classList.remove('bg-red-500', 'bg-amber-500');
            bar.classList.add('bg-emerald-500');
            badge.className = 'px-3 py-1 bg-emerald-100 text-emerald-700 font-bold text-xs uppercase rounded-full tracking-wide';
            badge.innerText = 'Ready';
        } else if (total > 100) {
            bar.classList.remove('bg-emerald-500', 'bg-amber-500');
            bar.classList.add('bg-red-500');
            badge.className = 'px-3 py-1 bg-red-100 text-red-700 font-bold text-xs uppercase rounded-full tracking-wide';
            badge.innerText = 'Over Limit';
        } else {
             bar.classList.remove('bg-emerald-500', 'bg-red-500');
             bar.classList.add('bg-primary');
             badge.className = 'px-3 py-1 bg-amber-100 text-amber-700 font-bold text-xs uppercase rounded-full tracking-wide';
             badge.innerText = 'Incomplete';
        }
    }

    function addCriteria(e) {
        e.preventDefault();
        const form = e.target;
        const name = form.name.value;
        const score = parseInt(form.max_score.value);

        if (currentTotal + score > 100) {
            Swal.fire('Limit Exceeded', `Adding ${score}% would exceed the 100% total. You only have ${100 - currentTotal}% remaining.`, 'warning');
            return;
        }

        fetch('../api/admin_criteria_actions.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'add', name: name, max_score: score })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                form.reset();
                fetchCriteria();
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Criteria Added',
                    showConfirmButton: false,
                    timer: 1500
                });
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    }

    function deleteCriteria(id) {
         fetch('../api/admin_criteria_actions.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'delete', id: id })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                fetchCriteria();
            }
        });
    }

    function resetDefaults() {
        Swal.fire({
            title: 'Reset to Defaults?',
            text: "This will remove all custom criteria and restore the standard global template.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Reset'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('../api/admin_criteria_actions.php', {
                    method: 'POST',
                    body: JSON.stringify({ action: 'reset_default' })
                })
                .then(res => res.json())
                .then(data => {
                    fetchCriteria();
                    Swal.fire('Reset!', 'Default criteria loaded.', 'success');
                });
            }
        });
    }

    // Init
    fetchCriteria();
</script>
</body>
</html>
