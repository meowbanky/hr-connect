<?php
session_start();
// Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff')) {
    header('Location: /admin/login');
    exit;
}

require_once __DIR__ . '/../includes/settings.php';
$pageTitle = 'Edit Job';

// Verify ID
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($job_id <= 0) {
    header('Location: /admin/jobs.php');
    exit;
}

// Fetch Job Details
try {
    $stmt = $pdo->prepare("SELECT * FROM job_postings WHERE id = ?");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        header('Location: /admin/jobs.php?error=notfound');
        exit;
    }

    // Fetch dynamic attributes
    $departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $empTypes = $pdo->query("SELECT * FROM employment_types ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo isset($_SESSION['theme_mode']) && $_SESSION['theme_mode'] === 'dark' ? 'dark' : 'light'; ?>">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Edit Job - HR Connect</title>
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

        <div class="flex-1 overflow-y-auto p-6 md:p-10 flex flex-col gap-8">
            <div class="w-full max-w-[1200px] mx-auto flex flex-col gap-6">
                
                <!-- Page Header with Actions -->
                <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                    <div class="flex flex-col gap-2 max-w-2xl">
                        <h1 class="text-3xl md:text-4xl font-black tracking-tight text-slate-900 dark:text-white">Edit Job Posting</h1>
                        <p class="text-slate-500 dark:text-slate-400 text-base">Update the details of the job posting below.</p>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <button id="saveDraftBtn" class="flex items-center justify-center h-10 px-4 rounded-lg bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm">
                            Save as Draft
                        </button>
                        <button id="updateBtn" class="flex items-center justify-center h-10 px-6 rounded-lg bg-primary hover:bg-blue-700 text-white text-sm font-bold shadow-md shadow-blue-500/20 transition-all hover:translate-y-[-1px]">
                            Update Job
                        </button>
                    </div>
                </div>

                <!-- Form Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column: Main Info (Span 2) -->
                    <div class="lg:col-span-2 flex flex-col gap-6">
                        <!-- Card: Basic Details -->
                        <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6 flex flex-col gap-6">
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">description</span>
                                Job Details
                            </h2>
                            <!-- Job Title -->
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-semibold text-slate-900 dark:text-slate-200">Job Title <span class="text-red-500">*</span></span>
                                <input name="title" value="<?php echo htmlspecialchars($job['title']); ?>" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder-slate-400 h-12 px-4 transition-all" placeholder="e.g. Senior Product Designer" type="text"/>
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Department Select -->
                                <label class="flex flex-col gap-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-slate-900 dark:text-slate-200">Department</span>
                                        <button type="button" id="addDeptBtn" class="text-xs font-semibold text-primary hover:text-blue-700 transition-colors flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[16px]">add_circle</span> Add New
                                        </button>
                                    </div>
                                    <div class="relative">
                                        <select name="department_id" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary h-12 pl-4 pr-10 appearance-none transition-all">
                                            <option value="">Select Department</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?php echo $dept['id']; ?>" <?php echo $job['department_id'] == $dept['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                            <span class="material-symbols-outlined text-sm">expand_more</span>
                                        </div>
                                    </div>
                                </label>
                                <!-- Employment Type -->
                                <label class="flex flex-col gap-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-slate-900 dark:text-slate-200">Employment Type</span>
                                        <button type="button" id="addEmpTypeBtn" class="text-xs font-semibold text-primary hover:text-blue-700 transition-colors flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[16px]">add_circle</span> Add New
                                        </button>
                                    </div>
                                    <div class="relative">
                                        <select name="employment_type_id" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary h-12 pl-4 pr-10 appearance-none transition-all">
                                            <option value="">Select Employment Type</option>
                                             <?php foreach ($empTypes as $type): ?>
                                                <option value="<?php echo $type['id']; ?>" <?php echo $job['employment_type_id'] == $type['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($type['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                            <span class="material-symbols-outlined text-sm">expand_more</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            
                            <!-- Location & Experience -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <label class="flex flex-col gap-2">
                                    <span class="text-sm font-semibold text-slate-900 dark:text-slate-200">Location</span>
                                    <input name="location" value="<?php echo htmlspecialchars($job['location']); ?>" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder-slate-400 h-12 px-4 transition-all" placeholder="e.g. Lagos, Nigeria (Remote)" type="text"/>
                                </label>
                                <label class="flex flex-col gap-2">
                                    <span class="text-sm font-semibold text-slate-900 dark:text-slate-200">Experience Level</span>
                                    <div class="relative">
                                        <select name="experience_level" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary h-12 pl-4 pr-10 appearance-none transition-all">
                                            <option value="">Select Level</option>
                                            <?php 
                                            $levels = ["Entry Level", "Mid Level", "Senior Level", "Director"];
                                            foreach($levels as $level): 
                                            ?>
                                            <option value="<?php echo $level; ?>" <?php echo $job['experience_level'] === $level ? 'selected' : ''; ?>><?php echo $level; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                            <span class="material-symbols-outlined text-sm">expand_more</span>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <!-- Salary -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <label class="flex flex-col gap-2">
                                    <span class="text-sm font-semibold text-slate-900 dark:text-slate-200">Min Salary</span>
                                    <input name="min_salary" value="<?php echo $job['min_salary']; ?>" type="number" step="0.01" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder-slate-400 h-12 px-4 transition-all" placeholder="0.00"/>
                                </label>
                                <label class="flex flex-col gap-2">
                                    <span class="text-sm font-semibold text-slate-900 dark:text-slate-200">Max Salary</span>
                                    <input name="max_salary" value="<?php echo $job['max_salary']; ?>" type="number" step="0.01" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder-slate-400 h-12 px-4 transition-all" placeholder="0.00"/>
                                </label>
                            </div>
                        </div>

                        <!-- Card: Rich Text Description -->
                        <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6 flex flex-col gap-6 flex-1">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary">article</span>
                                    Description
                                </h2>
                                <textarea name="description" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-300 dark:border-slate-700 rounded-lg p-4 min-h-[200px] text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder-slate-400 resize-y" placeholder="Enter job description..."><?php echo htmlspecialchars($job['description']); ?></textarea>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary">list_alt</span>
                                    Requirements
                                </h2>
                                <textarea name="requirements" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-300 dark:border-slate-700 rounded-lg p-4 min-h-[150px] text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder-slate-400 resize-y" placeholder="List job requirements..."><?php echo htmlspecialchars($job['requirements']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Meta Data (Span 1) -->
                    <div class="lg:col-span-1 flex flex-col gap-6">
                        <!-- Card: Schedule -->
                        <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6 flex flex-col gap-6">
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">calendar_month</span>
                                Schedule
                            </h2>
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-semibold text-slate-900 dark:text-slate-200">Open Date</span>
                                <input name="open_date" value="<?php echo $job['open_date']; ?>" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary h-12 px-4 transition-all" type="date"/>
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-semibold text-slate-900 dark:text-slate-200">Close Date</span>
                                <input name="close_date" value="<?php echo $job['application_deadline']; ?>" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary h-12 px-4 transition-all" type="date"/>
                            </label>
                        </div>
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
                        select.empty().append(`<option value="">Select ${label}</option>`);
                        const items = data[type === 'dept' ? 'departments' : 'employment_types'];
                        items.forEach(item => {
                            select.append(`<option value="${item.id}">${item.name}</option>`);
                        });
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

            $('#addDeptBtn').click(function() { handleAddNew('dept', '/api/create_department.php', 'department_id', 'Department'); });
            $('#addEmpTypeBtn').click(function() { handleAddNew('emp', '/api/create_employment_type.php', 'employment_type_id', 'Employment Type'); });

            // Consolidated Submit Handler
            function updateJob(status, btn) {
                const originalText = btn.html();
                
                // Basic Validation
                const title = $('input[name="title"]').val();
                if(!title) {
                    Swal.fire('Error', 'Job Title is required', 'error');
                    return;
                }

                const formData = {
                    id: <?php echo $job_id; ?>,
                    title: title,
                    department_id: $('select[name="department_id"]').val(),
                    employment_type_id: $('select[name="employment_type_id"]').val(),
                    location: $('input[name="location"]').val(),
                    experience_level: $('select[name="experience_level"]').val(),
                    min_salary: $('input[name="min_salary"]').val(),
                    max_salary: $('input[name="max_salary"]').val(),
                    description: $('textarea[name="description"]').val(),
                    requirements: $('textarea[name="requirements"]').val(),
                    open_date: $('input[name="open_date"]').val(),
                    application_deadline: $('input[name="close_date"]').val(),
                    status: status // 'published' or 'draft'
                };

                btn.prop('disabled', true).html('<span class="material-symbols-outlined animate-spin text-sm">refresh</span> Updating...');

                $.ajax({
                    url: '/api/update_job.php',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if(response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: status === 'published' ? 'Published!' : 'Saved!',
                                text: 'Job updated successfully.',
                                confirmButtonText: 'Back to Jobs'
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

            $('#updateBtn').click(function() {
                updateJob('published', $(this));
            });
            
            $('#saveDraftBtn').click(function() {
                updateJob('draft', $(this));
            });
        });
    </script>
</body>
</html>
