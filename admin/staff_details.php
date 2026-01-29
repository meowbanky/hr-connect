<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || 
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff' && $_SESSION['user_role'] !== 'superadmin')) {
    header('Location: /admin/login');
    exit;
}

$staffId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($staffId === 0) {
    header("Location: /admin/employees.php");
    exit;
}

// Fetch Staff Details
$stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->execute([$staffId]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$staff) {
    die("Staff member not found.");
}

// Fetch Education
$eduStmt = $pdo->prepare("SELECT * FROM staff_education WHERE staff_id = ? ORDER BY start_date DESC");
$eduStmt->execute([$staffId]);
$education = $eduStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Documents
$docStmt = $pdo->prepare("SELECT * FROM staff_documents WHERE staff_id = ? ORDER BY uploaded_at DESC");
$docStmt->execute([$staffId]);
$documents = $docStmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/settings.php';
$pageTitle = "Staff Profile: " . htmlspecialchars($staff['full_name']);
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo ($_SESSION['theme_mode'] ?? '') === 'dark' ? 'dark' : 'light'; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $pageTitle; ?></title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
<link href="/assets/css/style.css" rel="stylesheet">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "primary": "var(--color-primary, #3b2bee)",
            "background-light": "#f6f6f8",
            "background-dark": "#121022",
            "surface-dark": "#1e1b3a",
             "surface-light": "#ffffff",
             "border-light": "#e9e8f3",
             "border-dark": "#2d2b45",
          },
          fontFamily: {
            "display": ["Inter", "sans-serif"]
          }
        },
      },
      plugins: [
        function({ addBase, theme }) {
            addBase({
                ':root': {
                    '--color-primary': '<?php echo get_theme_color(); ?>',
                },
            });
        }
      ]
    }
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body { font-family: Inter, sans-serif; }
.material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
.timeline-line::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 15px;
    width: 2px;
    background-color: #e9e8f3;
    z-index: 0;
}
.dark .timeline-line::before {
    background-color: #2d2b45;
}
</style>

<?php echo get_theme_css(); ?>
</head>

<body class="font-display bg-background-light dark:bg-background-dark text-gray-900 dark:text-gray-100 antialiased overflow-hidden flex h-screen w-full flex-row">

<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

<!-- Main Content Area -->
<main class="flex flex-1 flex-col h-full overflow-hidden bg-background-light dark:bg-background-dark relative">

    <!-- Header -->
    <?php include __DIR__ . '/../includes/admin_header.php'; ?>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto p-4 md:p-8">
        <div class="mx-auto max-w-5xl flex flex-col gap-6">
            
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 px-1">
                <a class="hover:text-primary transition-colors" href="/admin">Dashboard</a>
                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                <a class="hover:text-primary transition-colors" href="/admin/employees.php">Staff Directory</a>
                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                <span class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($staff['full_name']); ?></span>
            </div>

            <!-- Profile Header -->
            <div class="flex flex-col bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm border border-border-light dark:border-border-dark overflow-hidden">
                <!-- Cover Banner -->
                <div class="h-32 bg-gradient-to-r from-indigo-500 to-purple-600 w-full" data-alt="Abstract gradient banner"></div>
                <div class="flex flex-col md:flex-row p-6 gap-6 -mt-12 items-start relative">
                    <!-- Avatar -->
                    <div class="relative">
                        <?php if($staff['profile_image']): ?>
                             <div class="bg-center bg-no-repeat bg-cover rounded-full size-32 border-4 border-white dark:border-surface-dark shadow-md" style='background-image: url("<?php echo htmlspecialchars($staff['profile_image']); ?>");'></div>
                        <?php else: ?>
                            <div class="size-32 rounded-full border-4 border-white dark:border-surface-dark shadow-md bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 font-bold text-4xl">
                                <?php echo strtoupper(substr($staff['full_name'], 0, 1) . (strpos($staff['full_name'], ' ') ? substr($staff['full_name'], strpos($staff['full_name'], ' ') + 1, 1) : '')); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="absolute bottom-2 right-2 <?php echo $staff['status'] === 'active' ? 'bg-green-500' : 'bg-red-500'; ?> size-4 rounded-full border-2 border-white dark:border-surface-dark" title="<?php echo ucfirst($staff['status']); ?>"></div>
                    </div>
                    
                    <!-- Info -->
                    <div class="flex flex-col flex-1 pt-12 md:pt-14 gap-1">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <h1 class="text-gray-900 dark:text-white text-2xl font-bold leading-tight"><?php echo htmlspecialchars($staff['full_name']); ?></h1>
                                <p class="text-gray-500 dark:text-gray-400 text-base font-normal">
                                    <?php echo htmlspecialchars($staff['job_title']); ?> &bull; <?php echo htmlspecialchars($staff['department']); ?>
                                </p>
                            </div>
                            <!-- Actions -->
                            <div class="flex gap-3">
                                <a href="/admin/edit_employee.php?id=<?php echo $staffId; ?>" class="flex items-center justify-center gap-2 rounded-lg h-9 px-4 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark hover:bg-border-light dark:hover:bg-border-dark transition-colors text-gray-900 dark:text-white text-sm font-bold">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                    <span>Edit</span>
                                </a>
                                <button class="flex items-center justify-center gap-2 rounded-lg h-9 px-4 bg-primary hover:bg-primary/90 transition-colors text-white text-sm font-bold shadow-md shadow-primary/30">
                                    <span class="material-symbols-outlined text-[18px]">download</span>
                                    <span>Export</span>
                                </button>
                            </div>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-x-6 gap-y-2 text-sm text-gray-500 dark:text-gray-400">
                            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px]">badge</span> ID: #<?php echo str_pad($staff['id'], 4, '0', STR_PAD_LEFT); ?></span>
                            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px]">location_on</span> <?php echo htmlspecialchars($staff['location'] ?? 'Not Assigned'); ?></span>
                            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px]">schedule</span> <?php echo htmlspecialchars($staff['employment_type']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs Navigation -->
                <div class="px-6 mt-2">
                    <div class="flex border-b border-border-light dark:border-border-dark gap-8 overflow-x-auto no-scrollbar" role="tablist">
                         <button onclick="switchTab('overview')" id="tab-btn-overview" class="tab-btn flex items-center gap-2 border-b-[3px] border-primary text-primary pb-3 pt-2 whitespace-nowrap transition-colors">
                            <span class="material-symbols-outlined text-[20px]">dashboard</span>
                            <span class="text-sm font-bold">Overview</span>
                        </button>
                        <button onclick="switchTab('documents')" id="tab-btn-documents" class="tab-btn flex items-center gap-2 border-b-[3px] border-transparent text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors pb-3 pt-2 whitespace-nowrap">
                            <span class="material-symbols-outlined text-[20px]">folder</span>
                            <span class="text-sm font-medium">Documents</span>
                        </button>
                        <button onclick="switchTab('history')" id="tab-btn-history" class="tab-btn flex items-center gap-2 border-b-[3px] border-transparent text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors pb-3 pt-2 whitespace-nowrap">
                            <span class="material-symbols-outlined text-[20px]">history_edu</span>
                            <span class="text-sm font-medium">Education & History</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="flex flex-col gap-1 rounded-xl p-5 bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark shadow-sm">
                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                        <span class="material-symbols-outlined text-[20px]">hourglass_top</span>
                        <p class="text-sm font-medium">Tenure</p>
                    </div>
                    <?php 
                        $joined = new DateTime($staff['start_date'] ?? 'now');
                        $now = new DateTime();
                        $interval = $joined->diff($now);
                        $tenure = $interval->y > 0 ? $interval->y . ' Years' : ($interval->m > 0 ? $interval->m . ' Months' : $interval->d . ' Days');
                    ?>
                    <p class="text-gray-900 dark:text-white text-2xl font-bold leading-tight"><?php echo $tenure; ?></p>
                     <p class="text-xs text-green-600 font-medium">Since <?php echo $joined->format('M Y'); ?></p>
                </div>
                <!-- Placeholders for other stats as they are not in DB yet -->
                <div class="flex flex-col gap-1 rounded-xl p-5 bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark shadow-sm">
                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                        <span class="material-symbols-outlined text-[20px]">beach_access</span>
                        <p class="text-sm font-medium">Leave Balance</p>
                    </div>
                    <p class="text-gray-900 dark:text-white text-2xl font-bold leading-tight">--</p>
                    <p class="text-xs text-gray-500 dark:text-gray-500">Not configured</p>
                </div>
                 <div class="flex flex-col gap-1 rounded-xl p-5 bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark shadow-sm">
                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                        <span class="material-symbols-outlined text-[20px]">payments</span>
                        <p class="text-sm font-medium">Grade Level</p>
                    </div>
                    <p class="text-gray-900 dark:text-white text-2xl font-bold leading-tight"><?php echo htmlspecialchars($staff['grade_level'] ?? '-'); ?></p>
                    <p class="text-xs text-primary font-medium">Step: <?php echo htmlspecialchars($staff['step'] ?? '-'); ?></p>
                </div>
                <div class="flex flex-col gap-1 rounded-xl p-5 bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark shadow-sm">
                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                        <span class="material-symbols-outlined text-[20px]">calendar_month</span>
                        <p class="text-sm font-medium">Next Review</p>
                    </div>
                    <p class="text-gray-900 dark:text-white text-2xl font-bold leading-tight">--</p>
                    <p class="text-xs text-primary font-medium">Due in --</p>
                </div>
            </div>

            <!-- Content Grid -->
            <!-- Tab: Overview -->
            <div id="tab-overview" class="grid grid-cols-1 lg:grid-cols-3 gap-6 tab-content">
                <!-- Left Column -->
                <div class="lg:col-span-2 flex flex-col gap-6">
                    <!-- Personal Info Card -->
                    <div class="bg-surface-light dark:bg-surface-dark rounded-xl border border-border-light dark:border-border-dark shadow-sm">
                        <div class="flex justify-between items-center px-6 py-4 border-b border-border-light dark:border-border-dark">
                            <h3 class="text-gray-900 dark:text-white font-bold text-base">Contact Information</h3>
                            <button class="text-primary hover:bg-primary/10 rounded p-1 transition-colors">
                                <span class="material-symbols-outlined text-[20px]">edit_square</span>
                            </button>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8">
                             <div class="flex flex-col gap-1">
                                <span class="text-xs font-semibold text-text-secondary uppercase tracking-wider text-gray-500">Email Address</span>
                                <div class="flex items-center gap-2 text-gray-900 dark:text-white font-medium">
                                    <span class="material-symbols-outlined text-[18px] text-gray-400">mail</span>
                                    <a href="mailto:<?php echo htmlspecialchars($staff['email']); ?>" class="hover:underline"><?php echo htmlspecialchars($staff['email']); ?></a>
                                </div>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-semibold text-text-secondary uppercase tracking-wider text-gray-500">Phone Number</span>
                                <div class="flex items-center gap-2 text-gray-900 dark:text-white font-medium">
                                    <span class="material-symbols-outlined text-[18px] text-gray-400">call</span>
                                    <?php echo htmlspecialchars($staff['phone']); ?>
                                </div>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-semibold text-text-secondary uppercase tracking-wider text-gray-500">Office Location</span>
                                <div class="flex items-center gap-2 text-gray-900 dark:text-white font-medium">
                                    <span class="material-symbols-outlined text-[18px] text-gray-400">apartment</span>
                                     <?php echo htmlspecialchars($staff['location'] ?? 'Not Assigned'); ?>
                                </div>
                            </div>
                             <div class="flex flex-col gap-1">
                                <span class="text-xs font-semibold text-text-secondary uppercase tracking-wider text-gray-500">Emergency Contact</span>
                                <div class="flex items-center gap-2 text-gray-900 dark:text-white font-medium">
                                    <span class="material-symbols-outlined text-[18px] text-gray-400">contact_emergency</span>
                                    <div>
                                        <?php echo htmlspecialchars($staff['emergency_contact_name'] ?? ''); ?>
                                        <span class="text-xs text-gray-500 block"><?php echo htmlspecialchars($staff['emergency_contact_relationship'] ?? ''); ?> &bull; <?php echo htmlspecialchars($staff['emergency_contact_phone'] ?? ''); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Position Details -->
                    <div class="bg-surface-light dark:bg-surface-dark rounded-xl border border-border-light dark:border-border-dark shadow-sm">
                         <div class="flex justify-between items-center px-6 py-4 border-b border-border-light dark:border-border-dark">
                            <h3 class="text-gray-900 dark:text-white font-bold text-base">Position Details</h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8">
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Department</span>
                                    <span class="text-gray-900 dark:text-white font-medium text-lg"><?php echo htmlspecialchars($staff['department']); ?></span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Worker Category</span>
                                    <span class="text-gray-900 dark:text-white font-medium text-lg"><?php echo htmlspecialchars($staff['worker_category']); ?></span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Employment Status</span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 w-fit">
                                        Active <?php echo htmlspecialchars($staff['employment_type']); ?>
                                    </span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Date Joined</span>
                                    <span class="text-gray-900 dark:text-white font-medium"><?php echo $staff['start_date'] ? date('F j, Y', strtotime($staff['start_date'])) : 'N/A'; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="flex flex-col gap-6">
                     <!-- Compensation Widget -->
                    <div class="bg-gradient-to-br from-[#3b2bee] to-[#6b5ff5] rounded-xl border border-primary shadow-md overflow-hidden text-white relative group">
                        <div class="absolute top-0 right-0 p-4 opacity-10">
                            <span class="material-symbols-outlined text-[100px]">payments</span>
                        </div>
                        <div class="p-6 relative z-10">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="font-bold text-base opacity-90">Salary Info</h3>
                                <span class="material-symbols-outlined text-[20px] opacity-70">lock</span>
                            </div>
                            <div class="mb-6">
                                <p class="text-xs font-medium opacity-70 uppercase tracking-wider mb-1">Base Salary</p>
                                <div class="flex items-center gap-3">
                                    <span class="text-3xl font-bold tracking-tight filter blur-sm select-none group-hover:blur-0 transition-all duration-300 cursor-pointer">
                                        <?php echo htmlspecialchars($staff['salary'] ?? '---'); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 border-t border-white/20 pt-4">
                                <div>
                                    <p class="text-xs font-medium opacity-70">Grade</p>
                                    <p class="font-semibold text-lg"><?php echo htmlspecialchars($staff['grade_level'] ?? '?'); ?></p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium opacity-70">Step</p>
                                    <p class="font-semibold text-lg"><?php echo htmlspecialchars($staff['step'] ?? '?'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Documents -->
            <div id="tab-documents" class="hidden grid grid-cols-1 lg:grid-cols-2 gap-6 tab-content">
                <?php if(empty($documents)): ?>
                    <div class="col-span-2 p-12 text-center bg-surface-light dark:bg-surface-dark rounded-xl border border-border-light dark:border-border-dark">
                        <p class="text-gray-500">No documents found for this staff member.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($documents as $doc): ?>
                    <div class="bg-surface-light dark:bg-surface-dark rounded-xl p-4 shadow-sm border border-border-light dark:border-border-dark flex items-center justify-between">
                         <div class="flex items-center gap-3">
                            <div class="size-10 rounded-lg bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400 flex items-center justify-center">
                                <span class="material-symbols-outlined">description</span>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white text-sm"><?php echo htmlspecialchars($doc['document_name']); ?></h4>
                                <p class="text-xs text-gray-500">Uploaded <?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></p>
                            </div>
                        </div>
                        <a href="/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="p-2 text-gray-400 hover:text-primary transition-colors">
                            <span class="material-symbols-outlined">download</span>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Tab: History/Education -->
            <div id="tab-history" class="hidden grid grid-cols-1 lg:grid-cols-2 gap-6 tab-content">
             <div class="bg-surface-light dark:bg-surface-dark rounded-xl border border-border-light dark:border-border-dark shadow-sm flex-1">
                <div class="flex justify-between items-center px-6 py-4 border-b border-border-light dark:border-border-dark">
                    <h3 class="text-gray-900 dark:text-white font-bold text-base">Education History</h3>
                </div>
                <div class="p-6 relative timeline-line">
                    <?php if(empty($education)): ?>
                        <p class="pl-8 text-gray-500">No education history recorded.</p>
                    <?php else: ?>
                        <?php foreach($education as $edu): ?>
                         <div class="relative pl-8 pb-8 last:pb-0">
                            <div class="absolute left-0 top-1 size-8 bg-surface-light dark:bg-surface-dark flex items-center justify-center z-10">
                                <div class="size-3 rounded-full bg-primary border-4 border-white dark:border-surface-dark"></div>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-xs text-text-secondary font-medium text-gray-500">
                                    <?php echo $edu['start_date'] ? date('Y', strtotime($edu['start_date'])) : '?'; ?> - 
                                    <?php echo $edu['end_date'] ? date('Y', strtotime($edu['end_date'])) : 'Present'; ?>
                                </span>
                                <p class="text-sm font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($edu['school_name']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($edu['qualification']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            </div>

        </div>
    </div>
</main>

<script>
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    // Show selected
    document.getElementById('tab-' + tabName).classList.remove('hidden');
    
    // Reset buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('border-primary', 'text-primary');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Activate button
    const activeBtn = document.getElementById('tab-btn-' + tabName);
    activeBtn.classList.remove('border-transparent', 'text-gray-500');
    activeBtn.classList.add('border-primary', 'text-primary');
}
</script>

</body>
</html>
