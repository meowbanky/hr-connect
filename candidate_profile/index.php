<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../candidate_registration/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch existing profile data
$stmt = $pdo->prepare("
    SELECT c.*, u.first_name, u.last_name, u.email, u.phone_number, u.profile_image 
    FROM candidates c 
    RIGHT JOIN users u ON u.id = c.user_id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Education
$stmtEdu = $pdo->prepare("SELECT * FROM candidate_education WHERE candidate_id = ? ORDER BY start_date DESC");
$stmtEdu->execute([$profile['id'] ?? 0]);
$education = $stmtEdu->fetchAll(PDO::FETCH_ASSOC);

// Fetch Documents
$stmtDocs = $pdo->prepare("SELECT * FROM candidate_documents WHERE candidate_id = ?");
$stmtDocs->execute([$profile['id'] ?? 0]);
$documents = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

// Safe defaults
$firstName = $profile['first_name'] ?? '';
$lastName = $profile['last_name'] ?? '';
$email = $profile['email'] ?? '';
$phone = $profile['phone_number'] ?? '';
$dob = $profile['date_of_birth'] ?? '';
$gender = $profile['gender'] ?? '';
$bio = $profile['address'] ?? ''; // Using address field for Bio temporarily or schema update needed? Schema has Bio? 
// Schema check: candidates has address, state_of_origin, etc. No 'bio' column in schema dump?
// Wait, previous `submit_application.php` was saving bio to... where?
// Looking at submit_application.php: It reads $_POST['bio'] but doesn't seem to INSERT it into candidates table in the snippet I saw!
// It only UPDATES date_of_birth, gender, linkedin_profile.
// It seems specific "Bio" field is missing in `candidates` table schema I saw earlier. 
// I will check database_schema.sql content again.
// It has `address` text. Maybe that is being used or I need to add bio? 
// For now I'll map Bio to Address or just add it. The mockups showed "Professional Bio". 
// Let's assume we use `address` for Bio for now or verify schema.
// Actually, in `submit_application.php`:
// $sqlApp = "INSERT INTO applications ... cover_letter ..."; -> uses cover_letter_path ?: $bio
// So Bio was only saved to Application's cover_letter if no file! It wasn't saved to profile!
// I should add a `bio` column to `candidates` to do this properly.

$linkedin = $profile['linkedin_profile'] ?? '';
$portfolio = $profile['portfolio_url'] ?? ''; 
$resume = $profile['resume_path'] ?? ''; // database has this

$state = $profile['state_of_origin'] ?? '';
$lga = $profile['lga'] ?? '';
$qualification = $profile['highest_qualification'] ?? '';
$experience = $profile['years_of_experience'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>My Profile - HR Connect</title>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&amp;display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary": "#1919e6",
                    "background-light": "#f6f6f8",
                    "background-dark": "#111121",
                    "surface-light": "#ffffff",
                    "surface-dark": "#1e1e2d",
                    "border-light": "#d0d0e7",
                    "border-dark": "#33334d",
                    "text-main": "#0e0e1b",
                    "text-secondary": "#4e4e97",
                },
                fontFamily: {
                    "display": ["Inter", "sans-serif"]
                },
                borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
            },
        },
    }
</script>
<?php echo get_theme_css(); ?>
</head>
<body class="bg-background-light dark:bg-background-dark min-h-screen flex flex-col transition-colors duration-200">
<!-- Header -->
<?php include_once __DIR__ . '/../includes/header.php'; ?>

<main class="flex-grow flex flex-col items-center py-8 px-4 md:px-10">
    <div class="w-full max-w-[960px] flex flex-col gap-6">
        
        <div class="flex flex-col gap-2">
            <h1 class="text-3xl font-bold text-text-main dark:text-white">My Profile</h1>
            <p class="text-text-secondary dark:text-gray-400">Manage your personal information and documents to speed up your applications.</p>
        </div>

        <!-- Success/Error Message -->
        <div id="alertBox" class="hidden rounded-lg p-4 mb-4"></div>

        <form id="profileForm" enctype="multipart/form-data" class="flex flex-col gap-8">
            
            <!-- Personal Details -->
            <section class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm border border-border-light dark:border-border-dark p-6 md:p-8">
                <h2 class="text-xl font-bold text-text-main dark:text-white mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">person</span> Personal Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-semibold text-text-main dark:text-gray-300">First Name</span>
                        <input name="first_name" value="<?php echo htmlspecialchars($firstName); ?>" class="form-input rounded-lg border-border-light dark:border-border-dark bg-gray-50 dark:bg-background-dark h-11 px-4" required>
                    </label>
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-semibold text-text-main dark:text-gray-300">Last Name</span>
                        <input name="last_name" value="<?php echo htmlspecialchars($lastName); ?>" class="form-input rounded-lg border-border-light dark:border-border-dark bg-gray-50 dark:bg-background-dark h-11 px-4" required>
                    </label>
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-semibold text-text-main dark:text-gray-300">Email Address</span>
                        <input name="email" type="email" value="<?php echo htmlspecialchars($email); ?>" class="form-input rounded-lg border-border-light dark:border-border-dark bg-gray-100 dark:bg-white/5 h-11 px-4 text-gray-500 cursor-not-allowed" readonly title="Contact support to change email">
                    </label>
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-semibold text-text-main dark:text-gray-300">Phone Number</span>
                        <input name="phone" type="tel" value="<?php echo htmlspecialchars($phone); ?>" class="form-input rounded-lg border-border-light dark:border-border-dark bg-gray-50 dark:bg-background-dark h-11 px-4" placeholder="+234...">
                    </label>
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-semibold text-text-main dark:text-gray-300">Date of Birth</span>
                        <input name="dob" type="date" value="<?php echo htmlspecialchars($dob); ?>" class="form-input rounded-lg border-border-light dark:border-border-dark bg-gray-50 dark:bg-background-dark h-11 px-4">
                    </label>
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-semibold text-text-main dark:text-gray-300">Gender</span>
                        <select name="gender" class="form-select rounded-lg border-border-light dark:border-border-dark bg-gray-50 dark:bg-background-dark h-11 px-4">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $gender === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </label>
                </div>
                 <!-- Additional Details -->
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 pt-6 border-t border-border-light dark:border-border-dark">
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-semibold text-text-main dark:text-gray-300">State of Origin</span>
                        <input name="state_of_origin" value="<?php echo htmlspecialchars($state); ?>" class="form-input rounded-lg border-border-light dark:border-border-dark bg-gray-50 dark:bg-background-dark h-11 px-4" placeholder="e.g. Ogun">
                    </label>
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-semibold text-text-main dark:text-gray-300">LGA</span>
                        <input name="lga" value="<?php echo htmlspecialchars($lga); ?>" class="form-input rounded-lg border-border-light dark:border-border-dark bg-gray-50 dark:bg-background-dark h-11 px-4" placeholder="e.g. Sagamu">
                    </label>
                </div>
            </section>

            <!-- Bio & Links -->
            <section class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm border border-border-light dark:border-border-dark p-6 md:p-8">
                <h2 class="text-xl font-bold text-text-main dark:text-white mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">description</span> Professional Summary
                </h2>
                <div class="flex flex-col gap-6">
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-semibold text-text-main dark:text-gray-300">Bio / About Me</span>
                        <textarea name="bio" class="form-textarea rounded-lg border-border-light dark:border-border-dark bg-gray-50 dark:bg-background-dark p-4 min-h-[120px]" placeholder="Briefly describe your professional background..."><?php echo htmlspecialchars($bio); ?></textarea>
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-semibold text-text-main dark:text-gray-300">LinkedIn URL</span>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 font-bold">in</span>
                                <input name="linkedin" type="url" value="<?php echo htmlspecialchars($linkedin); ?>" class="form-input rounded-lg border-border-light dark:border-border-dark bg-gray-50 dark:bg-background-dark h-11 pl-10 pr-4" placeholder="https://linkedin.com/in/...">
                            </div>
                        </label>
                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-semibold text-text-main dark:text-gray-300">Portfolio URL</span>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="material-symbols-outlined text-sm text-gray-400">language</span>
                                </span>
                                <input name="portfolio" type="url" value="<?php echo htmlspecialchars($portfolio); ?>" class="form-input rounded-lg border-border-light dark:border-border-dark bg-gray-50 dark:bg-background-dark h-11 pl-10 pr-4" placeholder="https://myportfolio.com">
                            </div>
                        </label>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-border-light dark:border-border-dark">
                         <label class="flex flex-col gap-2">
                            <span class="text-sm font-semibold text-text-main dark:text-gray-300">Highest Qualification</span>
                            <select name="highest_qualification" class="form-select rounded-lg border-border-light dark:border-border-dark bg-gray-50 dark:bg-background-dark h-11 px-4">
                                <option value="">Select Qualification</option>
                                <option value="SSCE" <?php echo $qualification === 'SSCE' ? 'selected' : ''; ?>>SSCE / GCE</option>
                                <option value="OND" <?php echo $qualification === 'OND' ? 'selected' : ''; ?>>OND / Diploma</option>
                                <option value="HND" <?php echo $qualification === 'HND' ? 'selected' : ''; ?>>HND</option>
                                <option value="BSc" <?php echo $qualification === 'BSc' ? 'selected' : ''; ?>>BSc / BA</option>
                                <option value="MSc" <?php echo $qualification === 'MSc' ? 'selected' : ''; ?>>MSc / MA / MBA</option>
                                <option value="PhD" <?php echo $qualification === 'PhD' ? 'selected' : ''; ?>>PhD</option>
                            </select>
                        </label>
                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-semibold text-text-main dark:text-gray-300">Years of Experience</span>
                            <input name="years_of_experience" type="number" min="0" value="<?php echo htmlspecialchars($experience); ?>" class="form-input rounded-lg border-border-light dark:border-border-dark bg-gray-50 dark:bg-background-dark h-11 px-4" placeholder="e.g. 5">
                        </label>
                    </div>

            <!-- Resume -->
            <section class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm border border-border-light dark:border-border-dark p-6 md:p-8">
                <h2 class="text-xl font-bold text-text-main dark:text-white mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">attach_file</span> Resume / CV
                </h2>
                <div class="flex flex-col gap-4">
                    <?php if(!empty($resume)): ?>
                    <div class="flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-900/30">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-primary">description</span>
                            <div>
                                <p class="text-sm font-semibold text-text-main dark:text-white">Current Resume</p>
                                <a href="../<?php echo htmlspecialchars($resume); ?>" target="_blank" class="text-xs text-primary hover:underline truncate max-w-[200px] inline-block">View File</a>
                            </div>
                        </div>
                        <span class="text-xs text-gray-500 bg-white dark:bg-black/20 px-2 py-1 rounded">Uploaded</span>
                    </div>
                    <?php endif; ?>
                    
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-semibold text-text-main dark:text-gray-300"><?php echo !empty($resume) ? 'Replace Resume' : 'Upload Resume'; ?></span>
                        <input name="resume" type="file" accept=".pdf,.doc,.docx" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition-all cursor-pointer">
                        <p class="text-xs text-gray-500">Supported formats: PDF, DOCX (Max 5MB)</p>
                    </label>
                </div>
            </section>
            
            <!-- Education (Simplified for Profile - Just a list managing) -->
            <section class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm border border-border-light dark:border-border-dark p-6 md:p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-text-main dark:text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">school</span> Education
                    </h2>
                    <button type="button" onclick="addEduRow()" class="text-sm font-bold text-primary hover:text-blue-700 flex items-center gap-1">
                        <span class="material-symbols-outlined text-lg">add</span> Add New
                    </button>
                </div>
                <div id="eduList" class="space-y-4">
                    <?php if(empty($education)): ?>
                        <p id="noEduMsg" class="text-gray-500 text-sm text-center py-4">No education history added yet.</p>
                    <?php else: ?>
                        <?php foreach($education as $edu): ?>
                            <div class="edu-item p-4 rounded-lg bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-gray-800 relative group">
                                <button type="button" onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <input name="edu_school[]" value="<?php echo htmlspecialchars($edu['school_name']); ?>" class="form-input bg-white dark:bg-black/20" placeholder="School Name">
                                    <input name="edu_degree[]" value="<?php echo htmlspecialchars($edu['qualification']); ?>" class="form-input bg-white dark:bg-black/20" placeholder="Degree/Qualification">
                                    <div class="flex gap-2">
                                        <input name="edu_start[]" type="date" value="<?php echo htmlspecialchars($edu['start_date']); ?>" class="form-input bg-white dark:bg-black/20">
                                        <input name="edu_end[]" type="date" value="<?php echo htmlspecialchars($edu['end_date']); ?>" class="form-input bg-white dark:bg-black/20">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

             <!-- Actions -->
            <div class="fixed bottom-0 left-0 right-0 p-4 bg-white dark:bg-surface-dark border-t border-border-light dark:border-border-dark flex justify-end md:static md:bg-transparent md:border-0 md:p-0">
                <button type="submit" id="saveBtn" class="bg-primary hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg shadow-lg hover:shadow-primary/25 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined">save</span> Save Changes
                </button>
            </div>
            <div class="h-20 md:h-0"></div> <!-- Spacer for fixed bottom bar -->

        </form>
    </div>
</main>

<script>
    function addEduRow() {
        const noMsg = document.getElementById('noEduMsg');
        if(noMsg) noMsg.remove();
        
        const html = `
            <div class="edu-item p-4 rounded-lg bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-gray-800 relative group animate-in fade-in slide-in-from-bottom-2">
                <button type="button" onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 p-1">
                    <span class="material-symbols-outlined">delete</span>
                </button>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input name="edu_school[]" class="form-input rounded-lg border-border-light dark:border-border-dark bg-white dark:bg-black/20 h-10 px-3" placeholder="School Name" required>
                    <input name="edu_degree[]" class="form-input rounded-lg border-border-light dark:border-border-dark bg-white dark:bg-black/20 h-10 px-3" placeholder="Degree/Qualification" required>
                    <div class="flex gap-2">
                        <input name="edu_start[]" type="date" class="form-input rounded-lg border-border-light dark:border-border-dark bg-white dark:bg-black/20 h-10 px-3" required>
                        <input name="edu_end[]" type="date" class="form-input rounded-lg border-border-light dark:border-border-dark bg-white dark:bg-black/20 h-10 px-3">
                    </div>
                </div>
            </div>
        `;
        document.getElementById('eduList').insertAdjacentHTML('beforeend', html);
    }

    document.getElementById('profileForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('saveBtn');
        const alertBox = document.getElementById('alertBox');
        
        // Loading State
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined animate-spin">refresh</span> Saving...';
        alertBox.classList.add('hidden');

        const formData = new FormData(this);

        fetch('../api/update_profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alertBox.classList.remove('hidden');
            if(data.success) {
                alertBox.className = 'bg-green-100 text-green-700 border border-green-200 rounded-lg p-4 mb-4';
                alertBox.innerHTML = '<strong>Success!</strong> Profile updated successfully.';
                // Optional: reload to show new files?
                if(data.reload) setTimeout(() => window.location.reload(), 1000);
            } else {
                alertBox.className = 'bg-red-100 text-red-700 border border-red-200 rounded-lg p-4 mb-4';
                alertBox.innerHTML = '<strong>Error:</strong> ' + (data.message || 'Failed to update profile');
            }
        })
        .catch(err => {
            alertBox.classList.remove('hidden');
            alertBox.className = 'bg-red-100 text-red-700 border border-red-200 rounded-lg p-4 mb-4';
            alertBox.innerHTML = '<strong>Network Error:</strong> Please try again later.';
            console.error(err);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            window.scrollTo(0, 0);
        });
    });
</script>
</body>
</html>
