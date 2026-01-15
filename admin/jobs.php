<?php
session_start();

// Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || 
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff')) {
    header('Location: /admin/login');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

$pageTitle = 'Manage Job Postings';

try {
    $page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $limit  = 10;
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if ($search) {
        $where[] = "(j.title LIKE ? OR j.id LIKE ? OR d.name LIKE ?)";
        $term = "%$search%";
        $params = array_merge($params, [$term, $term, $term]);
    }

    if ($status) {
        $where[] = "j.status = ?";
        $params[] = $status;
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM job_postings j
        LEFT JOIN departments d ON j.department_id = d.id
        $whereSQL
    ");
    $countStmt->execute($params);
    $totalJobs = $countStmt->fetchColumn();
    $totalPages = ceil($totalJobs / $limit);

    $stmt = $pdo->prepare("
        SELECT 
            j.*,
            d.name AS department_name,
            (SELECT COUNT(*) FROM applications WHERE job_id = j.id) AS applicant_count
        FROM job_postings j
        LEFT JOIN departments d ON j.department_id = d.id
        $whereSQL
        ORDER BY j.created_at DESC
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stats = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM job_postings WHERE status='published') AS active_jobs,
            (SELECT COUNT(*) FROM candidates) AS total_candidates,
            (SELECT COUNT(*) FROM job_postings 
                WHERE application_deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ) AS closing_soon
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
<title>Manage Job Postings</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
<link href="/assets/css/style.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body { font-family: Inter, sans-serif; }

/* Responsive Layout */
@media (max-width: 767px) {
    .desktop-table { display: none; }
    .mobile-jobs { display: flex; flex-direction: column; gap: 1rem; }
}

@media (min-width: 768px) {
    .mobile-jobs { display: none; }
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
        <h1 class="text-2xl sm:text-3xl font-black">Manage Job Postings</h1>
        <p class="text-slate-500 text-sm">Recruitment & listings</p>
    </div>
    <a href="/admin/jobs/create" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-bold">
        + Create Job
    </a>
</div>

<!-- STATS -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <?php foreach ([
        ['Active Jobs', $stats['active_jobs']],
        ['Total Candidates', $stats['total_candidates']],
        ['Closing This Week', $stats['closing_soon']]
    ] as $s): ?>
    <div class="bg-white dark:bg-surface-dark p-4 rounded-xl border">
        <p class="text-xs text-slate-500"><?php echo $s[0]; ?></p>
        <p class="text-3xl font-bold"><?php echo $s[1]; ?></p>
    </div>
    <?php endforeach; ?>
</div>

<!-- SEARCH & FILTER -->
<div class="flex flex-col sm:flex-row gap-4 py-4 border-b border-slate-200 dark:border-slate-800">
    <form method="GET" class="flex-1 relative" onsubmit="event.preventDefault();">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
        <input type="text" id="searchInput" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search job title, ID..." 
               class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-surface-dark focus:ring-2 focus:ring-primary/50 outline-none transition-all shadow-sm">
        
        <!-- Clear Button -->
        <button type="button" id="clearSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 <?php echo empty($search) ? 'hidden' : ''; ?>">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
        
        <?php if($status): ?><input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>"><?php endif; ?>
    </form>
    <form method="GET" class="w-full sm:w-auto">
        <?php if($search): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>
        <select name="status" onchange="this.form.submit()" class="w-full sm:w-[180px] px-4 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-surface-dark focus:ring-2 focus:ring-primary/50 outline-none transition-all shadow-sm cursor-pointer">
            <option value="">All Statuses</option>
            <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
            <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
            <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
        </select>
    </form>
</div>

<!-- DESKTOP TABLE -->
<div class="desktop-table bg-white dark:bg-surface-dark rounded-xl border overflow-hidden">
<table class="min-w-full divide-y">
<thead class="bg-slate-50 dark:bg-slate-800">
<tr>
<th class="px-6 py-3 text-left text-xs">JOB</th>
<th class="px-6 py-3 text-left text-xs hidden md:table-cell">DEPT</th>
<th class="px-6 py-3 text-left text-xs hidden md:table-cell">STATUS</th>
<th class="px-6 py-3 text-left text-xs hidden md:table-cell">CANDIDATES</th>
<th class="px-6 py-3"></th>
</tr>
</thead>
<tbody id="jobsTableBody">
<?php foreach ($jobs as $job): ?>
<tr class="border-t hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors" data-id="<?php echo $job['id']; ?>">
<td class="px-6 py-4">
    <strong><?php echo htmlspecialchars($job['title']); ?></strong>
    <div class="text-xs text-slate-500">J-<?php echo 1000 + $job['id']; ?></div>
</td>
<td class="px-6 py-4 hidden md:table-cell"><?php echo $job['department_name']; ?></td>
<td class="px-6 py-4 hidden md:table-cell">
    <span class="px-2 py-1 rounded text-xs font-semibold
        <?php echo $job['status'] === 'published' ? 'bg-green-100 text-green-800' : 
                  ($job['status'] === 'closed' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800'); ?>">
        <?php echo ucfirst($job['status']); ?>
    </span>
</td>
<td class="px-6 py-4 hidden md:table-cell"><?php echo $job['applicant_count']; ?></td>
<td class="px-6 py-4 text-right space-x-2">
    <a href="/job_board_%26_candidate_portal/view_job.php?id=<?php echo $job['id']; ?>" class="text-slate-400 hover:text-primary transition-colors" title="View">
        <span class="material-symbols-outlined text-[20px]">visibility</span>
    </a>
    <a href="/admin/edit_job.php?id=<?php echo $job['id']; ?>" class="text-slate-400 hover:text-blue-600 transition-colors" title="Edit">
        <span class="material-symbols-outlined text-[20px]">edit</span>
    </a>
    
    <?php if($job['status'] === 'published'): ?>
        <button class="action-btn text-slate-400 hover:text-amber-600 transition-colors" data-action="update_status" data-status="closed" title="Close Job">
            <span class="material-symbols-outlined text-[20px]">block</span>
        </button>
    <?php elseif($job['status'] === 'closed'): ?>
         <button class="action-btn text-slate-400 hover:text-green-600 transition-colors" data-action="update_status" data-status="published" title="Reopen Job">
            <span class="material-symbols-outlined text-[20px]">replay</span>
        </button>
    <?php endif; ?>

    <button class="action-btn text-slate-400 hover:text-red-600 transition-colors" data-action="delete" title="Delete">
        <span class="material-symbols-outlined text-[20px]">delete</span>
    </button>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- MOBILE CARDS -->
<div class="mobile-jobs">
<?php foreach ($jobs as $job): ?>
<div class="bg-white dark:bg-surface-dark border rounded-xl p-4" data-id="<?php echo $job['id']; ?>">
    <div class="flex justify-between items-start">
        <div>
            <h3 class="font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($job['title']); ?></h3>
            <p class="text-xs text-slate-500 mt-1">
                J-<?php echo 1000 + $job['id']; ?> • <?php echo $job['department_name']; ?>
            </p>
        </div>
        <span class="text-xs px-2 py-1 rounded font-medium
            <?php echo $job['status'] === 'published' ? 'bg-green-100 text-green-800' : 
                      ($job['status'] === 'closed' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800'); ?>">
            <?php echo ucfirst($job['status']); ?>
        </span>
    </div>

    <div class="flex justify-between text-sm mt-3 text-slate-600 dark:text-slate-400 border-t border-slate-100 dark:border-slate-800 pt-3">
        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">group</span> <?php echo $job['applicant_count']; ?> Applicants</span>
        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">calendar_today</span> <?php echo date('M d, Y', strtotime($job['created_at'])); ?></span>
    </div>

    <div class="flex justify-end gap-3 mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
        <a href="/job_board_%26_candidate_portal/view_job.php?id=<?php echo $job['id']; ?>" class="p-2 rounded-full hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-400 hover:text-primary transition-colors">
            <span class="material-symbols-outlined">visibility</span>
        </a>
        <a href="/admin/edit_job.php?id=<?php echo $job['id']; ?>" class="p-2 rounded-full hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-400 hover:text-blue-600 transition-colors">
            <span class="material-symbols-outlined">edit</span>
        </a>
        
        <?php if($job['status'] === 'published'): ?>
            <button class="action-btn p-2 rounded-full hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-400 hover:text-amber-600 transition-colors" data-action="update_status" data-status="closed" title="Close Job">
                <span class="material-symbols-outlined">block</span>
            </button>
        <?php elseif($job['status'] === 'closed'): ?>
             <button class="action-btn p-2 rounded-full hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-400 hover:text-green-600 transition-colors" data-action="update_status" data-status="published" title="Reopen Job">
                <span class="material-symbols-outlined">replay</span>
            </button>
        <?php endif; ?>

        <button class="action-btn p-2 rounded-full hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-400 hover:text-red-600 transition-colors" data-action="delete">
            <span class="material-symbols-outlined">delete</span>
        </button>
    </div>
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
    function fetchJobs(page = 1) {
        const search = $('#searchInput').val();
        const status = $('select[name="status"]').val(); // Get current status

        $.ajax({
            url: '/api/fetch_jobs.php',
            method: 'GET',
            data: {
                page: page,
                search: search,
                status: status
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#jobsTableBody').html(response.html);
                    $('.mobile-jobs').html(response.mobile_html);
                    
                    // Update Pagination Wrapper
                    // Since the API returns the inner Pagination logic, we need to locate the wrapper
                    // Or easier: replace the entire pagination block if we wrapped it.
                    // The API returns the content starting from <div class="flex flex-col...
                    // So we should target the parent of the current pagination div or replace it by ID if we added one. 
                    // Let's assume we replace the last `div` in main content or give it an ID.
                    // Best approach: Add ID `paginationContainer` to the wrapper in PHP and update it here.
                    $('#paginationContainer').html(response.pagination);
                    
                    // Update URL without reload
                    const url = new URL(window.location);
                    url.searchParams.set('page', page);
                    if(search) url.searchParams.set('search', search); else url.searchParams.delete('search');
                    if(status) url.searchParams.set('status', status); else url.searchParams.delete('status');
                    window.history.pushState({}, '', url);

                } else {
                    console.error('Failed to fetch jobs');
                }
            },
            error: function() {
                console.error('Error fetching jobs');
            }
        });
    }

$(document).ready(function() {
    let debounceTimer;

    // Search Input Logic
    $('#searchInput').on('input', function() {
        const val = $(this).val().trim();
        
        // Toggle Clear Button
        if (val.length > 0) {
            $('#clearSearch').removeClass('hidden');
        } else {
            $('#clearSearch').addClass('hidden');
        }

        clearTimeout(debounceTimer);
        
        // "Start filtering when the length > 3"
        if (val.length > 3 || val.length === 0) {
            debounceTimer = setTimeout(() => {
                fetchJobs(1);
            }, 300);
        }
    });

    // Clear Search Logic
    $('#clearSearch').on('click', function() {
        $('#searchInput').val('');
        $(this).addClass('hidden');
        fetchJobs(1);
    });

    // Handle Pagination Clicks (Delegated to body for dynamic content)
    // Note: The new pagination uses `onclick="fetchJobs(N)"` inline, which is fine.
    // But if we want to intercept links (<a> tags) from the initial load:
    $(document).on('click', '#paginationContainer nav a', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        const urlParams = new URLSearchParams(href.split('?')[1]);
        const page = urlParams.get('page') || 1;
        fetchJobs(page);
    });

    // Action Buttons (Delete, Status)
    $(document).on('click', '.action-btn', function(e) {
        e.preventDefault();
        const btn = $(this);
        const action = btn.data('action');
        const container = btn.closest('[data-id]');
        const jobId = container.data('id');
        
        const performAction = () => {
             let data = { action: action, job_id: jobId };
            if (action === 'update_status') {
                data.status = btn.data('status');
            }
            
            $.ajax({
                url: '/api/admin_job_actions.php',
                method: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message || 'Action completed successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            // Reload current state instead of full reload
                            const params = new URLSearchParams(window.location.search);
                            fetchJobs(params.get('page') || 1);
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'An error occurred.',
                        });
                    }
                },
                error: function() {
                     Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'A server error occurred.',
                    });
                }
            });
        };

        if (action === 'delete') {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    performAction();
                }
            });
        } else if (action === 'update_status') {
             const newStatus = btn.data('status');
             const confirmText = newStatus === 'closed' ? 'close' : 'reopen';
             const confirmColor = newStatus === 'closed' ? '#ef4444' : '#10b981';
             
             Swal.fire({
                title: `Are you sure you want to ${confirmText} this job?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                cancelButtonColor: '#64748b',
                confirmButtonText: `Yes, ${confirmText} it!`
            }).then((result) => {
                if (result.isConfirmed) {
                    performAction();
                }
            });
        }
    });
});
</script>

</body>
</html>
