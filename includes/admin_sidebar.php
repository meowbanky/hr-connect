<?php
require_once __DIR__ . '/settings.php';
$sidebarLogo = get_setting('company_logo');
// helpers/active_link helper could be added here
function isActive($path) {
    return strpos($_SERVER['REQUEST_URI'], $path) !== false ? 'bg-primary/10 text-primary' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white';
}
?>
<!-- Mobile Overlay -->
<div id="sidebar-overlay" onclick="document.getElementById('sidebar').classList.add('-translate-x-full'); this.classList.add('hidden')" class="fixed inset-0 bg-slate-900/50 z-40 hidden md:hidden glass-effect transition-opacity duration-300"></div>

<!-- Manual CSS Fallback for Sidebar (since build watcher might not update immediately) -->
<style>
    /* Mobile-first defaults (hidden/off-screen) */
    .-translate-x-full { transform: translateX(-100%) !important; }
    .transition-transform { transition-property: transform; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 300ms; }
    
    /* Desktop overrides (min-width: 768px) */
    @media (min-width: 768px) {
        #sidebar {
            position: static !important;
            transform: none !important;
            box-shadow: none !important;
        }
        .md\:hidden {
            display: none !important;
        }
        .md\:block {
            display: block !important;
        }
    }
</style>

<aside id="sidebar" class="fixed inset-y-0 left-0 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col justify-between shrink-0 transition-transform duration-300 z-50 w-64 -translate-x-full md:translate-x-0 md:static shadow-xl md:shadow-none">
    <!-- Sidebar Header -->
    <div class="h-16 flex items-center justify-between px-6 border-b border-slate-100 dark:border-slate-800">
        <div class="flex items-center gap-2 font-bold text-xl text-slate-900 dark:text-white">
            <?php if ($sidebarLogo && file_exists(__DIR__ . '/..' . $sidebarLogo)): ?>
                <img src="<?php echo htmlspecialchars($sidebarLogo); ?>" alt="Logo" class="w-8 h-8 object-contain">
            <?php else: ?>
                <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center text-white">
                    <span class="material-symbols-outlined text-[20px]">admin_panel_settings</span>
                </div>
            <?php endif; ?>
            HR Admin
        </div>
        <!-- Mobile Close Button -->
        <button onclick="document.getElementById('sidebar').classList.add('-translate-x-full'); document.getElementById('sidebar-overlay').classList.add('hidden')" class="md:hidden text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
        <p class="px-4 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Main Menu</p>
        
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-all <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin') !== false && !strpos($_SERVER['REQUEST_URI'], 'jobs') && !strpos($_SERVER['REQUEST_URI'], 'settings') && !strpos($_SERVER['REQUEST_URI'], 'applications') && !strpos($_SERVER['REQUEST_URI'], 'interviews') && !strpos($_SERVER['REQUEST_URI'], 'employees') && !strpos($_SERVER['REQUEST_URI'], 'reports') && !strpos($_SERVER['REQUEST_URI'], 'notifications')) ? 'bg-primary/10 text-primary' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'; ?>" href="/admin">
            <span class="material-symbols-outlined <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'fill' : ''; ?>">dashboard</span>
            <span>Dashboard</span>
        </a>

        <?php
        // Fetch unread notifications count
        $unreadNotifCount = 0;
        if(isset($pdo) && isset($_SESSION['user_id'])) {
            $nStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $nStmt->execute([$_SESSION['user_id']]);
            $unreadNotifCount = $nStmt->fetchColumn();
        }
        ?>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-all <?php echo (strpos($_SERVER['REQUEST_URI'], 'notifications') !== false) ? 'bg-primary/10 text-primary' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'; ?>" href="/admin/notifications.php">
            <span class="material-symbols-outlined <?php echo (strpos($_SERVER['REQUEST_URI'], 'notifications') !== false) ? 'icon-fill' : ''; ?>">notifications</span>
            <span>Notifications</span>
             <?php if($unreadNotifCount > 0): ?>
                <span class="ml-auto bg-primary text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm"><?php echo $unreadNotifCount; ?></span>
            <?php endif; ?>
        </a>
        
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-all <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/jobs') !== false) ? 'bg-primary/10 text-primary' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'; ?>" href="/admin/jobs">
            <span class="material-symbols-outlined <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/jobs') !== false) ? 'fill' : ''; ?>">work</span>
            <span>Jobs</span>
        </a>
        
        <?php
        // Fetch pending applications count
        $pendingCount = 0;
        if(isset($pdo)) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'");
            $pendingCount = $stmt->fetchColumn();
        }
        ?>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-all <?php echo (strpos($_SERVER['REQUEST_URI'], 'applications') !== false || strpos($_SERVER['REQUEST_URI'], 'view_application') !== false) ? 'bg-primary/10 text-primary' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'; ?>" href="/admin/applications">
            <span class="material-symbols-outlined <?php echo (strpos($_SERVER['REQUEST_URI'], 'applications') !== false) ? 'fill' : ''; ?>">group</span>
            <span>Applications</span>
            <?php if($pendingCount > 0): ?>
                <span class="ml-auto bg-primary/10 text-primary text-xs font-bold px-2 py-0.5 rounded-full"><?php echo $pendingCount; ?></span>
            <?php endif; ?>
        </a>
        
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-all <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/interviews') !== false) ? 'bg-primary/10 text-primary' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'; ?>" href="/admin/interviews">
            <span class="material-symbols-outlined">calendar_month</span>
            <span>Interviews</span>
        </a>
        
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-all <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/employees') !== false) ? 'bg-primary/10 text-primary' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'; ?>" href="/admin/employees">
            <span class="material-symbols-outlined">badge</span>
            <span>Employees</span>
        </a>
        
        <div class="pt-4 mt-4 border-t border-slate-100 dark:border-slate-800">
            <p class="px-4 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">System</p>
            <a class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-all <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/reports') !== false) ? 'bg-primary/10 text-primary' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'; ?>" href="/admin/reports">
                <span class="material-symbols-outlined">bar_chart</span>
                <span>Reports</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-all <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/settings') !== false) ? 'bg-primary/10 text-primary' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'; ?>" href="/admin/settings">
                <span class="material-symbols-outlined">settings</span>
                <span>Settings</span>
            </a>
        </div>
    </nav>
    
    <div class="p-4 border-t border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer transition-colors">
            <div class="size-10 rounded-full bg-cover bg-center border-2 border-white dark:border-slate-700 shadow-sm" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuAdmmkd7BVKmHzXUh_VEowlcJ_xivlLfpBqN4FCU3OxL0iSdnWxYIT6RsXYsHaDB_Faimp7VFJG2EEL6FlgGCT8-HU9Z45bfdabUDA_vVOL2QqjmWJgd13ZWp9hAfyDLE5cwbcXZQZaWwmkLcxbwAmsM50eZRS7u7P5w3IPKRW-Qr_7Rvj0KuuSfgdlqDrJuQpNyHe7VHm0ot8jQImafpFi86vKgUtR6Iajkeip_dPHUnimQ-0ldCnCEqHB_VHcc2wZMip71IkZwek');"></div>
            <div class="flex flex-col overflow-hidden">
                <p class="text-sm font-semibold text-slate-900 dark:text-white truncate"><?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin User'; ?></p>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">Administrator</p>
            </div>
            <a href="/logout" class="ml-auto text-slate-400 hover:text-red-500 transition-colors" title="Logout">
                <span class="material-symbols-outlined" style="font-size: 20px;">logout</span>
            </a>
        </div>
    </div>
</aside>
