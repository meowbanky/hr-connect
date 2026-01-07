<?php
session_start();
require_once __DIR__ . '/../includes/settings.php';
// Basic Session Info needed for initial render or just pass to JS logic if needed (e.g. login state for buttons)
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>HR Connect - Job Board</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link href="/assets/css/style.css" rel="stylesheet"/>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #1919e6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .material-symbols-outlined.filled {
            font-variation-settings: 'FILL' 1;
        }
    </style>
<?php echo get_theme_css(); ?>
</head>
<body class="bg-background-light dark:bg-background-dark text-text-main dark:text-gray-100 flex flex-col min-h-screen overflow-x-hidden font-display">

<!-- Top Navigation Bar -->
<!-- Top Navigation Bar -->
<?php include_once __DIR__ . '/../includes/header.php'; ?>

<!-- Hero Section -->
<div class="relative w-full bg-cover bg-center bg-no-repeat py-20 lg:py-24" style='background-image: linear-gradient(rgba(17, 17, 33, 0.7), rgba(17, 17, 33, 0.8)), url("https://lh3.googleusercontent.com/aida-public/AB6AXuAHzmm_c7cW2etOwufnOXmWV65qnCwhJPIGs9yoe4mgEGaybrLZnRNO0ocGVbNczeTod0wnfKvn4OKTfIyxj_cTH6ml3Bufq9oSj-PXHG7t6Jr__jyCIZH_bgC0pw93oqCvsl0JfIy63FmE6Tuac71xIBpxEStZaJSScrU91fQXAAM2xa7_KCBiOGWW1UQWyib4W4vj5wyuZ1UPfkbfvNbVjacrxADYC5QVDsRptQHlkk5sdjtiB5Ijjg3OJbuwS6Kuz5vX5q8-7kc");'>
    <div class="max-w-[1440px] mx-auto px-6 lg:px-12 flex flex-col items-center text-center">
        <h1 class="text-white text-4xl lg:text-6xl font-black leading-tight tracking-tight mb-4 max-w-4xl">
            Find your next career move
        </h1>
        <p class="text-gray-200 text-lg font-normal mb-8 max-w-2xl">
            Search thousands of remote and flexible jobs from top companies around the world.
        </p>
        <div class="w-full max-w-3xl bg-white dark:bg-[#1a1a2e] p-2 rounded-xl shadow-2xl flex flex-col sm:flex-row gap-2">
            <div class="flex-1 flex items-center h-12 sm:h-14 px-4 border-b sm:border-b-0 sm:border-r border-gray-100 dark:border-gray-700">
                <span class="material-symbols-outlined text-gray-400 mr-3">search</span>
                <input id="searchInput" class="w-full bg-transparent border-none focus:ring-0 text-text-main dark:text-white placeholder:text-gray-400 text-base" placeholder="Job title, keywords..."/>
            </div>
            <div class="flex-1 flex items-center h-12 sm:h-14 px-4">
                <span class="material-symbols-outlined text-gray-400 mr-3">location_on</span>
                <input id="locationInput" class="w-full bg-transparent border-none focus:ring-0 text-text-main dark:text-white placeholder:text-gray-400 text-base" placeholder="City, state, or remote"/>
            </div>
            <button id="searchBtn" class="h-12 sm:h-14 px-8 bg-primary hover:bg-blue-700 text-white rounded-lg font-bold transition-colors w-full sm:w-auto">
                Search
            </button>
        </div>
    </div>
</div>

<!-- Main Content Layout -->
<div class="flex-1 max-w-[1440px] mx-auto w-full px-6 lg:px-12 py-12 flex flex-col lg:flex-row gap-8">
    
    <!-- Sidebar Filters -->
    <aside class="w-full lg:w-72 flex-shrink-0 space-y-8">
         <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-gray-800">
            <h3 class="text-text-main dark:text-white text-lg font-bold">Filters</h3>
            <button id="clearFilters" class="text-sm text-primary font-medium hover:underline">Clear all</button>
        </div>
        
        <!-- Job Type Filter -->
        <div class="space-y-4">
            <h4 class="text-text-main dark:text-white font-semibold">Job Type</h4>
            <div class="space-y-3 filter-group" data-filter="type">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="checkbox" value="Full-time" class="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary/25 cursor-pointer transition-colors">
                    <span class="text-gray-600 dark:text-gray-300 group-hover:text-primary transition-colors">Full-time</span>
                </label>
                 <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="checkbox" value="Contract" class="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary/25 cursor-pointer transition-colors">
                    <span class="text-gray-600 dark:text-gray-300 group-hover:text-primary transition-colors">Contract</span>
                </label>
                 <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="checkbox" value="Part-time" class="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary/25 cursor-pointer transition-colors">
                    <span class="text-gray-600 dark:text-gray-300 group-hover:text-primary transition-colors">Part-time</span>
                </label>
            </div>
        </div>

        <!-- Experience Level -->
        <div class="space-y-4 pt-6 border-t border-slate-200 dark:border-gray-800">
            <h4 class="text-text-main dark:text-white font-semibold">Experience Level</h4>
            <div class="space-y-3 filter-group" data-filter="level">
                 <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="checkbox" value="Entry Level" class="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary/25 cursor-pointer transition-colors">
                    <span class="text-gray-600 dark:text-gray-300 group-hover:text-primary transition-colors">Entry Level</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="checkbox" value="Mid Level" class="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary/25 cursor-pointer transition-colors">
                    <span class="text-gray-600 dark:text-gray-300 group-hover:text-primary transition-colors">Mid Level</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="checkbox" value="Senior Level" class="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary/25 cursor-pointer transition-colors">
                    <span class="text-gray-600 dark:text-gray-300 group-hover:text-primary transition-colors">Senior Level</span>
                </label>
                 <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="checkbox" value="Director" class="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary/25 cursor-pointer transition-colors">
                    <span class="text-gray-600 dark:text-gray-300 group-hover:text-primary transition-colors">Director</span>
                </label>
            </div>
        </div>

        <!-- Salary Range -->
        <div class="space-y-4 pt-6 border-t border-slate-200 dark:border-gray-800">
             <h4 class="text-text-main dark:text-white font-semibold">Salary Range</h4>
             <div class="space-y-3 filter-group" data-filter="salary">
                 <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="radio" name="salary" value="40000" class="w-5 h-5 border-gray-300 text-primary focus:ring-primary/25 cursor-pointer">
                    <span class="text-gray-600 dark:text-gray-300">$40k +</span>
                </label>
                 <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="radio" name="salary" value="80000" class="w-5 h-5 border-gray-300 text-primary focus:ring-primary/25 cursor-pointer">
                    <span class="text-gray-600 dark:text-gray-300">$80k +</span>
                </label>
                 <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="radio" name="salary" value="120000" class="w-5 h-5 border-gray-300 text-primary focus:ring-primary/25 cursor-pointer">
                    <span class="text-gray-600 dark:text-gray-300">$120k +</span>
                </label>
                 <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="radio" name="salary" value="160000" class="w-5 h-5 border-gray-300 text-primary focus:ring-primary/25 cursor-pointer">
                    <span class="text-gray-600 dark:text-gray-300">$160k +</span>
                </label>
             </div>
        </div>

        <!-- Date Posted -->
        <div class="space-y-4 pt-6 border-t border-slate-200 dark:border-gray-800">
             <h4 class="text-text-main dark:text-white font-semibold">Date Posted</h4>
             <select id="dateFilter" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-[#1a1a2e] text-gray-700 dark:text-gray-300 focus:border-primary focus:ring-primary/25">
                <option value="">Any time</option>
                <option value="24h">Past 24 hours</option>
                <option value="7d">Past week</option>
                <option value="30d">Past month</option>
            </select>
        </div>
    </aside>

    <!-- Job Feed -->
    <main class="flex-1">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-text-main dark:text-white font-bold text-xl"><span id="jobCount" class="text-primary">0</span> Jobs Found</h3>
             <!-- Sort Options -->
             <div class="flex items-center gap-2">
                <span class="text-gray-500 text-sm hidden sm:inline">Sort by:</span>
                <select id="sortFilter" class="rounded-lg border-gray-300 bg-white py-1 pl-2 pr-8 text-sm font-semibold text-text-main focus:ring-primary cursor-pointer">
                    <option value="Newest">Newest</option>
                    <option value="Most Relevant">Most Relevant</option>
                    <option value="Highest Salary">Highest Salary</option>
                </select>
            </div>
        </div>

        <!-- Job Container -->
        <div id="jobsContainer" class="space-y-4">
            <!-- Jobs will be injected here -->
        </div>
        
        <!-- Loading Spinner -->
        <div id="loader" class="loader hidden"></div>

        <!-- Pagination -->
        <div id="paginationContainer" class="flex justify-center mt-12 gap-2">
            <!-- Pagination buttons injected here -->
        </div>

    </main>
</div>

<!-- Scripts -->
<script>
$(document).ready(function() {
    let currentPage = 1;
    const currencySymbol = '<?php echo get_currency_symbol(); ?>';

    function fetchJobs(page = 1) {
        currentPage = page;
        
        // Show loader, hide jobs
        $('#loader').removeClass('hidden');
        $('#jobsContainer').addClass('opacity-50');

        // Collect Filters
        const types = [];
        $('.filter-group[data-filter="type"] input:checked').each(function() { types.push($(this).val()); });

        const levels = [];
        $('.filter-group[data-filter="level"] input:checked').each(function() { levels.push($(this).val()); });

        const salary = $('input[name="salary"]:checked').val() || '';
        const date = $('#dateFilter').val();
        const sort = $('#sortFilter').val();
        const search = $('#searchInput').val();
        const location = $('#locationInput').val();

        $.ajax({
            url: '../api/fetch_jobs.php',
            method: 'GET',
            data: {
                page: page,
                type: types,
                level: levels,
                salary: salary,
                date: date,
                sort: sort,
                q: search,
                location: location
            },
            success: function(response) {
                $('#loader').addClass('hidden');
                $('#jobsContainer').removeClass('opacity-50');
                
                if(response.success) {
                    renderJobs(response.jobs);
                    renderPagination(response.current_page, response.total_pages);
                    $('#jobCount').text(response.total_jobs);
                } else {
                    $('#jobsContainer').html('<p class="text-red-500 text-center">Error loading jobs.</p>');
                }
            },
            error: function() {
                $('#loader').addClass('hidden');
                $('#jobsContainer').removeClass('opacity-50').html('<p class="text-red-500 text-center">Failed to connect to server.</p>');
            }
        });
    }

    function renderJobs(jobs) {
        const container = $('#jobsContainer');
        container.empty();

        if (jobs.length === 0) {
            container.html(`
                <div class="text-center py-10 bg-white dark:bg-[#1a1a2e] rounded-xl border border-slate-200 dark:border-gray-800">
                    <span class="material-symbols-outlined text-4xl text-gray-400 mb-2">work_off</span>
                    <h3 class="text-lg font-bold text-text-main dark:text-white">No jobs found</h3>
                    <p class="text-gray-500">Try adjusting your search filters.</p>
                </div>
            `);
            return;
        }

        jobs.forEach(job => {
            const initials = job.title.charAt(0).toUpperCase();
            const postedDate = new Date(job.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            
            // Safe helper for nulls
            const dept = job.department_name || 'General';
            
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
            
            const exp = job.experience_level ? `<span class="px-3 py-1 bg-blue-50 dark:bg-primary/10 text-primary text-xs font-semibold rounded-full">${job.experience_level}</span>` : '';
            
            const isSaved = parseInt(job.is_saved) > 0;
            const appStatus = job.application_status; // null if not applied, otherwise string 'pending', 'interviewed', etc.
            
            const iconClass = isSaved ? 'filled text-primary' : '';
            const iconName = isSaved ? 'bookmark' : 'bookmark_border';
            
            // Apply Button Logic
            let applyBtnHtml = '';
            
            if (appStatus) {
                // If applied, show status badge
                let statusClasses = 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
                let icon = 'check_circle';
                let label = 'Applied'; // default if status unknown

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

                applyBtnHtml = `<span class="inline-flex items-center px-4 py-2 border ${statusClasses} text-sm font-medium rounded-lg cursor-default">
                    <span class="material-symbols-outlined text-sm mr-1.5">${icon}</span>
                    ${label}
                </span>`;
            } else {
                applyBtnHtml = `<a href="/candidate_application_form/index.php?job_id=${job.id}" class="inline-flex items-center px-4 py-2 bg-primary hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">Apply Now</a>`;
            }

            const html = `
                <div class="group relative flex flex-col sm:flex-row gap-6 bg-white dark:bg-[#1a1a2e] p-6 rounded-xl border border-slate-200 dark:border-gray-800 hover:border-primary/50 hover:shadow-lg dark:hover:shadow-primary/5 transition-all">
                    <div class="shrink-0">
                        <div class="h-16 w-16 rounded-lg bg-gray-50 dark:bg-gray-800 flex items-center justify-center p-3 border border-gray-100 dark:border-gray-700">
                            <span class="text-xl font-bold text-primary">${initials}</span>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <a href="/view-job?id=${job.id}" class="hover:underline">
                                    <h3 class="text-lg font-bold text-text-main dark:text-white group-hover:text-primary transition-colors cursor-pointer">${job.title}</h3>
                                </a>
                                <p class="text-sm text-gray-500 font-medium">${dept} • <span class="text-gray-400 font-normal">Posted ${postedDate}</span></p>
                            </div>
                            <button class="text-gray-400 hover:text-primary transition-colors bookmark-btn" data-id="${job.id}">
                                <span class="material-symbols-outlined ${iconClass}">${iconName}</span>
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-y-2 gap-x-4 mb-4 text-sm text-gray-600 dark:text-gray-300">
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[18px] text-gray-400">location_on</span>
                                ${job.location}
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[18px] text-gray-400">payments</span>
                                ${salaryDisplay}
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[18px] text-gray-400">schedule</span>
                                ${job.employment_type}
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 line-clamp-2 mb-4">
                            ${job.description.substring(0, 150)}...
                        </p>
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div class="flex flex-wrap gap-2">
                                ${exp}
                            </div>
                            <div class="flex items-center gap-4 w-full sm:w-auto mt-2 sm:mt-0">
                                <a href="/view-job?id=${job.id}" class="text-sm font-medium text-gray-500 hover:text-primary flex items-center gap-1 transition-colors">
                                    View Details <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                </a>
                                ${applyBtnHtml}
                            </div>
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

        // Prev
        if (current > 1) {
            container.append(`
                <button onclick="changePage(${current - 1})" class="flex items-center justify-center w-10 h-10 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-600 transition-colors">
                    <span class="material-symbols-outlined text-sm">chevron_left</span>
                </button>
            `);
        }

        // Numbers
        for (let i = 1; i <= total; i++) {
            const activeClass = i === current 
                ? 'border-primary bg-primary text-white' 
                : 'border-gray-200 hover:bg-gray-50 text-gray-600';
            
            container.append(`
                <button onclick="changePage(${i})" class="flex items-center justify-center w-10 h-10 rounded-lg border ${activeClass} font-semibold text-sm transition-colors">
                    ${i}
                </button>
            `);
        }

        // Next
        if (current < total) {
            container.append(`
                <button onclick="changePage(${current + 1})" class="flex items-center justify-center w-10 h-10 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-600 transition-colors">
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                </button>
            `);
        }
    }

    // Expose changePage to global scope for onclick handlers
    window.changePage = function(page) {
        fetchJobs(page);
    };

    // Event Listeners
    $('input[type="checkbox"], input[type="radio"], #dateFilter, #sortFilter').on('change', function() {
        fetchJobs(1); // Reset to page 1 on filter change
    });

    $('#searchBtn').on('click', function() {
        fetchJobs(1);
    });

    // Enter key support for search
    $('#searchInput, #locationInput').on('keypress', function(e) {
        if(e.which == 13) fetchJobs(1);
    });

    $('#clearFilters').on('click', function() {
        $('input[type="checkbox"]').prop('checked', false);
        $('input[type="radio"]').prop('checked', false);
        $('select').val('');
        $('#searchInput').val('');
        $('#locationInput').val('');
        fetchJobs(1);
    });
    
    // Bookmark Click Handler
    $('#jobsContainer').on('click', '.bookmark-btn', function(e) {
        e.preventDefault();
        const btn = $(this);
        const jobId = btn.data('id');
        const icon = btn.find('span');

        $.ajax({
            url: '../api/toggle_bookmark.php',
            method: 'POST',
            data: { job_id: jobId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.action === 'saved') {
                        icon.addClass('filled text-primary').text('bookmark');
                    } else {
                        icon.removeClass('filled text-primary').text('bookmark_border');
                    }
                } else {
                    if (response.message === 'Unauthorized') {
                         window.location.href = '../candidate_registration/login.php';
                    } else {
                        alert(response.message);
                    }
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });

    // Initial Load
    fetchJobs();
});
</script>

<!-- Simple Footer -->
<!-- Simple Footer -->
<?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
