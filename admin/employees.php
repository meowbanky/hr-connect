<?php
// admin/employees.php
session_start();

// Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || 
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff' && $_SESSION['user_role'] !== 'superadmin')) {
    header('Location: /admin/login');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

$pageTitle = 'Staff Directory - HR Management';
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
        <div class="mx-auto max-w-7xl flex flex-col gap-6">
            
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a class="hover:text-primary transition-colors" href="/admin">Dashboard</a>
                <span class="material-symbols-outlined" style="font-size: 16px;">chevron_right</span>
                <span class="font-medium text-gray-900 dark:text-white">Staff Directory</span>
            </div>

            <!-- Page Heading & Actions -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Staff Directory</h2>
                    <p class="text-gray-500 dark:text-gray-400">Manage active employees, view profiles, and update employment statuses.</p>
                </div>
                <div class="flex gap-3">
                    <button class="flex items-center justify-center gap-2 px-4 h-10 rounded-lg border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 font-medium text-sm transition-colors shadow-sm">
                        <span class="material-symbols-outlined" style="font-size: 20px;">download</span>
                        <span>Export</span>
                    </button>
                    <!-- Link to Full Page Add Employee -->
                    <a href="/admin/add_employee.php" class="flex items-center justify-center gap-2 px-4 h-10 rounded-lg bg-primary text-white hover:bg-primary/90 font-medium text-sm transition-colors shadow-sm shadow-primary/30">
                        <span class="material-symbols-outlined" style="font-size: 20px;">add</span>
                        <span>Add New Staff</span>
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl p-5 border border-border-light dark:border-border-dark shadow-sm flex flex-col gap-1">
                    <div class="flex justify-between items-start">
                        <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">Total Employees</p>
                        <span class="material-symbols-outlined text-primary bg-primary/10 p-1.5 rounded-lg" style="font-size: 20px;">groups</span>
                    </div>
                    <div class="flex items-baseline gap-2 mt-2">
                        <h3 id="statTotal" class="text-2xl font-bold text-gray-900 dark:text-white">...</h3>
                        <!-- Optional Trend -->
                    </div>
                </div>
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl p-5 border border-border-light dark:border-border-dark shadow-sm flex flex-col gap-1">
                    <div class="flex justify-between items-start">
                        <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">Active Staff</p>
                        <span class="material-symbols-outlined text-green-600 bg-green-100 dark:bg-green-900/20 dark:text-green-400 p-1.5 rounded-lg" style="font-size: 20px;">check_circle</span>
                    </div>
                    <div class="flex items-baseline gap-2 mt-2">
                        <h3 id="statActive" class="text-2xl font-bold text-gray-900 dark:text-white">...</h3>
                    </div>
                </div>
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl p-5 border border-border-light dark:border-border-dark shadow-sm flex flex-col gap-1">
                    <div class="flex justify-between items-start">
                        <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">Doctors</p>
                        <span class="material-symbols-outlined text-orange-500 bg-orange-100 dark:bg-orange-900/20 dark:text-orange-400 p-1.5 rounded-lg" style="font-size: 20px;">stethoscope</span>
                    </div>
                    <div class="flex items-baseline gap-2 mt-2">
                        <h3 id="statDoctors" class="text-2xl font-bold text-gray-900 dark:text-white">...</h3>
                    </div>
                </div>
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl p-5 border border-border-light dark:border-border-dark shadow-sm flex flex-col gap-1">
                    <div class="flex justify-between items-start">
                        <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">Nurses</p>
                        <span class="material-symbols-outlined text-blue-500 bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 p-1.5 rounded-lg" style="font-size: 20px;">medication</span>
                    </div>
                    <div class="flex items-baseline gap-2 mt-2">
                        <h3 id="statNurses" class="text-2xl font-bold text-gray-900 dark:text-white">...</h3>
                    </div>
                </div>
            </div>

            <!-- Main Table Card -->
            <div class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl shadow-sm flex flex-col">
                <!-- Table Filters Toolbar -->
                <div class="p-4 border-b border-border-light dark:border-border-dark flex flex-col sm:flex-row gap-4 justify-between items-center">
                    <!-- Search -->
                    <div class="relative w-full sm:w-auto sm:min-w-[300px]">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-outlined" style="font-size: 20px;">search</span>
                        <input id="staffSearch" class="h-10 pl-10 pr-4 w-full rounded-lg bg-background-light dark:bg-[#2a2839] border border-transparent focus:border-primary focus:ring-1 focus:ring-primary text-sm text-gray-900 dark:text-white placeholder-gray-400 transition-all" placeholder="Search by name, ID or email..." type="text"/>
                    </div>
                    <!-- Filter Dropdowns -->
                    <div class="flex gap-3 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                        <div class="relative group">
                            <select id="deptFilter" class="appearance-none cursor-pointer flex items-center gap-2 pl-3 pr-8 h-10 rounded-lg border border-border-light dark:border-border-dark bg-white dark:bg-transparent text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 text-sm font-medium focus:ring-1 focus:ring-primary focus:border-primary transition-colors">
                                <option value="">All Departments</option>
                            </select>
                            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-outlined pointer-events-none" style="font-size: 20px;">expand_more</span>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="overflow-x-auto min-h-[400px]">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-white/5 border-b border-border-light dark:border-border-dark">
                                <th class="p-4 w-10">
                                    <input class="rounded border-gray-300 text-primary focus:ring-primary bg-white dark:bg-transparent" type="checkbox"/>
                                </th>
                                <th class="p-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Employee</th>
                                <th class="p-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Grade/Step</th>
                                <th class="p-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role & Dept</th>
                                <th class="p-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date Joined</th>
                                <th class="p-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="p-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="staffTableBody" class="divide-y divide-border-light dark:divide-border-dark">
                            <!-- Dynamic Rows -->
                             <tr>
                                <td colspan="7" class="p-12 text-center text-slate-500 flex flex-col items-center gap-2">
                                    <span class="material-symbols-outlined animate-spin text-3xl text-primary">progress_activity</span>
                                    <span>Loading staff data...</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="p-4 border-t border-border-light dark:border-border-dark flex flex-col sm:flex-row gap-4 items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Showing <span class="font-medium text-gray-900 dark:text-white" id="countStart">0</span> to <span class="font-medium text-gray-900 dark:text-white" id="countEnd">0</span> results
                    </p>
                    <div class="flex gap-2">
                        <button class="px-3 py-1.5 text-sm font-medium text-gray-500 bg-white dark:bg-transparent border border-border-light dark:border-border-dark rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 disabled:opacity-50" disabled="">Previous</button>
                        <button class="px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-transparent border border-border-light dark:border-border-dark rounded-lg hover:bg-gray-50 dark:hover:bg-white/5">Next</button>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    fetchDepartments();
    fetchStaff();

    // Event Listeners
    let debounceTimer;
    document.getElementById('staffSearch').addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => fetchStaff(), 300);
    });

    document.getElementById('deptFilter').addEventListener('change', () => {
        fetchStaff();
    });
});

async function fetchDepartments() {
    try {
        const response = await fetch('/api/staff_actions.php?action=fetch_departments');
        const result = await response.json();
        if (result.success) {
            const select = document.getElementById('deptFilter');
            result.data.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept;
                option.textContent = dept;
                select.appendChild(option);
            });
        }
    } catch (e) {
        console.error("Error loading departments", e);
    }
}

async function fetchStaff() {
    const tbody = document.getElementById('staffTableBody');
    const search = document.getElementById('staffSearch').value;
    const dept = document.getElementById('deptFilter').value;
    
    // Show loading if needed, or simple opacity
    tbody.style.opacity = '0.5';
    
    try {
        const response = await fetch(`/api/staff_actions.php?action=fetch&search=${encodeURIComponent(search)}&department=${encodeURIComponent(dept)}`);
        const result = await response.json();
        
        tbody.style.opacity = '1';

        if (result.success && result.data.length > 0) {
            
            // Calculate Stats (Note: This logic is client-side only based on VIEWED results currently. 
            // Ideally backend returns aggregates, but for now this works for filtered view)
            const total = result.data.length;
            const active = result.data.filter(s => (s.status || 'active').toLowerCase() === 'active').length;
            const doctors = result.data.filter(s => (s.worker_category||'').toLowerCase().includes('doctor')).length;
            const nurses = result.data.filter(s => (s.worker_category||'').toLowerCase().includes('nurse')).length;
            
            document.getElementById('statTotal').innerText = total;
            document.getElementById('statActive').innerText = active;
            document.getElementById('statDoctors').innerText = doctors;
            document.getElementById('statNurses').innerText = nurses;
            
            // Update counts
            document.getElementById('countStart').innerText = total > 0 ? 1 : 0;
            document.getElementById('countEnd').innerText = total;

            tbody.innerHTML = result.data.map(staff => {
                const status = (staff.status || 'Active').toLowerCase();
                let statusBadge = '';
                if (status === 'active') statusBadge = 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 border-green-200 dark:border-green-800';
                else if (status === 'inactive') statusBadge = 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-400 border-gray-200 dark:border-gray-700';
                else if (status === 'on_leave') statusBadge = 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800';
                else statusBadge = 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 border-red-200 dark:border-red-800';

                return `
                <tr class="group hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                    <td class="p-4">
                         <input class="rounded border-gray-300 text-primary focus:ring-primary bg-white dark:bg-transparent opacity-0 group-hover:opacity-100 transition-opacity" type="checkbox"/>
                    </td>
                    <td class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="size-10 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold text-sm overflow-hidden">
                                ${staff.profile_image 
                                    ? `<img src="${staff.profile_image}" class="w-full h-full object-cover">` 
                                    : (staff.full_name.charAt(0) + (staff.full_name.split(' ')[1] ? staff.full_name.split(' ')[1].charAt(0) : ''))
                                }
                            </div>
                            <div class="flex flex-col">
                                <a href="/admin/staff_details.php?id=${staff.id}" class="text-sm font-semibold text-gray-900 dark:text-white hover:text-primary transition-colors hover:underline">
                                    ${staff.full_name}
                                </a>
                                <span class="text-xs text-gray-500 dark:text-gray-400">${staff.email}</span>
                            </div>
                        </div>
                    </td>
                    <td class="p-4">
                        <span class="font-mono text-xs font-medium px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                            ${staff.grade_level || '-'} / ${staff.step || '-'}
                        </span>
                    </td>
                    <td class="p-4">
                        <div class="flex flex-col">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">${staff.job_title}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">${staff.department}</span>
                        </div>
                    </td>
                    <td class="p-4">
                        <span class="text-sm text-gray-600 dark:text-gray-300">${staff.start_date || 'N/A'}</span>
                    </td>
                    <td class="p-4">
                        <button onclick="changeStatus(${staff.id}, '${status}')" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border ${statusBadge} hover:opacity-80 transition-opacity cursor-pointer">
                            <span class="size-1.5 rounded-full bg-current"></span> ${status.charAt(0).toUpperCase() + status.replace('_', ' ').slice(1)}
                        </button>
                    </td>
                    <td class="p-4 text-right">
                        <div class="relative">
                            <button onclick="changeStatus(${staff.id}, '${status}')" class="text-gray-400 hover:text-primary p-1 rounded-full hover:bg-gray-100 dark:hover:bg-white/10 transition-colors" title="Change Status">
                                <span class="material-symbols-outlined" style="font-size: 20px;">edit_attributes</span>
                            </button>
                        </div>
                    </td>
                </tr>
            `}).join('');
        } else {
             tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="p-12 text-center text-slate-500">
                        <div class="flex flex-col items-center gap-3">
                            <div class="size-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                <span class="material-symbols-outlined text-2xl">search_off</span>
                            </div>
                            <p>No staff members found matching your criteria.</p>
                             <button onclick="document.getElementById('staffSearch').value=''; document.getElementById('deptFilter').value=''; fetchStaff();" class="text-primary text-sm font-bold hover:underline">Clear Filters</button>
                        </div>
                    </td>
                </tr>
            `;
            document.getElementById('statTotal').innerText = '0';
            document.getElementById('statActive').innerText = '0';
        }
    } catch (error) {
        console.error('Error fetching staff:', error);
    }
}

function changeStatus(id, currentStatus) {
    Swal.fire({
        title: 'Update Status',
        input: 'select',
        inputOptions: {
            'active': 'Active',
            'on_leave': 'On Leave',
            'inactive': 'Inactive',
            'terminated': 'Terminated'
        },
        inputValue: currentStatus,
        showCancelButton: true,
        confirmButtonText: 'Update',
        showLoaderOnConfirm: true,
        preConfirm: (status) => {
            return $.post('/api/staff_actions.php', {
                action: 'update_status',
                staff_id: id,
                status: status
            }).fail(() => {
                Swal.showValidationMessage('Request failed');
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Updated!',
                text: 'Staff status has been updated.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
            fetchStaff(); // Refresh list
        }
    });
}

</script>

</body>
</html>
