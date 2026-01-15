<?php
require_once __DIR__ . '/../config/db.php';
session_start();

// Security Check: Only Admins or HR Staff
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : '30';

try {
    // Base Query
    $sql = "SELECT 
                a.id as application_id,
                a.status,
                a.application_date,
                j.title as job_title,
                u.first_name,
                u.last_name,
                u.email,
                u.profile_image,
                c.highest_qualification,
                c.years_of_experience
            FROM applications a
            JOIN job_postings j ON a.job_id = j.id
            JOIN candidates c ON a.candidate_id = c.id
            JOIN users u ON c.user_id = u.id
            WHERE 1=1";

    $params = [];

    // Search Filter (Name, Email, Job Title)
    if (!empty($search)) {
        $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR j.title LIKE ?)";
        $term = "%$search%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    // Status Filter
    if (!empty($status) && $status !== 'all') {
        $sql .= " AND a.status = ?";
        $params[] = $status;
    }

    // Job Filter
    if ($job_id > 0) {
        $sql .= " AND a.job_id = ?";
        $params[] = $job_id;
    }

    // Date Filter (Simple logic: Last X days)
    if ($date_range !== 'all') {
        $days = (int)$date_range;
        if($days > 0) {
            $sql .= " AND a.application_date >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            $params[] = $days;
        }
    }

    // Count Total
    $countSql = str_replace(
        "SELECT 
                a.id as application_id,
                a.status,
                a.application_date,
                j.title as job_title,
                u.first_name,
                u.last_name,
                u.email,
                u.profile_image,
                c.highest_qualification,
                c.years_of_experience", 
        "SELECT COUNT(*) as total", 
        $sql
    );
    
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalApps = $data['total'] ?? 0;
    $totalPages = ceil($totalApps / $limit);

    // Fetch Data
    $sql .= " ORDER BY a.application_date DESC LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate HTML for Rows
    ob_start();
    if (empty($applications)): ?>
        <tr>
            <td colspan="8" class="p-8 text-center text-slate-500 dark:text-slate-400">
                <div class="flex flex-col items-center gap-2">
                    <span class="material-symbols-outlined text-4xl text-slate-300">inbox</span>
                    <p>No applications found matching your criteria.</p>
                </div>
            </td>
        </tr>
    <?php else:
        foreach ($applications as $app): 
            $statusColors = [
                'pending' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
                'reviewed' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300',
                'shortlisted' => 'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-300',
                'interviewed' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300',
                'offered' => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-900/20 dark:text-cyan-300',
                'hired' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                'rejected' => 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300',
            ];
            $statusClass = $statusColors[$app['status']] ?? $statusColors['pending'];
            $initials = strtoupper(substr($app['first_name'], 0, 1) . substr($app['last_name'], 0, 1));
        ?>
        <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors border-b last:border-0 border-slate-100 dark:border-slate-800">
             <td class="p-4 align-middle">
                <input type="checkbox" value="<?php echo $app['application_id']; ?>" class="app-checkbox rounded border-slate-300 dark:border-slate-600 text-primary focus:ring-primary h-4 w-4 cursor-pointer">
            </td>
            <td class="p-4 align-middle">
                <div class="flex items-center gap-3">
                    <?php if($app['profile_image']): ?>
                        <div class="h-10 w-10 rounded-full bg-cover bg-center border border-slate-200 dark:border-slate-700" style="background-image: url('/uploads/profile_images/<?php echo htmlspecialchars($app['profile_image']); ?>');"></div>
                    <?php else: ?>
                        <div class="h-10 w-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-400 text-xs font-bold border border-slate-200 dark:border-slate-700">
                            <?php echo $initials; ?>
                        </div>
                    <?php endif; ?>
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-primary transition-colors">
                            <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?>
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($app['email']); ?></span>
                    </div>
                </div>
            </td>
            <td class="p-4 align-middle text-sm text-slate-700 dark:text-slate-300 font-medium">
                <?php echo htmlspecialchars($app['job_title']); ?>
            </td>
            <td class="p-4 align-middle text-sm text-slate-500 dark:text-slate-400">
                <?php echo date('M d, Y', strtotime($app['application_date'])); ?>
            </td>
            <td class="p-4 align-middle text-center">
                <!-- Placeholder for automated screening logic aka Min Reqs -->
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-500 border border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700">
                    <span class="material-symbols-outlined text-[14px]">remove</span>
                    N/A
                </span>
            </td>
            <td class="p-4 align-middle">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium <?php echo $statusClass; ?>">
                    <?php echo ucfirst($app['status']); ?>
                </span>
            </td>
            <td class="p-4 align-middle text-right">
                <div class="flex items-center justify-end gap-2">
                    <a href="/admin/view_application.php?id=<?php echo $app['application_id']; ?>" class="p-1.5 rounded text-slate-400 hover:text-primary hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors" title="View Profile">
                        <span class="material-symbols-outlined text-[20px]">visibility</span>
                    </a>
                    
                    <?php if($app['status'] !== 'hired' && $app['status'] !== 'rejected'): ?>
                        <button onclick="updateStatus(<?php echo $app['application_id']; ?>, 'shortlisted')" class="p-1.5 rounded text-green-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors" title="Shortlist">
                            <span class="material-symbols-outlined text-[20px]">check</span>
                        </button>
                        <button onclick="updateStatus(<?php echo $app['application_id']; ?>, 'rejected')" class="p-1.5 rounded text-red-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="Reject">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach;
    endif;
    $html = ob_get_clean();

    // Generate Mobile HTML
    ob_start();
    if (empty($applications)): ?>
        <div class="flex flex-col items-center justify-center p-8 text-center text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
            <span class="material-symbols-outlined text-4xl text-slate-300 mb-2">inbox</span>
            <p>No applications found.</p>
        </div>
    <?php else:
        foreach ($applications as $app): 
            $statusColors = [
                'pending' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
                'reviewed' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300',
                'shortlisted' => 'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-300',
                'interviewed' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300',
                'offered' => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-900/20 dark:text-cyan-300',
                'hired' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                'rejected' => 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300',
            ];
            $statusClass = $statusColors[$app['status']] ?? $statusColors['pending'];
            $initials = strtoupper(substr($app['first_name'], 0, 1) . substr($app['last_name'], 0, 1));
        ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 flex flex-col gap-3 shadow-sm mb-4">
            <div class="flex justify-between items-start">
                <div class="flex items-center gap-3">
                    <?php if($app['profile_image']): ?>
                        <div class="h-10 w-10 min-w-[2.5rem] rounded-full bg-cover bg-center border border-slate-200 dark:border-slate-700" style="background-image: url('/uploads/profile_images/<?php echo htmlspecialchars($app['profile_image']); ?>');"></div>
                    <?php else: ?>
                        <div class="h-10 w-10 min-w-[2.5rem] rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-400 text-xs font-bold border border-slate-200 dark:border-slate-700">
                            <?php echo $initials; ?>
                        </div>
                    <?php endif; ?>
                    <div class="overflow-hidden">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate"><?php echo htmlspecialchars($app['job_title']); ?></p>
                    </div>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider <?php echo $statusClass; ?>">
                    <?php echo $app['status']; ?>
                </span>
            </div>
            
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-700 pt-3 mt-1">
                <span class="flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">calendar_today</span>
                    <?php echo date('M d, Y', strtotime($app['application_date'])); ?>
                </span>
                
                <div class="flex items-center gap-2">
                    <?php if($app['status'] !== 'hired' && $app['status'] !== 'rejected'): ?>
                        <button onclick="updateStatus(<?php echo $app['application_id']; ?>, 'shortlisted')" class="p-1.5 rounded bg-green-50 text-green-600 hover:bg-green-100 dark:bg-green-900/20 dark:text-green-400 dark:hover:bg-green-900/40 transition-colors" title="Shortlist">
                            <span class="material-symbols-outlined text-[18px]">check</span>
                        </button>
                        <button onclick="updateStatus(<?php echo $app['application_id']; ?>, 'rejected')" class="p-1.5 rounded bg-red-50 text-red-500 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40 transition-colors" title="Reject">
                            <span class="material-symbols-outlined text-[18px]">close</span>
                        </button>
                    <?php endif; ?>
                    <a href="/admin/view_application.php?id=<?php echo $app['application_id']; ?>" class="p-1.5 rounded bg-slate-50 dark:bg-slate-700 text-slate-500 dark:text-slate-300 hover:text-primary transition-colors" title="View Profile">
                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach;
    endif;
    $mobileHtml = ob_get_clean();

    // Pagination HTML
    ob_start();
    if ($totalPages > 1): ?>
        <div class="flex flex-col md:flex-row items-center justify-between border-t border-slate-200 dark:border-slate-800 pt-8 mt-8 gap-6 px-6 md:px-8">
            <!-- Results Counter -->
            <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-400">
                Showing <span class="font-medium text-slate-900 dark:text-white"><?php echo $offset + 1; ?></span> to 
                <span class="font-medium text-slate-900 dark:text-white"><?php echo min($offset + $limit, $totalApps); ?></span> of 
                <span class="font-medium text-slate-900 dark:text-white"><?php echo $totalApps; ?></span> results
            </p>

            <!-- Pagination Controls -->
            <nav class="isolate inline-flex -space-x-px rounded-lg shadow-sm overflow-hidden" aria-label="Pagination">
                <!-- Previous -->
                <?php if ($page > 1): ?>
                    <button onclick="fetchApplications(<?php echo $page - 1; ?>)" class="relative inline-flex items-center rounded-l-lg px-3 py-2 text-slate-400 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 focus:z-20 focus:outline-offset-0 bg-white dark:bg-slate-800 transition-colors">
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
                    echo '<span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-400 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 bg-white dark:bg-slate-800">...</span>';
                }
                
                for ($i = $start; $i <= $end; $i++): 
                    $isActive = ($i == $page);
                    $baseClass = "relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20 focus:outline-offset-0 transition-colors cursor-pointer";
                    $activeClass = "z-10 bg-primary text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary ring-1 ring-inset ring-primary";
                    $inactiveClass = "text-slate-900 dark:text-slate-300 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 bg-white dark:bg-slate-800";
                    $class = $isActive ? "$baseClass $activeClass" : "$baseClass $inactiveClass";
                ?>
                    <button onclick="fetchApplications(<?php echo $i; ?>)" class="<?php echo $class; ?>">
                        <?php echo $i; ?>
                    </button>
                <?php endfor; 
                
                if($end < $totalPages) {
                    echo '<span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-400 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 bg-white dark:bg-slate-800">...</span>';
                }
                ?>

                <!-- Next -->
                <?php if ($page < $totalPages): ?>
                    <button onclick="fetchApplications(<?php echo $page + 1; ?>)" class="relative inline-flex items-center rounded-r-lg px-3 py-2 text-slate-400 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 focus:z-20 focus:outline-offset-0 bg-white dark:bg-slate-800 transition-colors">
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
        'html' => $html,
        'mobile_html' => $mobileHtml,
        'pagination' => $paginationHtml,
        'total' => $totalApps
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
