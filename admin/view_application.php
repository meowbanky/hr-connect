<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Check Admin Access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff')) {
    header("Location: /admin/login.php");
    exit;
}

$application_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($application_id === 0) {
    header("Location: /admin/applications.php");
    exit;
}

// Fetch Application Details
$stmt = $pdo->prepare("
    SELECT 
        a.id as application_id,
        a.status,
        a.application_date,
        a.cover_letter,
        j.title as job_title,
        j.department_id,
        d.name as department_name,
        u.first_name,
        u.last_name,
        u.email,
        u.phone_number,
        u.profile_image,
        c.resume_path,
        c.linkedin_profile,
        c.portfolio_url,
        c.highest_qualification,
        c.years_of_experience,
        c.state_of_origin,
        c.lga
    FROM applications a
    JOIN job_postings j ON a.job_id = j.id
    LEFT JOIN departments d ON j.department_id = d.id
    JOIN candidates c ON a.candidate_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE a.id = ?
");
$stmt->execute([$application_id]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    die("Application not found.");
}

require_once __DIR__ . '/../includes/settings.php';
$pageTitle = "Application: " . htmlspecialchars($app['first_name'] . ' ' . $app['last_name']);

// Pipeline Steps logic
$pipelineSteps = ['pending', 'reviewed', 'shortlisted', 'interviewed', 'offered', 'hired', 'rejected'];
$currentStepIndex = array_search($app['status'], $pipelineSteps);
$progressWidth = ($currentStepIndex / (count($pipelineSteps) - 1)) * 100;
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo isset($_SESSION['theme_mode']) && $_SESSION['theme_mode'] === 'dark' ? 'dark' : 'light'; ?>">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $pageTitle; ?> - HR Connect</title>
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
        <!-- Breadcrumbs & Header -->
        <header class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 px-6 py-4 sticky top-0 z-10">
            <nav class="flex items-center text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                 <a class="hover:text-primary transition-colors" href="/admin/applications.php">Applications</a>
                 <span class="mx-2 text-slate-300 dark:text-slate-600">/</span>
                 <span class="text-slate-900 dark:text-white font-semibold">Details</span>
            </nav>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                    Candidate Application #<?php echo $app['application_id']; ?>
                </h1>
                <div class="flex gap-2">
                    <button onclick="window.print()" class="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200" title="Print Details">
                        <span class="material-symbols-outlined">print</span>
                    </button>
                </div>
            </div>
        </header>

        <div class="p-6 lg:p-8 max-w-7xl mx-auto w-full space-y-6">
            
            <!-- Profile Header Card -->
            <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="flex flex-col md:flex-row gap-6 justify-between items-start">
                    <div class="flex gap-5">
                       <?php if($app['profile_image']): ?>
                            <div class="h-24 w-24 rounded-full bg-cover bg-center border-4 border-white dark:border-slate-700 shadow-sm flex-shrink-0" style="background-image: url('/uploads/profile_images/<?php echo htmlspecialchars($app['profile_image']); ?>');"></div>
                        <?php else: ?>
                            <div class="h-24 w-24 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-400 text-3xl font-bold border-4 border-white dark:border-slate-700 shadow-sm flex-shrink-0">
                                <?php echo strtoupper(substr($app['first_name'], 0, 1) . substr($app['last_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex flex-col pt-1">
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white leading-tight">
                                <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?>
                            </h2>
                            <p class="text-slate-500 dark:text-slate-400 font-medium">Applied for <span class="text-primary"><?php echo htmlspecialchars($app['job_title']); ?></span></p>
                            
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-3 text-sm text-slate-500 dark:text-slate-400">
                                <?php if($app['state_of_origin']): ?>
                                    <div class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[18px]">location_on</span>
                                        <?php echo htmlspecialchars($app['state_of_origin']); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[18px]">schedule</span>
                                    Applied <?php echo date('M d, Y', strtotime($app['application_date'])); ?>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[18px]">school</span>
                                    <?php echo htmlspecialchars($app['highest_qualification']); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                        <?php if(!in_array($app['status'], ['hired', 'rejected'])): ?>
                            <button onclick="updateStatus('rejected')" class="flex-1 sm:flex-none h-10 px-4 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200 font-medium rounded-lg hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">block</span>
                                Reject
                            </button>
                            
                            <?php if($app['email']): ?>
                            <a href="mailto:<?php echo htmlspecialchars($app['email']); ?>" class="flex-1 sm:flex-none h-10 px-4 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200 font-medium rounded-lg hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">mail</span>
                                Email
                            </a>
                            <?php endif; ?>

                            <button onclick="updateStatus('shortlisted')" class="flex-1 sm:flex-none h-10 px-5 bg-primary text-white font-medium rounded-lg hover:bg-primary/90 shadow-sm shadow-primary/30 transition-all flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">check</span>
                                Shortlist Candidate
                            </button>
                             <button onclick="updateStatus('interviewed')" class="flex-1 sm:flex-none h-10 px-5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 shadow-sm transition-all flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">calendar_add_on</span>
                                Interview
                            </button>
                        <?php else: ?>
                            <div class="px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 font-medium border border-slate-200 dark:border-slate-700">
                                Status: <?php echo ucfirst($app['status']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Left Details Column -->
                <div class="space-y-6">
                    <!-- Contact Info -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
                        <h3 class="text-base font-semibold mb-4 flex items-center gap-2 text-slate-900 dark:text-white">
                            <span class="material-symbols-outlined text-slate-400">contact_page</span>
                            Contact Information
                        </h3>
                        <div class="space-y-4">
                            <?php if($app['email']): ?>
                            <div class="flex items-start gap-3">
                                <div class="bg-slate-100 dark:bg-slate-700 p-2 rounded text-slate-500 dark:text-slate-300">
                                    <span class="material-symbols-outlined text-sm">mail</span>
                                </div>
                                <div class="overflow-hidden">
                                    <p class="text-xs text-slate-500 uppercase font-semibold">Email</p>
                                    <a class="text-sm font-medium text-primary truncate hover:underline" href="mailto:<?php echo htmlspecialchars($app['email']); ?>"><?php echo htmlspecialchars($app['email']); ?></a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if($app['phone_number']): ?>
                            <div class="flex items-start gap-3">
                                <div class="bg-slate-100 dark:bg-slate-700 p-2 rounded text-slate-500 dark:text-slate-300">
                                    <span class="material-symbols-outlined text-sm">call</span>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 uppercase font-semibold">Phone</p>
                                    <p class="text-sm font-medium text-slate-900 dark:text-slate-200"><?php echo htmlspecialchars($app['phone_number']); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if($app['linkedin_profile']): ?>
                            <div class="flex items-start gap-3">
                                <div class="bg-slate-100 dark:bg-slate-700 p-2 rounded text-slate-500 dark:text-slate-300">
                                    <span class="material-symbols-outlined text-sm">link</span>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 uppercase font-semibold">LinkedIn</p>
                                    <a class="text-sm font-medium text-primary hover:underline" href="<?php echo htmlspecialchars($app['linkedin_profile']); ?>" target="_blank">View Profile</a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Documents -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
                        <h3 class="text-base font-semibold mb-4 flex items-center gap-2 text-slate-900 dark:text-white">
                            <span class="material-symbols-outlined text-slate-400">folder_open</span>
                            Documents
                        </h3>
                        <div class="space-y-3">
                            <?php if($app['resume_path']): ?>
                            <div class="flex items-center justify-between p-3 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-primary/50 transition-colors group bg-slate-50 dark:bg-slate-700/50">
                                <div class="flex items-center gap-3">
                                    <div class="bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 p-1.5 rounded">
                                        <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Resume / CV</span>
                                        <span class="text-xs text-slate-400">Uploaded</span>
                                    </div>
                                </div>
                                <a href="<?php echo htmlspecialchars($app['resume_path']); ?>" download class="text-slate-400 hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined">download</span>
                                </a>
                            </div>
                            <?php else: ?>
                                <p class="text-sm text-slate-500 italic">No resume uploaded.</p>
                            <?php endif; ?>
                            
                            <?php if($app['cover_letter']): ?>
                             <div class="mt-4 p-4 bg-slate-50 dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700">
                                <p class="text-xs font-bold text-slate-500 uppercase mb-2">Cover Letter Snippet</p>
                                <p class="text-sm text-slate-700 dark:text-slate-300 italic line-clamp-3">
                                    "<?php echo nl2br(htmlspecialchars(substr($app['cover_letter'], 0, 200))); ?>..."
                                </p>
                             </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Pipeline & Content -->
                <div class="xl:col-span-2 space-y-6">
                    <!-- Pipeline Status -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
                        <div class="flex justify-between items-center mb-6">
                             <h3 class="text-base font-semibold text-slate-900 dark:text-white">Pipeline Status</h3>
                             <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide bg-primary/10 text-primary">
                                 <?php echo ucfirst($app['status']); ?>
                             </span>
                        </div>
                        
                        <!-- Visual Stepper -->
                         <div class="relative flex items-center justify-between w-full px-4">
                            <div class="absolute left-0 top-[15px]  w-full h-1 bg-slate-100 dark:bg-slate-700 rounded z-0"></div>
                            <!-- Active Bar width logic would go here, simplified to full-width static for now since logic is complex in PHP without step array iteration -->
                           
                            <?php 
                            $displaySteps = ['Applied', 'Shortlisted', 'Interviewed', 'Hired'];
                            foreach($displaySteps as $index => $step): 
                                $isActive = false;
                                // Simple logic matching for demo
                                if ($app['status'] == 'hired') $isActive = true;
                                if ($app['status'] == 'interviewed' && $index <= 2) $isActive = true;
                                if ($app['status'] == 'shortlisted' && $index <= 1) $isActive = true;
                                if ($index == 0) $isActive = true;
                                
                                $colorClass = $isActive ? 'bg-primary text-white ring-primary' : 'bg-slate-200 dark:bg-slate-600 text-slate-500 ring-transparent';
                            ?>
                             <div class="relative z-10 flex flex-col items-center gap-2">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold ring-4 ring-white dark:ring-slate-800 <?php echo $colorClass; ?>">
                                    <?php if($isActive): ?>
                                        <span class="material-symbols-outlined text-sm">check</span>
                                    <?php else: ?>
                                        <?php echo $index + 1; ?>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs font-medium <?php echo $isActive ? 'text-primary' : 'text-slate-500'; ?>"><?php echo $step; ?></span>
                            </div>
                            <?php endforeach; ?>
                         </div>
                    </div>

                    <!-- Tabs & Content -->
                    <!-- Tabs & Content -->
                     <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 min-h-[400px]">
                        <div class="border-b border-slate-200 dark:border-slate-700 px-6">
                            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                                <button id="btn-overview" onclick="switchTab('overview')" class="tab-btn border-primary text-primary whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">Overview</button>
                                <button id="btn-coverletter" onclick="switchTab('coverletter')" class="tab-btn border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">Full Cover Letter</button>
                            </nav>
                        </div>
                        
                        <div class="p-6">
                            <!-- Overview Tab -->
                            <div id="tab-overview" class="space-y-6 tab-content">
                                <section>
                                    <h4 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Qualification & Experience</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                         <div class="bg-slate-50 dark:bg-slate-700/50 p-4 rounded-lg">
                                            <p class="text-xs text-slate-500 uppercase font-bold mb-1">Highest Qualification</p>
                                            <p class="text-base font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($app['highest_qualification'] ?? 'Not Specified'); ?></p>
                                         </div>
                                         <div class="bg-slate-50 dark:bg-slate-700/50 p-4 rounded-lg">
                                            <p class="text-xs text-slate-500 uppercase font-bold mb-1">Years of Experience</p>
                                            <p class="text-base font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($app['years_of_experience'] ?? '0'); ?> Years</p>
                                         </div>
                                    </div>
                                </section>

                                <section>
                                    <h4 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Portfolio</h4>
                                    <?php if($app['portfolio_url']): ?>
                                        <a href="<?php echo htmlspecialchars($app['portfolio_url']); ?>" target="_blank" class="text-primary hover:underline flex items-center gap-2">
                                            <span class="material-symbols-outlined text-sm">language</span>
                                            <?php echo htmlspecialchars($app['portfolio_url']); ?>
                                        </a>
                                    <?php else: ?>
                                        <p class="text-sm text-slate-500">No portfolio URL provided.</p>
                                    <?php endif; ?>
                                </section>
                            </div>

                            <!-- Cover Letter Tab (Hidden by default) -->
                            <div id="tab-coverletter" class="hidden space-y-6 tab-content">
                                <section>
                                    <h4 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Cover Letter</h4>
                                    <div class="bg-slate-50 dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap font-serif text-base">
<?php echo htmlspecialchars($app['cover_letter'] ?? 'No cover letter provided.'); ?>
                                    </div>
                                </section>
                            </div>
                        </div>
                     </div>
                </div>
            </div>
        </div>
        
        <?php include __DIR__ . '/../includes/admin_footer.php'; ?>
    </main>
</div>

<!-- Schedule Interview Modal -->
<div id="scheduleModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeScheduleModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <div class="bg-white dark:bg-slate-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-slate-900 dark:text-white" id="modal-title">Schedule Interview</h3>
                        <div class="mt-2">
                            <form id="scheduleForm" class="space-y-4">
                                <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Date</label>
                                        <input type="date" name="interview_date" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-primary focus:ring-primary dark:bg-slate-700 dark:border-slate-600 dark:text-white sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Time</label>
                                        <input type="time" name="interview_time" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-primary focus:ring-primary dark:bg-slate-700 dark:border-slate-600 dark:text-white sm:text-sm">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Venue Name</label>
                                    <input type="text" name="venue_name" placeholder="e.g. Head Office, Conference Room A" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-primary focus:ring-primary dark:bg-slate-700 dark:border-slate-600 dark:text-white sm:text-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Venue Address</label>
                                    <textarea name="venue_address" rows="2" placeholder="Full address" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-primary focus:ring-primary dark:bg-slate-700 dark:border-slate-600 dark:text-white sm:text-sm"></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Google Maps Link (Optional)</label>
                                    <input type="url" name="venue_link" id="venue_link" placeholder="https://maps.google.com/..." class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-primary focus:ring-primary dark:bg-slate-700 dark:border-slate-600 dark:text-white sm:text-sm">
                                    <p class="text-xs text-slate-500 mt-1">Paste a link to help candidates find the location.</p>
                                </div>

                                <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Map Location Picker</label>
                                    <input type="text" id="map-search" placeholder="Search for a location..." class="block w-full rounded-md border-slate-300 shadow-sm focus:border-primary focus:ring-primary dark:bg-slate-700 dark:border-slate-600 dark:text-white sm:text-sm mb-2">
                                    <div id="map" style="height: 300px; width: 100%; border-radius: 8px;" class="border border-slate-300 dark:border-slate-600"></div>
                                    <p class="text-xs text-slate-500 mt-1">Search or click on the map to set the location.</p>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Latitude (Optional)</label>
                                        <input type="text" name="venue_lat" id="venue_lat" placeholder="e.g. 6.5244" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-primary focus:ring-primary dark:bg-slate-700 dark:border-slate-600 dark:text-white sm:text-sm" readonly>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Longitude (Optional)</label>
                                        <input type="text" name="venue_lng" id="venue_lng" placeholder="e.g. 3.3792" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-primary focus:ring-primary dark:bg-slate-700 dark:border-slate-600 dark:text-white sm:text-sm" readonly>
                                    </div>
                                </div>
                                <p class="text-xs text-yellow-600 dark:text-yellow-500">
                                    <span class="material-symbols-outlined text-[14px] align-text-bottom">warning</span>
                                    Lat/Lng required for location check-in validation.
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-slate-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="submitSchedule()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm">
                    Schedule Interview
                </button>
                <button type="button" onclick="closeScheduleModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-slate-800 dark:text-slate-300 dark:border-slate-600 dark:hover:bg-slate-700">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ... existing switchTab ...
function switchTab(tabName) {
    // Hide all contents
    $('.tab-content').addClass('hidden');
    // Show selected
    $('#tab-' + tabName).removeClass('hidden');
    
    // Reset all buttons to inactive state
    $('.tab-btn').removeClass('border-primary text-primary')
                 .addClass('border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300');
                 
    // Set active button
    $('#btn-' + tabName).removeClass('border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300')
                        .addClass('border-primary text-primary');
}



function submitSchedule() {
    const form = document.getElementById('scheduleForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);

    // Disable button and show loading state if you want (omitted for brevity)
    
    $.ajax({
        url: '/api/schedule_interview.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    title: 'Scheduled!',
                    text: response.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Network error occurred.', 'error');
        }
    });
}

function updateStatus(newStatus) {
    const appId = <?php echo $application_id; ?>;
    
    if (newStatus === 'interviewed') {
        openScheduleModal();
        return;
    }
    
    Swal.fire({
        title: 'Update Status?',
        text: `Change candidate status to "${newStatus}"?`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#5749e9',
        confirmButtonText: 'Yes, update it!'
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
                            title: 'Updated!',
                            text: 'Status has been updated successfully.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
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
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $_ENV['GOOGLE_MAPS_API_KEY']; ?>&libraries=places&callback=initMap" async defer></script>
<script>
let map;
let marker;
let autocomplete;

function initMap() {
    // Default to Lagos, Nigeria or User's rough location
    const defaultLocation = { lat: 6.5244, lng: 3.3792 }; 
    
    map = new google.maps.Map(document.getElementById("map"), {
        zoom: 13,
        center: defaultLocation,
        mapTypeControl: false,
        fullscreenControl: true,
        streetViewControl: false
    });

    // Create Marker
    marker = new google.maps.Marker({
        position: defaultLocation,
        map: map,
        draggable: true,
        animation: google.maps.Animation.DROP
    });

    // Autocomplete
    const input = document.getElementById("map-search");
    autocomplete = new google.maps.places.Autocomplete(input);
    autocomplete.bindTo("bounds", map);

    autocomplete.addListener("place_changed", () => {
        const place = autocomplete.getPlace();

        if (!place.geometry || !place.geometry.location) {
            window.alert("No details available for input: '" + place.name + "'");
            return;
        }

        // Move map and marker
        if (place.geometry.viewport) {
            map.fitBounds(place.geometry.viewport);
        } else {
            map.setCenter(place.geometry.location);
            map.setZoom(17);
        }
        marker.setPosition(place.geometry.location);
        
        // Update Inputs
        updateLocationInputs(place.geometry.location);
    });

    // Map Click Listener
    map.addListener("click", (e) => {
        marker.setPosition(e.latLng);
        updateLocationInputs(e.latLng);
    });

    // Marker Drag Listener
    marker.addListener("dragend", (e) => {
        updateLocationInputs(e.latLng);
    });
}

function updateLocationInputs(latLng) {
    document.getElementById("venue_lat").value = latLng.lat().toFixed(6);
    document.getElementById("venue_lng").value = latLng.lng().toFixed(6);
    
    // Auto-fill Google Maps Link
    const link = `https://www.google.com/maps/?q=${latLng.lat()},${latLng.lng()}`;
    document.getElementById("venue_link").value = link;
}

function closeScheduleModal() {
    $('#scheduleModal').addClass('hidden');
}

function openScheduleModal() {
    $('#scheduleModal').removeClass('hidden');
    // Resize map when modal opens to prevent gray box
    if(map) {
        setTimeout(() => {
            google.maps.event.trigger(map, "resize");
            if(marker) map.setCenter(marker.getPosition());
        }, 300);
    }
}
</script>
</body>
</html>
