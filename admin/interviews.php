<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

// Check Admin Auth
if (!isset($_SESSION['user_role']) || 
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff' && $_SESSION['user_role'] !== 'superadmin')) {
    header("Location: ../admin/login.php");
    exit;
}

$pageTitle = "Interview Management";

// Fetch Interviews
// Join applications, candidates, users, jobs
$sql = "SELECT i.*, 
               j.title as job_title, 
               u.first_name, u.last_name, u.profile_image
        FROM interviews i
        JOIN applications a ON i.application_id = a.id
        JOIN candidates c ON a.candidate_id = c.id
        JOIN users u ON c.user_id = u.id
        JOIN job_postings j ON a.job_id = j.id
        ORDER BY i.interview_date ASC, i.interview_time ASC";

$stmt = $pdo->query($sql);
$allInterviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for JS
$calendarEvents = [];
$now = new DateTime();

// Counters
$counts = [
    'upcoming' => 0,
    'in_progress' => 0,
    'completed' => 0
];

foreach ($allInterviews as &$int) {
    $date = new DateTime($int['interview_date'] . ' ' . $int['interview_time']);
    $int['js_date'] = $date->format('c'); // ISO 8601
    
    // Status Logic
    $status = 'upcoming'; 
    $diff = $now->diff($date);
    $minutesDiff = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
    $isPast = $date < $now;
    
    if ($int['status'] === 'completed' || $int['status'] === 'cancelled' || $int['status'] === 'no_show') {
        $status = 'completed';
    } else {
        // Scheduled
        // Check if "Live" (within last hour and next hour?) or strictly "In Progress" check
        $endTime = clone $date;
        $endTime->modify('+1 hour');
        
        if ($date <= $now && $now <= $endTime) {
            $status = 'in_progress';
        } elseif ($now > $endTime) {
            // Technically passed, but not marked completed. Let's put in 'completed' tab but mark as 'Missed'? 
            // Or keep in upcoming but expired?
            // For simplicity and to not lose them, let's keep as 'upcoming' or 'completed'
            // Let's put them in 'completed' tab as 'Pending Action'
            $status = 'completed';
        } else {
            $status = 'upcoming';
        }
    }
    
    // Assign calculated category for filtering
    $int['category'] = $status;
    $counts[$status]++;
    
    // Calendar Event
    $calendarEvents[] = [
        'title' => $int['first_name'] . ' - ' . $int['job_title'],
        'start' => $int['interview_date'] . 'T' . $int['interview_time'],
        'url'   => '../admin/view_application.php?id=' . $int['application_id'],
        'color' => ($status === 'completed' ? '#64748b' : ($status === 'in_progress' ? '#22c55e' : '#1313ec'))
    ];
}
unset($int); // Break reference
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Admin Interview Management | HR Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#1313ec", 
                        "background-light": "#f6f6f8",
                        "background-dark": "#121220",
                        "slate-custom": "#535393",
                        "dark-custom": "#0f0f1a",
                    },
                    fontFamily: {
                        "display": ["Inter"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
    <?php echo get_theme_css(); ?>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .bento-grid {
             display: grid;
             grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
             gap: 1.5rem;
        }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        .active-tab { border-bottom-color: var(--color-primary); color: var(--color-primary); }
        .inactive-tab { border-bottom-color: transparent; color: #535393; }
        .inactive-tab:hover { color: #0f0f1a; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-dark-custom dark:text-white min-h-screen flex">

    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col md:ml-0 transition-all duration-300">
        
        <!-- Header -->
        <?php include __DIR__ . '/../includes/admin_header.php'; ?>

        <!-- Viewport Content -->
        <div class="p-8">
            <!-- Breadcrumbs & Actions -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <div class="flex items-center gap-2 text-sm text-slate-custom mb-1 font-medium">
                        <span>Recruitment</span>
                        <span class="material-symbols-outlined text-xs">chevron_right</span>
                        <span class="text-dark-custom dark:text-white">Interviews</span>
                    </div>
                    <h3 class="text-2xl font-bold">Scheduled Sessions</h3>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Segmented Control View Switcher -->
                    <div class="bg-[#e8e8f2] dark:bg-slate-800 p-1 rounded-xl flex">
                        <button onclick="switchView('grid')" id="btn-grid" class="px-4 py-1.5 rounded-lg bg-white dark:bg-slate-700 shadow-sm text-sm font-bold flex items-center gap-2 transition-all">
                            <span class="material-symbols-outlined text-lg">grid_view</span>
                            Grid
                        </button>
                        <button onclick="switchView('calendar')" id="btn-calendar" class="px-4 py-1.5 rounded-lg text-slate-custom text-sm font-medium flex items-center gap-2 hover:text-dark-custom transition-all">
                            <span class="material-symbols-outlined text-lg">calendar_month</span>
                            Calendar
                        </button>
                    </div>
                    <a href="../admin/applications.php" class="bg-primary text-white px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-primary/90 transition-all shadow-lg shadow-primary/20">
                        <span class="material-symbols-outlined text-lg">add_circle</span>
                        Schedule Interview
                    </a>
                </div>
            </div>

            <!-- Tabs Section (Only visible in Grid View) -->
            <div id="tabs-container" class="flex border-b border-[#e8e8f2] dark:border-slate-800 mb-6 gap-8 overflow-x-auto">
                <button onclick="filterInterviews('upcoming')" id="tab-upcoming" class="active-tab pb-4 border-b-2 text-sm font-bold whitespace-nowrap transition-colors">
                    Upcoming <span class="ml-2 px-2 py-0.5 bg-primary/10 text-primary rounded-full text-[10px]"><?php echo $counts['upcoming']; ?></span>
                </button>
                <button onclick="filterInterviews('in_progress')" id="tab-in_progress" class="inactive-tab pb-4 border-b-2 text-sm font-medium whitespace-nowrap transition-colors">
                    In Progress <span class="ml-2 px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-[10px]"><?php echo $counts['in_progress']; ?></span>
                </button>
                <button onclick="filterInterviews('completed')" id="tab-completed" class="inactive-tab pb-4 border-b-2 text-sm font-medium whitespace-nowrap transition-colors">
                    Completed <span class="ml-2 px-2 py-0.5 bg-gray-100 text-gray-700 rounded-full text-[10px]"><?php echo $counts['completed']; ?></span>
                </button>
            </div>

            <!-- Bento Interview Cards Grid -->
            <div id="view-grid" class="bento-grid">
                
                <?php if (empty($allInterviews)): ?>
                    <div class="col-span-full py-12 text-center text-slate-400">
                        <span class="material-symbols-outlined text-4xl mb-2">event_busy</span>
                        <p>No interviews scheduled.</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($allInterviews as $interview): 
                    $cat = $interview['category'];
                    $iDate = new DateTime($interview['interview_date'] . ' ' . $interview['interview_time']);
                    $isLive = ($cat === 'in_progress');
                ?>
                <div class="interview-card group" data-category="<?php echo $cat; ?>" style="<?php echo $cat !== 'upcoming' ? 'display: none;' : ''; ?>">
                    <div class="bg-white dark:bg-[#1a1a2e] border border-[#e8e8f2] dark:border-slate-800 <?php echo $isLive ? 'border-2 border-green-500/30' : ''; ?> rounded-xl p-5 shadow-sm hover:shadow-md transition-all relative overflow-hidden h-full">
                        
                        <?php if ($isLive): ?>
                        <div class="absolute top-0 right-0 p-3">
                            <div class="flex items-center gap-1.5 bg-green-50 text-green-600 px-2 py-1 rounded-full">
                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                <span class="text-[10px] font-bold uppercase tracking-wider">Live</span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="flex items-start gap-4 mb-4">
                            <div class="size-12 rounded-xl bg-slate-100 overflow-hidden shrink-0 border border-slate-200">
                                <?php if(!empty($interview['profile_image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($interview['profile_image']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center bg-primary/10 text-primary font-bold">
                                        <?php echo substr($interview['first_name'], 0, 1) . substr($interview['last_name'], 0, 1); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h4 class="font-bold text-base"><?php echo htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']); ?></h4>
                                <p class="text-xs text-slate-custom font-medium"><?php echo htmlspecialchars($interview['job_title']); ?></p>
                                <div class="mt-2 flex items-center gap-2 text-[11px] font-bold <?php echo $isLive ? 'text-green-600' : 'text-slate-custom'; ?>">
                                    <span class="material-symbols-outlined text-sm">calendar_today</span>
                                    <?php echo $iDate->format('M d, Y'); ?>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 py-4 border-y border-dashed border-slate-200 dark:border-slate-800 my-4">
                            <div>
                                <p class="text-[10px] text-slate-custom uppercase font-bold tracking-tight">Time</p>
                                <p class="text-xs font-semibold mt-0.5"><?php echo $iDate->format('g:i A'); ?></p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-custom uppercase font-bold tracking-tight">Location</p>
                                <p class="text-xs font-semibold mt-0.5 truncate" title="<?php echo htmlspecialchars($interview['venue_name']); ?>">
                                    <?php echo htmlspecialchars($interview['venue_name']); ?>
                                </p>
                            </div>
                        </div>
                        
                         <div class="flex gap-2 mt-auto">
                            <?php if($cat === 'upcoming'): ?>
                            <a href="../admin/view_application.php?id=<?php echo $interview['application_id']; ?>" class="flex-1 border border-primary text-primary py-2 rounded-lg text-xs font-bold hover:bg-primary/5 transition-colors text-center">
                                View Details
                            </a>
                            <?php else: ?>
                                <a href="../admin/view_application.php?id=<?php echo $interview['application_id']; ?>" class="flex-1 bg-slate-100 text-slate-600 py-2 rounded-lg text-xs font-bold hover:bg-slate-200 transition-colors text-center">
                                Review
                            </a>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
                <?php endforeach; ?>

            </div>
            
            <!-- Calendar View (Hidden by default) -->
            <div id="view-calendar" class="hidden bg-white dark:bg-[#1a1a2e] p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                <div id="calendar"></div>
            </div>

             <!-- Panelist Insights (Visual Only) -->
            <div class="mt-12 bg-primary/5 dark:bg-primary/10 rounded-2xl p-6 border border-primary/10">
                <div class="flex items-center justify-between mb-6">
                    <h5 class="text-sm font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">analytics</span>
                        Panelist Availability & Load
                    </h5>
                    <a class="text-xs font-bold text-primary hover:underline" href="#">View Full Schedule</a>
                </div>
                <div class="flex gap-4 overflow-x-auto pb-2 scrollbar-hide">
                    <!-- Static Data for Visual -->
                    <div class="min-w-[200px] bg-white dark:bg-[#1a1a2e] p-3 rounded-xl shadow-sm border border-white dark:border-slate-800">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="size-10 rounded-full bg-slate-100 overflow-hidden flex items-center justify-center text-slate-500 font-bold">EC</div>
                            <div>
                                <p class="text-xs font-bold">Dr. Emily Chen</p>
                                <p class="text-[10px] text-slate-custom">Engineering</p>
                            </div>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-1 rounded-full overflow-hidden">
                            <div class="bg-primary h-full w-[80%]"></div>
                        </div>
                        <p class="text-[9px] mt-2 font-bold text-slate-custom">8/10 hours scheduled this week</p>
                    </div>
                </div>
            </div>

        </div>
    </main>
    
    <script>
        // State
        let currentView = 'grid';
        let currentTab = 'upcoming';
        const calendarEl = document.getElementById('calendar');
        let calendar = null;

        // Init
        document.addEventListener('DOMContentLoaded', function() {
            // Initial filter applied by PHP logic in loop (style="display:none")
        });

        function switchView(view) {
            currentView = view;
            
            const btnGrid = document.getElementById('btn-grid');
            const btnCal = document.getElementById('btn-calendar');
            const viewGrid = document.getElementById('view-grid');
            const viewCal = document.getElementById('view-calendar');
            const tabs = document.getElementById('tabs-container');
            
            if (view === 'grid') {
                btnGrid.classList.add('bg-white', 'dark:bg-slate-700', 'shadow-sm', 'text-dark-custom');
                btnGrid.classList.remove('text-slate-custom', 'hover:text-dark-custom');
                
                btnCal.classList.remove('bg-white', 'dark:bg-slate-700', 'shadow-sm', 'text-dark-custom');
                btnCal.classList.add('text-slate-custom', 'hover:text-dark-custom');
                
                viewGrid.classList.remove('hidden');
                viewCal.classList.add('hidden');
                tabs.classList.remove('hidden');
            } else {
                btnCal.classList.add('bg-white', 'dark:bg-slate-700', 'shadow-sm', 'text-dark-custom');
                btnCal.classList.remove('text-slate-custom', 'hover:text-dark-custom');
                
                btnGrid.classList.remove('bg-white', 'dark:bg-slate-700', 'shadow-sm', 'text-dark-custom');
                btnGrid.classList.add('text-slate-custom', 'hover:text-dark-custom');
                
                viewGrid.classList.add('hidden');
                viewCal.classList.remove('hidden');
                tabs.classList.add('hidden');
                
                // Init Calendar if not already
                if (!calendar) {
                    initCalendar();
                } else {
                    calendar.render(); // Re-render to fix size issues
                }
            }
        }

        function filterInterviews(status) {
            currentTab = status;
            
            // Update Tab Styles
            ['upcoming', 'in_progress', 'completed'].forEach(tab => {
                const el = document.getElementById('tab-' + tab);
                if (tab === status) {
                    el.classList.add('active-tab', 'font-bold');
                    el.classList.remove('inactive-tab', 'font-medium');
                } else {
                    el.classList.remove('active-tab', 'font-bold');
                    el.classList.add('inactive-tab', 'font-medium');
                }
            });
            
            // Filter Items
            const items = document.querySelectorAll('.interview-card');
            items.forEach(item => {
                if (item.dataset.category === status) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        function initCalendar() {
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: <?php echo json_encode($calendarEvents); ?>,
                eventClick: function(info) {
                    if (info.event.url) {
                        window.location.href = info.event.url;
                        info.jsEvent.preventDefault();
                    }
                },
                height: 'auto',
                themeSystem: 'standard'
            });
            calendar.render();
        }
    </script>
</body>
</html>
