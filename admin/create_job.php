<?php
session_start();
// Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || 
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff' && $_SESSION['user_role'] !== 'superadmin')) {
    header('Location: /admin/login');
    exit;
}

require_once __DIR__ . '/../includes/settings.php';
$pageTitle = 'Create New Job';

// Fetch dynamic attributes
try {
    $departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $empTypes = $pdo->query("SELECT * FROM employment_types ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error gracefully or log
    $departments = [];
    $empTypes = [];
}
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo isset($_SESSION['theme_mode']) && $_SESSION['theme_mode'] === 'dark' ? 'dark' : 'light'; ?>">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Create New Job - HR Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link href="/assets/css/style.css" rel="stylesheet"/>

    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; font-size: 24px; line-height: 1; }
         /* Fix SweetAlert2 Buttons visibility conflict */
        div:where(.swal2-container) button:where(.swal2-styled).swal2-confirm {
            background-color: #3b82f6 !important; /* Blue-500 */
            color: white !important;
        }
        div:where(.swal2-container) button:where(.swal2-styled).swal2-cancel {
            background-color: #64748b !important; /* Slate-500 */
            color: white !important;
        }
    </style>
     <?php echo get_theme_css(); ?>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-800 dark:text-slate-100 flex flex-col md:flex-row md:h-screen md:overflow-hidden font-display antialiased min-h-screen">
    
    <!-- Sidebar -->
    <?php include_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="flex-1 flex flex-col w-full md:h-full md:overflow-hidden relative">
        <!-- Header -->
        <?php include_once __DIR__ . '/../includes/admin_header.php'; ?>

        <div class="flex-1 overflow-y-auto p-6 md:p-10 flex flex-col gap-8 bg-slate-50 dark:bg-slate-900/50">
            <!-- Reuse content from reference HTML -->
            <div class="w-full max-w-4xl mx-auto flex flex-col gap-8">
                
                <!-- Page Header with Actions -->
                <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                    <div class="flex flex-col gap-2 max-w-2xl">
                        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Create Job Template</h1>
                        <p class="text-slate-500 dark:text-slate-400 text-sm">Define the core details for a job role. Use this template to launch recruitment cycles later.</p>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <button id="publishBtn" class="flex items-center justify-center py-2.5 px-6 rounded-lg bg-primary hover:bg-blue-700 text-white text-sm font-semibold shadow-md shadow-primary/20 transition-all hover:translate-y-[-1px]">
                            Create Template
                        </button>
                    </div>
                </div>

                <!-- Form Content -->
                <div class="flex flex-col gap-6">
                    
                    <!-- Card: Job Details -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 flex flex-col gap-8">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-wide">
                            <span class="material-symbols-outlined text-primary text-xl">description</span>
                            Job Details
                        </h2>
                        
                        <!-- Job Title -->
                        <div class="flex flex-col gap-2">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Job Title <span class="text-red-500">*</span></label>
                            <input name="title" class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder-slate-400 h-12 px-4 transition-all text-sm" placeholder="e.g. Senior Product Designer" type="text"/>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Department Select -->
                            <div class="flex flex-col gap-2">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Department</label>
                                    <button type="button" id="addDeptBtn" class="text-xs font-bold text-primary hover:text-blue-700 transition-colors flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[16px]">add_circle</span> Add New
                                    </button>
                                </div>
                                <div class="relative">
                                    <select name="department_id" class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary h-12 pl-4 pr-10 appearance-none transition-all text-sm">
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                        <span class="material-symbols-outlined text-sm">expand_more</span>
                                    </div>
                                </div>
                            </div>
                            <!-- Employment Type -->
                            <div class="flex flex-col gap-2">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Employment Type</label>
                                    <button type="button" id="addEmpTypeBtn" class="text-xs font-bold text-primary hover:text-blue-700 transition-colors flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[16px]">add_circle</span> Add New
                                    </button>
                                </div>
                                <div class="relative">
                                    <select name="employment_type_id" class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary h-12 pl-4 pr-10 appearance-none transition-all text-sm">
                                        <option value="">Select Employment Type</option>
                                            <?php foreach ($empTypes as $type): ?>
                                            <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                        <span class="material-symbols-outlined text-sm">expand_more</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Location & Experience -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Location</label>
                                <input name="location" class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder-slate-400 h-12 px-4 transition-all text-sm" placeholder="e.g. Lagos, Nigeria" type="text"/>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Experience Level</label>
                                <div class="relative">
                                    <select name="experience_level" class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary h-12 pl-4 pr-10 appearance-none transition-all text-sm">
                                        <option value="">Select Level</option>
                                        <option value="Entry Level">Entry Level</option>
                                        <option value="Mid Level">Mid Level</option>
                                        <option value="Senior Level">Senior Level</option>
                                        <option value="Director">Director</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                        <span class="material-symbols-outlined text-sm">expand_more</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Salary -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Min Salary</label>
                                <input name="min_salary" type="number" step="0.01" class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder-slate-400 h-12 px-4 transition-all text-sm" placeholder="0.00"/>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Max Salary</label>
                                <input name="max_salary" type="number" step="0.01" class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder-slate-400 h-12 px-4 transition-all text-sm" placeholder="0.00"/>
                            </div>
                        </div>
                    </div>

                    <!-- Card: Description -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 flex flex-col gap-6">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-wide">
                            <span class="material-symbols-outlined text-primary text-xl">subject</span>
                            Description
                        </h2>
                        <textarea name="description" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-lg p-4 min-h-[160px] text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder-slate-400 resize-y text-sm" placeholder="Enter job description..."></textarea>
                    </div>

                    <!-- Card: Requirements -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 flex flex-col gap-6">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-wide">
                            <span class="material-symbols-outlined text-primary text-xl">checklist</span>
                            Requirements
                        </h2>
                        <textarea name="requirements" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-lg p-4 min-h-[160px] text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder-slate-400 resize-y text-sm" placeholder="List job requirements..."></textarea>
                    </div>

                </div>
            </div>
        </div>
    <!-- Footer -->
    <?php include_once __DIR__ . '/../includes/admin_footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Helper to add new item
            const handleAddNew = (type, url, selectName, label) => {
                Swal.fire({
                    title: `Add New ${label}`,
                    input: 'text',
                    inputLabel: `${label} Name`,
                    inputPlaceholder: `Enter ${label.toLowerCase()} name`,
                    showCancelButton: true,
                    confirmButtonText: 'Add',
                    showLoaderOnConfirm: true,
                    preConfirm: (name) => {
                        if (!name) {
                            Swal.showValidationMessage(`Please enter a name`);
                            return false;
                        }
                        return $.ajax({
                            url: url,
                            method: 'POST',
                            data: { name: name }
                        }).then(response => {
                            if (!response.success) {
                                throw new Error(response.message);
                            }
                            return response;
                        }).catch(error => {
                            Swal.showValidationMessage(`Request failed: ${error}`);
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        const data = result.value;
                        const select = $(`select[name="${selectName}"]`);
                        
                        // Clear and reload options
                        select.empty().append(`<option value="">Select ${label}</option>`);
                        
                        const items = data[type === 'dept' ? 'departments' : 'employment_types'];
                        items.forEach(item => {
                            select.append(`<option value="${item.id}">${item.name}</option>`);
                        });

                        // Select the newly added item
                        select.val(data.new_id);

                        Swal.fire({
                            icon: 'success',
                            title: 'Added!',
                            text: `${label} has been added successfully.`,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                });
            };

            // Event Listeners for Add Buttons
            $('#addDeptBtn').click(function() {
                handleAddNew('dept', '/api/create_department.php', 'department_id', 'Department');
            });

            $('#addEmpTypeBtn').click(function() {
                handleAddNew('emp', '/api/create_employment_type.php', 'employment_type_id', 'Employment Type');
            });

            // Publish Handler
            // Consolidated Submit Handler
            function submitJob(btn) {
                const originalText = btn.html();
                
                // Basic Validation
                const title = $('input[name="title"]').val();
                if(!title) {
                    Swal.fire('Error', 'Job Title is required', 'error');
                    return;
                }

                const formData = {
                    title: title,
                    department_id: $('select[name="department_id"]').val(),
                    employment_type_id: $('select[name="employment_type_id"]').val(),
                    location: $('input[name="location"]').val(),
                    experience_level: $('select[name="experience_level"]').val(),
                    min_salary: $('input[name="min_salary"]').val(),
                    max_salary: $('input[name="max_salary"]').val(),
                    description: $('textarea[name="description"]').val(),
                    requirements: $('textarea[name="requirements"]').val()
                };

                btn.prop('disabled', true).html('<span class="material-symbols-outlined animate-spin text-sm">refresh</span> Saving...');

                $.ajax({
                    url: '/api/create_job_template.php',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if(response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Template Created!',
                                text: 'You can now launch recruitment cycles from this template.',
                                confirmButtonText: 'View Job List'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = '/admin/jobs';
                                }
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                            btn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'An error occurred.', 'error');
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            }

            $('#publishBtn').click(function() {
                submitJob($(this));
            });
        });
    </script>
</body>
</html>
