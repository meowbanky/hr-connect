<?php
// admin/add_employee.php
session_start();

// Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || 
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff' && $_SESSION['user_role'] !== 'superadmin')) {
    header('Location: /admin/login');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

$pageTitle = 'Add New Staff - HR Management';
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo ($_SESSION['theme_mode'] ?? '') === 'dark' ? 'dark' : 'light'; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $pageTitle; ?></title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

<body class="bg-gray-50 dark:bg-slate-900 flex min-h-screen">

<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

<main class="flex-1 flex flex-col h-screen overflow-hidden">
    <?php include __DIR__ . '/../includes/admin_header.php'; ?>

    <div class="flex-1 overflow-y-auto p-6 md:p-8">
        <div class="max-w-5xl mx-auto flex flex-col gap-6">
            
            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-2 text-slate-500 text-sm font-medium">
                    <a class="hover:text-primary transition-colors" href="/admin">Dashboard</a>
                    <span class="material-symbols-outlined text-xs">chevron_right</span>
                    <a class="hover:text-primary transition-colors" href="/admin/employees.php">Staff Directory</a>
                    <span class="material-symbols-outlined text-xs">chevron_right</span>
                    <span class="text-slate-900 dark:text-slate-100">Add New Staff</span>
                </div>
                
                <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mt-2">
                    <div class="flex flex-col gap-1">
                        <h1 class="text-slate-900 dark:text-white text-3xl font-black leading-tight tracking-tight">Onboard New Employee</h1>
                        <p class="text-slate-500 dark:text-slate-400 text-base">Complete the form below to add a new staff member to the system.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <form id="addStaffForm" enctype="multipart/form-data" class="flex flex-col">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="p-6 md:p-8 space-y-8">
                        
                        <!-- 1. Personal Info -->
                        <section>
                            <h3 class="flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-700 pb-3 mb-4">
                                <span class="material-symbols-outlined text-primary">person</span> Personal Information
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Full Name <span class="text-red-500">*</span></label>
                                    <input required name="full_name" class="rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700/50" placeholder="e.g. Dr. Sarah Jenkins" type="text"/>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Email Address <span class="text-red-500">*</span></label>
                                    <input required name="email" class="rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700/50" placeholder="sarah.j@company.com" type="email"/>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Phone Number</label>
                                    <input name="phone" class="rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700/50" placeholder="+234..." type="tel"/>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Date of Birth</label>
                                    <input name="dob" type="date" class="rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700/50"/>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Gender</label>
                                    <select name="gender" class="rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700/50">
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                            </div>
                        </section>

                        <!-- 2. Employment Details -->
                        <section>
                            <h3 class="flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-700 pb-3 mb-4">
                                <span class="material-symbols-outlined text-primary">badge</span> Employment Details
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Job Title <span class="text-red-500">*</span></label>
                                    <input required name="job_title" class="rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700/50" type="text"/>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Department <span class="text-red-500">*</span></label>
                                    <select required name="department" class="rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700/50">
                                        <option value="">Select Department</option>
                                        <?php 
                                        $depts = ['Administration', 'Internal Medicine', 'Surgery', 'Pediatrics', 'Obstetrics & Gynaecology', 'Nursing Services', 'Pharmacy', 'Laboratory', 'Radiology', 'Accounts'];
                                        foreach($depts as $d) echo "<option value='$d'>$d</option>";
                                        ?>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Worker Category</label>
                                    <select name="worker_category" class="rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700/50">
                                        <option value="Medical Doctor">Medical Doctor</option>
                                        <option value="Nurse">Nurse</option>
                                        <option value="Pharmacist">Pharmacist</option>
                                        <option value="Laboratory Scientist">Laboratory Scientist</option>
                                        <option value="Admin">Admin</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Grade Level</label>
                                    <select name="grade_level" class="rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700/50">
                                        <option value="">Select Grade</option>
                                        <option value="CONMESS">CONMESS</option>
                                        <option value="CONHESS">CONHESS</option>
                                        <option value="CONRAISS">CONRAISS</option>
                                        <?php for($i=1; $i<=17; $i++) echo "<option value='GL ".str_pad($i, 2, '0', STR_PAD_LEFT)."'>GL ".str_pad($i, 2, '0', STR_PAD_LEFT)."</option>"; ?>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Step</label>
                                    <select name="step" class="rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700/50">
                                        <option value="">Select Step</option>
                                        <?php for($i=1; $i<=15; $i++) echo "<option value='$i'>Step $i</option>"; ?>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Date of Assumption of Duty</label>
                                    <input name="start_date" type="date" class="rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700/50"/>
                                </div>
                            </div>
                        </section>

                        <!-- 3. Emergency Contact -->
                        <section>
                            <h3 class="flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-700 pb-3 mb-4">
                                <span class="material-symbols-outlined text-primary">emergency</span> Emergency Contact
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Contact Name</label>
                                    <input name="emergency_contact_name" class="rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700/50" type="text"/>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Relationship</label>
                                    <input name="emergency_contact_relationship" class="rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700/50" placeholder="e.g. Spouse, Brother" type="text"/>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Contact Phone</label>
                                    <input name="emergency_contact_phone" class="rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700/50" type="tel"/>
                                </div>
                            </div>
                        </section>
                        
                        <!-- 4. Educational History -->
                        <section>
                            <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-700 pb-3 mb-4">
                                <h3 class="flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-white">
                                    <span class="material-symbols-outlined text-primary">school</span> School History
                                </h3>
                                <button type="button" onclick="addEducationRow()" class="text-primary text-sm font-bold hover:underline flex items-center gap-1">
                                    <span class="material-symbols-outlined">add</span> Add School
                                </button>
                            </div>
                            <div id="educationContainer" class="space-y-4">
                                <!-- Dynamic Rows -->
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-slate-50 dark:bg-slate-700/30 p-4 rounded-lg border border-slate-100 dark:border-slate-700 relative group">
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold text-slate-500">School Name</label>
                                        <input name="edu_school[]" class="rounded-md border-slate-300 dark:border-slate-600 text-sm" type="text"/>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold text-slate-500">Qualification</label>
                                        <input name="edu_degree[]" class="rounded-md border-slate-300 dark:border-slate-600 text-sm" placeholder="e.g. BSc" type="text"/>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold text-slate-500">Start Year</label>
                                        <input name="edu_start[]" class="rounded-md border-slate-300 dark:border-slate-600 text-sm" type="date"/>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold text-slate-500">End Year</label>
                                        <input name="edu_end[]" class="rounded-md border-slate-300 dark:border-slate-600 text-sm" type="date"/>
                                    </div>
                                </div>
                            </div>
                        </section>

                         <!-- 5. Documents -->
                        <section>
                            <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-700 pb-3 mb-4">
                                <h3 class="flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-white">
                                    <span class="material-symbols-outlined text-primary">folder</span> Documents
                                </h3>
                                <button type="button" onclick="addDocumentRow()" class="text-primary text-sm font-bold hover:underline flex items-center gap-1">
                                    <span class="material-symbols-outlined">add</span> Add Document
                                </button>
                            </div>
                            <div id="documentsContainer" class="space-y-4">
                                <!-- Dynamic Rows -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-700/30 p-4 rounded-lg border border-slate-100 dark:border-slate-700">
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold text-slate-500">Document Type</label>
                                        <select name="doc_name[]" class="rounded-md border-slate-300 dark:border-slate-600 text-sm bg-white dark:bg-slate-800">
                                            <option value="">Select Document Type</option>
                                            <option value="CV/Resume">CV/Resume</option>
                                            <option value="Cover Letter">Cover Letter</option>
                                            <option value="Offer Letter">Offer Letter</option>
                                            <option value="Acceptance Letter">Acceptance Letter</option>
                                            <option value="Guarrantors Form">Guarrantors Form</option>
                                            <option value="Medical Report">Medical Report</option>
                                            <option value="Birth Certificate">Birth Certificate</option>
                                            <option value="SSCE Certificate">SSCE Certificate</option>
                                            <option value="Degree Certificate">Degree Certificate</option>
                                            <option value="NYSC Certificate">NYSC Certificate</option>
                                            <option value="Professional Certificate">Professional Certificate</option>
                                            <option value="Passport Photograph">Passport Photograph</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold text-slate-500">File</label>
                                        <input name="doc_file[]" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20" type="file"/>
                                    </div>
                                </div>
                            </div>
                        </section>

                    </div>
                    
                    <div class="px-6 md:px-8 py-5 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-700 flex justify-end gap-3 sticky bottom-0 z-10">
                         <a href="/admin/employees.php" class="flex items-center justify-center rounded-lg h-11 px-6 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 text-sm font-bold transition-all hover:bg-slate-50 dark:hover:bg-slate-700">
                            Cancel
                        </a>
                        <button type="submit" class="flex items-center justify-center rounded-lg h-11 px-8 bg-primary text-white text-sm font-bold shadow-lg shadow-primary/30 transition-all hover:brightness-110">
                            <span class="material-symbols-outlined mr-2">save</span> Save Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
function addEducationRow() {
    const container = document.getElementById('educationContainer');
    const div = document.createElement('div');
    div.className = 'grid grid-cols-1 md:grid-cols-4 gap-4 bg-slate-50 dark:bg-slate-700/30 p-4 rounded-lg border border-slate-100 dark:border-slate-700 relative group animate-fade-in';
    div.innerHTML = `
        <div class="absolute -top-2 -right-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <button type="button" onclick="this.parentElement.parentElement.remove()" class="bg-red-500 text-white rounded-full p-1 shadow-sm">
                <span class="material-symbols-outlined text-xs">close</span>
            </button>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-slate-500">School Name</label>
            <input name="edu_school[]" class="rounded-md border-slate-300 dark:border-slate-600 text-sm" type="text"/>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-slate-500">Qualification</label>
            <input name="edu_degree[]" class="rounded-md border-slate-300 dark:border-slate-600 text-sm" placeholder="e.g. BSc" type="text"/>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-slate-500">Start Year</label>
            <input name="edu_start[]" class="rounded-md border-slate-300 dark:border-slate-600 text-sm" type="date"/>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-slate-500">End Year</label>
            <input name="edu_end[]" class="rounded-md border-slate-300 dark:border-slate-600 text-sm" type="date"/>
        </div>
    `;
    container.appendChild(div);
}

function addDocumentRow() {
    const container = document.getElementById('documentsContainer');
    const div = document.createElement('div');
    div.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-700/30 p-4 rounded-lg border border-slate-100 dark:border-slate-700 relative group animate-fade-in';
    div.innerHTML = `
        <div class="absolute -top-2 -right-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <button type="button" onclick="this.parentElement.parentElement.remove()" class="bg-red-500 text-white rounded-full p-1 shadow-sm">
                <span class="material-symbols-outlined text-xs">close</span>
            </button>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-slate-500">Document Type</label>
            <select name="doc_name[]" class="rounded-md border-slate-300 dark:border-slate-600 text-sm bg-white dark:bg-slate-800">
                <option value="">Select Document Type</option>
                <option value="CV/Resume">CV/Resume</option>
                <option value="Cover Letter">Cover Letter</option>
                <option value="Offer Letter">Offer Letter</option>
                <option value="Acceptance Letter">Acceptance Letter</option>
                <option value="Guarrantors Form">Guarrantors Form</option>
                <option value="Medical Report">Medical Report</option>
                <option value="Birth Certificate">Birth Certificate</option>
                <option value="SSCE Certificate">SSCE Certificate</option>
                <option value="Degree Certificate">Degree Certificate</option>
                <option value="NYSC Certificate">NYSC Certificate</option>
                <option value="Professional Certificate">Professional Certificate</option>
                <option value="Passport Photograph">Passport Photograph</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-slate-500">File</label>
            <input name="doc_file[]" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20" type="file"/>
        </div>
    `;
    container.appendChild(div);
}


// Set Date Constraints
document.addEventListener('DOMContentLoaded', () => {
    const today = new Date().toISOString().split('T')[0];
    
    // Prevent future date for DOB
    const dobInput = document.querySelector('input[name="dob"]');
    if(dobInput) dobInput.setAttribute('max', today);
    
    // Prevent past date for Assumption of Duty
    const startInput = document.querySelector('input[name="start_date"]');
    if(startInput) startInput.setAttribute('min', today);
});

document.getElementById('addStaffForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Loading State
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm mr-2">progress_activity</span> Saving...';
    
    try {
        const response = await fetch('/api/staff_actions.php', {
            method: 'POST',
            body: formData
        });
        
        let result;
        try {
            result = await response.json();
        } catch(e) {
            console.error('JSON Error:', e);
            throw new Error('Invalid server response');
        }
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: result.message,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = '/admin/employees.php';
            });
        } else {
             Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message || 'Unknown error occurred'
            });
        }
    } catch (error) {
        console.error('Error submitting form:', error);
        Swal.fire({
            icon: 'error',
            title: 'Connection Error',
            text: 'Could not connect to the server. Please check console.'
        });
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});
</script>

</body>
</html>
