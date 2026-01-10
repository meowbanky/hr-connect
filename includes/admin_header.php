<header class="h-18 px-4 md:px-8 py-5 flex items-center justify-between bg-background-light dark:bg-background-dark z-10">
    <div class="flex flex-col">
        <div class="flex items-center gap-3 md:gap-0">
            <button onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full'); document.getElementById('sidebar-overlay').classList.toggle('hidden')" class="md:hidden text-slate-500 hover:text-primary">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h2 class="text-xl md:text-2xl font-bold text-slate-900 dark:text-white tracking-tight"><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></h2>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400 hidden md:block">Overview of recruitment progress</p>
    </div>
    <div class="flex items-center gap-4">
        <div class="relative group hidden md:block">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="material-symbols-outlined text-slate-400 group-focus-within:text-primary transition-colors" style="font-size: 20px;">search</span>
            </div>
            <input class="block w-64 pl-10 pr-3 py-2 border-none rounded-lg leading-5 bg-white dark:bg-surface-dark text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/20 shadow-sm transition-all text-sm" placeholder="Search candidates, jobs..." type="text"/>
        </div>
        <button class="p-2 rounded-full bg-white dark:bg-surface-dark text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-slate-50 dark:hover:bg-slate-800 shadow-sm transition-all relative">
            <span class="absolute top-2 right-2.5 size-2 bg-red-500 rounded-full border border-white dark:border-surface-dark"></span>
            <span class="material-symbols-outlined" style="font-size: 22px;">notifications</span>
        </button>
        <button class="p-2 rounded-full bg-white dark:bg-surface-dark text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-slate-50 dark:hover:bg-slate-800 shadow-sm transition-all">
            <span class="material-symbols-outlined" style="font-size: 22px;">help</span>
        </button>
    </div>
</header>
