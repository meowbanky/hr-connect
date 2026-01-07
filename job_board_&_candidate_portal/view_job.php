<?php
session_start();
require_once __DIR__ . '/../includes/settings.php';
// Login state for initial UI shell (buttons) - though actual logic will also be checked via JS/API
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Job Details - HR Connect</title>
<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<!-- Custom CSS -->
<link href="/assets/css/style.css" rel="stylesheet"/>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .material-symbols-outlined.filled {
             font-variation-settings: 'FILL' 1;
        }
        .loader {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #1313ec;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
<?php echo get_theme_css(); ?>
</head>
<body class="bg-background-light dark:bg-background-dark text-text-main dark:text-white font-display min-h-screen flex flex-col">
<!-- Top Navigation Bar -->
<?php include_once __DIR__ . '/../includes/header.php'; ?>

<!-- Main Content Layout -->
<main class="flex-grow w-full max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8 py-8" id="mainContent">
    <div id="pageLoader" class="flex justify-center items-center h-64">
        <div class="loader"></div>
    </div>

    <!-- Content Wrapper (Hidden until loaded) -->
    <div id="contentWrapper" class="hidden">
        <!-- Breadcrumbs -->
        <div class="mb-6">
            <nav aria-label="Breadcrumb" class="flex">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a class="inline-flex items-center text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-white" href="index.php">
                            <span class="material-symbols-outlined text-[18px] mr-2">work</span>
                            Jobs
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <span class="material-symbols-outlined text-gray-600 dark:text-gray-500 text-[18px]">chevron_right</span>
                            <span class="ml-1 text-sm font-medium text-text-main dark:text-gray-200 md:ml-2" id="jobTitleBreadcrumb">Loading...</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
        <!-- Header Image & Overlay Title -->
        <div class="relative w-full h-[240px] rounded-xl overflow-hidden mb-8 shadow-sm group">
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent z-10"></div>
            <div class="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-105" data-alt="Modern office workspace with laptops and design sketches" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuA7geU5D1YktCuOQD1qZEXtQh__TlEpCHFprPy7EkAhh7_c2cp8BGpE6trQNMsE4IAwKboZwe5pru8L_8trX3Y0RBevAHA3ENVLTWy2UpOd9SulTH4Qy5zNeEFSEXFaVz1QqgBGFdTElsKEs64uYS1qUISeeMH8qjsXmLVl9jqkcL-bGjay5bBc--bodFt-EgpuQxm8mwOjQpS0_GixuVCWRH6KizrD42RdFkzk0pVW3kNd2AqOoUkvKWckR2ZHcpUyFXS6vIfVq4I');">
            </div>
            <div class="absolute bottom-0 left-0 p-6 z-20 w-full flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="bg-white/20 backdrop-blur-sm text-white text-xs px-2 py-1 rounded border border-white/30" id="deptLabel">General</span>
                        <span class="bg-green-500/80 backdrop-blur-sm text-white text-xs px-2 py-1 rounded border border-green-400/30 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> Open
                        </span>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-black text-white leading-tight tracking-tight" id="jobTitleHeader">Loading...</h1>
                    <p class="text-gray-200 mt-1 flex items-center gap-1 text-sm md:text-base">
                        <span class="material-symbols-outlined text-[18px]">location_on</span> <span id="locationHeader">...</span>
                    </p>
                </div>
            </div>
        </div>
        <!-- Two Column Layout: Main Details + Sidebar -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Detailed Job Description -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Quick Info Chips -->
                <div class="flex flex-wrap gap-3">
                    <div class="inline-flex items-center px-3 py-1.5 rounded-lg bg-background-light dark:bg-gray-800 text-text-main dark:text-gray-200 text-sm font-medium">
                        <span class="material-symbols-outlined text-[18px] mr-1.5 text-primary">schedule</span>
                        <span id="employmentType">...</span>
                    </div>
                    <div class="inline-flex items-center px-3 py-1.5 rounded-lg bg-background-light dark:bg-gray-800 text-text-main dark:text-gray-200 text-sm font-medium">
                        <span class="material-symbols-outlined text-[18px] mr-1.5 text-primary">work_history</span>
                        <span id="expLevel">Not Specified</span>
                    </div>
                    <div class="inline-flex items-center px-3 py-1.5 rounded-lg bg-background-light dark:bg-gray-800 text-text-main dark:text-gray-200 text-sm font-medium">
                        <span class="material-symbols-outlined text-[18px] mr-1.5 text-primary">laptop_chromebook</span>
                        Remote Friendly
                    </div>
                    <div class="inline-flex items-center px-3 py-1.5 rounded-lg bg-background-light dark:bg-gray-800 text-text-main dark:text-gray-200 text-sm font-medium">
                        <span class="material-symbols-outlined text-[18px] mr-1.5 text-primary">payments</span>
                        <span id="salaryRange">...</span>
                    </div>
                </div>
                <!-- Content Sections -->
                <div class="bg-white dark:bg-[#1a1a2e] p-6 md:p-8 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm">
                    <section class="mb-8">
                        <h3 class="text-xl font-bold text-text-main dark:text-white mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">info</span> About the Role
                        </h3>
                        <div class="text-gray-600 dark:text-gray-300 leading-relaxed space-y-4 whitespace-pre-line" id="jobDescription"></div>
                    </section>
                    <section class="mb-8">
                        <h3 class="text-xl font-bold text-text-main dark:text-white mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">verified</span> Requirements
                        </h3>
                        <div class="text-gray-600 dark:text-gray-300 leading-relaxed space-y-4 whitespace-pre-line" id="jobRequirements"></div>
                    </section>
                    <section>
                        <h3 class="text-xl font-bold text-text-main dark:text-white mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">redeem</span> Benefits
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex items-start gap-3 p-3 rounded-lg bg-background-light dark:bg-gray-800/50">
                                <span class="material-symbols-outlined text-green-600 dark:text-green-400">health_and_safety</span>
                                <div>
                                    <p class="font-semibold text-sm text-text-main dark:text-white">Full Healthcare</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Medical, Dental, Vision</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 rounded-lg bg-background-light dark:bg-gray-800/50">
                                <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">savings</span>
                                <div>
                                    <p class="font-semibold text-sm text-text-main dark:text-white">401(k) Matching</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Up to 5% match</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 rounded-lg bg-background-light dark:bg-gray-800/50">
                                <span class="material-symbols-outlined text-purple-600 dark:text-purple-400">beach_access</span>
                                <div>
                                    <p class="font-semibold text-sm text-text-main dark:text-white">Unlimited PTO</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Take time when you need it</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 rounded-lg bg-background-light dark:bg-gray-800/50">
                                <span class="material-symbols-outlined text-orange-600 dark:text-orange-400">fitness_center</span>
                                <div>
                                    <p class="font-semibold text-sm text-text-main dark:text-white">Wellness Stipend</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">$100/mo for gym/health</p>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
                <!-- Secondary CTA at bottom of content -->
                <div class="bg-primary/5 dark:bg-primary/10 rounded-xl p-8 text-center border border-primary/20">
                    <h3 class="text-lg font-bold text-text-main dark:text-white mb-2">Interested in this role?</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6 text-sm">Don't miss out on this opportunity to join our team.</p>
                    <?php if ($isLoggedIn): ?>
                        <a id="applyBtnMain" href="#" class="inline-flex items-center justify-center bg-primary hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition-all shadow-lg shadow-primary/30 w-full sm:w-auto">
                            Apply for this Position
                        </a>
                    <?php else: ?>
                        <a href="../candidate_registration/login.php" class="inline-block bg-primary hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition-all shadow-lg shadow-primary/30 w-full sm:w-auto">
                            Log In to Apply
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Right Column: Sticky Sidebar -->
            <div class="lg:col-span-1">
                <div class="sticky top-24 space-y-6">
                    <!-- Action Card -->
                    <div class="bg-white dark:bg-[#1a1a2e] p-6 rounded-xl border border-gray-100 dark:border-gray-800 shadow-lg">
                        <?php if ($isLoggedIn): ?>
                            <a id="applyBtnSidebar" href="#" class="w-full bg-primary hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition-all shadow-lg shadow-primary/25 mb-4 flex items-center justify-center gap-2">
                                Apply Now <span class="material-symbols-outlined text-[20px]">send</span>
                            </a>
                        <?php else: ?>
                            <a href="../candidate_registration/login.php" class="w-full bg-primary hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition-all shadow-lg shadow-primary/25 mb-4 flex items-center justify-center gap-2">
                                Log In to Apply <span class="material-symbols-outlined text-[20px]">login</span>
                            </a>
                        <?php endif; ?>

                        <!-- Save Job Button -->
                        <button id="bookmarkBtn" class="w-full bg-white dark:bg-transparent border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-text-main dark:text-white font-semibold py-2.5 px-4 rounded-lg transition-colors mb-6 flex items-center justify-center gap-2" data-id="">
                            <span class="material-symbols-outlined text-[20px]" id="bookmarkIcon">bookmark_border</span>
                            <span id="bookmarkText">Save Job</span>
                        </button>

                        <div class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Date Posted</span>
                                <span class="text-sm font-medium text-text-main dark:text-gray-200" id="datePosted">...</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Closing Date</span>
                                <span class="text-sm font-medium text-red-500">Open until filled</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Job ID</span>
                                <span class="text-sm font-medium text-text-main dark:text-gray-200" id="jobIdLabel">...</span>
                            </div>
                        </div>
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-4 mt-4">
                            <p class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-500 mb-3 tracking-wider">Share this job</p>
                            <div class="flex gap-2">
                                <button id="shareLinkedIn" aria-label="Share on LinkedIn" class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center hover:opacity-90 transition-opacity">
                                    <svg class="w-4 h-4" fill="currentColor" viewbox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"></path></svg>
                                </button>
                                <button id="shareTwitter" aria-label="Share on Twitter" class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center hover:opacity-90 transition-opacity">
                                    <svg class="w-4 h-4" fill="currentColor" viewbox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"></path></svg>
                                </button>
                                <button id="shareCopy" aria-label="Copy Link" class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center hover:opacity-90 transition-opacity relative group">
                                    <svg class="w-4 h-4" fill="#ffffff" viewbox="0 0 24 24"><path d="M16 1H4C2.9 1 2 1.9 2 3V17H4V3H16V1ZM19 5H8C6.9 5 6 5.9 6 7V21C6 22.1 6.9 23 8 23H19C20.1 23 21 22.1 21 21V7C21 5.9 20.1 5 19 5ZM19 21H8V7H19V21Z"/></svg>
                                    <span class="opacity-0 group-hover:opacity-100 absolute -top-8 left-1/2 -translate-x-1/2 text-xs bg-black text-white px-2 py-1 rounded transition-opacity">Copy</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Map/Location Card -->
                    <div class="bg-white dark:bg-[#1a1a2e] p-1 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                        <div class="relative w-full h-48 bg-gray-100 rounded-lg overflow-hidden">
                            <!-- Using a placeholder pattern for map as per strict image rules -->
                            <img alt="Map view" class="w-full h-full object-cover opacity-80" data-location="San Francisco, CA" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDxjuGzlcNTB-3OYN9TOPapQkSrJ3C-kmYMYtWODdgDjk2lqLMzsEn4k4AJaaDeh_L53hhABJHD7toa4RRXe5s8MAJf20hinv_HXvOeKDIW4ASSCIEX1JxxoOO3qzqjaju0G8TfuDNdpXrNayMJyjktTzg81FG6ak2SlEyy5pAPDe-KGWzQCtGDVOGBcLpGiTu6HXc6BD9AZRWdd_ZMFiqVnsY2eukSzjVEaPOR1r74F_Hsp9qhqYXfrcNkgtvV9SwEwaQCHi0NyIU"/>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="bg-white/90 dark:bg-black/70 backdrop-blur-sm p-2 rounded-lg shadow-lg">
                                    <span class="material-symbols-outlined text-red-500 text-3xl">location_on</span>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <h4 class="text-sm font-bold text-text-main dark:text-white mb-1">Office Location</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400" id="locationLabel">...</p>
                            <a class="text-primary text-xs font-semibold mt-2 inline-block hover:underline" href="#">Get Directions</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Similar Jobs Section (Default visible for debugging) -->
        <div id="similarJobsSection" class="mt-16 p-6 rounded-xl bg-gray-50 dark:bg-slate-900/50 border border-gray-100 dark:border-gray-800">
             <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Similar Jobs</h3>
             <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="similarJobsContainer">
                 <p class="text-gray-500 dark:text-gray-400 col-span-3 text-center py-4">Loading similar jobs...</p>
             </div>
        </div>
        
    </div>
</main>
<!-- Footer -->
<!-- Simple Footer -->
<?php include_once __DIR__ . '/../includes/footer.php'; ?>

<script>
    $(document).ready(function() {
        // Handle URL Params and Hash
        let jobId = null;
        let jobTitle = '';
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('id')) {
            jobId = urlParams.get('id');
            // Clean URL after getting ID
            if (history.replaceState) {
                const newUrl = window.location.pathname + '#' + jobId;
                history.replaceState(null, '', newUrl);
            }
        } else if (window.location.hash) {
            jobId = window.location.hash.substring(1); // Remove '#'
        }

        if (!jobId) {
            window.location.href = 'index.php';
            return;
        }

        const shareUrl = window.location.origin + window.location.pathname + '?id=' + jobId; // Use query param for sharing

        // Fetch Main Job Data
        $.ajax({
            url: '../api/get_job_details.php',
            method: 'GET',
            data: { id: jobId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const job = response.job;
                    jobTitle = job.title;
                    
                    // Populate Fields
                    document.title = job.title + ' - HR Connect';
                    $('#jobTitleBreadcrumb').text(job.title);
                    $('#jobTitleHeader').text(job.title);
                    $('#locationHeader').text(job.location);
                    $('#locationLabel').text(job.location);
                    $('#deptLabel').text(job.department_name || 'General');
                    
                    $('#employmentType').text(job.employment_type);
                    $('#expLevel').text(job.experience_level || 'Not Specified');
                    
                    // Dynamic Currency Formatting
                    const currencySymbol = '<?php echo get_currency_symbol(); ?>';
                    let salaryDisplay = job.salary_range; // Fallback
                    if (job.min_salary && job.max_salary) {
                         const min = parseFloat(job.min_salary);
                         const max = parseFloat(job.max_salary);
                         // Simple formatting logic matching PHP side
                         const format = (n) => {
                            if (n >= 1000000) return (n/1000000).toFixed(1) + 'M';
                            if (n >= 1000) return (n/1000).toFixed(0) + 'k';
                            return n;
                         };
                         salaryDisplay = currencySymbol + format(min) + ' - ' + currencySymbol + format(max);
                    }
                    $('#salaryRange').text(salaryDisplay);
                    
                    $('#jobDescription').html(job.description);
                    $('#jobRequirements').html(job.requirements);
                    
                    $('#datePosted').text(new Date(job.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }));
                    $('#jobIdLabel').text('#JOB-' + job.id);
                    
                    // Update Apply Buttons
                    const applyUrl = '/candidate_application_form/index.php?job_id=' + job.id;
                    $('#applyBtnMain, #applyBtnSidebar').attr('href', applyUrl);
                    
                    // Bookmark State
                    $('#bookmarkBtn').data('id', job.id);
                    if (parseInt(job.is_saved) > 0) {
                        $('#bookmarkIcon').addClass('filled text-primary').text('bookmark');
                        $('#bookmarkText').text('Saved');
                    }

                    // Reveal Content
                    $('#pageLoader').addClass('hidden');
                    $('#contentWrapper').removeClass('hidden');

                    // Fetch Similar Jobs
                    fetchSimilarJobs(jobId);

                } else {
                    $('#mainContent').html('<div class="text-center py-20"><h2 class="text-2xl font-bold text-gray-700">Job not found</h2><a href="/jobs" class="text-primary hover:underline mt-4 inline-block">Return to Job Board</a></div>');
                }
            },
            error: function() {
                 $('#mainContent').html('<div class="text-center py-20"><h2 class="text-2xl font-bold text-red-600">Error loading content</h2><a href="/jobs" class="text-primary hover:underline mt-4 inline-block">Return to Job Board</a></div>');
            }
        });

        function fetchSimilarJobs(currentId) {
            const container = $('#similarJobsContainer');
            
            $.ajax({
                url: '../api/fetch_jobs.php',
                method: 'GET',
                data: { limit: 3, exclude_id: currentId, sort: 'Most Relevant' },
                dataType: 'json',
                success: function(response) {
                    container.empty();
                    
                    if (response.success && response.jobs.length > 0) {
                        response.jobs.forEach(job => {
                             const initials = job.title.charAt(0).toUpperCase();
                             const posted = new Date(job.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                             
                             const html = `
                                <div class="bg-white dark:bg-[#1a1a2e] p-5 rounded-xl border border-gray-100 dark:border-gray-800 hover:shadow-md transition-shadow group cursor-pointer" onclick="window.location.href='/view-job?id=${job.id}'">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="h-10 w-10 rounded-lg bg-gray-50 dark:bg-gray-800 flex items-center justify-center text-primary font-bold border border-gray-100 dark:border-gray-700">
                                            ${initials}
                                        </div>
                                        <span class="text-xs text-gray-400">${posted}</span>
                                    </div>
                                    <h4 class="font-bold text-text-main dark:text-white mb-1 group-hover:text-primary transition-colors line-clamp-1">${job.title}</h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">${job.department_name || 'General'}</p>
                                    <div class="flex items-center justify-between text-xs text-gray-600">
                                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">location_on</span> ${job.location}</span>
                                        <span class="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-gray-600 dark:text-gray-300 font-medium">${job.employment_type}</span>
                                    </div>
                                </div>
                             `;
                             container.append(html);
                        });
                    } else {
                        container.html('<p class="text-gray-500 dark:text-gray-400 col-span-3 text-center py-4">No similar jobs found.</p>');
                    }
                },
                error: function(xhr, status, error) {
                     console.error("Similar jobs fetch error:", status, error);
                     container.html('<p class="text-red-500 col-span-3 text-center py-4">Unable to load similar jobs. Error: ' + status + '</p>');
                }
            });
        }

        // Bookmark Handler
        $('#bookmarkBtn').on('click', function() {
            const btn = $(this);
            const jobId = btn.data('id');
            const icon = $('#bookmarkIcon');
            const text = $('#bookmarkText');
            
            $.ajax({
                url: '../api/toggle_bookmark.php',
                method: 'POST',
                data: { job_id: jobId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (response.action === 'saved') {
                             icon.addClass('filled text-primary').text('bookmark');
                             text.text('Saved');
                        } else {
                             icon.removeClass('filled text-primary').text('bookmark_border');
                             text.text('Save Job');
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

        // Sharing Handlers
        $('#shareLinkedIn').on('click', function() {
            // Use window.open for sharing
            const url = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl)}`;
            window.open(url, '_blank', 'width=600,height=600');
        });

        $('#shareTwitter').on('click', function() {
             const text = `Check out this ${jobTitle} role at HR Connect!`;
             const url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(shareUrl)}`;
             window.open(url, '_blank', 'width=600,height=600');
        });

        $('#shareCopy').on('click', function() {
            navigator.clipboard.writeText(shareUrl).then(function() {
                const btn = $('#shareCopy');
                const originalContent = btn.html();
                // Simple visual feedback
                btn.html('<span class="material-symbols-outlined text-[16px]">check</span>');
                setTimeout(() => {
                    btn.html(originalContent);
                }, 2000);
            });
        });
    });
</script>

</body></html>
