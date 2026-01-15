<?php
session_start();
// Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff')) {
    header('Location: /admin/login');
    exit;
}

require_once __DIR__ . '/../includes/settings.php';
$pageTitle = 'Notifications';
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo isset($_SESSION['theme_mode']) && $_SESSION['theme_mode'] === 'dark' ? 'dark' : 'light'; ?>">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Notifications - HR Connect Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link href="/assets/css/style.css" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#5245e8",
                        "background-light": "#f6f6f8",
                        "background-dark": "#121121",
                        "card-dark": "#1e293b",
                        "card-light": "#ffffff",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; font-size: 24px; line-height: 1; }
        .material-symbols-outlined.icon-fill { font-variation-settings: 'FILL' 1; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    <?php echo get_theme_css(); ?>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-800 dark:text-slate-100 flex h-screen overflow-hidden font-display antialiased">

    <!-- Sidebar -->
    <?php include_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full overflow-hidden relative">
        <!-- Header -->
        <?php include_once __DIR__ . '/../includes/admin_header.php'; ?>

        <!-- Main Content (Adapted from code.html) -->
        <div class="flex-1 overflow-y-auto p-4 md:p-8 pt-2">
            <div class="max-w-5xl mx-auto w-full flex flex-col gap-6">
                <!-- Page Heading -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex flex-col gap-1">
                        <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Notifications</h2>
                        <p class="text-slate-500 dark:text-[#9894c7] text-base" id="unreadSummary">Loading...</p>
                    </div>
                    <button id="markAllReadBtn" class="flex items-center gap-2 justify-center rounded-lg h-10 px-4 bg-white dark:bg-[#282546] border border-slate-200 dark:border-transparent text-slate-700 dark:text-white text-sm font-bold shadow-sm hover:bg-slate-50 dark:hover:bg-[#322f54] transition-colors whitespace-nowrap">
                        <span class="material-symbols-outlined text-[18px]">done_all</span>
                        <span>Mark all as read</span>
                    </button>
                </div>

                <!-- Toolbar: Search & Filters -->
                <div class="flex flex-col gap-4">
                    <!-- Search Bar -->
                    <div class="w-full">
                        <label class="flex flex-col w-full h-12">
                            <div class="flex w-full flex-1 items-stretch rounded-xl h-full shadow-sm bg-white dark:bg-[#1e293b] border border-slate-200 dark:border-slate-700 focus-within:border-primary dark:focus-within:border-primary transition-colors overflow-hidden">
                                <div class="text-slate-400 flex items-center justify-center pl-4 pr-2">
                                    <span class="material-symbols-outlined">search</span>
                                </div>
                                <input id="searchInput" class="flex w-full min-w-0 flex-1 bg-transparent text-slate-900 dark:text-white focus:outline-0 border-none h-full placeholder:text-slate-400 px-2 text-sm font-normal" placeholder="Search notifications by keyword..." />
                            </div>
                        </label>
                    </div>
                    <!-- Filter Chips -->
                    <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide" id="filterContainer">
                        <button class="filter-btn active flex h-8 shrink-0 items-center justify-center gap-x-2 rounded-full bg-primary text-white px-4 transition-transform active:scale-95" data-filter="all">
                            <p class="text-sm font-medium leading-normal">All</p>
                        </button>
                        <button class="filter-btn flex h-8 shrink-0 items-center justify-center gap-x-2 rounded-full bg-slate-200 dark:bg-[#282546] hover:bg-slate-300 dark:hover:bg-[#363259] px-4 transition-colors" data-filter="unread">
                            <p class="text-slate-700 dark:text-white text-sm font-medium leading-normal">Unread</p>
                            <span class="hidden h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] text-white" id="unreadFilterBadge">0</span>
                        </button>
                        <button class="filter-btn flex h-8 shrink-0 items-center justify-center gap-x-2 rounded-full bg-slate-200 dark:bg-[#282546] hover:bg-slate-300 dark:hover:bg-[#363259] px-4 transition-colors" data-filter="application">
                            <p class="text-slate-700 dark:text-white text-sm font-medium leading-normal">Applications</p>
                        </button>
                        <button class="filter-btn flex h-8 shrink-0 items-center justify-center gap-x-2 rounded-full bg-slate-200 dark:bg-[#282546] hover:bg-slate-300 dark:hover:bg-[#363259] px-4 transition-colors" data-filter="system">
                            <p class="text-slate-700 dark:text-white text-sm font-medium leading-normal">System</p>
                        </button>
                    </div>
                </div>

                <!-- Notifications Feed -->
                <div class="flex flex-col gap-3" id="notificationFeed">
                    <!-- Dynamic Content -->
                    <div class="flex justify-center py-20">
                        <div class="loader ease-linear rounded-full border-4 border-t-4 border-gray-200 h-12 w-12 text-primary"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        $(document).ready(function() {
            let currentFilter = 'all';
            let searchQuery = '';

            loadNotifications();

            // Filter Click Handler
            $('.filter-btn').click(function() {
                // Reset all
                $('.filter-btn').removeClass('bg-primary text-white active')
                    .addClass('bg-slate-200 dark:bg-[#282546] text-slate-700 dark:text-white hover:bg-slate-300 dark:hover:bg-[#363259]');
                
                // Reset text inside (specifically specific classes if any)
                $('.filter-btn p').removeClass('text-white').addClass('text-slate-700 dark:text-white');

                // Set Active
                $(this).removeClass('bg-slate-200 dark:bg-[#282546] text-slate-700 dark:text-white hover:bg-slate-300 dark:hover:bg-[#363259]')
                    .addClass('bg-primary text-white active');
                
                // Set text inside
                $(this).find('p').removeClass('text-slate-700 dark:text-white').addClass('text-white');
                
                currentFilter = $(this).data('filter');
                loadNotifications();
            });

            // Search Handler
            let searchTimeout;
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimeout);
                searchQuery = $(this).val();
                searchTimeout = setTimeout(loadNotifications, 300);
            });

            // Mark All Read
            $('#markAllReadBtn').click(function() {
                $.post('../api/admin_notification_action.php', { action: 'mark_all_read' }, function(res) {
                    if(res.success) {
                        loadNotifications();
                        // Update sidebar badge if exists (generic refresh)
                    }
                }, 'json');
            });

            function loadNotifications() {
                $.ajax({
                    url: '../api/fetch_admin_notifications.php',
                    data: { filter: currentFilter, search: searchQuery },
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            renderNotifications(response.notifications);
                            updateHeader(response.unread_count);
                        } else {
                            $('#notificationFeed').html('<p class="text-center text-red-500">Failed to load notifications.</p>');
                        }
                    },
                    error: function() {
                        $('#notificationFeed').html('<p class="text-center text-gray-500">Error fetching data. Check endpoints.</p>');
                    }
                });
            }

            function updateHeader(count) {
                // Update text summary
                if(count > 0) {
                    $('#unreadSummary').text(`You have ${count} unread alerts needing your attention.`);
                } else {
                    $('#unreadSummary').text("You're all caught up!");
                }
                
                // Update Filter Pill Badge
                if(count > 0) {
                     $('#unreadFilterBadge').text(count).removeClass('hidden').addClass('flex');
                } else {
                     $('#unreadFilterBadge').addClass('hidden').removeClass('flex');
                }

                // Update Header Badge (Admin Header)
                var headerBadge = $('#adminHeaderBadge');
                if (headerBadge.length) {
                    if (count > 0) {
                        var display = count > 9 ? '9+' : count;
                        headerBadge.text(display).removeClass('hidden');
                    } else {
                        headerBadge.text('0').addClass('hidden');
                    }
                }
            }

            function renderNotifications(items) {
                const container = $('#notificationFeed');
                container.empty();

                if(items.length === 0) {
                     container.html(`
                        <div class="flex justify-center py-6">
                            <p class="text-slate-400 text-sm flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                No notifications found.
                            </p>
                        </div>
                     `);
                     return;
                }

                const groups = {
                    'Today': [],
                    'Yesterday': [],
                    'Earlier': []
                };

                const now = new Date();
                const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);

                items.forEach(item => {
                    const itemDate = new Date(item.created_at);
                    const itemDay = new Date(itemDate.getFullYear(), itemDate.getMonth(), itemDate.getDate());

                    if (itemDay.getTime() === today.getTime()) {
                        groups['Today'].push(item);
                    } else if (itemDay.getTime() === yesterday.getTime()) {
                        groups['Yesterday'].push(item);
                    } else {
                        groups['Earlier'].push(item);
                    }
                });

                Object.keys(groups).forEach(groupName => {
                    const groupItems = groups[groupName];
                    if (groupItems.length > 0) {
                        // Render Group Header
                        container.append(`
                            <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-4 mb-2 pl-1">
                                ${groupName}
                            </h3>
                        `);

                        // Render Items
                        groupItems.forEach(item => {
                            // Determine Icon and Colors based on Type
                            let icon = 'notifications';
                            let colorClass = 'bg-primary/10 dark:bg-[#282546] text-primary';
                            let borderClass = 'border-l-4 border-primary';
                            let urgentBadge = '';
                            
                            if(item.type === 'system') {
                                icon = 'info';
                                colorClass = 'bg-blue-100 dark:bg-blue-900/20 text-blue-500 dark:text-blue-400';
                                borderClass = 'border border-slate-200 dark:border-slate-800/50';
                            } else if (item.type === 'application') {
                                icon = 'description';
                                colorClass = 'bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400';
                                borderClass = 'border-l-4 border-green-500';
                            } else if (item.type === 'interview') {
                                icon = 'calendar_month';
                                colorClass = 'bg-purple-100 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400';
                                borderClass = 'border-l-4 border-purple-500';
                            }
                            
                            // Check importance/tags via title or message content (Mock logic)
                            if (item.title.toLowerCase().includes('urgent')) {
                                urgentBadge = '<span class="ml-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-[10px] uppercase font-bold px-1.5 py-0.5 rounded">Urgent</span>';
                            }

                            const isRead = parseInt(item.is_read) === 1;
                            const opacityClass = isRead ? 'opacity-90 hover:opacity-100' : '';
                            const bgClass = isRead ? 'bg-slate-50 dark:bg-[#131221]' : 'bg-white dark:bg-card-dark';
                            if(isRead) borderClass = 'border border-slate-200 dark:border-slate-800/50'; // Override bold border for read items

                            const html = `
                                <div class="group relative flex flex-col md:flex-row gap-4 ${bgClass} p-5 rounded-xl ${borderClass} shadow-sm hover:shadow-md transition-all hover:bg-slate-50 dark:hover:bg-[#26334d] cursor-pointer ${opacityClass}" onclick="${item.action_url ? `window.location.href='${item.action_url}'` : ''}">
                                    <div class="absolute top-4 right-4 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        ${!isRead ? `<button onclick="event.stopPropagation(); markRead(${item.id})" class="p-1 hover:bg-slate-200 dark:hover:bg-white/10 rounded text-slate-400 hover:text-slate-600 dark:hover:text-white" title="Mark as read"><span class="material-symbols-outlined text-[20px]">mark_email_read</span></button>` : ''}
                                        <button onclick="event.stopPropagation(); deleteNotif(${item.id})" class="p-1 hover:bg-slate-200 dark:hover:bg-white/10 rounded text-slate-400 hover:text-red-400" title="Delete"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                                    </div>
                                    <div class="flex items-start gap-4 flex-1">
                                        <div class="flex items-center justify-center rounded-lg ${colorClass} shrink-0 size-12">
                                            <span class="material-symbols-outlined ${!isRead ? 'icon-fill' : ''}">${icon}</span>
                                        </div>
                                        <div class="flex flex-col gap-1.5 flex-1 pr-8">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <p class="text-slate-900 dark:text-white text-base font-semibold leading-tight">${item.title}</p>
                                                ${urgentBadge}
                                                ${!isRead ? '<span class="size-2 rounded-full bg-primary animate-pulse"></span>' : ''}
                                            </div>
                                            <div class="text-slate-600 dark:text-[#9894c7] text-sm font-normal leading-relaxed">${item.message}</div>
                                            <p class="text-slate-500 dark:text-slate-400 text-xs mt-1">${formatTime(item.created_at)}</p>
                                        </div>
                                    </div>
                                </div>
                            `;
                            container.append(html);
                        });
                    }
                });
            }

            // Helper: Format Time specifically (since we group by date now)
            function formatTime(dateString) {
                const date = new Date(dateString);
                const now = new Date();
                const diffMs = now - date;
                const diffMins = Math.round(diffMs / 60000);
                
                if(diffMins < 60) return `${diffMins} minutes ago`;
                
                // Return AM/PM time
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }

            // Global Actions
            window.markRead = function(id) {
                $.post('../api/admin_notification_action.php', { action: 'mark_read', id: id }, function(res) {
                    if(res.success) loadNotifications();
                }, 'json');
            };
            
            window.deleteNotif = function(id) {
                if(!confirm('Delete this notification?')) return;
                $.post('../api/admin_notification_action.php', { action: 'delete', id: id }, function(res) {
                    if(res.success) loadNotifications();
                }, 'json');
            };
        });
    </script>
</body>
</html>
