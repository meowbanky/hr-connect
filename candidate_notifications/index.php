<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch Notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count Unread
$unreadCount = 0;
foreach ($notifications as $n) {
    if (!$n['is_read']) $unreadCount++;
}

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Notifications - Candidate Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <!-- Use absolute path for CSS -->
    <link href="https://hr.prismtechnologies.com.ng/assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet"/>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        .icon-fill { font-variation-settings: 'FILL' 1; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-text-main dark:text-gray-100 min-h-screen flex flex-col transition-colors duration-200">

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="flex-1 max-w-[1100px] w-full mx-auto p-4 lg:p-8">
    
    <!-- Page Heading -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex flex-col gap-1">
            <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Notifications</h2>
            <p class="text-slate-500 dark:text-[#9894c7] text-base">You have <?php echo $unreadCount; ?> unread alerts.</p>
        </div>
        <button id="markAllReadBtn" class="flex items-center gap-2 justify-center rounded-lg h-10 px-4 bg-white dark:bg-[#282546] border border-slate-200 dark:border-transparent text-slate-700 dark:text-white text-sm font-bold shadow-sm hover:bg-slate-50 dark:hover:bg-[#322f54] transition-colors whitespace-nowrap">
            <span class="material-symbols-outlined text-[18px]">done_all</span>
            <span>Mark all as read</span>
        </button>
    </div>

    <!-- Toolbar: Search & Filters -->
    <div class="flex flex-col gap-4 mb-6">
        <!-- Search Bar -->
        <div class="w-full">
            <label class="flex flex-col w-full h-12">
                <div class="flex w-full flex-1 items-stretch rounded-xl h-full shadow-sm bg-white dark:bg-[#1e293b] border border-slate-200 dark:border-slate-700 focus-within:border-primary dark:focus-within:border-primary transition-colors overflow-hidden">
                    <div class="text-slate-400 flex items-center justify-center pl-4 pr-2">
                        <span class="material-symbols-outlined">search</span>
                    </div>
                    <input id="searchInput" class="flex w-full min-w-0 flex-1 bg-transparent text-slate-900 dark:text-white focus:outline-0 border-none h-full placeholder:text-slate-400 px-2 text-sm font-normal" placeholder="Search notifications..." value=""/>
                </div>
            </label>
        </div>
        <!-- Filter Chips -->
        <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
            <button class="filter-btn active flex h-8 shrink-0 items-center justify-center gap-x-2 rounded-full bg-primary text-white px-4 transition-transform active:scale-95" data-filter="all">
                <p class="text-sm font-medium leading-normal">All</p>
            </button>
            <button class="filter-btn flex h-8 shrink-0 items-center justify-center gap-x-2 rounded-full bg-slate-200 dark:bg-[#282546] hover:bg-slate-300 dark:hover:bg-[#363259] px-4 transition-colors" data-filter="unread">
                <p class="text-slate-700 dark:text-white text-sm font-medium leading-normal">Unread</p>
                <?php if ($unreadCount > 0): ?>
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] text-white"><?php echo $unreadCount; ?></span>
                <?php endif; ?>
            </button>
            <button class="filter-btn flex h-8 shrink-0 items-center justify-center gap-x-2 rounded-full bg-slate-200 dark:bg-[#282546] hover:bg-slate-300 dark:hover:bg-[#363259] px-4 transition-colors" data-filter="application">
                <p class="text-slate-700 dark:text-white text-sm font-medium leading-normal">Applications</p>
            </button>
            <button class="filter-btn flex h-8 shrink-0 items-center justify-center gap-x-2 rounded-full bg-slate-200 dark:bg-[#282546] hover:bg-slate-300 dark:hover:bg-[#363259] px-4 transition-colors" data-filter="system">
                <p class="text-slate-700 dark:text-white text-sm font-medium leading-normal">System</p>
            </button>
            <button class="filter-btn flex h-8 shrink-0 items-center justify-center gap-x-2 rounded-full bg-slate-200 dark:bg-[#282546] hover:bg-slate-300 dark:hover:bg-[#363259] px-4 transition-colors" data-filter="archived">
                <p class="text-slate-700 dark:text-white text-sm font-medium leading-normal">Archived</p>
            </button>
        </div>
    </div>

    <!-- Notifications Feed -->
    <div class="flex flex-col gap-3" id="notificationsFeed">
        <?php if (empty($notifications)): ?>
            <div class="flex justify-center py-12 text-slate-400">
                <p>No notifications yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): 
                $isRead = $notification['is_read'];
                $type = $notification['type'] ?? 'system';
                
                // Style based on type
                $borderClass = 'border-slate-200 dark:border-slate-800/50';
                $iconBg = 'bg-blue-100 dark:bg-blue-900/20 text-blue-500';
                $iconName = 'info';

                if (!$isRead) {
                    $borderClass = 'border-l-4 border-primary'; // Default unread
                }

                if ($type == 'application') {
                    $iconBg = 'bg-green-500/10 dark:bg-green-900/20 text-green-600';
                    $iconName = 'check_circle';
                    if (!$isRead) $borderClass = 'border-l-4 border-green-500';
                } elseif ($type == 'interview') {
                    $iconBg = 'bg-primary/10 dark:bg-[#282546] text-primary';
                    $iconName = 'calendar_month';
                    if (!$isRead) $borderClass = 'border-l-4 border-primary';
                }

                $readClass = $isRead ? 'opacity-90 bg-slate-50 dark:bg-[#131221]' : 'bg-white dark:bg-card-dark shadow-sm hover:shadow-md';
            ?>
            <div class="notification-item group relative flex flex-col md:flex-row gap-4 <?php echo $readClass; ?> p-5 rounded-xl <?php echo $borderClass; ?> transition-all cursor-pointer hover:bg-slate-50 dark:hover:bg-[#26334d]" 
                 onclick="window.location.href='/notification-details?id=<?php echo $notification['id']; ?>'"
                 data-id="<?php echo $notification['id']; ?>" 
                 data-read="<?php echo $isRead; ?>"
                 data-type="<?php echo $type; ?>"
                 data-archived="<?php echo $notification['is_archived'] ?? 0; ?>">
                
                <div class="absolute top-4 right-4 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <?php if (!$isRead): ?>
                    <button class="p-1 hover:bg-slate-200 dark:hover:bg-white/10 rounded text-slate-400 hover:text-slate-600 dark:hover:text-white mark-read-btn" title="Mark as read">
                        <span class="material-symbols-outlined text-[20px]">mark_email_read</span>
                    </button>
                    <?php endif; ?>
                </div>

                <div class="flex items-start gap-4 flex-1">
                    <div class="flex items-center justify-center rounded-lg <?php echo $iconBg; ?> shrink-0 size-12">
                        <span class="material-symbols-outlined <?php echo !$isRead ? 'icon-fill' : ''; ?>"><?php echo $iconName; ?></span>
                    </div>
                    <div class="flex flex-col gap-1.5 flex-1 pr-8">
                        <div class="flex items-center gap-2">
                            <p class="text-slate-900 dark:text-white text-base font-semibold leading-tight"><?php echo htmlspecialchars($notification['title']); ?></p>
                            <?php if (!$isRead): ?>
                            <span class="size-2 rounded-full bg-primary animate-pulse"></span>
                            <?php endif; ?>
                        </div>
                <div class="text-slate-600 dark:text-[#9894c7] text-sm font-normal leading-relaxed">
                            <?php echo $notification['message']; // Allow HTML in message ?>
                        </div>
                        
                        <div class="mt-2 flex gap-3">
                            <a href="/notification-details?id=<?php echo $notification['id']; ?>" class="bg-primary hover:bg-primary/90 text-white text-xs font-semibold py-2 px-4 rounded-lg transition-colors inline-block text-center min-w-[100px]" onclick="event.stopPropagation()">View Details</a>
                        </div>
                    </div>
                </div>
                <div class="shrink-0">
                    <p class="text-slate-400 text-xs font-medium"><?php echo time_elapsed_string($notification['created_at']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- End State -->
    <div class="flex justify-center py-6">
        <p class="text-slate-400 text-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
            You're all caught up!
        </p>
    </div>

</main>

<script>
$(document).ready(function() {
    
    // Mark All Read
    $('#markAllReadBtn').click(function() {
        $.post('/api/notifications_read.php', { action: 'mark_all_read' }, function(res) {
            if (res.success) {
                location.reload();
            }
        });
    });

    // Mark Single Read
    $('.mark-read-btn').click(function(e) {
        e.stopPropagation(); // Prevent card click
        var id = $(this).closest('.notification-item').data('id');
        var btn = $(this);
        $.post('/api/notifications_read.php', { action: 'mark_read', id: id }, function(res) {
            if (res.success) {
                location.reload(); // Simple reload for state update
            }
        });
    });

    // Filtering
    // Filtering
    $('.filter-btn').click(function() {
        // UI Active State - Blue (#1313ec is primary)
        $('.filter-btn').removeClass('active bg-primary text-white').addClass('bg-slate-200 text-slate-700 dark:bg-[#282546] dark:text-white');
        $('.filter-btn p').removeClass('text-white').addClass('text-slate-700 dark:text-white');
        
        // Ensure count badges reset text color if needed (though white text on blue is fine, on slate-200 needs correction if any)
        
        $(this).removeClass('bg-slate-200 text-slate-700 dark:bg-[#282546]').addClass('active bg-primary text-white');
        $(this).find('p').removeClass('text-slate-700 dark:text-white').addClass('text-white');
        
        // Logic
        var filter = $(this).data('filter');
        $('.notification-item').each(function() {
            var isArchived = $(this).data('archived') == 1;
            var isRead = $(this).data('read') == 1;
            var type = $(this).data('type');

            if (filter === 'archived') {
                // Only show archived
                if (isArchived) $(this).removeClass('hidden'); else $(this).addClass('hidden');
            } else {
                // For all other filters, HIDE archived items generally? 
                // Usually "All" means "All Active". User said "filter by archive", suggesting it's a separate view.
                if (isArchived) {
                    $(this).addClass('hidden');
                    return; // Skip rest
                }

                if (filter === 'all') {
                    $(this).removeClass('hidden');
                } else if (filter === 'unread') {
                    if (!isRead) $(this).removeClass('hidden'); else $(this).addClass('hidden');
                } else {
                    if (type === filter) $(this).removeClass('hidden'); else $(this).addClass('hidden');
                }
            }
        });
    });

    // Default filter run on load to hide archived
    $('.filter-btn.active').click();

    // Search
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.notification-item').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

});
</script>
</body>
</html>
<?php
// Helper function for time elapsed
function time_elapsed_string($datetime, $full = false) {
    if ($datetime == '0000-00-00 00:00:00') return "Unknown";
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>
