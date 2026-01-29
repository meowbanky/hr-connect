<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

// Fetch Logo
$companyLogo = get_setting('company_logo', '/assets/images/logo.png');

if (isset($_SESSION['panelist_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = trim($_POST['username'] ?? ''); // Accepts Email or Username
    $password = $_POST['password'] ?? '';

    if (empty($loginInput) || empty($password)) {
        $error = "Please enter your email/username and password.";
    } else {
        // Allow login by Username OR Email
        $stmt = $pdo->prepare("SELECT * FROM panelists WHERE username = ? OR email = ?");
        $stmt->execute([$loginInput, $loginInput]);
        $panelist = $stmt->fetch();

        if ($panelist && password_verify($password, $panelist['password_hash'])) {
            if ($panelist['status'] === 'inactive') {
                $error = "Your account is deactivated. Please contact HR.";
            } else {
                $_SESSION['panelist_id'] = $panelist['id'];
                $_SESSION['panelist_name'] = $panelist['name'];
                $_SESSION['panelist_role'] = $panelist['role'];
                
                // Update status to active if pending
                if ($panelist['status'] === 'pending') {
                    $upd = $pdo->prepare("UPDATE panelists SET status = 'active' WHERE id = ?");
                    $upd->execute([$panelist['id']]);
                }
                
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $error = "Invalid credentials. Please check your email/password.";
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Panelist Portal Login - HR Management</title>
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
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-[#0f0e1b] dark:text-[#f9f8fb] antialiased">
<div class="flex min-h-screen">
    <!-- Left Sidebar: Branding & Illustration -->
    <aside class="hidden lg:flex lg:w-[42%] bg-primary/10 dark:bg-primary/5 flex-col justify-between p-12 border-r border-[#e8e8f3] dark:border-[#2a293e] relative overflow-hidden">
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-12">
                <div class="h-10">
                    <img src="<?php echo htmlspecialchars($companyLogo); ?>" alt="Company Logo" class="h-full w-auto object-contain">
                </div>
                <h1 class="text-xl font-bold tracking-tight text-primary">HR Masterclass</h1>
            </div>
            <div class="space-y-6">
                <h2 class="text-4xl font-black leading-tight text-[#0f0e1b] dark:text-white">Seamless Interview Coordination.</h2>
                <p class="text-lg text-[#544f96] dark:text-gray-400 max-w-md">Connect with top talent effortlessly. Your dashboard centralizes resumes, schedules, and evaluation tools in one high-performance interface.</p>
            </div>
        </div>
        <div class="relative z-10 mt-auto">
            <div class="p-6 bg-white dark:bg-[#1b1a2e] rounded-xl shadow-sm border border-[#e8e8f3] dark:border-[#2a293e]">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-center bg-cover border-2 border-primary/20" data-alt="professional headshot of an interviewer" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuALx14vRTrlUOt0tZep-9dbHMdXt6sA3Toz7xtVHDgz6ENOrrQ8y13xrSdg8FatQM4O4PIwy5J9g8wG_QvKoMHvO4LvAK1VZCWdzL_b8bZ6rHU-44XF7Oygei09ctKNKWK_Ns7v-DN1h3C9YjMQRcQl9jryrK0rnvIHJVpaV1NUjzn0iZwm31zpZS9v8wxXZ0tgywILn2JMjiqEK4t37BR4BdBEs6-BQ3fXSRfPqXqV83hecBN-LMo_liIkNau8oAIo1D-LleqGFHg');"></div>
                    <div>
                        <p class="text-sm font-semibold">Sarah Jenkins</p>
                        <p class="text-xs text-[#544f96] dark:text-gray-500">Senior Tech Recruiter</p>
                    </div>
                </div>
                <p class="mt-4 text-sm italic text-[#544f96] dark:text-gray-400">"The unified portal changed how we track feedback across teams."</p>
            </div>
        </div>
        <!-- Abstract Background Decorations -->
        <div class="absolute top-0 right-0 -mr-20 -mt-20 size-80 rounded-full bg-primary/5 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -ml-20 -mb-20 size-80 rounded-full bg-primary/10 blur-3xl"></div>
    </aside>

    <!-- Right Side: Login Form -->
    <main class="flex-1 flex flex-col justify-center items-center px-6 py-12 lg:px-24">
        <div class="w-full max-w-md space-y-8">
            <!-- Mobile Header -->
            <div class="lg:hidden flex items-center gap-3 mb-8">
                <div class="h-8">
                     <img src="<?php echo htmlspecialchars($companyLogo); ?>" alt="Company Logo" class="h-full w-auto object-contain">
                </div>
                <h1 class="text-lg font-bold">HR Management</h1>
            </div>

            <div class="space-y-2">
                <h2 class="text-3xl font-black tracking-tight text-[#0f0e1b] dark:text-white">Panelist Login</h2>
                <p class="text-[#544f96] dark:text-gray-400">Enter your credentials to access your assigned interview dashboard.</p>
            </div>

            <?php if($error): ?>
                <div class="bg-red-50 border border-red-100 text-red-600 p-4 rounded-xl text-sm font-medium flex items-center gap-2 animate-pulse">
                    <span class="material-symbols-outlined text-lg">error</span>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6 mt-10">
                <div class="space-y-4">
                    <!-- Email/Username Input -->
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-semibold text-[#0f0e1b] dark:text-gray-200">Email Address / Username</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-[#544f96] text-xl">alternate_email</span>
                            <input name="username" required class="w-full pl-12 pr-4 h-14 bg-white dark:bg-[#1b1a2e] border border-[#d2d0e6] dark:border-[#2a293e] rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all text-[#0f0e1b] dark:text-white placeholder:text-[#544f96]/50" placeholder="name@company.com" type="text"/>
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-semibold text-[#0f0e1b] dark:text-gray-200">Password</label>
                            <a href="forgot_password.php" class="text-xs text-primary font-medium hover:underline cursor-pointer">Forgot password?</a>
                        </div>
                        <div class="flex w-full items-stretch rounded-lg shadow-sm">
                            <div class="relative flex-1">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-[#544f96] text-xl">lock</span>
                                <input name="password" required class="w-full pl-12 pr-4 h-14 bg-white dark:bg-[#1b1a2e] border border-[#d2d0e6] dark:border-[#2a293e] rounded-l-lg border-r-0 focus:ring-2 focus:ring-primary focus:border-primary transition-all text-[#0f0e1b] dark:text-white placeholder:text-[#544f96]/50" placeholder="Enter your password" type="password"/>
                            </div>
                            <button class="px-4 flex items-center justify-center bg-white dark:bg-[#1b1a2e] border border-[#d2d0e6] dark:border-[#2a293e] rounded-r-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" type="button" title="Info">
                                <span class="material-symbols-outlined text-[#544f96]">info</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="pt-4">
                    <button class="w-full h-14 bg-primary hover:bg-[#3f36c5] text-white font-bold rounded-lg shadow-lg shadow-primary/20 transition-all flex items-center justify-center gap-3 active:scale-[0.98]" type="submit">
                        <span>Join Interview Dashboard</span>
                        <span class="material-symbols-outlined text-xl">arrow_forward</span>
                    </button>
                </div>
            </form>

            <div class="pt-8 flex flex-col items-center gap-4 text-sm font-medium">
                <button class="text-primary hover:text-primary/80 transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">help_center</span>
                    Need help accessing?
                </button>
                <a href="../" class="text-[#544f96] dark:text-gray-400 hover:text-[#0f0e1b] dark:hover:text-white transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">arrow_back</span>
                    Return to main site
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
