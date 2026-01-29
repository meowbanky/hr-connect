<?php
session_start();
// Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /admin/login');
    exit;
}

require_once __DIR__ . '/../includes/settings.php';
$pageTitle = 'Account Settings'; // Admin context

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT first_name, last_name, email, phone_number, profile_image FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$profileImage = $user['profile_image'] ?? null;
// Fallback logic
if (!$profileImage || !file_exists(__DIR__ . '/..' . $profileImage)) {
    $profileImage = null; // Let UI handle default
}

?>
<!DOCTYPE html>
<html lang="en" class="<?php echo isset($_SESSION['theme_mode']) && $_SESSION['theme_mode'] === 'dark' ? 'dark' : 'light'; ?>">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Account Settings - HR Connect</title>
    <?php if(get_setting('company_logo')): ?>
        <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars(get_setting('company_logo')); ?>">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="/assets/css/style.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php echo get_theme_css(); ?>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-800 dark:text-slate-100 flex h-screen overflow-hidden font-display antialiased">
    
    <!-- Sidebar -->
    <?php include_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full overflow-hidden relative">
        <!-- Header -->
        <?php include_once __DIR__ . '/../includes/admin_header.php'; ?>

        <div class="flex-1 overflow-y-auto p-4 md:p-10 flex flex-col gap-6">
            <div class="w-full max-w-5xl mx-auto flex flex-col gap-8">
                
                <!-- Page Header -->
                <div>
                     <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Account Settings</h1>
                     <p class="text-slate-500 dark:text-slate-400">Update your personal information and account preferences.</p>
                </div>

                <div class="flex flex-col md:flex-row gap-8 items-start">
                    
                    <!-- Settings Sidebar (Nav) -->
                    <div class="w-full md:w-64 flex flex-col gap-1 shrink-0">
                        <a href="#photo" class="flex items-center gap-3 px-4 py-3 rounded-lg bg-primary text-white font-medium shadow-md shadow-primary/20 transition-all">
                            <span class="material-symbols-outlined">account_circle</span>
                            Profile Photo
                        </a>
                        <a href="#personal" role="button" onclick="Swal.fire('Info', 'Personal info updates are managed by the admin.', 'info')" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <span class="material-symbols-outlined">person</span>
                            Personal Info
                        </a>
                        <a href="#security" role="button" onclick="Swal.fire('Info', 'Security settings coming soon.', 'info')" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <span class="material-symbols-outlined">lock</span>
                            Security
                        </a>
                        <a href="#notifications" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <span class="material-symbols-outlined">notifications</span>
                            Notifications
                        </a>
                    </div>

                    <!-- Main Content Card -->
                    <div class="flex-1 w-full bg-white dark:bg-surface-dark rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                        <div class="p-8 border-b border-slate-100 dark:border-slate-800">
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-1">Profile Photo</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">This will be displayed on your profile and visible to recruiters and team members.</p>
                        </div>
                        
                        <div class="p-8 flex flex-col md:flex-row gap-10 items-start">
                             <!-- Current Photo Preview -->
                            <div class="flex flex-col items-center gap-4">
                                <div class="w-40 h-40 rounded-full border-4 border-slate-100 dark:border-slate-800 overflow-hidden bg-slate-50 dark:bg-slate-900 relative group">
                                    <?php if($profileImage): ?>
                                        <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile" id="currentPreview" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center bg-slate-200 dark:bg-slate-800 text-slate-400">
                                            <span class="material-symbols-outlined text-6xl">person</span>
                                        </div>
                                        <img src="" id="currentPreview" class="w-full h-full object-cover hidden">
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center gap-4 text-sm font-medium">
                                    <button type="button" onclick="document.getElementById('profileUpload').click()" class="text-primary hover:text-blue-700 transition-colors">Change Photo</button>
                                    <span class="text-slate-300">|</span>
                                    <button type="button" onclick="removePhoto()" class="text-red-500 hover:text-red-700 transition-colors">Remove</button>
                                </div>
                            </div>

                            <!-- Upload Area -->
                            <div class="flex-1 w-full flex flex-col gap-4">
                                <div id="dropZone" class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900/50 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-colors h-48 flex flex-col items-center justify-center cursor-pointer group relative">
                                    <input type="file" id="profileUpload" accept="image/png, image/jpeg, image/gif, image/svg+xml" class="absolute inset-0 opacity-0 cursor-pointer z-10">
                                    
                                    <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center text-primary mb-3 group-hover:scale-110 transition-transform">
                                        <span class="material-symbols-outlined">cloud_upload</span>
                                    </div>
                                    <p class="text-slate-900 dark:text-white font-semibold">Click to upload or drag and drop</p>
                                    <p class="text-slate-500 text-sm mt-1">SVG, PNG, JPG or GIF (max. 800x800px)</p>
                                </div>

                                <!-- Progress Bar (Hidden initially) -->
                                <div id="uploadProgress" class="hidden">
                                    <div class="flex justify-between text-xs font-semibold text-slate-500 mb-1">
                                        <span id="fileName">uploading_profile_v2.jpg</span>
                                        <span id="progressText">0%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                                        <div id="progressBar" class="bg-primary h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                    </div>
                                </div>
                                
                               <!-- Error / Info Box -->
                               <div class="bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-900/20 rounded-lg p-4 flex gap-3 items-start">
                                   <span class="material-symbols-outlined text-primary shrink-0">info</span>
                                   <p class="text-sm text-slate-600 dark:text-slate-300">For best results, use an image at least 400x400 pixels in .jpg or .png format.</p>
                               </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="p-6 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3 bg-slate-50 dark:bg-slate-900/20">
                            <button type="button" onclick="location.reload()" class="px-5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 font-semibold text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-800 transition-colors">Cancel</button>
                            <button type="button" id="saveBtn" class="px-5 py-2.5 rounded-lg bg-primary text-white font-semibold shadow-lg shadow-primary/20 hover:bg-blue-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed">Save Changes</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

<script>
    const saveBtn = document.getElementById('saveBtn');
    const profileUpload = document.getElementById('profileUpload');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const uploadProgress = document.getElementById('uploadProgress');
    const currentPreview = document.getElementById('currentPreview');
    let selectedFile = null;

    // Drag & Drop
    const dropZone = document.getElementById('dropZone');
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.add('border-primary', 'bg-blue-50', 'dark:bg-blue-900/10'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.remove('border-primary', 'bg-blue-50', 'dark:bg-blue-900/10'), false);
    });

    dropZone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }

    profileUpload.addEventListener('change', function() {
        handleFiles(this.files);
    });

    function handleFiles(files) {
        if(files.length > 0) {
            selectedFile = files[0];
            document.getElementById('fileName').innerText = selectedFile.name;
            
            // Preview
            const reader = new FileReader();
            reader.onload = (e) => {
                currentPreview.src = e.target.result;
                currentPreview.classList.remove('hidden');
                // Hide icon placeholder if visible (handled by z-index or DOM logic usually, but here simple img src swap)
            }
            reader.readAsDataURL(selectedFile);
        }
    }

    saveBtn.addEventListener('click', () => {
        if(!selectedFile) {
            Swal.fire('No Changes', 'Please select a photo to update.', 'info');
            return;
        }

        const formData = new FormData();
        formData.append('profile_image', selectedFile);

        // Show Progress
        uploadProgress.classList.remove('hidden');
        saveBtn.disabled = true;
        saveBtn.innerText = 'Uploading...';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../api/update_profile.php', true);

        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percent + '%';
                progressText.innerText = percent + '%';
            }
        };

        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const result = JSON.parse(xhr.responseText);
                    if(result.success) {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'bottom-right',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.onmouseenter = Swal.stopTimer;
                                toast.onmouseleave = Swal.resumeTimer;
                            }
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Profile photo updated successfully!',
                            customClass: {
                                popup: 'bg-slate-900 text-white rounded-xl shadow-2xl'
                            }
                        });
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        Swal.fire('Error', result.message || 'Upload failed', 'error');
                        saveBtn.disabled = false;
                        saveBtn.innerText = 'Save Changes';
                    }
                } catch(e) {
                    Swal.fire('Error', 'Invalid server response', 'error');
                }
            } else {
                 Swal.fire('Error', 'Upload failed', 'error');
            }
        };

        xhr.send(formData);
    });

    function removePhoto() {
         Swal.fire({
            title: 'Remove Photo?',
            text: "This will reset your profile picture to default.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, remove',
            confirmButtonColor: '#ef4444'
        }).then((result) => {
            if (result.isConfirmed) {
                // Implement remove logic (maybe send empty file or specific action)
                // For now, prompt user it's cleaned locally or separate API needed?
                // Assuming we just clear it.
                // update_profile.php currently only updates if file sent.
                // Providing 'remove_photo' flag to API would be needed. 
                // Currently just alert.
                Swal.fire('Info', 'To remove, please upload a default placeholder or contact admin.', 'info');
            }
        });
    }

</script>
</body>
</html>
