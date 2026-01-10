<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Admin Login - HR Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link href="../assets/css/style.css" rel="stylesheet"/>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 flex items-center justify-center min-h-screen p-4">

    <div class="w-full max-w-md bg-white dark:bg-slate-800 rounded-2xl shadow-xl overflow-hidden border border-slate-100 dark:border-slate-700">
        <div class="p-8">
            <div class="flex items-center justify-center gap-3 mb-8">
                <div class="size-10 rounded-lg bg-primary text-white flex items-center justify-center shadow-lg shadow-primary/30">
                    <span class="material-symbols-outlined">hr_resting</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">HR Connect</h1>
            </div>

            <div class="mb-6 text-center">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Admin Login</h2>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Enter your credentials to access the dashboard</p>
            </div>

            <div id="alertMessage" class="hidden px-4 py-3 rounded-lg text-sm font-medium mb-6" role="alert">
                <span class="block sm:inline"></span>
            </div>

            <form id="adminLoginForm" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5" for="email">Email Address</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">mail</span>
                        <input class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white h-11 pl-10 pr-4 focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder:text-slate-400 dark:placeholder:text-slate-500 text-sm transition-all" id="email" name="email" placeholder="admin@company.com" required type="email"/>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5" for="password">Password</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">lock</span>
                        <input class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white h-11 pl-10 pr-4 focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder:text-slate-400 dark:placeholder:text-slate-500 text-sm transition-all" id="password" name="password" placeholder="••••••••" required type="password"/>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary/20" />
                        <span class="text-xs text-slate-600 dark:text-slate-400">Remember me</span>
                    </label>
                    <a href="#" class="text-xs font-medium text-primary hover:text-blue-700">Forgot password?</a>
                </div>

                <button id="submitBtn" type="submit" class="w-full h-11 bg-primary hover:bg-blue-700 text-white rounded-lg font-bold text-sm tracking-wide transition-all shadow-md hover:translate-y-px hover:shadow-lg shadow-primary/20 flex items-center justify-center gap-2">
                    Sign In
                    <span class="material-symbols-outlined text-[18px]">login</span>
                </button>
                 <div id="loader" class="hidden flex justify-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                </div>
            </form>
        </div>
        <div class="bg-slate-50 dark:bg-slate-900/50 p-4 border-t border-slate-100 dark:border-slate-700 text-center">
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Are you a candidate? <a href="../candidate_registration/login.php" class="text-primary font-medium hover:underline">Login here</a>
            </p>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('#adminLoginForm').on('submit', function(e) {
            e.preventDefault();
            
            const btn = $('#submitBtn');
            const loader = $('#loader');
            const alert = $('#alertMessage');
            
            btn.addClass('hidden');
            loader.removeClass('hidden');
            alert.addClass('hidden').removeClass('bg-red-100 text-red-700 bg-green-100 text-green-700');

            $.ajax({
                url: '../api/auth_login.php', // Reusing the same auth logic
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        alert.addClass('bg-green-100 text-green-700').removeClass('hidden');
                        alert.text('Login successful! Redirecting...');
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 1000);
                    } else {
                        btn.removeClass('hidden');
                        loader.addClass('hidden');
                        alert.addClass('bg-red-100 text-red-700').removeClass('hidden');
                        alert.text(response.message || 'Invalid credentials');
                    }
                },
                error: function() {
                    btn.removeClass('hidden');
                    loader.addClass('hidden');
                    alert.addClass('bg-red-100 text-red-700').removeClass('hidden');
                    alert.text('Connection error. Please try again.');
                }
            });
        });
    });
    </script>
</body>
</html>
