<?php
session_start();
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../config/db.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../candidate_registration/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch Interviews
$sql = "
    SELECT 
        i.*,
        j.title as job_title,
        d.name as department_name,
        a.id as application_id
    FROM interviews i
    JOIN applications a ON i.application_id = a.id
    JOIN candidates c ON a.candidate_id = c.id
    JOIN job_postings j ON a.job_id = j.id
    LEFT JOIN departments d ON j.department_id = d.id
    WHERE c.user_id = ?
    ORDER BY i.interview_date DESC, i.interview_time ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$interviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>My Interviews - RecruitFlow</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary": "#1313ec", // Kept project primary
                    "background-light": "#f6f6f8",
                    "background-dark": "#101022",
                },
                fontFamily: {
                    "display": ["Inter", "sans-serif"]
                },
                borderRadius: {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
                },
            },
        },
    }
</script>
<?php echo get_theme_css(); ?>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-[#100f1a] dark:text-white transition-colors duration-200 min-h-screen flex flex-col">

<!-- Top Navigation -->
<?php include_once __DIR__ . '/../includes/header.php'; ?>

<main class="flex-1 w-full bg-background-light dark:bg-background-dark py-8 px-4 sm:px-6 md:px-8">
    <div class="max-w-[1200px] mx-auto">
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 mb-6">
            <a class="text-[#5c5492] dark:text-gray-400 text-sm font-medium hover:underline" href="/dashboard">Portal</a>
            <span class="material-symbols-outlined text-[16px] text-[#5c5492]">chevron_right</span>
            <a class="text-[#5c5492] dark:text-gray-400 text-sm font-medium hover:underline" href="/dashboard">My Applications</a>
            <span class="material-symbols-outlined text-[16px] text-[#5c5492]">chevron_right</span>
            <span class="text-[#100f1a] dark:text-white text-sm font-bold">Interviews</span>
        </nav>

        <!-- Page Heading -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-8">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider mb-3">
                    <span class="size-2 rounded-full bg-primary animate-pulse"></span>
                    Manage Interviews
                </div>
                <h1 class="text-[#100f1a] dark:text-white text-4xl font-black leading-tight tracking-tight">Your Interviews</h1>
                <p class="text-[#5c5492] dark:text-gray-400 text-lg mt-2">Manage your upcoming schedules and details.</p>
            </div>

        </div>
        
        <?php if (empty($interviews)): ?>
            <div class="flex flex-col items-center justify-center py-20 text-center rounded-xl bg-white dark:bg-[#1c1a2e] shadow-sm border border-slate-200 dark:border-slate-800">
                <div class="rounded-full bg-blue-50 dark:bg-blue-900/20 p-6 mb-4">
                    <span class="material-symbols-outlined text-center text-4xl text-primary">calendar_clock</span>
                </div>
                <h3 class="text-lg font-semibold text-text-main-light dark:text-white">No Interviews Scheduled</h3>
                <p class="mt-2 text-sm text-text-sub-light dark:text-text-sub-dark max-w-xs mx-auto">
                    You have no interviews scheduled at the moment. We will notify you when an interview is set up.
                </p>
                <div class="mt-6">
                    <a href="/jobs" class="inline-flex items-center rounded-md bg-primary px-3 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90">
                        Browse Jobs
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($interviews as $interview): 
                 $isUpcoming = strtotime($interview['interview_date'] . ' ' . $interview['interview_time']) > time();
            ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12 border-b border-gray-200 dark:border-gray-800 pb-12 last:border-0 last:pb-0">
                <!-- Main Card -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-[#1c1a2e] rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.05)] overflow-hidden">
                        <!-- Hero Image/Banner -->
                        <div class="h-48 w-full bg-primary/10 relative overflow-hidden">
                            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 2px 2px, currentColor 1px, transparent 0); background-size: 24px 24px; color: var(--color-primary);"></div>
                            <div class="absolute bottom-6 left-8 flex items-center gap-4">
                                <div class="size-16 bg-white dark:bg-gray-800 rounded-xl flex items-center justify-center shadow-lg border border-white/20">
                                    <span class="material-symbols-outlined text-primary text-3xl">work</span>
                                </div>
                                <div class="text-white drop-shadow-md">
                                    <p class="bg-white text-primary px-2 py-0.5 rounded w-fit mb-1 text-xs font-bold uppercase tracking-widest">Position</p>
                                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($interview['job_title']); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="p-8">
                            <h3 class="text-xl font-bold text-[#100f1a] dark:text-white mb-6">Interview Schedule</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div class="flex items-start gap-4 p-4 rounded-xl bg-background-light dark:bg-gray-800/50 border border-[#eae8f2] dark:border-white/5">
                                    <div class="size-10 rounded-lg bg-primary/20 flex items-center justify-center text-primary">
                                        <span class="material-symbols-outlined">calendar_today</span>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-[#5c5492] dark:text-gray-400 uppercase tracking-wider mb-1">Date</p>
                                        <p class="text-[#100f1a] dark:text-white font-semibold"><?php echo date('l, M jS, Y', strtotime($interview['interview_date'])); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4 p-4 rounded-xl bg-background-light dark:bg-gray-800/50 border border-[#eae8f2] dark:border-white/5">
                                    <div class="size-10 rounded-lg bg-primary/20 flex items-center justify-center text-primary">
                                        <span class="material-symbols-outlined">schedule</span>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-[#5c5492] dark:text-gray-400 uppercase tracking-wider mb-1">Time</p>
                                        <p class="text-[#100f1a] dark:text-white font-semibold"><?php echo date('g:i A', strtotime($interview['interview_time'])); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4 p-4 rounded-xl bg-background-light dark:bg-gray-800/50 border border-[#eae8f2] dark:border-white/5">
                                    <div class="size-10 rounded-lg bg-primary/20 flex items-center justify-center text-primary">
                                        <span class="material-symbols-outlined">location_on</span>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-[#5c5492] dark:text-gray-400 uppercase tracking-wider mb-1">Venue</p>
                                        <p class="text-[#100f1a] dark:text-white font-semibold text-sm truncate max-w-[150px]" title="<?php echo htmlspecialchars($interview['venue_name']); ?>"><?php echo htmlspecialchars($interview['venue_name']); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4 p-4 rounded-xl bg-background-light dark:bg-gray-800/50 border border-[#eae8f2] dark:border-white/5">
                                    <div class="size-10 rounded-lg bg-primary/20 flex items-center justify-center text-primary">
                                        <span class="material-symbols-outlined">category</span>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-[#5c5492] dark:text-gray-400 uppercase tracking-wider mb-1">Department</p>
                                        <p class="text-[#100f1a] dark:text-white font-semibold"><?php echo htmlspecialchars($interview['department_name'] ?? 'General'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900/50 rounded-xl p-6 mb-8">
                                <div class="flex items-center gap-3 mb-2 text-blue-700 dark:text-blue-400">
                                    <span class="material-symbols-outlined">info</span>
                                    <h4 class="font-bold">Preparation Instructions</h4>
                                </div>
                                <p class="text-blue-900/80 dark:text-blue-200/80 text-sm leading-relaxed">
                                    Please arrive 15 minutes early. Bring a valid ID and a copy of your resume. Good luck!
                                </p>
                            </div>
                            <!-- Actions -->
                            <div class="flex flex-col sm:flex-row gap-4 pt-4 border-t border-[#eae8f2] dark:border-white/10">
                                <a href="../application/<?php echo $interview['application_id']; ?>" class="flex-1 bg-primary text-white py-4 px-6 rounded-xl font-bold text-lg hover:shadow-lg hover:opacity-90 transition-all flex items-center justify-center gap-2">
                                    <span class="material-symbols-outlined">visibility</span>
                                    View Application
                                </a>
                                <?php if ($isUpcoming): ?>
                                    <button class="flex-1 bg-white dark:bg-gray-800 border-2 border-[#eae8f2] dark:border-white/10 text-[#100f1a] dark:text-white py-4 px-6 rounded-xl font-bold text-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all flex items-center justify-center gap-2">
                                        <span class="material-symbols-outlined">calendar_month</span>
                                        Add to Calendar
                                    </button>
                                <?php else: ?>
                                    <div class="flex-1 bg-gray-100 dark:bg-gray-800 text-gray-400 py-4 px-6 rounded-xl font-bold text-lg flex items-center justify-center gap-2 cursor-not-allowed">
                                        <span class="material-symbols-outlined">history</span>
                                        Past Event
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar Panels -->
                <div class="space-y-6">
                    <!-- Location/Meeting Link Card -->
                    <div class="bg-white dark:bg-[#1c1a2e] rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.05)] p-6 border-l-4 border-primary">
                        <h3 class="text-lg font-bold text-[#100f1a] dark:text-white mb-4">Venue Access</h3>
                        <div class="bg-background-light dark:bg-gray-800 rounded-lg p-4 mb-4">
                            <div class="flex items-center gap-2 text-primary font-bold text-sm mb-1">
                                <span class="material-symbols-outlined text-[18px]">location_on</span>
                                Address
                            </div>
                            <p class="text-xs text-[#5c5492] dark:text-gray-400 italic"><?php echo htmlspecialchars($interview['venue_address']); ?></p>
                        </div>
                        <?php if(!empty($interview['venue_link'])): ?>
                            <a href="<?php echo htmlspecialchars($interview['venue_link']); ?>" target="_blank" class="w-full py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-primary rounded-lg font-bold flex items-center justify-center gap-2 transition-colors">
                                <span class="material-symbols-outlined">map</span>
                                View Map
                            </a>
                        <?php else: ?>
                            <button class="w-full py-3 bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 rounded-lg font-bold flex items-center justify-center gap-2 cursor-not-allowed" disabled>
                                <span class="material-symbols-outlined">map</span>
                                Map Unavailable
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Help Card -->
                    <div class="bg-primary/5 dark:bg-primary/10 rounded-xl p-6 border border-primary/10">
                        <p class="text-sm text-[#100f1a] dark:text-white mb-4">Need to reschedule or have questions?</p>
                        <a class="text-primary font-bold text-sm flex items-center gap-2 hover:underline" href="#">
                            Contact HR Team
                            <span class="material-symbols-outlined text-[16px]">mail</span>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>
