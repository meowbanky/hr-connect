<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Check Admin Access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff')) {
    header('Location: /admin/login');
    exit;
}

// Fetch Jobs for Filter
$stmt = $pdo->query("SELECT id, title FROM job_postings ORDER BY title ASC");
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/settings.php';
$pageTitle = "Applications";
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo isset($_SESSION['theme_mode']) && $_SESSION['theme_mode'] === 'dark' ? 'dark' : 'light'; ?>">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Applications - HR Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link href="/assets/css/style.css" rel="stylesheet"/>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; font-size: 24px; line-height: 1; }
        .material-symbols-outlined.fill { font-variation-settings: 'FILL' 1; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
    <?php echo get_theme_css(); ?>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-800 dark:text-slate-100 flex h-screen overflow-hidden font-display antialiased">
<div class="flex h-screen w-full overflow-hidden bg-slate-50 dark:bg-slate-900">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full relative overflow-y-auto scroll-smooth">
        
        <!-- Page Content -->
        <div class="flex-1 p-6 lg:p-10 max-w-7xl mx-auto w-full flex flex-col gap-6">
            <!-- Breadcrumbs -->
            <nav class="flex items-center text-sm font-medium text-slate-500 dark:text-slate-400">
                <a class="hover:text-primary transition-colors" href="/admin/index.php">Dashboard</a>
                <span class="mx-2 text-slate-300 dark:text-slate-600">/</span>
                <span class="text-slate-900 dark:text-white font-semibold">Applications</span>
            </nav>

            <!-- Page Header & Main Actions -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white tracking-tight">Application Manager</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-base">Filter, review, and manage incoming candidates efficiently.</p>
                </div>
                <div class="flex gap-3">
                    <button id="exportBtn" class="h-10 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-semibold text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors flex items-center gap-2 shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">download</span>
                        Export
                    </button>
                </div>
            </div>

            <!-- Filters & Controls Section -->
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col gap-5">
                <!-- Top Row Filters -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <!-- Search Filter -->
                    <div class="md:col-span-5 flex flex-col gap-1.5">
                        <label class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Search Candidate</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                            <input type="text" id="filterSearch" placeholder="Name, email, or job title..." class="w-full h-11 pl-10 pr-10 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                             <!-- Clear Button -->
                            <button type="button" id="clearSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hidden">
                                <span class="material-symbols-outlined text-[18px]">close</span>
                            </button>
                        </div>
                    </div>

                    <!-- Job Title Filter -->
                    <div class="md:col-span-4 flex flex-col gap-1.5">
                        <label class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Job Role</label>
                        <div class="relative">
                            <select id="filterJob" class="w-full h-11 pl-3 pr-10 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-primary focus:border-transparent appearance-none cursor-pointer">
                                <option value="0">All Roles</option>
                                <?php foreach($jobs as $job): ?>
                                    <option value="<?php echo $job['id']; ?>"><?php echo htmlspecialchars($job['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500">expand_more</span>
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="md:col-span-3 flex flex-col gap-1.5">
                        <label class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</label>
                        <div class="relative">
                            <select id="filterStatus" class="w-full h-11 pl-3 pr-10 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-primary focus:border-transparent appearance-none cursor-pointer">
                                <option value="all">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="reviewed">Reviewed</option>
                                <option value="shortlisted">Shortlisted</option>
                                <option value="interviewed">Interviewed</option>
                                <option value="offered">Offered</option>
                                <option value="hired">Hired</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500">expand_more</span>
                        </div>
                    </div>
                </div>

                <div class="h-px bg-slate-100 dark:bg-slate-700 w-full"></div>

                <!-- Quick Filter Tags -->
                <div class="flex flex-wrap gap-2" id="quickFilters">
                    <button data-status="all" class="px-3 py-1.5 rounded-full text-xs font-semibold bg-primary text-white dark:bg-white dark:text-slate-900 shadow-sm transition-all filter-tag">All Applications</button>
                    <button data-status="pending" class="px-3 py-1.5 rounded-full text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary transition-all filter-tag">Pending</button>
                    <button data-status="shortlisted" class="px-3 py-1.5 rounded-full text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary transition-all filter-tag">Shortlisted</button>
                    <button data-status="rejected" class="px-3 py-1.5 rounded-full text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary transition-all filter-tag">Rejected</button>
                </div>
            </div>

                <!-- Bulk Actions Toolbar (Hidden by default) -->
                <div id="bulkActions" class="hidden bg-slate-900 dark:bg-slate-700 text-white p-3 rounded-lg flex items-center justify-between mb-4 shadow-lg transition-all duration-300">
                    <div class="flex items-center gap-3">
                        <span class="bg-white/10 px-2 py-1 rounded text-xs font-bold"><span id="selectedCount">0</span> Selected</span>
                        <span class="text-sm text-slate-300">Apply action on selected:</span>
                    </div>
                    <div class="flex items-center gap-2">
                         <button onclick="bulkUpdate('shortlisted')" class="px-3 py-1.5 rounded bg-white/10 hover:bg-white/20 text-xs font-medium transition-colors flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">check</span> Shortlist
                        </button>
                         <button onclick="bulkUpdate('rejected')" class="px-3 py-1.5 rounded bg-red-500/20 hover:bg-red-500/30 text-red-200 text-xs font-medium transition-colors flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">block</span> Reject
                        </button>
                        <button onclick="bulkUpdate('interviewed')" class="px-3 py-1.5 rounded bg-blue-500/20 hover:bg-blue-500/30 text-blue-200 text-xs font-medium transition-colors flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">calendar_month</span> Interview
                        </button>
                    </div>
                </div>

                <!-- Mobile Card List (Visible on Mobile) -->
                <div id="mobileApplicationList" class="md:hidden flex flex-col gap-4">
                    <!-- Mobile Cards Loaded via AJAX -->
                </div>

                <!-- Table Section (Hidden on Mobile) -->
                <div class="hidden md:flex bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden flex-col flex-1">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700">
                                    <th class="p-4 w-10">
                                        <input type="checkbox" id="selectAll" class="rounded border-slate-300 dark:border-slate-600 text-primary focus:ring-primary h-4 w-4 cursor-pointer">
                                    </th>
                                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Candidate</th>
                                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Applied Role</th>
                                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Date Applied</th>
                                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 text-center">Reqs</th>
                                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 text-right">Actions</th>
                                </tr>
                            </thead>
                        <tbody id="applicationTableBody" class="divide-y divide-slate-100 dark:divide-slate-700">
                           <!-- Rows Loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div id="paginationContainer">
                    <!-- Pagination Loaded via AJAX -->
                </div>
            </div>
        </div>

        <?php include __DIR__ . '/../includes/admin_footer.php'; ?>
    </main>
</div>

<!-- Scripts -->
<script>
    let currentFilters = {
        page: 1,
        search: '',
        status: 'all',
        job_id: 0
    };

    function fetchApplications(page = 1) {
        currentFilters.page = page;
        currentFilters.search = $('#filterSearch').val();
        currentFilters.job_id = $('#filterJob').val();
         // Status is already updated in currentFilters by change handlers if explicit
         // But for the dropdown, we sync it:
         // Note: If quick tags are used, status might be set differently.
         // Let's create a source of truth priority.
         // If generic load, use dropdown.
         
        // Actually, let's keep it simple: The dropdown and tags should update the global state.
        
        $.ajax({
            url: '/api/fetch_applications.php',
            method: 'GET',
            data: currentFilters,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#applicationTableBody').html(response.html);
                    $('#mobileApplicationList').html(response.mobile_html);
                    $('#paginationContainer').html(response.pagination);
                } else {
                    console.error('API Error:', response.message);
                }
            },
            error: function() {
                console.error('Network Error');
            }
        });
    }

    function updateStatus(appId, newStatus) {
        Swal.fire({
            title: 'Confirm Action',
            text: `Mark this candidate as ${newStatus}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#5749e9',
            confirmButtonText: 'Yes, update'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/api/admin_application_actions.php',
                    method: 'POST',
                    data: {
                        action: 'update_status',
                        application_id: appId,
                        status: newStatus
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Updated!',
                                text: 'Candidate status updated.',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            // Refresh list to show new status
                            fetchApplications(currentFilters.page);
                        } else {
                            Swal.fire('Error', response.message || 'Failed to update status', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Network error occurred', 'error');
                    }
                });
            }
        });
    }

    $(document).ready(function() {
        // Initial Fetch
        fetchApplications();

        // Search Input with Debounce
        let debounceTimer;
        $('#filterSearch').on('input', function() {
             const val = $(this).val().trim();
             // Toggle Clear Button
            if (val.length > 0) {
                $('#clearSearch').removeClass('hidden');
            } else {
                $('#clearSearch').addClass('hidden');
            }

             clearTimeout(debounceTimer);
             debounceTimer = setTimeout(() => {
                 fetchApplications(1);
             }, 300);
        });
        
         // Clear Search Logic
        $('#clearSearch').on('click', function() {
            $('#filterSearch').val('');
            $(this).addClass('hidden');
            fetchApplications(1);
        });

        // Dropdown Filters
        $('#filterJob, #filterStatus').on('change', function() {
             currentFilters.status = $('#filterStatus').val(); // update state from dropdown
             // Update tags UI
             $('.filter-tag').removeClass('bg-primary text-white dark:bg-white dark:text-slate-900')
                .addClass('bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300');
             // If status matches a tag, highlight it (approximate match)
             $(`.filter-tag[data-status="${currentFilters.status}"]`).removeClass('bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300')
                .addClass('bg-primary text-white dark:bg-white dark:text-slate-900');

             fetchApplications(1);
        });

        // Quick Filter Tags
        $('.filter-tag').on('click', function() {
            const status = $(this).data('status');
            currentFilters.status = status;
            
            // Update UI Select
            $('#filterStatus').val(status);
            
            // Update Tag Styles
            $('.filter-tag').removeClass('bg-primary text-white dark:bg-white dark:text-slate-900')
                .addClass('bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300');
            $(this).removeClass('bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300')
                .addClass('bg-primary text-white dark:bg-white dark:text-slate-900');

            fetchApplications(1);
        });

        // Export Functionality
        $('#exportBtn').on('click', function() {
            let params = $.param(currentFilters);
            window.location.href = '/api/export_applications.php?' + params;
        });

        // Select All Logic
        $('#selectAll').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.app-checkbox').prop('checked', isChecked);
            updateBulkToolbar();
        });

        // Individual Checkbox Logic (Delegated)
        $(document).on('change', '.app-checkbox', function() {
            updateBulkToolbar();
            // Update Select All state
            const allChecked = $('.app-checkbox:checked').length === $('.app-checkbox').length;
            $('#selectAll').prop('checked', allChecked);
        });
    });

    function updateBulkToolbar() {
        const count = $('.app-checkbox:checked').length;
        if (count > 0) {
            $('#bulkActions').removeClass('hidden');
            $('#selectedCount').text(count);
        } else {
            $('#bulkActions').addClass('hidden');
            $('#selectAll').prop('checked', false);
        }
    }

    function bulkUpdate(newStatus) {
        const selectedIds = $('.app-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) return;

        Swal.fire({
            title: 'Bulk Action',
            text: `Update ${selectedIds.length} candidates to "${newStatus}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#5749e9',
            confirmButtonText: 'Yes, proceed'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/api/admin_application_actions.php',
                    method: 'POST',
                    data: {
                        action: 'update_status', // Reusing same action
                        application_ids: selectedIds, // Passing array
                        status: newStatus
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Batch Updated!',
                                text: `${response.updated_count} candidates updated.`,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            $('#selectAll').prop('checked', false);
                            $('#bulkActions').addClass('hidden');
                            fetchApplications(currentFilters.page);
                        } else {
                            Swal.fire('Error', response.message || 'Failed to update', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Network error occurred', 'error');
                    }
                });
            }
        });
    }
</script>

</body>
</html>
