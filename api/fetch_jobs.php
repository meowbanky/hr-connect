<?php
require_once __DIR__ . '/../config/db.php';
session_start();

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 1. Unified Search Term
$search = isset($_GET['search']) ? trim($_GET['search']) : (isset($_GET['q']) ? trim($_GET['q']) : '');

// 2. Status Logic (Default to 'published' if not specified, allowing 'all' or specific status for admin)
$status = isset($_GET['status']) ? trim($_GET['status']) : 'published'; 
if ($status === 'all') $status = ''; // Admin "All Statuses" usually passes empty or specific string

// 3. Additional Public Filters
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$types = isset($_GET['type']) ? $_GET['type'] : []; // Array
$levels = isset($_GET['level']) ? $_GET['level'] : []; // Array
$minSalary = isset($_GET['salary']) ? (int)$_GET['salary'] : 0;
$datePosted = isset($_GET['date']) ? $_GET['date'] : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'Newest';

// Build Query
$where = [];
$params = [];

// Status Filter
if ($status) {
    $where[] = "j.status = ?";
    $params[] = $status;
}

// Search Filter (Title, ID, Dept, Description)
if ($search) {
    $where[] = "(j.title LIKE ? OR j.id LIKE ? OR d.name LIKE ? OR j.description LIKE ?)";
    $term = "%$search%";
    $params[] = $term; // Title
    $params[] = $term; // ID
    $params[] = $term; // Dept
    $params[] = $term; // Desc
}

// Location Filter
if ($location) {
    $where[] = "j.location LIKE ?";
    $params[] = "%$location%";
}

// Job Type Filter (Array)
if (!empty($types) && is_array($types)) {
    $placeholders = implode(',', array_fill(0, count($types), '?'));
    $where[] = "j.employment_type IN ($placeholders)";
    foreach($types as $type) $params[] = $type;
}

// Experience Level Filter (Array)
if (!empty($levels) && is_array($levels)) {
    $placeholders = implode(',', array_fill(0, count($levels), '?'));
    $where[] = "j.experience_level IN ($placeholders)";
    foreach($levels as $level) $params[] = $level;
}

// Salary Filter (Min Salary)
if ($minSalary > 0) {
    // Assuming max_salary or min_salary check. Let's check if max_salary >= requested or min_salary >= requested
    // Simplest: min_salary >= requested value
    $where[] = "(j.min_salary >= ? OR j.max_salary >= ?)";
    $params[] = $minSalary;
    $params[] = $minSalary;
}

// Date Posted Filter
if ($datePosted) {
    $interval = 0;
    if ($datePosted === '24h') $interval = 1;
    elseif ($datePosted === '7d') $interval = 7;
    elseif ($datePosted === '30d') $interval = 30;

    if ($interval > 0) {
        $where[] = "j.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        $params[] = $interval;
    }
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Sort Logic
$orderBy = "j.created_at DESC"; // Default
if ($sortBy === 'Highest Salary') {
    $orderBy = "j.max_salary DESC";
} elseif ($sortBy === 'Most Relevant') {
    // Basic relevance if search term exists could be complex, keeping simple for now
    $orderBy = "j.created_at DESC"; 
} elseif ($sortBy === 'Oldest') {
    $orderBy = "j.created_at ASC";
}

// Count Total
$countQuery = "
    SELECT COUNT(*) 
    FROM job_postings j
    LEFT JOIN departments d ON j.department_id = d.id
    $whereSQL
";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalJobs = $countStmt->fetchColumn();
$totalPages = ceil($totalJobs / $limit);

// Fetch Jobs

// If user logged in, add user_id to params at the START
// We need to bind the user_id twice now (once for is_saved, once for application_status)
// Actually, let's optimize the query string construction to make binding easier.
// Or simpler: Just use a subquery that depends on a session value? No, bad practice.
// Let's rewrite the query slightly to be cleaner with parameters.

// New Query Construction with proper Parameter Binding order
$selectFields = "
    j.*, 
    d.name as department_name, 
    (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as applicant_count,
    (SELECT COUNT(*) FROM applications WHERE job_id = j.id AND status = 'hired') as hired_count
";

if (isset($_SESSION['user_id'])) {
    // We need to fetch candidate_id first to be safe, or subquery it.
    // Let's assume candidate_id link exists.
    $candidateSubquery = "(SELECT id FROM candidates WHERE user_id = ? LIMIT 1)";
    $selectFields .= ", 
    (SELECT COUNT(*) FROM saved_jobs sj WHERE sj.job_id = j.id AND sj.user_id = ?) as is_saved,
    (SELECT status FROM applications a WHERE a.job_id = j.id AND a.candidate_id = $candidateSubquery LIMIT 1) as application_status";
} else {
    $selectFields .= ", 0 as is_saved, NULL as application_status";
}

$query = "
    SELECT $selectFields
    FROM job_postings j
    LEFT JOIN departments d ON j.department_id = d.id
    $whereSQL
    ORDER BY $orderBy
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($query);

// Bind Params Logic
// 1. IS_SAVED and APP_STATUS params (User ID) - appearing TWICE in the SELECT clause if logged in
$execParams = [];
if (isset($_SESSION['user_id'])) {
    $execParams[] = $_SESSION['user_id']; // For is_saved
    $execParams[] = $_SESSION['user_id']; // For application_status
}
// 2. WHERE clause params (Search, Filters)
foreach($params as $p) {
    $execParams[] = $p;
}

$stmt->execute($execParams);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Start HTML Buffering for Rows
ob_start();
if (empty($jobs)): ?>
    <tr>
        <td colspan="7" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
            No jobs found.
        </td>
    </tr>
<?php else:
    foreach ($jobs as $job): 
        $statusClass = '';
        $statusLabel = ucfirst($job['status']);
        switch($job['status']) {
            case 'published': $statusClass = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'; break;
            case 'closed': $statusClass = 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300'; break;
            case 'draft': $statusClass = 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400'; break;
        }
    ?>
    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group job-row" data-id="<?php echo $job['id']; ?>" data-title="<?php echo strtolower($job['title']); ?>" data-status="<?php echo $job['status']; ?>">
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex flex-col gap-1">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm font-semibold text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($job['title']); ?></span>
                    <span class="md:hidden inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                        <?php echo $statusLabel; ?>
                    </span>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                    <span>ID: J-<?php echo 1000 + $job['id']; ?></span>
                    <span class="md:hidden">• <?php echo $job['applicant_count']; ?> Applicants</span>
                </div>
            </div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap hidden md:table-cell">
            <div class="flex items-center">
                <span class="text-sm text-slate-700 dark:text-slate-300"><?php echo htmlspecialchars($job['department_name'] ?? 'General'); ?></span>
            </div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap hidden md:table-cell">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                <?php echo $statusLabel; ?>
            </span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap hidden md:table-cell">
            <span class="text-sm text-slate-500 dark:text-slate-400"><?php echo $job['applicant_count']; ?> Applicants</span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400 hidden lg:table-cell">
            <?php echo date('M d, Y', strtotime($job['created_at'])); ?>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400 hidden lg:table-cell">
            <?php echo $job['application_deadline'] ? date('M d, Y', strtotime($job['application_deadline'])) : '-'; ?>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
            <div class="flex items-center justify-end gap-2 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity">
                <!-- View Job (Public) -->
                <a href="/job_board_%26_candidate_portal/view_job.php?id=<?php echo $job['id']; ?>" target="_blank" class="text-slate-400 hover:text-primary transition-colors action-icon" title="View Details">
                    <span class="material-symbols-outlined text-[20px]">visibility</span>
                </a>

                <!-- Edit Job -->
                <a href="/admin/edit_job.php?id=<?php echo $job['id']; ?>" class="text-slate-400 hover:text-blue-600 transition-colors action-icon" title="Edit Job">
                    <span class="material-symbols-outlined text-[20px]">edit</span>
                </a>
                
                <?php if($job['status'] === 'published'): ?>
                    <button class="text-slate-400 hover:text-amber-600 transition-colors action-btn action-icon" data-action="update_status" data-status="closed" title="Close Job">
                        <span class="material-symbols-outlined text-[20px]">block</span>
                    </button>
                <?php elseif($job['status'] === 'closed'): ?>
                        <button class="text-slate-400 hover:text-green-600 transition-colors action-btn action-icon" data-action="update_status" data-status="published" title="Reopen Job">
                        <span class="material-symbols-outlined text-[20px]">replay</span>
                    </button>
                <?php endif; ?>

                <button class="text-slate-400 hover:text-red-600 transition-colors action-btn action-icon" data-action="delete" title="Delete Job">
                    <span class="material-symbols-outlined text-[20px]">delete</span>
                </button>
            </div>
        </td>
    </tr>
    <?php endforeach;
endif;
$rowsHtml = ob_get_clean();

// Start HTML Buffering for Mobile Cards
ob_start();
if (empty($jobs)): ?>
    <div class="text-center py-8 text-slate-500 dark:text-slate-400">No jobs found.</div>
<?php else:
    foreach ($jobs as $job): ?>
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
    <?php endforeach;
endif;
$mobileHtml = ob_get_clean();

// Start HTML Buffering for Pagination
ob_start();
if ($totalPages > 1): ?>
    <div class="flex flex-col md:flex-row items-center justify-between border-t border-slate-200 dark:border-slate-800 pt-8 mt-8 gap-6 px-6 md:px-8">
        <!-- Results Counter -->
        <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-400">
            Showing <span class="font-medium text-slate-900 dark:text-white"><?php echo $offset + 1; ?></span> to 
            <span class="font-medium text-slate-900 dark:text-white"><?php echo min($offset + $limit, $totalJobs); ?></span> of 
            <span class="font-medium text-slate-900 dark:text-white"><?php echo $totalJobs; ?></span> results
        </p>

        <nav class="isolate inline-flex -space-x-px rounded-lg shadow-sm overflow-hidden" aria-label="Pagination">
            <!-- Previous -->
            <?php if ($page > 1): ?>
                <button onclick="fetchJobs(<?php echo $page - 1; ?>)" class="relative inline-flex items-center rounded-l-lg px-3 py-2 text-slate-400 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 focus:z-20 focus:outline-offset-0 bg-white dark:bg-surface-dark transition-colors">
                    <span class="sr-only">Previous</span>
                    <span class="material-symbols-outlined text-sm">chevron_left</span>
                </button>
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
                echo '<span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-400 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 bg-white dark:bg-surface-dark">...</span>';
            }
            
            for ($i = $start; $i <= $end; $i++): 
                $isActive = ($i == $page);
                $baseClass = "relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20 focus:outline-offset-0 transition-colors";
                $activeClass = "z-10 bg-primary text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary ring-1 ring-inset ring-primary";
                $inactiveClass = "text-slate-900 dark:text-slate-300 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 bg-white dark:bg-surface-dark";
                $class = $isActive ? "$baseClass $activeClass" : "$baseClass $inactiveClass";
            ?>
                <button onclick="fetchJobs(<?php echo $i; ?>)" class="<?php echo $class; ?>">
                    <?php echo $i; ?>
                </button>
            <?php endfor; 
            
            if($end < $totalPages) {
                echo '<span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-400 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 bg-white dark:bg-surface-dark">...</span>';
            }
            ?>

            <!-- Next -->
            <?php if ($page < $totalPages): ?>
                <button onclick="fetchJobs(<?php echo $page + 1; ?>)" class="relative inline-flex items-center rounded-r-lg px-3 py-2 text-slate-400 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 focus:z-20 focus:outline-offset-0 bg-white dark:bg-surface-dark transition-colors">
                    <span class="sr-only">Next</span>
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                </button>
            <?php else: ?>
                <span class="relative inline-flex items-center rounded-r-lg px-3 py-2 text-slate-300 dark:text-slate-600 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 bg-slate-50 dark:bg-slate-800 cursor-not-allowed">
                    <span class="sr-only">Next</span>
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                </span>
            <?php endif; ?>
        </nav>
    </div>
<?php endif;
$paginationHtml = ob_get_clean();

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'html' => $rowsHtml,
    'mobile_html' => $mobileHtml,
    'pagination' => $paginationHtml,
    'jobs' => $jobs,
    'total_jobs' => $totalJobs,
    'total_pages' => $totalPages,
    'current_page' => $page
]);
?>
