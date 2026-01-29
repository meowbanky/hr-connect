<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/MailHelper.php';

// Fetch Logo
$companyLogo = get_setting('company_logo', '/assets/images/logo.png');

if (isset($_SESSION['panelist_id'])) {
    header("Location: dashboard.php");
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = trim($_POST['username'] ?? '');

    if (empty($loginInput)) {
        $message = "Please enter your email or username.";
        $messageType = "error";
    } else {
        // Find Panelist
        $stmt = $pdo->prepare("SELECT id, name, email FROM panelists WHERE username = ? OR email = ?");
        $stmt->execute([$loginInput, $loginInput]);
        $panelist = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($panelist) {
            try {
                // Generate Token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Save to DB
                $upd = $pdo->prepare("UPDATE panelists SET reset_token = ?, reset_expires = ? WHERE id = ?");
                $upd->execute([$token, $expires, $panelist['id']]);

                // Send Email
                $siteUrl = get_setting('site_url', 'https://hr.prismtechnologies.com.ng');
                $resetLink = $siteUrl . '/panelist/reset_password.php?token=' . $token;
                
                $mail = new MailHelper();
                $mail->sendPanelistPasswordReset($panelist['email'], $panelist['name'], $resetLink);
                
                $message = "If an account exists, a password reset link has been sent to your email.";
                $messageType = "success";

            } catch (Exception $e) {
                $message = "An error occurred. Please try again later.";
                $messageType = "error";
                error_log("Reset Password Error: " . $e->getMessage());
            }
        } else {
            // Same message for security
            $message = "If an account exists, a password reset link has been sent to your email.";
            $messageType = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Forgot Password - Panelist Portal</title>
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
                <h2 class="text-4xl font-black leading-tight text-[#0f0e1b] dark:text-white">Account Recovery.</h2>
                <p class="text-lg text-[#544f96] dark:text-gray-400 max-w-md">Securely reset your password to regain access to your interview dashboard.</p>
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
                <h2 class="text-3xl font-black tracking-tight text-[#0f0e1b] dark:text-white">Forgot Password?</h2>
                <p class="text-[#544f96] dark:text-gray-400">Enter your email or username and we'll send you a link to reset your password.</p>
            </div>

            <?php if($message): ?>
                <div class="<?php echo $messageType === 'success' ? 'bg-green-50 border-green-100 text-green-600' : 'bg-red-50 border-red-100 text-red-600'; ?> border p-4 rounded-xl text-sm font-medium flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg"><?php echo $messageType === 'success' ? 'check_circle' : 'error'; ?></span>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6 mt-10">
                <div class="space-y-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-semibold text-[#0f0e1b] dark:text-gray-200">Email Address / Username</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-[#544f96] text-xl">alternate_email</span>
                            <input name="username" required class="w-full pl-12 pr-4 h-14 bg-white dark:bg-[#1b1a2e] border border-[#d2d0e6] dark:border-[#2a293e] rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all text-[#0f0e1b] dark:text-white placeholder:text-[#544f96]/50" placeholder="name@company.com" type="text"/>
                        </div>
                    </div>
                </div>

                <div class="pt-4">
                    <button class="w-full h-14 bg-primary hover:bg-[#3f36c5] text-white font-bold rounded-lg shadow-lg shadow-primary/20 transition-all flex items-center justify-center gap-3 active:scale-[0.98]" type="submit">
                        <span>Send Reset Link</span>
                        <span class="material-symbols-outlined text-xl">send</span>
                    </button>
                </div>
            </form>

            <div class="pt-8 flex flex-col items-center gap-4 text-sm font-medium">
                <a href="login.php" class="text-[#544f96] dark:text-gray-400 hover:text-[#0f0e1b] dark:hover:text-white transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">arrow_back</span>
                    Back to Login
                </a>
            </div>
        </div>

        <footer class="mt-auto pt-10 text-xs text-[#544f96] dark:text-gray-500">
            © <?php echo date('Y'); ?> HR Management Portal. Built for high-efficiency recruitment.
        </footer>
    </main>
</div>
</body>
</html>
