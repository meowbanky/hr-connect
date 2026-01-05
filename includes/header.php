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
?>
<nav class="sticky top-0 z-50 w-full border-b border-slate-200 dark:border-gray-800 bg-white dark:bg-[#1a1a2e]/90 backdrop-blur-md">
    <div class="px-6 lg:px-12 flex items-center justify-between h-16 max-w-[1440px] mx-auto">
        <a href="/jobs" class="flex items-center gap-4">
            <div class="size-8 text-primary">
                <svg class="w-full h-full" fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                    <path clip-rule="evenodd" d="M24 0.757355L47.2426 24L24 47.2426L0.757355 24L24 0.757355ZM21 35.7574V12.2426L9.24264 24L21 35.7574Z" fill="currentColor" fill-rule="evenodd"></path>
                </svg>
            </div>
            <h2 class="text-text-main dark:text-white text-xl font-bold tracking-tight">HR Connect</h2>
        </a>
        <div class="hidden md:flex items-center gap-8">
            <a class="text-text-main dark:text-gray-300 hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium" href="/jobs">Job Board</a>
            <a class="text-text-main dark:text-gray-300 hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium" href="/bookmarks">Saved Jobs</a>
            <a class="text-text-main dark:text-gray-300 hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium" href="/dashboard">My Applications</a>
        </div>
        <div class="flex gap-3 items-center">
            <?php if ($isLoggedIn): ?>
                <span class="text-sm font-medium text-text-main dark:text-white">Hi, <?php echo htmlspecialchars($userName); ?></span>
                <a href="/logout" class="h-9 flex items-center justify-center rounded-lg px-4 border border-slate-200 dark:border-gray-700 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-800 text-text-main dark:text-white text-sm font-bold transition-colors">
                    Log Out
                </a>
            <?php else: ?>
                <a href="/login" class="hidden sm:flex h-9 items-center justify-center rounded-lg px-4 border border-slate-200 dark:border-gray-700 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-800 text-text-main dark:text-white text-sm font-bold transition-colors">
                    Log In
                </a>
                <a href="/register" class="flex h-9 items-center justify-center rounded-lg px-4 bg-primary hover:bg-blue-700 text-white text-sm font-bold shadow-lg shadow-blue-500/20 transition-all">
                    Sign Up
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>
