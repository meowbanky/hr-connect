<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- Mobile Overlay -->
<div id="mobileOverlay" class="fixed inset-0 bg-slate-900/50 z-40 hidden backdrop-blur-sm md:hidden" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside id="panelSidebar" class="w-64 fixed inset-y-0 left-0 z-50 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out md:static md:h-screen shrink-0">
    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="h-9">
                 <img src="<?php echo htmlspecialchars(get_setting('company_logo', '/assets/images/logo.png')); ?>" alt="Company Logo" class="h-full w-auto object-contain">
            </div>
            <div>
                <h1 class="text-sm font-bold leading-tight">Interview Portal</h1>
                <p class="text-xs text-slate-500">HR Management</p>
            </div>
        </div>
        <!-- Close Button (Mobile Only) -->
        <button class="md:hidden text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" onclick="toggleSidebar()">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    
    <nav class="flex-1 overflow-y-auto p-4 space-y-1 custom-scrollbar">
        <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Navigation</p>
        
        <a class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors <?php echo $currentPage === 'dashboard.php' ? 'bg-primary/10 text-primary' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>" href="dashboard.php">
            <span class="material-symbols-outlined text-[20px]">dashboard</span>
            <span class="text-sm font-medium">Dashboard</span>
        </a>

        <a class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors <?php echo $currentPage === 'candidates.php' ? 'bg-primary/10 text-primary' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>" href="candidates.php">
            <span class="material-symbols-outlined text-[20px]">group</span>
            <span class="text-sm font-medium">My Candidates</span>
        </a>
        
        <!-- Placeholder Links -->
        <a class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" href="#">
            <span class="material-symbols-outlined text-[20px]">work</span>
            <span class="text-sm font-medium">Job Postings</span>
        </a>
        
        <a class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" href="#">
            <span class="material-symbols-outlined text-[20px]">calendar_today</span>
            <span class="text-sm font-medium">Interviews</span>
        </a>

        <div class="pt-6">
            <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Account</p>
             <a class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20" href="logout.php">
                <span class="material-symbols-outlined text-[20px]">logout</span>
                <span class="text-sm font-medium">Logout</span>
            </a>
        </div>
    </nav>
    
    <div class="p-4 border-t border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-3 px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-800/50">
            <div class="size-8 rounded-full bg-slate-200 bg-cover bg-center flex items-center justify-center text-slate-500 font-bold">
                 <?php echo strtoupper(substr($_SESSION['panelist_name'] ?? 'User', 0, 1)); ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold truncate"><?php echo htmlspecialchars($_SESSION['panelist_name'] ?? 'Panelist'); ?></p>
                <p class="text-[10px] text-slate-500 truncate"><?php echo htmlspecialchars($_SESSION['panelist_role'] ?? 'Panelist'); ?></p>
            </div>
            <span class="material-symbols-outlined text-slate-400 text-sm">settings</span>
        </div>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('panelSidebar');
        const overlay = document.getElementById('mobileOverlay');
        
        if (sidebar.classList.contains('-translate-x-full')) {
            // Open
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        } else {
            // Close
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    }
</script>
