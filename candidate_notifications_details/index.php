<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/NotificationHelper.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

$user_id = $_SESSION['user_id'];
$notification_id = $_GET['id'] ?? null;

if (!$notification_id) {
    header("Location: /notifications");
    exit;
}

// Fetch Notification
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = ? AND user_id = ?");
$stmt->execute([$notification_id, $user_id]);
$notification = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notification) {
    // Not found or not owned by user
    header("Location: /notifications");
    exit;
}

// Mark as Read automatically
if (!$notification['is_read']) {
    NotificationHelper::markAsRead($notification_id, $user_id);
    $notification['is_read'] = 1; // Update local state
}

// Previous/Next Logic (Simple based on ID or Date)
// Prev = Newer (higher ID/date), Next = Older (lower ID/date)
$stmtPrev = $pdo->prepare("SELECT id FROM notifications WHERE user_id = ? AND created_at > ? ORDER BY created_at ASC LIMIT 1");
$stmtPrev->execute([$user_id, $notification['created_at']]);
$prevId = $stmtPrev->fetchColumn();

$stmtNext = $pdo->prepare("SELECT id FROM notifications WHERE user_id = ? AND created_at < ? ORDER BY created_at DESC LIMIT 1");
$stmtNext->execute([$user_id, $notification['created_at']]);
$nextId = $stmtNext->fetchColumn();

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Notification Detail - HR Connect</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link href="/assets/css/style.css" rel="stylesheet"/>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
    <?php echo get_theme_css(); ?>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-white font-display antialiased min-h-screen flex flex-col">

<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- Main Content Wrapper -->
<main class="flex-1 flex flex-col h-full relative overflow-hidden">
    <!-- Scrollable Page Content -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 scroll-smooth">
        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Breadcrumbs -->
            <nav class="flex items-center text-sm text-slate-500 dark:text-slate-400">
                <a class="hover:text-primary transition-colors" href="/dashboard">Home</a>
                <span class="mx-2 text-slate-300 dark:text-slate-600">/</span>
                <a class="hover:text-primary transition-colors" href="/notifications">Notifications</a>
                <span class="mx-2 text-slate-300 dark:text-slate-600">/</span>
                <span class="text-slate-900 dark:text-white font-medium">Message Detail</span>
            </nav>

            <!-- Main Detail Card -->
            <div class="bg-white dark:bg-[#1a1a2e] rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                <!-- Header / Toolbar -->
                <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="bg-primary/10 text-primary dark:text-primary-400 text-[10px] uppercase font-bold px-2 py-0.5 rounded tracking-wide"><?php echo htmlspecialchars($notification['type'] ?? 'System'); ?></span>
                                <span class="text-slate-400 text-xs">#NTF-<?php echo $notification['id']; ?></span>
                            </div>
                            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white leading-tight">
                                <?php echo htmlspecialchars($notification['title']); ?>
                            </h1>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <!-- Toggle Unread/Read -->
                            <?php if ($notification['is_read']): ?>
                            <button onclick="performAction('mark_unread', <?php echo $notification['id']; ?>)" class="flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" title="Mark as unread">
                                <span class="material-symbols-outlined text-[20px]">mark_email_unread</span>
                                <span class="hidden sm:inline">Unread</span>
                            </button>
                            <?php else: ?>
                            <button onclick="performAction('mark_read', <?php echo $notification['id']; ?>)" class="flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" title="Mark as read">
                                <span class="material-symbols-outlined text-[20px]">mark_email_read</span>
                                <span class="hidden sm:inline">Read</span>
                            </button>
                            <?php endif; ?>

                            <!-- Archive / Unarchive -->
                            <?php if ($notification['is_archived'] ?? 0): ?>
                            <button onclick="performAction('unarchive', <?php echo $notification['id']; ?>)" class="flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" title="Unarchive notification">
                                <span class="material-symbols-outlined text-[20px]">unarchive</span>
                                <span class="hidden sm:inline">Unarchive</span>
                            </button>
                            <?php else: ?>
                            <button onclick="performAction('archive', <?php echo $notification['id']; ?>)" class="flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" title="Archive notification">
                                <span class="material-symbols-outlined text-[20px]">archive</span>
                                <span class="hidden sm:inline">Archive</span>
                            </button>
                            <?php endif; ?>

                            <!-- Delete -->
                            <button onclick="performAction('delete', <?php echo $notification['id']; ?>)" class="flex items-center justify-center size-10 rounded-lg border border-slate-200 dark:border-slate-700 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 hover:border-red-100 transition-colors" title="Delete notification">
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6 md:p-8">
                    <!-- Profile Header (Mocked for now as System) -->
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-8">
                        <div class="relative">
                            <div class="size-14 rounded-full bg-slate-100 dark:bg-white/10 flex items-center justify-center border-2 border-white dark:border-slate-700 shadow-sm">
                                <span class="material-symbols-outlined text-3xl text-slate-400">notifications</span>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">System Notification</h3>
                            <div class="flex flex-wrap items-center gap-x-2 text-sm text-slate-500 dark:text-slate-400">
                                <span>HR Connect</span>
                                <span class="size-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                <span>To: You</span>
                            </div>
                        </div>
                        <div class="text-sm text-slate-500 dark:text-slate-400">
                            <?php echo date('M d, Y, h:i A', strtotime($notification['created_at'])); ?>
                        </div>
                    </div>

                    <!-- Message Body -->
                    <div class="prose prose-slate dark:prose-invert max-w-none text-slate-600 dark:text-slate-300 leading-relaxed">
                        <?php echo $notification['message']; // Trusted source (system generated) ?>
                    </div>
                    
                    <?php if (!empty($notification['action_url'])): ?>
                    <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800">
                         <a href="<?php echo htmlspecialchars($notification['action_url']); ?>" class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary/90 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors shadow-sm shadow-primary/30">
                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                            View Associated Resource
                         </a>
                    </div>
                    <?php endif; ?>

                </div>

                <!-- Action Footer (Reply not applicable for system notifications generally, keeping minimal) -->
                <!-- <div class="bg-slate-50 dark:bg-slate-900/50 p-6 border-t border-slate-100 dark:border-slate-800 flex flex-wrap gap-3"> ... </div> -->
            </div>

            <!-- Quick Navigation -->
            <div class="flex justify-between items-center text-sm text-slate-500 dark:text-slate-400 pt-2 pb-8">
                <?php if ($prevId): ?>
                <a class="flex items-center gap-1 hover:text-primary transition-colors" href="/notification-details?id=<?php echo $prevId; ?>">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    Newer Notification
                </a>
                <?php else: ?>
                <span class="opacity-50 flex items-center gap-1 cursor-not-allowed">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span> Newer Notification
                </span>
                <?php endif; ?>

                <?php if ($nextId): ?>
                <a class="flex items-center gap-1 hover:text-primary transition-colors" href="/notification-details?id=<?php echo $nextId; ?>">
                    Older Notification
                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </a>
                <?php else: ?>
                <span class="opacity-50 flex items-center gap-1 cursor-not-allowed">
                    Older Notification <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
    function performAction(action, id) {
        if (action === 'delete' && !confirm('Are you sure you want to delete this notification?')) return;
        
        $.post('/api/notifications_read.php', { action: action, id: id }, function(res) {
            if (res.success) {
                if (action === 'delete' || action === 'archive' || action === 'mark_unread') {
                    window.location.href = '/notifications';
                } else {
                    location.reload();
                }
            } else {
                alert('Action failed: ' + (res.message || 'Unknown error'));
            }
        });
    }
</script>
</body>
</html>
