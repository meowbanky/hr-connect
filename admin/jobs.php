<?php
session_start();

// Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || 
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff' && $_SESSION['user_role'] !== 'superadmin')) {
    header('Location: /admin/login');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

// Auto-close expired jobs
$pdo->query("UPDATE job_postings SET status = 'closed' WHERE status = 'published' AND application_deadline < CURDATE()");

$pageTitle = 'Manage Job Templates';

try {
    $page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $limit  = 10;
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if ($search) {
        $where[] = "(t.title LIKE ? OR d.name LIKE ?)";
        $term = "%$search%";
        $params = array_merge($params, [$term, $term]);
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Count Templates
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM job_templates t
        LEFT JOIN departments d ON t.department_id = d.id
        $whereSQL
    ");
    $countStmt->execute($params);
    $totalTemplates = $countStmt->fetchColumn();
    $totalPages = ceil($totalTemplates / $limit);

    // Fetch Templates with Cycle Info
    // We want to know if there is an active cycle
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            d.name AS department_name,
            e.name AS employment_type_name,
            (SELECT COUNT(*) FROM job_postings p WHERE p.job_template_id = t.id) AS cycle_count,
            (SELECT COUNT(*) FROM job_postings p WHERE p.job_template_id = t.id AND p.status = 'published') AS active_cycles,
            (SELECT cycle_label FROM job_postings p WHERE p.job_template_id = t.id ORDER BY p.created_at DESC LIMIT 1) AS latest_cycle_label,
            (SELECT status FROM job_postings p WHERE p.job_template_id = t.id ORDER BY p.created_at DESC LIMIT 1) AS latest_cycle_status
        FROM job_templates t
        LEFT JOIN departments d ON t.department_id = d.id
        LEFT JOIN employment_types e ON t.employment_type_id = e.id
        $whereSQL
        ORDER BY t.title ASC
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats for Top Cards
    $stats = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM job_templates) AS total_templates,
            (SELECT COUNT(*) FROM job_postings WHERE status='published') AS active_cycles,
            (SELECT COUNT(*) FROM candidates) AS total_candidates
    ")->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo ($_SESSION['theme_mode'] ?? '') === 'dark' ? 'dark' : 'light'; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manage Recruitment - HR Connect</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
<link href="/assets/css/style.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body { font-family: Inter, sans-serif; }
@media (max-width: 767px) {
    .desktop-table { display: none; }
    .mobile-list { display: flex; flex-direction: column; gap: 1rem; }
}
@media (min-width: 768px) {
    .mobile-list { display: none; }
}
</style>

<?php echo get_theme_css(); ?>
</head>

<body class="bg-background-light dark:bg-background-dark flex min-h-screen">

<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

<main class="flex-1 flex flex-col">

<?php include __DIR__ . '/../includes/admin_header.php'; ?>

<div class="p-4 sm:p-8 space-y-6">

<!-- PAGE HEADER -->
<div class="flex justify-between items-center">
    <div>
        <h1 class="text-2xl sm:text-3xl font-black">Recruitment Management</h1>
        <p class="text-slate-500 text-sm">Manage Job Templates & Cycles</p>
    </div>
    <a href="/admin/create_job.php" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-bold shadow-lg shadow-blue-500/20 hover:bg-blue-600 transition-all">
        + Create Template
    </a>
</div>

<!-- STATS -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <?php foreach ([
        ['Job Templates', $stats['total_templates'], 'description'],
        ['Active Cycles', $stats['active_cycles'], 'campaign'],
        ['Total Candidates', $stats['total_candidates'], 'group']
    ] as $s): ?>
    <div class="bg-white dark:bg-surface-dark p-4 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-between">
        <div>
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wider"><?php echo $s[0]; ?></p>
            <p class="text-3xl font-bold mt-1 text-slate-900 dark:text-white"><?php echo $s[1]; ?></p>
        </div>
        <div class="h-10 w-10 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-primary">
            <span class="material-symbols-outlined"><?php echo $s[2]; ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- SEARCH -->
<div class="flex flex-col sm:flex-row gap-4 py-4 border-b border-slate-200 dark:border-slate-800">
    <form method="GET" class="flex-1 relative">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search job templates..." 
               class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-surface-dark focus:ring-2 focus:ring-primary/50 outline-none transition-all shadow-sm">
        
        <?php if($search): ?>
        <a href="?" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- DESKTOP TABLE -->
<div class="desktop-table bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
<table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
<thead class="bg-slate-50 dark:bg-slate-900">
<tr>
<th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Template</th>
<th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Dept</th>
<th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Recruitment Status</th>
<th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Latest Cycle</th>
<th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
</tr>
</thead>
<tbody class="divide-y divide-slate-200 dark:divide-slate-800">
<?php foreach ($templates as $tpl): ?>
<tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
    <td class="px-6 py-4">
        <div class="font-bold text-slate-900 dark:text-white text-base"><?php echo htmlspecialchars($tpl['title']); ?></div>
        <div class="text-xs text-slate-500 mt-0.5"><?php echo $tpl['employment_type_name']; ?></div>
    </td>
    <td class="px-6 py-4 hidden md:table-cell text-sm text-slate-600 dark:text-slate-300">
        <?php echo $tpl['department_name']; ?>
    </td>
    <td class="px-6 py-4">
        <?php if($tpl['active_cycles'] > 0): ?>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400 border border-green-200 dark:border-green-500/20">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                Active Recruitment
            </span>
        <?php else: ?>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                Idle
            </span>
        <?php endif; ?>
        <div class="text-xs text-slate-400 mt-1"><?php echo $tpl['cycle_count']; ?> Total Cycles</div>
    </td>
    <td class="px-6 py-4 hidden md:table-cell text-sm">
        <?php if($tpl['latest_cycle_label']): ?>
            <div class="text-slate-900 dark:text-white font-medium"><?php echo htmlspecialchars($tpl['latest_cycle_label']); ?></div>
            <div class="text-xs text-slate-500 capitalize"><?php echo $tpl['latest_cycle_status']; ?></div>
        <?php else: ?>
            <span class="text-slate-400 italic text-xs">No history</span>
        <?php endif; ?>
    </td>
    <td class="px-6 py-4 text-right">
        <div class="flex justify-end items-center gap-2">
            <button onclick="launchCycle(<?php echo $tpl['id']; ?>, '<?php echo htmlspecialchars(addslashes($tpl['title'])); ?>')" 
                    class="flex items-center justify-center px-3 py-1.5 rounded-lg bg-primary text-white text-xs font-bold shadow-sm hover:bg-blue-600 transition-all">
                Launch Cycle
            </button>
            <button onclick="viewHistory(<?php echo $tpl['id']; ?>, '<?php echo htmlspecialchars(addslashes($tpl['title'])); ?>')" 
                    class="p-2 rounded-lg text-slate-400 hover:text-primary hover:bg-slate-100 dark:hover:bg-slate-800 transition-all" title="View History">
                <span class="material-symbols-outlined text-[20px]">history</span>
            </button>
            <a href="/admin/edit_job.php?id=<?php echo $tpl['id']; ?>" class="p-2 rounded-lg text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-all" title="Edit Template">
                <span class="material-symbols-outlined text-[20px]">edit</span>
            </a>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- MOBILE LIST -->
<div class="mobile-list">
    <?php foreach ($templates as $tpl): ?>
    <div class="bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm">
        <div class="flex justify-between items-start mb-3">
             <div>
                <h3 class="font-bold text-slate-900 dark:text-white text-lg"><?php echo htmlspecialchars($tpl['title']); ?></h3>
                <p class="text-xs text-slate-500"><?php echo $tpl['department_name']; ?></p>
            </div>
             <?php if($tpl['active_cycles'] > 0): ?>
                <span class="h-2 w-2 rounded-full bg-green-500"></span>
            <?php endif; ?>
        </div>
        
        <div class="grid grid-cols-2 gap-2 text-sm text-slate-600 dark:text-slate-400 border-t border-slate-100 dark:border-slate-800 pt-3 mt-1">
             <div>
                 <span class="block text-xs text-slate-400 uppercase">Cycles</span>
                 <?php echo $tpl['cycle_count']; ?>
             </div>
              <div class="text-right">
                 <span class="block text-xs text-slate-400 uppercase">Latest</span>
                 <?php echo $tpl['latest_cycle_label'] ? htmlspecialchars($tpl['latest_cycle_label']) : '-'; ?>
             </div>
        </div>

        <button onclick="launchCycle(<?php echo $tpl['id']; ?>, '<?php echo htmlspecialchars(addslashes($tpl['title'])); ?>')" 
                class="w-full mt-4 flex items-center justify-center h-10 rounded-lg bg-primary/10 text-primary font-bold text-sm hover:bg-primary hover:text-white transition-all">
            Launch Recruitment Cycle
        </button>
    </div>
    <?php endforeach; ?>
</div>

</div>

<!-- PAGINATION -->
<div id="paginationContainer">
<?php if ($totalPages > 1): ?>
<div class="flex flex-col md:flex-row items-center justify-between border-t border-slate-200 dark:border-slate-800 pt-8 mt-8 gap-6 px-6 md:px-8">
    <!-- Results Counter -->
    <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-400">
        Showing <span class="font-medium text-slate-900 dark:text-white"><?php echo $offset + 1; ?></span> to 
        <span class="font-medium text-slate-900 dark:text-white"><?php echo min($offset + $limit, $totalJobs); ?></span> of 
        <span class="font-medium text-slate-900 dark:text-white"><?php echo $totalJobs; ?></span> results
    </p>

    <!-- Pagination Controls -->
    <nav class="isolate inline-flex -space-x-px rounded-lg shadow-sm overflow-hidden" aria-label="Pagination">
        <!-- Previous -->
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" 
               class="relative inline-flex items-center rounded-l-lg px-3 py-2 text-slate-400 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 focus:z-20 focus:outline-offset-0 bg-white dark:bg-surface-dark transition-colors">
                <span class="sr-only">Previous</span>
                <span class="material-symbols-outlined text-sm">chevron_left</span>
            </a>
        <?php else: ?>
            <span class="relative inline-flex items-center rounded-l-lg px-3 py-2 text-slate-300 dark:text-slate-600 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 bg-slate-50 dark:bg-slate-800 cursor-not-allowed">
                <span class="sr-only">Previous</span>
                <span class="material-symbols-outlined text-sm">chevron_left</span>
            </span>
        <?php endif; ?>

        <!-- Numbers -->
        <?php 
        $range = 2; 
        $start = max(1, $page - $range);
        $end = min($totalPages, $page + $range);
        
        if($start > 1) {
            echo '<span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-400 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 bg-white dark:bg-surface-dark">...</span>';
        }
        
        for ($i = $start; $i <= $end; $i++): 
            $isActive = ($i == $page);
            $baseClass = "relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20 focus:outline-offset-0 transition-colors";
            $activeClass = "z-10 bg-primary text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary ring-1 ring-inset ring-primary";
            $inactiveClass = "text-slate-900 dark:text-slate-300 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 bg-white dark:bg-surface-dark";
            $class = $isActive ? "$baseClass $activeClass" : "$baseClass $inactiveClass";
        ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" class="<?php echo $class; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; 
        
        if($end < $totalPages) {
            echo '<span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-400 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 bg-white dark:bg-surface-dark">...</span>';
        }
        ?>

        <!-- Next -->
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" 
               class="relative inline-flex items-center rounded-r-lg px-3 py-2 text-slate-400 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 focus:z-20 focus:outline-offset-0 bg-white dark:bg-surface-dark transition-colors">
                <span class="sr-only">Next</span>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
            </a>
        <?php else: ?>
            <span class="relative inline-flex items-center rounded-r-lg px-3 py-2 text-slate-300 dark:text-slate-600 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 bg-slate-50 dark:bg-slate-800 cursor-not-allowed">
                <span class="sr-only">Next</span>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
            </span>
        <?php endif; ?>
    </nav>
</div>
<?php endif; ?>
</div>

</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>

</main>

<script>
    // Simple Search Handling
    $('#searchInput').on('keypress', function(e) {
        if(e.which === 13) {
            e.preventDefault();
            const val = $(this).val().trim();
            const url = new URL(window.location);
            if(val) url.searchParams.set('search', val);
            else url.searchParams.delete('search');
            url.searchParams.set('page', 1); // Reset page
            window.location.href = url.toString();
        }
    });

    // Launch Cycle Modal
    window.launchCycle = function(templateId, jobTitle) {
        Swal.fire({
            title: `Launch Recruitment: ${jobTitle}`,
            html: `
                <div class="flex flex-col gap-4 text-left">
                    <label class="flex flex-col gap-1">
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Cycle Label <span class="text-red-500">*</span></span>
                        <input id="swal-label" class="swal2-input m-0 w-full" placeholder="e.g. Summer 2026 Intake">
                    </label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex flex-col gap-1">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Open Date</span>
                            <input id="swal-open" type="date" class="swal2-input m-0 w-full" value="<?php echo date('Y-m-d'); ?>">
                        </label>
                        <label class="flex flex-col gap-1">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Close Date</span>
                            <input id="swal-close" type="date" class="swal2-input m-0 w-full">
                        </label>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Launch Cycle',
            confirmButtonColor: '#2563eb', // Primary Blue
            focusConfirm: false,
            preConfirm: () => {
                const label = document.getElementById('swal-label').value;
                const open = document.getElementById('swal-open').value;
                const close = document.getElementById('swal-close').value;

                if (!label) {
                    Swal.showValidationMessage('Cycle Label is required');
                    return false;
                }
                return { template_id: templateId, cycle_label: label, open_date: open, close_date: close };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Show Loading
                Swal.fire({
                    title: 'Launching...',
                    text: 'Creating recruitment cycle',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                $.ajax({
                    url: '/api/launch_cycle.php',
                    method: 'POST',
                    data: result.value,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Launched!',
                                text: 'The recruitment cycle is now active.',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Server error occurred', 'error');
                    }
                });
            }
        });
    };

    // Toggle Cycle Status
    window.toggleCycleStatus = function(id, status) {
        Swal.fire({
            title: status === 'published' ? 'Activate Cycle?' : 'Close Cycle?',
            text: status === 'published' ? 'This will make the job visible to candidates.' : 'This will stop new applications.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, proceed'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('/api/admin_job_actions.php', { action: 'update_status', job_id: id, status: status }, function(res) {
                    if(res.success) {
                        Swal.fire('Success', res.message, 'success').then(() => window.location.reload());
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                });
            }
        });
    };

    // View History Modal
    window.viewHistory = function(templateId, jobTitle) {
        Swal.fire({
            title: `History: ${jobTitle}`,
            html: '<div class="text-center py-4"><span class="material-symbols-outlined animate-spin text-4xl text-primary">progress_activity</span></div>',
            width: '800px',
            showConfirmButton: false,
            showCloseButton: true,
            didOpen: () => {
                $.ajax({
                    url: '/api/get_template_cycles.php',
                    data: { template_id: templateId },
                    success: function(response) {
                        if(response.success && response.cycles.length > 0) {
                            let rows = response.cycles.map(c => `
                                <tr class="border-b text-sm">
                                    <td class="py-3 text-left font-medium text-slate-900 dark:text-gray-100">${c.cycle_label || 'Unlabeled'}</td>
                                    <td class="py-3 text-left">
                                        <span class="px-2 py-1 rounded text-xs font-semibold ${c.status === 'published' ? 'bg-green-100 text-green-800' : (c.status === 'draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-slate-100 text-slate-600')}">
                                            ${c.status.charAt(0).toUpperCase() + c.status.slice(1)}
                                        </span>
                                    </td>
                                    <td class="py-3 text-left text-slate-500 dark:text-slate-400">${new Date(c.created_at).toLocaleDateString()}</td>
                                    <td class="py-3 text-left font-bold text-slate-700 dark:text-slate-300">${c.applicant_count}</td>
                                    <td class="py-3 text-right space-x-2">
                                        <a href="/job_board_%26_candidate_portal/view_job.php?id=${c.id}" target="_blank" class="text-blue-600 hover:underline text-xs">View Ad</a>
                                        ${c.status === 'draft' ? `<button onclick="toggleCycleStatus(${c.id}, 'published')" class="text-green-600 hover:text-green-800 text-xs font-bold">Activate</button>` : ''}
                                        ${c.status === 'published' ? `<button onclick="toggleCycleStatus(${c.id}, 'closed')" class="text-red-500 hover:text-red-700 text-xs text-left">Close</button>` : ''}
                                    </td>
                                </tr>
                            `).join('');
                            
                            Swal.getHtmlContainer().innerHTML = `
                                <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead class="bg-slate-50 dark:bg-slate-700 text-xs uppercase text-slate-500 dark:text-slate-300">
                                        <tr>
                                            <th class="py-2 px-1">Cycle</th>
                                            <th class="py-2 px-1">Status</th>
                                            <th class="py-2 px-1">Launched</th>
                                            <th class="py-2 px-1">Apps</th>
                                            <th class="py-2 px-1 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="dark:text-gray-200">${rows}</tbody>
                                </table>
                                </div>
                            `;
                        } else {
                            Swal.getHtmlContainer().innerHTML = '<div class="text-slate-500 py-8 italic">No recruitment cycles found for this template.</div>';
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Could not fetch history', 'error');
                    }
                });
            }
        });
    };
</script>

</body>
</html>
