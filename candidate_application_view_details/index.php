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
$stmt = $pdo->prepare("SELECT id FROM candidates WHERE user_id = ?");
$stmt->execute([$user_id]);
$candidate = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$candidate) {
    // Should not happen if flow is correct, but handle just in case
    header("Location: ../candidate_registration/index.php"); 
    exit;
}
$candidate_id = $candidate['id'];

// Get Application ID
$application_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($application_id <= 0) {
    header("Location: ../candidate_application_myapplication/index.php");
    exit;
}

// Fetch Application Details
$query = "
    SELECT 
        a.id AS app_id, 
        a.status, 
        a.cover_letter,
        a.application_date,
        a.updated_at,
        j.title AS job_title,
        j.location,
        j.employment_type,
        j.salary_range,
        j.id AS job_id,
        d.name AS department_name,
        c.resume_path,
        c.portfolio_url,
        i.interview_date,
        i.interview_time,
        i.id as interview_id,
        i.venue_name,
        i.venue_address,
        i.venue_link,
        i.status as interview_status
    FROM applications a
    JOIN job_postings j ON a.job_id = j.id
    LEFT JOIN departments d ON j.department_id = d.id
    JOIN candidates c ON a.candidate_id = c.id
    LEFT JOIN interviews i ON a.id = i.application_id
    WHERE a.id = ? AND a.candidate_id = ?
";
$stmt = $pdo->prepare($query);
$stmt->execute([$application_id, $candidate_id]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    // Application not found or doesn't belong to this candidate
    header("Location: ../candidate_application_myapplication/index.php");
    exit;
}

// Fetch Candidate Documents
$stmt = $pdo->prepare("SELECT * FROM candidate_documents WHERE candidate_id = ?");
$stmt->execute([$candidate_id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper to fix path
function getDownloadUrl($path) {
    if (empty($path)) return '#';
    if (filter_var($path, FILTER_VALIDATE_URL)) return $path; // It's a full URL
    // If it's a relative path starting with uploads/, prepend ../
    if (strpos($path, 'uploads/') === 0) {
        return '../' . $path;
    }
    return $path;
}

function getStepStatus($currentStatus, $stepName) {
    // Pipeline: Applied -> Viewed (reviewed) -> Technical (technical_review) -> Interview (interviewed) -> Decision (offered/hired/rejected)
    
    $c = $currentStatus;
    
    // 1. Applied is always completed if record exists
    if ($stepName == 'applied') return 'completed';
    
    // 2. Viewed
    if ($stepName == 'viewed') {
        if (in_array($c, ['reviewed', 'technical_review', 'shortlisted', 'interviewed', 'offered', 'hired', 'rejected'])) return 'completed';
        return 'pending'; 
    }
    
    // 3. Technical
    if ($stepName == 'technical') {
        if (in_array($c, ['technical_review', 'shortlisted', 'interviewed', 'offered', 'hired', 'rejected'])) return 'completed'; // Assumes if illegal state jump, we consider it done
        // If just 'reviewed', it hasn't reached technical yet unless status is specifically technical_review
        return 'pending';
    }

    // 4. Interview
    if ($stepName == 'interview') {
        if (in_array($c, ['interviewed', 'offered', 'hired'])) return 'completed';
        if ($c == 'shortlisted') return 'active'; // Shortlisted means ready for interview
        return 'pending';
    }
    
    // 5. Decision
    if ($stepName == 'decision') {
        if (in_array($c, ['offered', 'hired', 'rejected'])) return 'completed';
        return 'pending';
    }
    
    return 'pending';
}

function getStepClass($status) {
    if ($status == 'completed') {
        return 'bg-primary text-white ring-4 ring-white dark:ring-surface-dark';
    } elseif ($status == 'active') {
        return 'bg-white border-2 border-primary text-primary ring-4 ring-white dark:ring-surface-dark dark:bg-surface-dark';
    } else { // pending
        return 'bg-border-light text-text-sub-light ring-4 ring-white dark:bg-border-dark dark:text-text-sub-dark dark:ring-surface-dark';
    }
}

function getStepIcon($status, $number) {
    if ($status == 'completed') {
        return '<span class="material-symbols-outlined text-sm">check</span>';
    } elseif ($status == 'active') {
        return '<span class="text-xs font-bold">'.$number.'</span>';
    } else {
        return '<span class="text-xs font-bold">'.$number.'</span>';
    }
}

// Progress Bar Width
$progressWidth = '0%';
switch($application['status']) {
    case 'pending': $progressWidth = '5%'; break;
    case 'reviewed': $progressWidth = '25%'; break;
    case 'technical_review': $progressWidth = '50%'; break;
    case 'shortlisted': $progressWidth = '75%'; break;
    case 'interviewed': $progressWidth = '85%'; break;
    case 'offered': 
    case 'hired': 
    case 'rejected': $progressWidth = '100%'; break;
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Application Details - <?php echo htmlspecialchars($application['job_title']); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary": "#1313ec",
                    "primary-hover": "#0f0fb8",
                    "background-light": "#f6f6f8",
                    "background-dark": "#101022",
                    "surface-light": "#ffffff",
                    "surface-dark": "#1a1a2e",
                    "border-light": "#e5e7eb",
                    "border-dark": "#2d2d42",
                    "text-main-light": "#0d0d1b",
                    "text-main-dark": "#ffffff",
                    "text-sub-light": "#4b5563",
                    "text-sub-dark": "#9ca3af",
                },
                fontFamily: {
                    "display": ["Inter", "sans-serif"]
                },
                borderRadius: { "DEFAULT": "0.375rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
            },
        },
    }
</script>
<?php echo get_theme_css(); ?>
</head>
<body class="bg-background-light dark:bg-background-dark text-text-main-light dark:text-text-main-dark font-display antialiased transition-colors duration-200">
<div class="flex min-h-screen flex-col">

<!-- Top Navigation -->
<?php include_once __DIR__ . '/../includes/header.php'; ?>

<main class="flex-grow">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <!-- Breadcrumbs -->
        <nav aria-label="Breadcrumb" class="mb-6 flex">
            <ol class="flex items-center space-x-2">
                <li><a class="text-text-sub-light hover:text-primary dark:text-text-sub-dark dark:hover:text-white text-sm font-medium" href="../job_board_&_candidate_portal/index.php">Jobs</a></li>
                <li><span class="text-text-sub-light dark:text-text-sub-dark text-sm">/</span></li>
                <li><a class="text-text-sub-light hover:text-primary dark:text-text-sub-dark dark:hover:text-white text-sm font-medium" href="../candidate_application_myapplication/index.php">My Applications</a></li>
                <li><span class="text-text-sub-light dark:text-text-sub-dark text-sm">/</span></li>
                <li><span class="text-text-main-light dark:text-white text-sm font-semibold">Details</span></li>
            </ol>
        </nav>
        
        <!-- Page Header -->
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-3xl font-bold tracking-tight text-text-main-light dark:text-white sm:text-4xl"><?php echo htmlspecialchars($application['job_title']); ?></h2>
                    <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/30 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:text-blue-300">
                        ID: #<?php echo $application['app_id']; ?>
                    </span>
                </div>
                <p class="mt-2 text-text-sub-light dark:text-text-sub-dark">
                    Submitted on <?php echo date('F d, Y', strtotime($application['application_date'])); ?> • <?php echo htmlspecialchars($application['location'] ?? 'Remote'); ?>

                </p>
            </div>
            <!-- Withdraw Button (Visual Only for now) -->
            <!-- 
            <button class="inline-flex items-center justify-center rounded-lg border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-background-dark">
                Withdraw Application
            </button> 
            -->
        </div>

        <!-- Main Layout Grid -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- Left Column: Status & Details (2/3 width) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Status Stepper Card -->
                <div class="overflow-hidden rounded-xl border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark shadow-sm">
                    <div class="border-b border-border-light dark:border-border-dark px-6 py-4">
                        <h3 class="text-lg font-semibold text-text-main-light dark:text-white">Application Progress</h3>
                    </div>
                    <div class="px-6 py-8">
                        <!-- Stepper Visual -->
                        <div class="relative">
                            <!-- Line Background -->
                            <div class="absolute left-0 top-1/2 h-0.5 w-full -translate-y-1/2 bg-border-light dark:bg-border-dark"></div>
                            <!-- Active Progress Line -->
                            <div class="absolute left-0 top-1/2 h-0.5 -translate-y-1/2 bg-primary transition-all duration-500" style="width: <?php echo $progressWidth; ?>"></div>
                            
                            <div class="relative flex justify-between">
                                <!-- Step 1: Applied -->
                                <div class="group flex flex-col items-center gap-2 z-10">
                                    <div class="flex size-8 items-center justify-center rounded-full <?php echo getStepClass('completed'); ?>">
                                        <span class="material-symbols-outlined text-sm">check</span>
                                    </div>
                                    <span class="text-xs font-medium text-primary">Applied</span>
                                </div>
                                
                                <!-- Step 2: Viewed (Reviewed) -->
                                <?php $s2 = getStepStatus($application['status'], 'viewed'); ?>
                                <div class="group flex flex-col items-center gap-2 z-10">
                                    <div class="flex size-8 items-center justify-center rounded-full <?php echo getStepClass($s2); ?>">
                                        <?php echo getStepIcon($s2, 2); ?>
                                    </div>
                                    <span class="text-xs font-medium <?php echo $s2 == 'pending' ? 'text-text-sub-light dark:text-text-sub-dark' : 'text-primary'; ?>">Viewed</span>
                                </div>
                                
                                <!-- Step 3: Technical Review -->
                                <?php $s3 = getStepStatus($application['status'], 'technical'); ?>
                                <div class="group flex flex-col items-center gap-2 z-10">
                                    <div class="flex size-8 items-center justify-center rounded-full <?php echo getStepClass($s3); ?>">
                                        <?php echo getStepIcon($s3, 3); ?>
                                    </div>
                                    <span class="text-xs font-medium <?php echo $s3 == 'pending' ? 'text-text-sub-light dark:text-text-sub-dark' : 'text-primary'; ?>">Technical</span>
                                </div>
                                
                                <!-- Step 4: Interview -->
                                <?php $s4 = getStepStatus($application['status'], 'interview'); ?>
                                <div class="group flex flex-col items-center gap-2 z-10">
                                    <div class="flex size-8 items-center justify-center rounded-full <?php echo getStepClass($s4); ?>">
                                        <?php echo getStepIcon($s4, 4); ?>
                                    </div>
                                    <span class="text-xs font-medium <?php echo $s4 == 'pending' ? 'text-text-sub-light dark:text-text-sub-dark' : 'text-primary'; ?>">Interview</span>
                                </div>
                                
                                <!-- Step 5: Decision -->
                                <?php $s5 = getStepStatus($application['status'], 'decision'); ?>
                                <div class="group flex flex-col items-center gap-2 z-10">
                                    <div class="flex size-8 items-center justify-center rounded-full <?php echo getStepClass($s5); ?>">
                                        <?php echo getStepIcon($s5, 5); ?>
                                    </div>
                                    <span class="text-xs font-medium <?php echo $s5 == 'pending' ? 'text-text-sub-light dark:text-text-sub-dark' : 'text-primary'; ?>">Decision</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Status Info Box -->
                        <div class="mt-8 rounded-lg bg-blue-50 dark:bg-blue-900/20 p-4 border border-blue-100 dark:border-blue-900/30 flex items-start gap-4">
                            <span class="material-symbols-outlined text-primary mt-1">info</span>
                            <div>
                                <h4 class="text-sm font-bold text-text-main-light dark:text-white">Current Status: <?php echo ucfirst($application['status']); ?></h4>
                                <p class="mt-1 text-sm text-text-sub-light dark:text-text-sub-dark">
                                    <?php 
                                    if ($application['status'] == 'pending') echo "Your application has been received and is awaiting review.";
                                    elseif ($application['status'] == 'reviewed') echo "Your application is currently being reviewed by our HR team.";
                                    elseif ($application['status'] == 'interviewed') echo "Great! You have been selected for an interview.";
                                    elseif ($application['status'] == 'rejected') echo "Thank you for your interest. Unfortunately, we will not be moving forward at this time.";
                                    elseif ($application['status'] == 'offered') echo "Congratulations! You have received an offer.";
                                    else echo "Check back soon for updates.";
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Interview Details Card (Conditional) -->
                <?php if (!empty($application['interview_id'])): ?>
                <div class="mt-6 overflow-hidden rounded-xl border border-primary/20 bg-primary/5 dark:bg-primary/10 shadow-sm">
                    <div class="border-b border-primary/10 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-primary dark:text-blue-400 flex items-center gap-2">
                            <span class="material-symbols-outlined">event</span> Interview Scheduled
                        </h3>
                        <?php if($application['interview_status'] == 'completed'): ?>
                            <span class="inline-flex items-center rounded-full bg-green-100 text-green-800 px-2 py-1 text-xs font-semibold">
                                Completed
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full bg-blue-100 text-blue-800 px-2 py-1 text-xs font-semibold">
                                Upcoming
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="p-6">
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-semibold text-text-sub-light dark:text-text-sub-dark uppercase tracking-wider mb-1">Date & Time</p>
                                <p class="text-base font-bold text-text-main-light dark:text-white">
                                    <?php echo date('l, F j, Y', strtotime($application['interview_date'])); ?>
                                </p>
                                <p class="text-sm font-medium text-text-main-light dark:text-white">
                                    <?php echo date('g:i A', strtotime($application['interview_time'])); ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-text-sub-light dark:text-text-sub-dark uppercase tracking-wider mb-1">Venue</p>
                                <p class="text-sm font-bold text-text-main-light dark:text-white">
                                    <?php echo htmlspecialchars($application['venue_name']); ?>
                                </p>
                                <p class="text-sm text-text-sub-light dark:text-text-sub-dark">
                                    <?php echo htmlspecialchars($application['venue_address']); ?>
                                </p>
                                <?php if(!empty($application['venue_link'])): ?>
                                    <a href="<?php echo htmlspecialchars($application['venue_link']); ?>" target="_blank" class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                                        <span class="material-symbols-outlined text-sm">map</span> View Map
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mt-6 rounded-lg bg-white dark:bg-surface-dark p-4 border border-blue-100 dark:border-blue-900/30">
                            <h4 class="text-sm font-bold text-text-main-light dark:text-white mb-2">Instructions</h4>
                            <ul class="list-disc list-inside text-sm text-text-sub-light dark:text-text-sub-dark space-y-1">
                                <li>Please arrive 15 minutes before your scheduled time.</li>
                                <li>Bring a copy of your resume and a valid ID.</li>
                                <li>Dress code is Business Formal.</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Application Details -->
                <div class="rounded-xl border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark">
                    <div class="border-b border-border-light dark:border-border-dark px-6 py-4 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-text-main-light dark:text-white">My Submission</h3>
                    </div>
                    <div class="p-6 grid gap-6 sm:grid-cols-2">
                        <!-- Resume File -->
                        <div class="col-span-1">
                            <label class="text-xs font-semibold text-text-sub-light dark:text-text-sub-dark uppercase tracking-wider">Resume</label>
                            <?php if(!empty($application['resume_path'])): ?>
                            <div class="mt-2 flex items-center gap-3 rounded-lg border border-border-light dark:border-border-dark p-3 bg-background-light dark:bg-background-dark/50">
                                <div class="flex size-10 items-center justify-center rounded bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                                    <span class="material-symbols-outlined">picture_as_pdf</span>
                                </div>
                                <div class="flex-1 overflow-hidden">
                                    <p class="truncate text-sm font-medium text-text-main-light dark:text-white"><?php echo basename($application['resume_path']); ?></p>
                                    <p class="text-xs text-text-sub-light dark:text-text-sub-dark">Resume</p>
                                </div>
                                <a href="<?php echo htmlspecialchars(getDownloadUrl($application['resume_path'])); ?>" target="_blank" class="text-text-sub-light hover:text-primary dark:text-text-sub-dark dark:hover:text-white">
                                    <span class="material-symbols-outlined text-xl">download</span>
                                </a>
                            </div>
                            <?php else: ?>
                            <p class="text-sm text-slate-500 mt-2">No resume attached.</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Portfolio Link -->
                        <div class="col-span-1">
                            <label class="text-xs font-semibold text-text-sub-light dark:text-text-sub-dark uppercase tracking-wider">Portfolio</label>
                            <?php if(!empty($application['portfolio_url'])): ?>
                            <div class="mt-2 flex items-center gap-3 rounded-lg border border-border-light dark:border-border-dark p-3 bg-background-light dark:bg-background-dark/50">
                                <div class="flex size-10 items-center justify-center rounded bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                    <span class="material-symbols-outlined">language</span>
                                </div>
                                <div class="flex-1 overflow-hidden">
                                    <p class="truncate text-sm font-medium text-text-main-light dark:text-white"><?php echo htmlspecialchars($application['portfolio_url']); ?></p>
                                    <p class="text-xs text-text-sub-light dark:text-text-sub-dark">Website</p>
                                </div>
                                <a href="<?php echo htmlspecialchars(getDownloadUrl($application['portfolio_url'])); ?>" target="_blank" class="text-text-sub-light hover:text-primary dark:text-text-sub-dark dark:hover:text-white">
                                    <span class="material-symbols-outlined text-xl">open_in_new</span>
                                </a>
                            </div>
                            <?php else: ?>
                            <p class="text-sm text-slate-500 mt-2">No portfolio linked.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Additional Documents -->
                        <?php if (!empty($documents)): ?>
                        <div class="col-span-full">
                            <label class="text-xs font-semibold text-text-sub-light dark:text-text-sub-dark uppercase tracking-wider">Additional Documents</label>
                            <div class="mt-2 grid gap-4 sm:grid-cols-2">
                                <?php foreach ($documents as $doc): ?>
                                <div class="flex items-center gap-3 rounded-lg border border-border-light dark:border-border-dark p-3 bg-background-light dark:bg-background-dark/50">
                                    <div class="flex size-10 items-center justify-center rounded bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                        <span class="material-symbols-outlined">description</span>
                                    </div>
                                    <div class="flex-1 overflow-hidden">
                                        <p class="truncate text-sm font-medium text-text-main-light dark:text-white"><?php echo htmlspecialchars($doc['document_name'] ?? 'Document'); ?></p>
                                        <p class="text-xs text-text-sub-light dark:text-text-sub-dark">Uploaded File</p>
                                    </div>
                                    <a href="<?php echo htmlspecialchars(getDownloadUrl($doc['file_path'])); ?>" target="_blank" class="text-text-sub-light hover:text-primary dark:text-text-sub-dark dark:hover:text-white">
                                        <span class="material-symbols-outlined text-xl">download</span>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Cover Letter -->
                        <div class="col-span-full">
                            <label class="text-xs font-semibold text-text-sub-light dark:text-text-sub-dark uppercase tracking-wider">Cover Letter / Note</label>
                            <?php 
                            $cover_val = $application['cover_letter'] ?? '';
                            if (strpos($cover_val, 'uploads/') === 0): 
                                // It is a file path
                            ?>
                                <div class="mt-2 flex items-center gap-3 rounded-lg border border-border-light dark:border-border-dark p-3 bg-background-light dark:bg-background-dark/50">
                                    <div class="flex size-10 items-center justify-center rounded bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                                        <span class="material-symbols-outlined">description</span>
                                    </div>
                                    <div class="flex-1 overflow-hidden">
                                        <p class="truncate text-sm font-medium text-text-main-light dark:text-white"><?php echo basename($cover_val); ?></p>
                                        <p class="text-xs text-text-sub-light dark:text-text-sub-dark">Cover Letter Document</p>
                                    </div>
                                    <a href="<?php echo htmlspecialchars(getDownloadUrl($cover_val)); ?>" target="_blank" class="text-text-sub-light hover:text-primary dark:text-text-sub-dark dark:hover:text-white">
                                        <span class="material-symbols-outlined text-xl">download</span>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="mt-2 rounded-lg bg-background-light dark:bg-background-dark/50 p-4 text-sm text-text-sub-light dark:text-text-sub-dark leading-relaxed">
                                    <?php echo nl2br(htmlspecialchars($cover_val ?: 'No cover letter submitted.')); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Timeline & Info (1/3 width) -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Timeline Widget (Simplified Mock for now as we don't track history table yet) -->
                <div class="rounded-xl border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark">
                    <div class="border-b border-border-light dark:border-border-dark px-6 py-4">
                        <h3 class="text-lg font-semibold text-text-main-light dark:text-white">Activity History</h3>
                    </div>
                    <div class="p-6">
                        <div class="relative pl-4 border-l border-border-light dark:border-border-dark space-y-8">
                            <!-- Show Current Status Event -->
                            <div class="relative">
                                <div class="absolute -left-[21px] top-1 h-3 w-3 rounded-full bg-primary ring-4 ring-surface-light dark:ring-surface-dark"></div>
                                <p class="text-sm font-medium text-text-main-light dark:text-white">Status: <?php echo ucfirst($application['status']); ?></p>
                                <p class="text-xs text-text-sub-light dark:text-text-sub-dark mt-1"><?php echo date('M d, Y • h:i A', strtotime($application['updated_at'])); ?></p>
                            </div>
                            
                            <!-- Show Submitted Event -->
                            <div class="relative">
                                <div class="absolute -left-[21px] top-1 h-3 w-3 rounded-full bg-border-light dark:bg-border-dark ring-4 ring-surface-light dark:ring-surface-dark"></div>
                                <p class="text-sm font-medium text-text-main-light dark:text-white">Application Submitted</p>
                                <p class="text-xs text-text-sub-light dark:text-text-sub-dark mt-1"><?php echo date('M d, Y • h:i A', strtotime($application['application_date'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Job Snapshot -->
                <div class="rounded-xl border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark">
                    <div class="border-b border-border-light dark:border-border-dark px-6 py-4">
                        <h3 class="text-lg font-semibold text-text-main-light dark:text-white">Job Snapshot</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="rounded-md bg-background-light dark:bg-background-dark p-2 text-text-sub-light dark:text-text-sub-dark">
                                <span class="material-symbols-outlined">work</span>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-text-sub-light dark:text-text-sub-dark uppercase">Job Type</p>
                                <p class="text-sm font-semibold text-text-main-light dark:text-white"><?php echo htmlspecialchars($application['employment_type'] ?? 'Full-time'); ?></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="rounded-md bg-background-light dark:bg-background-dark p-2 text-text-sub-light dark:text-text-sub-dark">
                                <span class="material-symbols-outlined">location_on</span>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-text-sub-light dark:text-text-sub-dark uppercase">Location</p>
                                <p class="text-sm font-semibold text-text-main-light dark:text-white"><?php echo htmlspecialchars($application['location'] ?? 'Remote'); ?></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="rounded-md bg-background-light dark:bg-background-dark p-2 text-text-sub-light dark:text-text-sub-dark">
                                <span class="material-symbols-outlined">payments</span>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-text-sub-light dark:text-text-sub-dark uppercase">Salary Range</p>
                                <p class="text-sm font-semibold text-text-main-light dark:text-white">
                                    <?php 
                                    $salary = $application['salary_range'] ?? 'Competitive';
                                    // Strip existing symbols ($, ₦, £, etc.) and whitespace
                                    $val = trim(preg_replace('/[^\d,kK\s\-]/', '', $salary));
                                    
                                    // Prepend global currency
                                    $currency = get_currency_symbol();
                                    
                                    // If stripping resulted in empty (e.g. it was just text "Competitive"), keep original string
                                    // If original was "Competitive", val might be empty or "Competitive" if regex didn't match text chars.
                                    // Let's rely on checking if it looks like a number/range.
                                    if (preg_match('/[0-9]/', $val)) {
                                         echo $currency . $val;
                                    } else {
                                        echo htmlspecialchars($salary);
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                        <div class="pt-4 border-t border-border-light dark:border-border-dark">
                            <a class="flex items-center gap-1 text-sm font-medium text-primary hover:underline" href="../job_board_&_candidate_portal/view_job.php?id=<?php echo $application['job_id']; ?>">
                                View Full Job Description
                                <span class="material-symbols-outlined text-sm">arrow_outward</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Footer -->
<?php include_once __DIR__ . '/../includes/footer.php'; ?>

</div>
</body>
</html>
