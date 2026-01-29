<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

// Check Admin Auth
if (!isset($_SESSION['user_role']) || 
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff' && $_SESSION['user_role'] !== 'superadmin')) {
    header("Location: ../admin/login.php");
    exit;
}

$pageTitle = "Manage Panelists";

// Fetch Panelists with Job Counts
// Handle Search
$search = $_GET['search'] ?? '';
$params = [];
$sql = "SELECT p.* FROM panelists p";

if ($search) {
    $sql .= " WHERE p.name LIKE ? OR p.email LIKE ? OR p.username LIKE ?";
    $term = "%$search%";
    $params = [$term, $term, $term];
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$panelists = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Jobs for all panelists avoiding N+1
$jobsSql = "SELECT pj.*, 
            CONCAT(j.title, ' (', COALESCE(j.cycle_label, 'Unlabeled'), ')') as title, 
            j.id as real_job_id 
            FROM panelist_jobs pj 
            JOIN job_postings j ON pj.job_id = j.id";
$allJobs = $pdo->query($jobsSql)->fetchAll(PDO::FETCH_ASSOC);
// Map jobs to panelists
$panelistJobs = [];
foreach ($allJobs as $job) {
    $panelistJobs[$job['panelist_id']][] = $job;
}

// Fetch all available jobs for dropdown
$availJobs = $pdo->query("SELECT id, CONCAT(title, ' (', COALESCE(cycle_label, 'Unlabeled'), ')') as title FROM job_postings WHERE status IN ('published', 'closed') ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Manage Panelists | HR Connect</title>
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
        .table-container { scrollbar-gutter: stable; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-dark-custom dark:text-white min-h-screen flex">

    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col md:ml-0 transition-all duration-300">
        
        <!-- Header -->
        <?php include __DIR__ . '/../includes/admin_header.php'; ?>

        <!-- Viewport Content -->
        <div class="p-8">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-slate-custom mb-6 uppercase tracking-widest">
                <a class="hover:text-primary" href="#">Admin</a>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <a class="hover:text-primary" href="#">Recruitment</a>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-dark-custom dark:text-white">Panelists</span>
            </nav>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-8">
                <div>
                    <h2 class="text-3xl font-black text-dark-custom dark:text-white tracking-tight">Manage Panelists</h2>
                    <p class="text-slate-500 mt-1">Configure your interview board and track invitation lifecycle.</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                    <!-- Search Input -->
                    <form method="GET" class="relative group w-full sm:w-64">
                         <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">search</span>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search panelists..." 
                               class="w-full pl-10 pr-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-primary/50 outline-none transition-all shadow-sm">
                    </form>

                    <a href="export_panelists?search=<?php echo urlencode($search); ?>" target="_blank" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-semibold flex items-center justify-center gap-2 hover:bg-slate-50 transition-colors">
                        <span class="material-symbols-outlined">file_download</span>
                        Export
                    </a>
                    <a href="/admin/invite_panelist" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-bold flex items-center justify-center gap-2 hover:opacity-90 transition-all shadow-md">
                        <span class="material-symbols-outlined">add</span>
                        New Panelist
                    </a>
                </div>
            </div>

            <!-- Data Table -->
            <div class="bg-white dark:bg-[#1a1a2e] border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
                
                <div class="overflow-x-auto table-container">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/50 text-slate-custom">
                                <th class="p-4 w-10">
                                    <input class="rounded border-slate-300 text-primary focus:ring-primary" type="checkbox"/>
                                </th>
                                <th class="p-4 text-xs font-bold uppercase tracking-wider">Name / Email</th>
                                <th class="p-4 text-xs font-bold uppercase tracking-wider">Assigned Job Roles</th>
                                <th class="p-4 text-xs font-bold uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            
                            <?php if (empty($panelists)): ?>
                                <tr>
                                    <td colspan="4" class="p-8 text-center text-slate-500">
                                        No panelists found. Invite one to get started.
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($panelists as $p): 
                                $myJobs = $panelistJobs[$p['id']] ?? [];
                            ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group">
                                <td class="p-4 align-top">
                                    <input class="rounded border-slate-300 text-primary focus:ring-primary" type="checkbox"/>
                                </td>
                                <td class="p-4 align-top w-1/4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center font-bold text-slate-400 text-xs uppercase shrink-0">
                                            <?php echo substr($p['name'], 0, 2); ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-sm text-dark-custom dark:text-white"><?php echo htmlspecialchars($p['name']); ?></p>
                                            <p class="text-xs text-slate-500"><?php echo htmlspecialchars($p['email']); ?></p>
                                            <div class="mt-1">
                                                <span class="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-slate-100 text-slate-500"><?php echo htmlspecialchars($p['role']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="space-y-2">
                                        <?php if(empty($myJobs)): ?>
                                            <span class="text-xs text-slate-400 italic">No active assignments</span>
                                        <?php else: ?>
                                            <?php foreach ($myJobs as $job): ?>
                                                <div class="flex items-center justify-between bg-slate-50 dark:bg-slate-800/50 p-2 rounded-lg border border-slate-100 dark:border-slate-700/50">
                                                    <div class="flex items-center gap-2">
                                                        <span class="material-symbols-outlined text-base text-slate-400">work</span>
                                                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300"><?php echo htmlspecialchars($job['title']); ?></span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <?php if ($job['status'] === 'active' || $job['status'] === 'accepted'): ?>
                                                            <span class="text-[10px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full border border-green-100">
                                                                Accepted
                                                            </span>
                                                        <?php elseif ($job['status'] === 'pending'): ?>
                                                            <span class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-100 flex items-center gap-1">
                                                                Pending
                                                                <button onclick="resendInvite(<?php echo $p['id']; ?>, <?php echo $job['real_job_id']; ?>)" class="ml-1 hover:text-amber-800 underline decoration-dotted" title="Resend Invite Email">Resend</button>
                                                            </span>
                                                        <?php else: ?>
                                                             <span class="text-[10px] font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full border border-red-100">
                                                                <?php echo ucfirst($job['status']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        
                                                        <button onclick="toggleChairman(<?php echo $p['id']; ?>, <?php echo $job['real_job_id']; ?>, <?php echo $job['is_chairman']; ?>)" 
                                                                class="p-0.5 rounded transition-colors <?php echo $job['is_chairman'] ? 'text-amber-400 hover:text-slate-400' : 'text-slate-200 hover:text-amber-400'; ?>" 
                                                                title="<?php echo $job['is_chairman'] ? 'Revoke Chairman' : 'Appoint Chairman'; ?>">
                                                            <span class="material-symbols-outlined text-base <?php echo $job['is_chairman'] ? 'fill-current' : ''; ?>">star</span>
                                                        </button>

                                                        <button onclick="removeJob(<?php echo $p['id']; ?>, <?php echo $job['real_job_id']; ?>)" class="text-slate-400 hover:text-red-500 p-0.5 rounded transition-colors" title="Remove Assignment">
                                                            <span class="material-symbols-outlined text-base">close</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        
                                        <!-- Add Role Button -->
                                        <button onclick="openAddJobModal(<?php echo $p['id']; ?>, '<?php echo addslashes($p['name']); ?>')" class="text-xs font-semibold text-primary hover:text-primary/80 flex items-center gap-1 mt-2 pl-1">
                                            <span class="material-symbols-outlined text-sm">add_circle</span>
                                            Assign New Role
                                        </button>
                                    </div>
                                </td>
                                <td class="p-4 text-right align-top">
                                    <button onclick="deletePanelist(<?php echo $p['id']; ?>)" class="p-2 hover:bg-red-50 dark:hover:bg-red-900/10 rounded-lg text-slate-400 hover:text-red-500 transition-colors tooltip" title="Delete Account">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <!-- Add Job Modal (Hidden) -->
    <!-- SweetAlert will handle the UI for adding job -->

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const jobs = <?php echo json_encode($availJobs); ?>;
        
        // Build map of Panelist ID -> Array of Assigned Job IDs
        const panelistJobMap = {};
        <?php foreach ($panelistJobs as $pid => $pJobs): ?>
            panelistJobMap[<?php echo $pid; ?>] = [<?php echo implode(',', array_column($pJobs, 'real_job_id')); ?>];
        <?php endforeach; ?>

        function openAddJobModal(panelistId, panelistName) {
            // Get already assigned jobs
            const assignedIds = panelistJobMap[panelistId] || [];
            
            // Filter available jobs (ensure string comparison)
            const availableOptions = jobs.filter(j => !assignedIds.map(String).includes(String(j.id)));

            if (availableOptions.length === 0) {
                Swal.fire('No Jobs Available', 'This panelist is already assigned to all active job postings.', 'info');
                return;
            }

            // Build select HTML
            let optionsHtml = availableOptions.map(j => `<option value="${j.id}">${j.title}</option>`).join('');
            
            Swal.fire({
                title: 'Assign Job Roles',
                html: `
                    <p class="text-sm text-slate-500 mb-4">Select one or more roles to assign to <strong>${panelistName}</strong></p>
                    <div class="text-left">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Available Jobs</label>
                        <select id="swal-job-select" class="w-full border-slate-300 rounded-lg focus:ring-primary focus:border-primary text-sm form-multiselect h-48" multiple>
                            ${optionsHtml}
                        </select>
                        <p class="text-xs text-slate-400 mt-2">Hold Ctrl/Cmd to select multiple options.</p>
                        
                        <div class="mt-4 flex items-center gap-2">
                             <input type="checkbox" id="swal-chairman-check" class="rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                             <label for="swal-chairman-check" class="text-sm font-bold text-slate-700">Appoint as Chairman?</label>
                        </div>
                        <p class="text-xs text-slate-400 ml-6">If selected, they will lead evaluations for ALL selected jobs.</p>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Assign & Invite',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const select = document.getElementById('swal-job-select');
                    const isChairman = document.getElementById('swal-chairman-check').checked ? 1 : 0;
                    
                    // Get selected values
                    const selectedValues = Array.from(select.selectedOptions).map(option => option.value);

                    if (selectedValues.length === 0) {
                        Swal.showValidationMessage('Please select at least one job');
                        return false;
                    }

                    // Send comma-separated IDs or handle array in backend. 
                    // Let's modify logic to send comma-separated and handle it in backend.
                    return fetch('../api/admin_panelist_actions.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `action=add_job&panelist_id=${panelistId}&job_id=${selectedValues.join(',')}&is_chairman=${isChairman}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) throw new Error(data.message);
                        return data;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error}`);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire('Assigned!', 'Panelist has been invited to the new roles.', 'success').then(() => location.reload());
                }
            });
        }

        function resendInvite(panelistId, jobId) {
            Swal.fire({
                title: 'Resend Invitation?',
                text: "This will send a fresh email with the access credentials.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, send it',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch('../api/admin_panelist_actions.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `action=resend_invite&panelist_id=${panelistId}&job_id=${jobId}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(!data.success) throw new Error(data.message);
                    })
                    .catch(error => Swal.showValidationMessage(error));
                }
            }).then((result) => {
                if(result.isConfirmed) {
                    Swal.fire('Sent!', 'Invitation has been resent.', 'success');
                }
            });
        }

        function removeJob(panelistId, jobId) {
             Swal.fire({
                title: 'Remove Assignment?',
                text: "The panelist will lose access to this job's dashboard.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, remove',
                preConfirm: () => {
                     return fetch('../api/admin_panelist_actions.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `action=remove_job&panelist_id=${panelistId}&job_id=${jobId}`
                    }).then(res => res.json())
                }
            }).then((result) => {
                if(result.isConfirmed && result.value.success) {
                    Swal.fire('Removed', 'Assignment removed successfully.', 'success').then(() => location.reload());
                } else if (result.isConfirmed) {
                    Swal.fire('Error', result.value.message, 'error');
                }
            });
        }

        function toggleChairman(panelistId, jobId, currentStatus) {
            const newStatus = currentStatus ? 0 : 1;
            const actionText = newStatus ? 'Appoint' : 'Revoke';
            
            Swal.fire({
                title: `${actionText} Chairman?`,
                text: newStatus ? "This panelist will lead evaluations." : "This panelist will no longer be chairman.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: `Yes, ${actionText}`,
                preConfirm: () => {
                   return fetch('../api/admin_panelist_actions.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `action=set_chairman&panelist_id=${panelistId}&job_id=${jobId}&is_chairman=${newStatus}`
                    }).then(res => res.json())
                }
            }).then((result) => {
                 if(result.isConfirmed && result.value.success) {
                    Swal.fire('Updated', `Chairman status updated.`, 'success').then(() => location.reload());
                } else if (result.isConfirmed) {
                    Swal.fire('Error', result.value.message, 'error');
                }
            });
        }

        function deletePanelist(id) {
            Swal.fire({
                title: 'Delete Account?',
                text: "This will permanently remove the panelist.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete',
                preConfirm: () => {
                    return fetch('../api/delete_panelist.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ id: id })
                    }).then(res => res.json())
                }
            }).then((result) => {
                 if(result.isConfirmed && result.value.success) {
                    Swal.fire('Deleted!', 'Panelist account deleted.', 'success').then(() => location.reload());
                }
            });
        }
    </script>
</body>
</html>
