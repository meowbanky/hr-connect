<?php
session_start();
require_once __DIR__ . '/../includes/settings.php';

// strict login check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../candidate_registration/login.php");
    exit;
}

$userName = $_SESSION['user_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>My Bookmarks - HR Connect</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link href="../assets/css/style.css" rel="stylesheet"/>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .material-symbols-outlined.filled { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    <?php echo get_theme_css(); ?>
</head>
<body class="bg-background-light dark:bg-background-dark text-text-main dark:text-white transition-colors duration-200 min-h-screen flex flex-col">

<!-- Navigation -->
<?php include_once __DIR__ . '/../includes/header.php'; ?>

<!-- Main Content -->
<main class="flex-1 w-full flex flex-col items-center py-8 px-4 sm:px-6 md:px-8">
    <div class="w-full max-w-[1024px] flex flex-col gap-6">
        
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-2">
            <div class="flex flex-col gap-2">
                <h1 class="text-3xl md:text-4xl font-black tracking-tight text-text-main dark:text-white">My Bookmarks</h1>
                <p class="text-gray-500 dark:text-gray-400 text-base max-w-2xl">
                     Manage the opportunities you've saved for later. Keep track of application deadlines.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 bg-primary/10 text-primary text-sm font-bold rounded-full"><span id="jobCount">0</span> Saved Jobs</span>
            </div>
        </div>

        <!-- Search and Filter Bar -->
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Search Input -->
            <div class="flex-1">
                <label class="relative flex w-full h-12 items-center rounded-xl bg-white dark:bg-[#1a1a2e] shadow-sm ring-1 ring-black/5 dark:ring-white/10 focus-within:ring-2 focus-within:ring-primary transition-all">
                    <div class="absolute left-4 text-gray-400 flex items-center">
                        <span class="material-symbols-outlined">search</span>
                    </div>
                    <input id="searchInput" class="w-full h-full bg-transparent border-none pl-12 pr-4 text-text-main dark:text-white placeholder:text-gray-400 focus:ring-0 focus:outline-none text-base" placeholder="Search saved jobs by title, company, or keyword" type="text"/>
                </label>
            </div>
            <!-- Filter Chips -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 lg:pb-0 scrollbar-hide">
                <button onclick="toggleSort()" class="group flex h-10 shrink-0 items-center gap-2 rounded-xl bg-white dark:bg-[#1a1a2e] px-4 text-sm font-medium text-text-main dark:text-white shadow-sm ring-1 ring-black/5 dark:ring-white/10 hover:bg-gray-50 dark:hover:bg-white/10 transition-colors">
                    <span id="sortLabel">Sort by Date</span>
                    <span class="material-symbols-outlined text-base">swap_vert</span>
                </button>
                 <!-- Remote Toggle -->
                <button onclick="toggleRemote()" id="remoteBtn" class="group flex h-10 shrink-0 items-center gap-2 rounded-xl bg-white dark:bg-[#1a1a2e] px-4 text-sm font-medium text-text-main dark:text-white shadow-sm ring-1 ring-black/5 dark:ring-white/10 hover:bg-gray-50 dark:hover:bg-white/10 transition-colors">
                    <span>Remote Only</span>
                    <span class="material-symbols-outlined text-base text-gray-400 group-hover:text-primary transition-colors" id="remoteIcon">check_box_outline_blank</span>
                </button>
            </div>
        </div>

        <!-- Bookmarks List -->
        <div id="jobsContainer" class="grid grid-cols-1 gap-4">
             <!-- Jobs injected here -->
             <div class="p-8 text-center text-gray-500">Loading bookmarks...</div>
        </div>

        <!-- Pagination -->
        <div id="paginationContainer" class="flex justify-center mt-6 gap-2"></div>

    </div>
</main>

<!-- Footer -->
<?php include_once __DIR__ . '/../includes/footer.php'; ?>

<script>
    let currentSort = 'Newest';
    let isRemote = false;
    const currencySymbol = '<?php echo get_currency_symbol(); ?>';

    $(document).ready(function() {
        fetchSavedJobs();

        // Type search listener
        let debounceTimer;
        $('#searchInput').on('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fetchSavedJobs(1), 300);
        });
    });

    function toggleSort() {
        currentSort = currentSort === 'Newest' ? 'Oldest' : 'Newest'; // Simple toggle for now, or add more
        $('#sortLabel').text(currentSort === 'Newest' ? 'Sort by Newest' : 'Sort by Oldest');
        fetchSavedJobs(1);
    }

    function toggleRemote() {
        isRemote = !isRemote;
        const icon = $('#remoteIcon');
        if (isRemote) {
            icon.text('check_box').addClass('text-primary');
            $('#remoteBtn').addClass('bg-primary/5 border-primary');
        } else {
            icon.text('check_box_outline_blank').removeClass('text-primary');
            $('#remoteBtn').removeClass('bg-primary/5 border-primary');
        }
        fetchSavedJobs(1);
    }

    function fetchSavedJobs(page = 1) {
        const search = $('#searchInput').val();
        
        $.ajax({
            url: '../api/fetch_jobs.php',
            method: 'GET',
            data: {
                page: page,
                saved_only: 'true',
                q: search,
                sort: currentSort,
                location: isRemote ? 'Remote' : ''
            },
            success: function(response) {
                if(response.success) {
                    $('#jobCount').text(response.total_jobs);
                    renderJobs(response.jobs);
                    renderPagination(response.current_page, response.total_pages);
                } else {
                    $('#jobsContainer').html('<p class="text-red-500 text-center">Error loading jobs.</p>');
                }
            },
            error: function() {
                $('#jobsContainer').html('<p class="text-red-500 text-center">Connection failed.</p>');
            }
        });
    }

    function timeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        let interval = seconds / 31536000;
        if (interval > 1) return Math.floor(interval) + " years ago";
        interval = seconds / 2592000;
        if (interval > 1) return Math.floor(interval) + " months ago";
        interval = seconds / 86400;
        if (interval > 1) return Math.floor(interval) + " days ago";
        interval = seconds / 3600;
        if (interval > 1) return Math.floor(interval) + " hours ago";
        interval = seconds / 60;
        if (interval > 1) return Math.floor(interval) + " minutes ago";
        return "Just now";
    }

    function renderJobs(jobs) {
        const container = $('#jobsContainer');
        container.empty();

        if (jobs.length === 0) {
            container.html(`
                <div class="text-center py-16 bg-white dark:bg-[#1a1a2e] rounded-xl border border-slate-200 dark:border-gray-800">
                    <span class="material-symbols-outlined text-4xl text-gray-300 mb-3">bookmark_border</span>
                    <h3 class="text-lg font-bold text-text-main dark:text-white">No saved jobs found</h3>
                    <p class="text-gray-500 mb-6">Try adjusting your filters or search terms.</p>
                </div>
            `);
            return;
        }

        jobs.forEach(job => {
            const initials = job.title.charAt(0).toUpperCase();
            const company = 'HR Connect Company'; // Placeholder for company logic if needed
            const savedTime = job.saved_at ? timeAgo(job.saved_at) : 'Recently';
            
            let salaryDisplay = job.salary_range || 'Negotiable';
            if (job.min_salary && job.max_salary) {
                 const min = parseFloat(job.min_salary);
                 const max = parseFloat(job.max_salary);
                 const format = (n) => {
                    if (n >= 1000000) return (n/1000000).toFixed(1) + 'M';
                    if (n >= 1000) return (n/1000).toFixed(0) + 'k';
                    return n;
                 };
                 salaryDisplay = currencySymbol + format(min) + ' - ' + currencySymbol + format(max);
            }

            const appStatus = job.application_status; 
            
            let applyActionHtml = '';
            
            if (appStatus) {
                // If applied, show status badge
                let statusClasses = 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
                let icon = 'check_circle';
                let label = 'Applied'; 

                if (appStatus === 'pending') {
                    statusClasses = 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800';
                    label = 'Submitted';
                    icon = 'send';
                } else if (appStatus === 'reviewed' || appStatus === 'shortlisted') {
                    statusClasses = 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800';
                    label = 'Under Review';
                    icon = 'schedule';
                } else if (appStatus === 'interviewed') {
                    statusClasses = 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-900/20 dark:text-purple-400 dark:border-purple-800';
                    label = 'Interview';
                    icon = 'event';
                } else if (appStatus === 'offered' || appStatus === 'hired') {
                    statusClasses = 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800';
                    label = 'Offer';
                    icon = 'star';
                } else if (appStatus === 'rejected') {
                    statusClasses = 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800';
                    label = 'Rejected';
                    icon = 'cancel';
                }

                applyActionHtml = `<div class="flex-1 md:flex-none h-10 px-5 rounded-lg border ${statusClasses} text-sm font-medium flex items-center justify-center gap-2 cursor-default">
                    <span class="material-symbols-outlined text-sm">${icon}</span>
                    ${label}
                </div>`;
            } else {
                 applyActionHtml = `<a href="../job_board_&_candidate_portal/view_job.php?id=${job.id}" class="flex-1 md:flex-none h-10 px-5 rounded-lg bg-primary hover:bg-blue-700 text-white text-sm font-semibold transition-colors shadow-sm shadow-primary/20 whitespace-nowrap flex items-center justify-center">
                                Apply Now
                            </a>`;
            }

            const html = `
                <div class="group relative flex flex-col md:flex-row gap-6 p-6 rounded-2xl bg-white dark:bg-[#1a1a2e] shadow-sm border border-gray-100 dark:border-white/5 hover:border-primary/30 hover:shadow-md transition-all duration-300" id="job-${job.id}">
                    <div class="flex flex-1 gap-5">
                        <div class="shrink-0 size-14 rounded-xl bg-gray-50 dark:bg-white/5 p-2 border border-gray-100 dark:border-white/5 flex items-center justify-center">
                            <span class="text-xl font-bold text-primary">${initials}</span>
                        </div>
                        <div class="flex flex-col gap-1.5 w-full">
                            <div class="flex items-start justify-between w-full">
                                <a href="../job_board_&_candidate_portal/view_job.php?id=${job.id}">
                                    <h3 class="text-lg font-bold text-text-main dark:text-white group-hover:text-primary transition-colors cursor-pointer">${job.title}</h3>
                                </a>
                                <button onclick="removeBookmark(${job.id})" class="text-primary hover:text-red-500 transition-colors p-1" title="Remove Bookmark">
                                    <span class="material-symbols-outlined filled">bookmark</span>
                                </button>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-500 dark:text-gray-400">
                                <span class="font-medium text-gray-900 dark:text-gray-200">${job.department_name || 'General'}</span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">location_on</span>
                                    ${job.location}
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">payments</span>
                                    ${salaryDisplay}
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">${job.employment_type}</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300">Active</span>
                            </div>
                            <p class="mt-3 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                ${job.description}
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex flex-row md:flex-col items-center md:items-end justify-between md:justify-center gap-4 border-t md:border-t-0 md:border-l border-gray-100 dark:border-white/10 pt-4 md:pt-0 md:pl-6 min-w-[140px]">
                        <div class="text-xs text-gray-400 font-medium text-right w-full md:w-auto">Saved ${savedTime}</div>
                        <div class="flex gap-3 w-full md:w-auto">
                            ${applyActionHtml}
                        </div>
                    </div>
                </div>
            `;
            container.append(html);
        });
    }

    function renderPagination(current, total) {
        const container = $('#paginationContainer');
        container.empty();
        if (total <= 1) return;
        
        // Simple pagination
         if (current > 1) {
            container.append(`<button onclick="fetchSavedJobs(${current - 1})" class="w-10 h-10 rounded-lg border border-gray-200 hover:bg-gray-50 flex items-center justify-center"><span class="material-symbols-outlined">chevron_left</span></button>`);
        }
        // ... (can expand pagination logic if needed)
        if (current < total) {
            container.append(`<button onclick="fetchSavedJobs(${current + 1})" class="w-10 h-10 rounded-lg border border-gray-200 hover:bg-gray-50 flex items-center justify-center"><span class="material-symbols-outlined">chevron_right</span></button>`);
        }
    }

    function removeBookmark(jobId) {
        if(!confirm('Remove this job from bookmarks?')) return;
        
        $.ajax({
            url: '../api/toggle_bookmark.php',
            method: 'POST',
            data: { job_id: jobId },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.action === 'removed') {
                    $(`#job-${jobId}`).fadeOut(300, function() { $(this).remove(); });
                    let count = parseInt($('#jobCount').text());
                    $('#jobCount').text(Math.max(0, count - 1));
                    if(count - 1 === 0) fetchSavedJobs(1); // Reload empty state
                }
            }
        });
    }
</script>
</body>
</html>
