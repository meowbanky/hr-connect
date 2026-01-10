<?php
session_start();
// Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff')) {
    header('Location: /admin/login');
    exit;
}

require_once __DIR__ . '/../includes/settings.php';
$pageTitle = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo isset($_SESSION['theme_mode']) && $_SESSION['theme_mode'] === 'dark' ? 'dark' : 'light'; ?>">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Admin Dashboard - HR Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link href="/assets/css/style.css" rel="stylesheet"/>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
<body class="bg-background-light dark:bg-background-dark text-slate-800 dark:text-slate-100 flex h-screen overflow-hidden font-display antialiased selection:bg-primary/20 selection:text-primary">

    <!-- Sidebar -->
    <?php include_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full overflow-hidden relative">
        <!-- Header -->
        <?php include_once __DIR__ . '/../includes/admin_header.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto p-4 md:p-8 pt-2 space-y-6 md:space-y-8">
            
            <!-- Statistics Cards -->
            <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                <!-- Active Jobs -->
                <div class="bg-surface-light dark:bg-surface-dark p-4 md:p-6 rounded-lg shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col gap-4 relative overflow-hidden group hover:-translate-y-1 transition-transform duration-300">
                    <div class="flex justify-between items-start">
                        <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-blue-600 dark:text-blue-400">
                            <span class="material-symbols-outlined">briefcase_meal</span>
                        </div>
                        <span class="px-2 py-1 bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-xs font-semibold rounded-full">+2 new</span>
                    </div>
                    <div>
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Active Jobs</p>
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">18</h3>
                    </div>
                </div>
                
                <!-- New Applications -->
                <div class="bg-surface-light dark:bg-surface-dark p-4 md:p-6 rounded-lg shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col gap-4 relative overflow-hidden group hover:-translate-y-1 transition-transform duration-300">
                    <div class="flex justify-between items-start">
                        <div class="p-2 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg text-primary">
                            <span class="material-symbols-outlined">group_add</span>
                        </div>
                        <span class="px-2 py-1 bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-xs font-semibold rounded-full flex items-center gap-1">
                            <span class="material-symbols-outlined" style="font-size: 12px;">trending_up</span> 12%
                        </span>
                    </div>
                    <div>
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">New Applications</p>
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">1,240</h3>
                    </div>
                </div>

                <!-- Interviews -->
                <div class="bg-surface-light dark:bg-surface-dark p-4 md:p-6 rounded-lg shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col gap-4 relative overflow-hidden group hover:-translate-y-1 transition-transform duration-300">
                    <div class="flex justify-between items-start">
                        <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-purple-600 dark:text-purple-400">
                            <span class="material-symbols-outlined">event_available</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Interviews Scheduled</p>
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">42</h3>
                    </div>
                </div>

                <!-- Hired -->
                <div class="bg-surface-light dark:bg-surface-dark p-4 md:p-6 rounded-lg shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col gap-4 relative overflow-hidden group hover:-translate-y-1 transition-transform duration-300">
                    <div class="flex justify-between items-start">
                        <div class="p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg text-emerald-600 dark:text-emerald-400">
                            <span class="material-symbols-outlined">verified</span>
                        </div>
                        <span class="text-slate-400 text-xs mt-1">This Month</span>
                    </div>
                    <div>
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Hired Candidates</p>
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">7</h3>
                    </div>
                </div>
            </section>

            <!-- Layout Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Column (Actions & Jobs & Applications) -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Quick Actions -->
                    <div class="bg-surface-light dark:bg-surface-dark p-4 rounded-lg shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white pl-2">Quick Actions</h3>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button class="flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors text-sm font-medium w-full sm:w-auto">
                                <span class="material-symbols-outlined" style="font-size: 18px;">person_add</span>
                                Add Candidate
                            </button>
                            <button class="flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-primary text-white hover:bg-blue-700 transition-colors shadow-md shadow-blue-500/20 text-sm font-medium w-full sm:w-auto">
                                <span class="material-symbols-outlined" style="font-size: 18px;">add</span>
                                Post New Job
                            </button>
                        </div>
                    </div>

                    <!-- Job Postings -->
                    <div class="bg-surface-light dark:bg-surface-dark rounded-lg shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
                        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Job Postings</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage active listings</p>
                            </div>
                            <!-- Search & Action -->
                            <div class="flex gap-3">
                                <a href="/admin/jobs/create" class="px-4 py-2 rounded-lg bg-primary text-white hover:bg-blue-700 transition-colors text-sm font-medium flex items-center gap-2 shadow-sm whitespace-nowrap">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">add_circle</span>
                                    Create Job
                                </a>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <!-- Placeholder Table - will populate via API later -->
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                    <tr>
                                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Role</th>
                                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Date Posted</th>
                                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Applicants</th>
                                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                        <td class="px-6 py-4"><p class="text-sm font-semibold text-slate-900 dark:text-white">Senior Product Designer</p></td>
                                        <td class="px-6 py-4 text-sm text-slate-500">Oct 15, 2023</td>
                                        <td class="px-6 py-4 text-sm font-bold">24</td>
                                        <td class="px-6 py-4"><span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Active</span></td>
                                        <td class="px-6 py-4 text-right"><span class="material-symbols-outlined text-slate-400 cursor-pointer hover:text-primary">more_vert</span></td>
                                    </tr>
                                    <!-- More rows can be dynamic -->
                                </tbody>
                            </table>
                        </div>
                        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/30 border-t border-slate-100 dark:border-slate-800 flex items-center justify-center">
                            <a href="/admin/jobs" class="text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-primary transition-colors flex items-center gap-2">
                                View All Job Postings <span class="material-symbols-outlined" style="font-size: 16px;">arrow_forward</span>
                            </a>
                        </div>
                    </div>

                </div>

                <!-- Right Sidebar (Interviews & Dept Needs) -->
                <div class="space-y-6">
                    <div class="bg-surface-light dark:bg-surface-dark rounded-lg shadow-sm border border-slate-100 dark:border-slate-800 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Today's Interviews</h3>
                            <button class="text-primary hover:text-blue-700">
                                <span class="material-symbols-outlined" style="font-size: 20px;">calendar_month</span>
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div class="flex gap-4 items-start p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors border border-transparent hover:border-slate-100 dark:hover:border-slate-700">
                                <div class="flex flex-col items-center min-w-[3rem]">
                                    <span class="text-xs font-bold text-slate-500 uppercase">10:00</span>
                                    <span class="text-xs text-slate-400">AM</span>
                                </div>
                                <div class="w-1 h-10 bg-indigo-500 rounded-full"></div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Technical Round</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">w/ Michael Kim</p>
                                </div>
                            </div>
                            <!-- More events... -->
                        </div>
                        <button class="w-full mt-4 py-2 text-sm text-slate-500 hover:text-primary hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg transition-colors border border-dashed border-slate-300 dark:border-slate-700">
                            View Calendar
                        </button>
                    </div>
                    
                    <div class="bg-surface-light dark:bg-surface-dark rounded-lg shadow-sm border border-slate-100 dark:border-slate-800 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Department Needs</h3>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-medium text-slate-700 dark:text-slate-300">Engineering</span>
                                    <span class="text-slate-500">8 Open</span>
                                </div>
                                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full" style="width: 70%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-medium text-slate-700 dark:text-slate-300">Design</span>
                                    <span class="text-slate-500">3 Open</span>
                                </div>
                                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2">
                                    <div class="bg-purple-500 h-2 rounded-full" style="width: 40%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
        <!-- Footer -->
        <?php include_once __DIR__ . '/../includes/admin_footer.php'; ?>
    </main>
</body>
</html>
