<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

// Check Admin Auth
if (!isset($_SESSION['user_role']) || 
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff' && $_SESSION['user_role'] !== 'superadmin')) {
    header("Location: ../admin/login.php");
    exit;
}

$pageTitle = "Invite Panelist";

// Fetch Job Postings for Dropdown
// Fetch Job Postings for Dropdown
$sql = "SELECT id, title, status FROM job_postings ORDER BY created_at DESC";
$stmt = $pdo->query($sql);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Admin Invite Panelist | HR Connect</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#452ed6",
                        "background-light": "#f9fafb",
                        "background-dark": "#111827",
                    },
                    fontFamily: {
                        "display": ["Inter"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .sidebar-item-active { background-color: rgba(69, 46, 214, 0.1); border-right: 3px solid #452ed6; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen">
<div class="flex h-screen overflow-hidden">
    <!-- Sidebar Navigation -->
    <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto bg-background-light dark:bg-background-dark">
        <div class="max-w-5xl mx-auto px-8 py-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 mb-6">
                <a class="text-xs font-medium text-slate-400 hover:text-primary uppercase tracking-wider" href="#">Recruitment</a>
                <span class="material-symbols-outlined text-slate-300 text-sm">chevron_right</span>
                <a class="text-xs font-medium text-slate-400 hover:text-primary uppercase tracking-wider" href="/admin/panelists">Panels</a>
                <span class="material-symbols-outlined text-slate-300 text-sm">chevron_right</span>
                <span class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Invite Panelist</span>
            </nav>

            <!-- Page Heading -->
            <div class="mb-10">
                <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight mb-2">Invite Panelist</h2>
                <p class="text-slate-500 dark:text-slate-400 max-w-2xl">Add a new interviewer to an interview session. They will receive automated login credentials to access the evaluation portal.</p>
            </div>

            <!-- Form Section -->
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                <form id="inviteForm">
                    <!-- Panelist Info -->
                    <div class="p-8 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">person_add</span>
                            Panelist Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Full Name</label>
                                <input name="name" required class="w-full px-4 py-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-slate-400" placeholder="e.g. Sarah Jenkins" type="text"/>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Professional Email Address</label>
                                <input name="email" required class="w-full px-4 py-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-slate-400" placeholder="s.jenkins@company.com" type="email"/>
                            </div>
                        </div>
                    </div>

                    <!-- Assignment Details -->
                    <div class="p-8 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">assignment</span>
                            Assignment Details
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Target Job Posting</label>
                                <select name="job_ids[]" multiple required class="w-full px-4 py-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all h-32">
                                    <?php foreach ($jobs as $job): ?>
                                        <option value="<?php echo $job['id']; ?>">
                                            <?php echo htmlspecialchars($job['title']); ?> (<?php echo ucfirst($job['status']); ?>)
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                                <p class="text-xs text-slate-500">Hold Ctrl/Cmd to select multiple jobs.</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Access Level & Role</label>
                                <div class="grid grid-cols-1 gap-4">
                                    <label class="relative flex cursor-pointer rounded-xl border border-slate-200 dark:border-slate-700 p-4 focus:outline-none bg-white dark:bg-slate-900 hover:border-primary transition-all">
                                        <input checked="" class="mt-1 size-4 text-primary focus:ring-primary" name="role" type="radio" value="panelist"/>
                                        <div class="ml-3">
                                            <span class="block text-sm font-bold text-slate-900 dark:text-white">Panelist</span>
                                            <span class="block text-xs text-slate-500 mt-1">Standard evaluator access to candidate profile and scorecards.</span>
                                        </div>
                                    </label>
                                    <label class="relative flex cursor-pointer rounded-xl border border-slate-200 dark:border-slate-700 p-4 focus:outline-none bg-white dark:bg-slate-900 hover:border-primary transition-all">
                                        <input class="mt-1 size-4 text-primary focus:ring-primary" name="role" type="radio" value="chairman"/>
                                        <div class="ml-3">
                                            <span class="block text-sm font-bold text-slate-900 dark:text-white">Chairman</span>
                                            <span class="block text-xs text-slate-500 mt-1">Supervisory access with authority to submit final group decision.</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Footer -->
                    <div class="p-8 bg-slate-50 dark:bg-slate-800/50 flex flex-col md:flex-row items-center justify-between gap-6">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-slate-400 text-xl">info</span>
                            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm">An automated email with unique login credentials will be sent immediately upon clicking "Send Invite".</p>
                        </div>
                        <div class="flex items-center gap-4 w-full md:w-auto">
                            <a href="/admin/panelists" class="flex-1 md:flex-none px-6 py-3 text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors text-center">
                                Cancel
                            </a>
                            <button class="flex-1 md:flex-none px-8 py-3 text-sm font-bold text-white bg-primary hover:bg-primary/90 rounded-lg shadow-lg shadow-primary/20 flex items-center justify-center gap-2 transition-all" type="submit" id="submitBtn">
                                <span class="material-symbols-outlined text-sm">send</span>
                                Send Invite
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Auxiliary Information -->
            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="p-6 rounded-xl bg-primary/5 border border-primary/10">
                    <span class="material-symbols-outlined text-primary mb-3">security</span>
                    <h4 class="text-sm font-bold mb-1">Secure Access</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">Unique one-time login links ensure candidate data privacy and security compliance.</p>
                </div>
                <div class="p-6 rounded-xl bg-primary/5 border border-primary/10">
                    <span class="material-symbols-outlined text-primary mb-3">notifications_active</span>
                    <h4 class="text-sm font-bold mb-1">Auto Reminders</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">System automatically pings panelists 24 hours before the scheduled interview round.</p>
                </div>
                <div class="p-6 rounded-xl bg-primary/5 border border-primary/10">
                    <span class="material-symbols-outlined text-primary mb-3">history</span>
                    <h4 class="text-sm font-bold mb-1">Invite Tracking</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">Track open rates and credential activation from the Panelist Management dashboard.</p>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    document.getElementById('inviteForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('submitBtn');
        const originalText = btn.innerHTML;
        
        btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">progress_activity</span> Sending...';
        btn.disabled = true;

        const formData = new FormData(this);

        fetch('../api/invite_panelist.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Invitation Sent!',
                    text: 'Panelist has been invited successfully.',
                    icon: 'success',
                    confirmButtonColor: '#452ed6'
                }).then(() => {
                    window.location.href = '../admin/panelists';
                });
            } else {
                Swal.fire('Error', data.message || 'Failed to send invite', 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(error => {
            Swal.fire('Error', 'Network error occurred', 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });
</script>
</body>
</html>
