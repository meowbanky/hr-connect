<?php
session_start();
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../candidate_registration/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
// $pdo is initialized in db.php

// Get Candidate ID
$stmt = $pdo->prepare("SELECT id, first_name, last_name, profile_image FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT id FROM candidates WHERE user_id = ?");
$stmt->execute([$user_id]);
$candidate = $stmt->fetch(PDO::FETCH_ASSOC);
$candidate_id = $candidate['id'] ?? 0;

// Fetch Applications
$query = "
    SELECT 
        a.id, 
        a.status, 
        a.application_date,
        j.title AS job_title,
        d.name AS department_name
    FROM applications a
    JOIN job_postings j ON a.job_id = j.id
    LEFT JOIN departments d ON j.department_id = d.id
    WHERE a.candidate_id = ?
    ORDER BY a.application_date DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$candidate_id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Profile Completion
$stmt = $pdo->prepare("SELECT * FROM candidates WHERE user_id = ?");
$stmt->execute([$user_id]);
$candidate_data = $stmt->fetch(PDO::FETCH_ASSOC);

$total_fields = 9; // dob, gender, address, state, lga, resume, linkedin, qualification, experience
$filled_fields = 0;
if ($candidate_data) {
    if (!empty($candidate_data['date_of_birth'])) $filled_fields++;
    if (!empty($candidate_data['gender'])) $filled_fields++;
    if (!empty($candidate_data['address'])) $filled_fields++;
    if (!empty($candidate_data['state_of_origin'])) $filled_fields++;
    if (!empty($candidate_data['lga'])) $filled_fields++;
    if (!empty($candidate_data['resume_path'])) $filled_fields++;
    if (!empty($candidate_data['linkedin_profile'])) $filled_fields++;
    if (!empty($candidate_data['highest_qualification'])) $filled_fields++;
    if (isset($candidate_data['years_of_experience'])) $filled_fields++;
}
$profile_percentage = round(($filled_fields / $total_fields) * 100);

// Helper for status colors
function getStatusBadge($status) {
    switch($status) {
        case 'interviewed':
            return '<span class="inline-flex items-center gap-1.5 rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800 dark:bg-purple-900/30 dark:text-purple-300"><span class="h-1.5 w-1.5 rounded-full bg-purple-600 dark:bg-purple-400"></span>Interview</span>';
        case 'reviewed':
        case 'shortlisted':
            return '<span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300"><span class="h-1.5 w-1.5 rounded-full bg-amber-600 dark:bg-amber-400"></span>Under Review</span>';
        case 'offered':
        case 'hired':
            return '<span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-300"><span class="h-1.5 w-1.5 rounded-full bg-green-600 dark:bg-green-400"></span>Offer Extended</span>';
        case 'rejected':
            return '<span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-300"><span class="h-1.5 w-1.5 rounded-full bg-red-600 dark:bg-red-400"></span>Rejected</span>';
        default: // pending
            return '<span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300"><span class="h-1.5 w-1.5 rounded-full bg-blue-600 dark:bg-blue-400"></span>Submitted</span>';
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>My Applications</title>
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
                    "background-dark": "#101022",
                },
                fontFamily: {
                    "display": ["Inter", "sans-serif"]
                },
                borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
            },
        },
    }
</script>
<?php echo get_theme_css(); ?>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-slate-900 dark:text-white min-h-screen flex flex-col">

<!-- Top Navigation Bar -->
<?php include_once __DIR__ . '/../includes/header.php'; ?>

<!-- Main Content Area -->
<main class="flex-1 w-full bg-background-light dark:bg-background-dark py-8 px-4 sm:px-6 md:px-8">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
        
        <!-- Header & Profile Stats -->
        <div class="flex flex-col md:flex-row gap-6 justify-between items-start md:items-center">
            <header class="flex flex-col gap-2">
                <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white md:text-4xl">My Applications</h1>
                <p class="text-base text-slate-500 dark:text-slate-400">Track and manage your current and past job applications.</p>
            </header>

            <!-- Profile Completion Card -->
             <div class="w-full md:w-auto min-w-[300px] bg-white dark:bg-[#15152a] rounded-xl p-4 shadow-sm border border-slate-200 dark:border-slate-800 flex items-center gap-4">
                <div class="relative size-12 shrink-0">
                    <svg class="size-full -rotate-90" viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="18" cy="18" r="16" fill="none" class="stroke-current text-slate-200 dark:text-slate-700" stroke-width="3"></circle>
                        <circle cx="18" cy="18" r="16" fill="none" class="stroke-current text-primary" stroke-width="3" stroke-dasharray="100" stroke-dashoffset="<?php echo 100 - $profile_percentage; ?>" stroke-linecap="round"></circle>
                    </svg>
                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-xs font-bold text-primary">
                        <?php echo $profile_percentage; ?>%
                    </div>
                </div>
                <div class="flex flex-col flex-1">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">Profile Completion</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        <?php if($profile_percentage < 100): ?>
                            Complete your profile to stand out.
                        <?php else: ?>
                            Your profile is top notch!
                        <?php endif; ?>
                    </p>
                    <?php if($profile_percentage < 100): ?>
                        <a href="#" class="text-xs text-primary font-medium hover:underline mt-1">Complete Profile</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Checks if No Applications -->
        <?php if(empty($applications)): ?>
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 py-12 text-center dark:border-slate-700 dark:bg-slate-800/30">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                    <span class="material-symbols-outlined text-slate-400">folder_open</span>
                </div>
                <h3 class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">No applications found</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Get started by searching for a new job.</p>
                <div class="mt-6">
                    <a href="../job_board_&_candidate_portal/index.php" class="inline-flex items-center rounded-md bg-primary px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        <span class="material-symbols-outlined mr-1.5 text-sm">add</span>
                        Browse Jobs
                    </a>
                </div>
            </div>
        <?php else: ?>

        <!-- Toolbar (Search & Filters) -->
        <div class="flex flex-col gap-4">
            <!-- Search Bar -->
            <div class="relative w-full md:max-w-md">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <span class="material-symbols-outlined text-slate-400">search</span>
                </div>
                <input id="searchInput" class="block w-full rounded-lg border-0 bg-white py-3 pl-10 pr-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-primary dark:bg-slate-800 dark:ring-slate-700 dark:text-white sm:text-sm sm:leading-6" placeholder="Search by job title or company..." type="text" onkeyup="filterTable()"/>
            </div>
            <!-- Filter Chips -->
            <div class="flex flex-wrap gap-2" id="filterContainer">
                <button onclick="filterStatus('all', this)" class="filter-btn active group flex items-center gap-2 rounded-full border border-primary bg-primary px-4 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors" data-filter="all">
                    <span class="material-symbols-outlined text-[18px]">check_circle</span>
                    All
                </button>
                <button onclick="filterStatus('pending', this)" class="filter-btn group flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-1.5 text-sm font-medium text-slate-600 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors" data-filter="pending">
                    <span class="material-symbols-outlined text-[18px]">send</span>
                    Submitted
                </button>
                <button onclick="filterStatus('reviewed', this)" class="filter-btn group flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-1.5 text-sm font-medium text-slate-600 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors" data-filter="reviewed">
                    <span class="material-symbols-outlined text-[18px]">schedule</span>
                    Under Review
                </button>
                <button onclick="filterStatus('interviewed', this)" class="filter-btn group flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-1.5 text-sm font-medium text-slate-600 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors" data-filter="interviewed">
                    <span class="material-symbols-outlined text-[18px]">event</span>
                    Interview
                </button>
                <button onclick="filterStatus('offered', this)" class="filter-btn group flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-1.5 text-sm font-medium text-slate-600 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors" data-filter="offered">
                    <span class="material-symbols-outlined text-[18px]">star</span>
                    Offer Extended
                </button>
                <button onclick="filterStatus('rejected', this)" class="filter-btn group flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-1.5 text-sm font-medium text-slate-600 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors" data-filter="rejected">
                    <span class="material-symbols-outlined text-[18px]">cancel</span>
                    Rejected
                </button>
            </div>
        </div>

        <!-- Applications Table -->
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-[#15152a] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm" id="appsTable">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-6 py-4 font-semibold text-slate-900 dark:text-white" scope="col">Job Title</th>
                            <th class="hidden px-6 py-4 font-semibold text-slate-900 dark:text-white sm:table-cell" scope="col">Department</th>
                            <th class="hidden px-6 py-4 font-semibold text-slate-900 dark:text-white md:table-cell" scope="col">Date Applied</th>
                            <th class="px-6 py-4 font-semibold text-slate-900 dark:text-white" scope="col">Status</th>
                            <th class="px-6 py-4 text-right font-semibold text-slate-900 dark:text-white" scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php foreach($applications as $app): ?>
                        <tr class="group transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50 item-row" data-status="<?php echo htmlspecialchars($app['status']); ?>">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <a href="../candidate_application_view_details/index.php?id=<?php echo $app['id']; ?>" class="font-medium text-primary dark:text-indigo-400 group-hover:underline cursor-pointer text-base"><?php echo htmlspecialchars($app['job_title']); ?></a>
                                    <span class="sm:hidden text-xs text-slate-500 mt-1"><?php echo htmlspecialchars($app['department_name'] ?? 'General'); ?></span>
                                </div>
                            </td>
                            <td class="hidden px-6 py-4 text-slate-600 dark:text-slate-300 sm:table-cell"><?php echo htmlspecialchars($app['department_name'] ?? 'General'); ?></td>
                            <td class="hidden px-6 py-4 text-slate-500 dark:text-slate-400 md:table-cell"><?php echo date('M d, Y', strtotime($app['application_date'])); ?></td>
                            <td class="px-6 py-4">
                                <?php echo getStatusBadge($app['status']); ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="../candidate_application_view_details/index.php?id=<?php echo $app['id']; ?>" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                    View Details
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination / Results Count -->
            <div class="flex items-center justify-between border-t border-slate-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-[#15152a] sm:px-6">
                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                     <div>
                        <p class="text-sm text-slate-700 dark:text-slate-400" id="resultCountText">
                            Showing all <span class="font-medium"><?php echo count($applications); ?></span> results
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</main>

<!-- Footer -->
<?php include_once __DIR__ . '/../includes/footer.php'; ?>

<script>
    function filterTable() {
        const input = document.getElementById("searchInput");
        const filter = input.value.toUpperCase();
        const table = document.getElementById("appsTable");
        const tr = table.getElementsByTagName("tr");
        let visibleCount = 0;

        for (let i = 0; i < tr.length; i++) {
            // Skip header
            if (tr[i].getElementsByTagName("th").length > 0) continue;
            
            // Should also respect current status filter
            const activeFilterBtn = document.querySelector('.filter-btn.active');
            const currentStatus = activeFilterBtn ? activeFilterBtn.dataset.filter : 'all';
            const rowStatus = tr[i].dataset.status;

            // Check Status Match
            const statusMatch = (currentStatus === 'all') || (rowStatus === currentStatus) || (currentStatus === 'pending' && rowStatus === 'pending'); // Adjust 'pending' logic if needed
            
            // Check Search Match
            let searchMatch = false;
            let tdTitle = tr[i].getElementsByTagName("td")[0];
            let tdDept = tr[i].getElementsByTagName("td")[1];
            
            if (tdTitle || tdDept) {
                let txtValueTitle = tdTitle.textContent || tdTitle.innerText;
                let txtValueDept = tdDept.textContent || tdDept.innerText;
                if (txtValueTitle.toUpperCase().indexOf(filter) > -1 || txtValueDept.toUpperCase().indexOf(filter) > -1) {
                    searchMatch = true;
                }
            }
            
            if (statusMatch && searchMatch) {
                tr[i].style.display = "";
                visibleCount++;
            } else {
                tr[i].style.display = "none";
            }
        }
        updateResultText(visibleCount);
    }

    function filterStatus(status, btnElement) {
        // Toggle Active Class
        const buttons = document.querySelectorAll('.filter-btn');
        const activeClass = ['border-primary', 'bg-primary', 'text-white'];
        const inactiveClass = ['border-slate-200', 'bg-white', 'text-slate-600', 'hover:border-slate-300', 'hover:bg-slate-50', 'dark:border-slate-700', 'dark:bg-slate-800', 'dark:text-slate-300', 'dark:hover:bg-slate-700'];

        buttons.forEach(btn => {
            btn.classList.remove(...activeClass);
            btn.classList.remove('active');
            btn.classList.add(...inactiveClass);
        });

        if (btnElement) {
            btnElement.classList.remove(...inactiveClass);
            btnElement.classList.add(...activeClass);
            btnElement.classList.add('active');
        }

        // Trigger Filter Logic (re-uses filterTable to combine with search)
        filterTable();
    }
    
    function updateResultText(count) {
        const activeFilterBtn = document.querySelector('.filter-btn.active');
        let statusText = 'All';
        
        if (activeFilterBtn) {
            // Clone to avoid modifying DOM, remove icon to get just text
            const clone = activeFilterBtn.cloneNode(true);
            const icon = clone.querySelector('.material-symbols-outlined');
            if (icon) icon.remove();
            statusText = clone.textContent.trim();
        }
        
        const textElement = document.getElementById('resultCountText');
        textElement.innerHTML = `Showing ${statusText} <span class="font-medium">${count}</span> results`;
    }
</script>
</body>
</html>
