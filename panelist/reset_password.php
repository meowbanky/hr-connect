<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

// Fetch Logo
$companyLogo = get_setting('company_logo', '/assets/images/logo.png');

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (!$token) {
    die("Invalid request.");
}

// Verify Token
$stmt = $pdo->prepare("SELECT id, name FROM panelists WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$panelist = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$panelist && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $error = "This password reset link is invalid or has expired.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$panelist) {
        $error = "This password reset link is invalid or has expired.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Reset Password
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE panelists SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $upd->execute([$hash, $panelist['id']]);

        $success = "Password has been reset successfully. You can now login.";
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Reset Password - Panelist Portal</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#5045e8",
                        "background-light": "#f6f6f8",
                        "background-dark": "#121121",
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
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-[#0f0e1b] dark:text-[#f9f8fb] antialiased">
<div class="flex min-h-screen">
    <!-- Left Sidebar -->
    <aside class="hidden lg:flex lg:w-[42%] bg-primary/10 dark:bg-primary/5 flex-col justify-between p-12 border-r border-[#e8e8f3] dark:border-[#2a293e] relative overflow-hidden">
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-12">
                <div class="h-10">
                    <img src="<?php echo htmlspecialchars($companyLogo); ?>" alt="Company Logo" class="h-full w-auto object-contain">
                </div>
                <h1 class="text-xl font-bold tracking-tight text-primary">HR Masterclass</h1>
            </div>
            <div class="space-y-6">
                <h2 class="text-4xl font-black leading-tight text-[#0f0e1b] dark:text-white">Secure Access.</h2>
                <p class="text-lg text-[#544f96] dark:text-gray-400 max-w-md">Set a strong password to protect your account and candidate data.</p>
            </div>
        </div>
        <!-- Background Decorations -->
        <div class="absolute top-0 right-0 -mr-20 -mt-20 size-80 rounded-full bg-primary/5 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -ml-20 -mb-20 size-80 rounded-full bg-primary/10 blur-3xl"></div>
    </aside>

    <!-- Right Content -->
    <main class="flex-1 flex flex-col justify-center items-center px-6 py-12 lg:px-24">
        <div class="w-full max-w-md space-y-8">
            <div class="lg:hidden flex items-center gap-3 mb-8">
                <div class="h-8">
                     <img src="<?php echo htmlspecialchars($companyLogo); ?>" alt="Company Logo" class="h-full w-auto object-contain">
                </div>
                <h1 class="text-lg font-bold">HR Management</h1>
            </div>

            <div class="space-y-2">
                <h2 class="text-3xl font-black tracking-tight text-[#0f0e1b] dark:text-white">Reset Password</h2>
                <p class="text-[#544f96] dark:text-gray-400">Enter your new password below.</p>
            </div>

            <?php if($error): ?>
                <div class="bg-red-50 border border-red-100 text-red-600 p-4 rounded-xl text-sm font-medium flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">error</span>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="bg-green-50 border-green-100 text-green-600 p-4 rounded-xl text-sm font-medium flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">check_circle</span>
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <a href="login.php" class="w-full h-14 bg-primary hover:bg-[#3f36c5] text-white font-bold rounded-lg shadow-lg shadow-primary/20 transition-all flex items-center justify-center gap-3 active:scale-[0.98]">
                    <span>Go to Login</span>
                    <span class="material-symbols-outlined text-xl">login</span>
                </a>
            <?php else: ?>
                <?php if ($panelist): ?>
                <form method="POST" class="space-y-6 mt-10">
                    <div class="space-y-4">
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-semibold text-[#0f0e1b] dark:text-gray-200">New Password</label>
                            <input name="password" required class="w-full px-4 h-14 bg-white dark:bg-[#1b1a2e] border border-[#d2d0e6] dark:border-[#2a293e] rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all text-[#0f0e1b] dark:text-white" type="password" minlength="6"/>
                        </div>
                         <div class="flex flex-col gap-2">
                            <label class="text-sm font-semibold text-[#0f0e1b] dark:text-gray-200">Confirm Password</label>
                            <input name="confirm_password" required class="w-full px-4 h-14 bg-white dark:bg-[#1b1a2e] border border-[#d2d0e6] dark:border-[#2a293e] rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all text-[#0f0e1b] dark:text-white" type="password" minlength="6"/>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button class="w-full h-14 bg-primary hover:bg-[#3f36c5] text-white font-bold rounded-lg shadow-lg shadow-primary/20 transition-all flex items-center justify-center gap-3 active:scale-[0.98]" type="submit">
                            <span>Change Password</span>
                            <span class="material-symbols-outlined text-xl">check</span>
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!$success): ?>
            <div class="pt-8 flex flex-col items-center gap-4 text-sm font-medium">
                <a href="login.php" class="text-[#544f96] dark:text-gray-400 hover:text-[#0f0e1b] dark:hover:text-white transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">arrow_back</span>
                    Back to Login
                </a>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
