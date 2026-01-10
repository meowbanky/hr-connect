<?php
// Ensure session is started if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/settings.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';

// Determine relative path depth to assets/root
// Simple logic: check if we are in a subdir (e.g. job_board/index.php) or deeper
// Adjust links dynamically or assume a standard depth.
// For simplicity in this project structure, most pages are 1 level deep from root (e.g. candidate_registration/login.php)
// so '../' usually works. We can use a helper if needed, but hardcoded relative paths are requested in the prompt context often.
// Let's use a standardized path assuming inclusion from 1-level deep scripts.
$cssVersion = time();
$headerLogo = get_setting('company_logo');
?>
<nav class="sticky top-0 z-50 w-full border-b border-slate-200 dark:border-gray-800 bg-white dark:bg-[#1a1a2e]/90 backdrop-blur-md">
<?php
// Calculate Profile Stat if logged in
$headerProfilePercent = 0;
if (isset($_SESSION['user_id'])) {
    global $pdo; // Ensure $pdo is available
    if($pdo) {
         $stmtHead = $pdo->prepare("SELECT * FROM candidates WHERE user_id = ?");
         $stmtHead->execute([$_SESSION['user_id']]);
         $headCand = $stmtHead->fetchColumn();
         
         // Fix: create mock array if fetching column failed or create validation logic
         // Actually fetch(PDO::FETCH_ASSOC) is better
         $stmtHead->execute([$_SESSION['user_id']]);
         $headCand = $stmtHead->fetch(PDO::FETCH_ASSOC);
         
         if ($headCand) {
             $headerProfilePercent = calculateProfileCompletion($headCand);
         }
    }
}
?>
    <div class="px-4 md:px-6 lg:px-12 flex items-center justify-between h-16 max-w-[1440px] mx-auto">
        <div class="flex items-center gap-4">
            <!-- Mobile Menu Button -->
            <button onclick="document.getElementById('mobile-menu').classList.remove('hidden')" class="md:hidden text-slate-500 hover:text-primary transition-colors">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <a href="/jobs" class="flex items-center gap-3">
                <?php if ($headerLogo && file_exists(__DIR__ . '/..' . $headerLogo)): ?>
                    <img src="<?php echo htmlspecialchars($headerLogo); ?>" alt="Logo" class="h-8 w-auto object-contain">
                <?php else: ?>
                    <div class="size-8 text-primary">
                        <svg class="w-full h-full" fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                            <path clip-rule="evenodd" d="M24 0.757355L47.2426 24L24 47.2426L0.757355 24L24 0.757355ZM21 35.7574V12.2426L9.24264 24L21 35.7574Z" fill="currentColor" fill-rule="evenodd"></path>
                        </svg>
                    </div>
                <?php endif; ?>
                <h2 class="text-text-main dark:text-white text-xl font-bold tracking-tight hidden sm:block">HR Connect</h2>
            </a>
        </div>
        
        <div class="hidden md:flex items-center gap-8">
            <a class="text-text-main dark:text-gray-300 hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium" href="/jobs">Job Board</a>
            <a class="text-text-main dark:text-gray-300 hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium" href="/bookmarks">Saved Jobs</a>
            <a class="text-text-main dark:text-gray-300 hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium" href="/dashboard">My Applications</a>
            <a class="text-text-main dark:text-gray-300 hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium" href="/profile">My Profile</a>
        </div>
        <div class="flex gap-3 items-center">
            
            <?php if($isLoggedIn): ?>
                <!-- Profile Completion Ring (Mini) -->
                <a href="/profile" class="hidden sm:flex items-center justify-center mr-2 relative group" title="Profile Completion: <?php echo $headerProfilePercent; ?>%">
                    <svg class="size-8 -rotate-90" viewBox="0 0 36 36">
                        <circle cx="18" cy="18" r="16" fill="none" class="stroke-slate-200 dark:stroke-slate-700" stroke-width="4"></circle>
                        <circle cx="18" cy="18" r="16" fill="none" class="<?php echo $headerProfilePercent == 100 ? 'stroke-green-500' : 'stroke-primary'; ?>" stroke-width="4" stroke-dasharray="100" stroke-dashoffset="<?php echo 100 - $headerProfilePercent; ?>" stroke-linecap="round"></circle>
                    </svg>
                    <span class="absolute text-[8px] font-bold text-text-main dark:text-white"><?php echo $headerProfilePercent; ?>%</span>
                </a>
            <?php endif; ?>

            <!-- Notification Icon (Visible on all screens) -->
            <a class="text-text-main dark:text-gray-300 hover:text-primary dark:hover:text-primary transition-colors relative group mr-2" href="/notifications">
                <span class="material-symbols-outlined text-[24px]">notifications</span>
                <?php
                if (isset($_SESSION['user_id'])) {
                    // $pdo assumed global from previous snippet or settings
                    if ($pdo) {
                        $stmtBadge = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
                        $stmtBadge->execute([$_SESSION['user_id']]);
                        $badgeCount = $stmtBadge->fetchColumn();
                        if($badgeCount > 0) {
                            $displayCount = $badgeCount > 9 ? '9+' : $badgeCount;
                            echo '<span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold">' . $displayCount . '</span>';
                        }
                    }
                }
                ?>
            </a>

            <?php if ($isLoggedIn): ?>
                <span class="hidden sm:inline text-sm font-medium text-text-main dark:text-white">Hi, <?php echo htmlspecialchars($userName); ?></span>
                <a href="/logout" class="h-9 w-9 sm:w-auto flex items-center justify-center rounded-lg sm:px-4 border border-slate-200 dark:border-gray-700 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-800 text-text-main dark:text-white text-sm font-bold transition-colors" title="Log Out">
                    <span class="sm:hidden material-symbols-outlined text-[20px]">logout</span>
                    <span class="hidden sm:inline">Log Out</span>
                </a>
            <?php else: ?>
                <a href="/login" class="flex h-9 items-center justify-center rounded-lg px-4 border border-slate-200 dark:border-gray-700 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-800 text-text-main dark:text-white text-sm font-bold transition-colors">
                    Log In
                </a>
                <a href="/register" class="flex h-9 items-center justify-center rounded-lg px-4 bg-primary hover:bg-blue-700 text-white text-sm font-bold shadow-lg shadow-blue-500/20 transition-all">
                    Sign Up
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Mobile Menu Overlay -->
<div id="mobile-menu" class="hidden fixed inset-0 z-[99999]" style="z-index: 99999;">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="document.getElementById('mobile-menu').classList.add('hidden')"></div>
    
    <!-- Drawer -->
    <div class="fixed inset-y-0 left-0 w-64 bg-white dark:bg-slate-900 shadow-xl flex flex-col z-[100000]" style="z-index: 100000;">
        <!-- Header -->
        <div class="h-16 flex items-center justify-between px-6 border-b border-slate-100 dark:border-slate-800">
            <span class="text-lg font-bold text-slate-900 dark:text-white">Menu</span>
            <button onclick="document.getElementById('mobile-menu').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <!-- Links -->
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="/jobs" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-primary transition-colors">
                <span class="material-symbols-outlined">work</span>
                Job Board
            </a>
            <a href="/bookmarks" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-primary transition-colors">
                <span class="material-symbols-outlined">bookmark</span>
                Saved Jobs
            </a>
            <a href="/dashboard" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-primary transition-colors">
                <span class="material-symbols-outlined">dashboard</span>
                My Applications
            </a>
            <a href="/profile" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-primary transition-colors">
                    <span class="material-symbols-outlined">person</span>
                My Profile
            </a>
        </nav>
        
        <!-- Mobile Footer Info -->
        <?php if ($isLoggedIn): ?>
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3 px-2 mb-3">
                        <div class="size-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs"><?php echo substr($userName, 0, 1); ?></div>
                        <div class="flex flex-col">
                            <span class="text-sm font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($userName); ?></span>
                            <span class="text-xs text-slate-500">Candidate</span>
                        </div>
                    </div>
            </div>
        <?php endif; ?>
    </div>
</div>
