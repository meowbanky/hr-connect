<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title>Forgot Password - HR Connect</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link href="assets/css/style.css" rel="stylesheet"/>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
    <!-- Use same theme colors as admin login -->
    <script>
        tailwind.config = {
             darkMode: "class",
             theme: {
                 extend: {
                     colors: {
                         "primary": "#5245e8",
                     }
                 }
             }
        }
    </script>
</head>
<body class="bg-slate-50 dark:bg-slate-900 flex items-center justify-center min-h-screen p-4">

    <div class="w-full max-w-md bg-white dark:bg-slate-800 rounded-2xl shadow-xl overflow-hidden border border-slate-100 dark:border-slate-700">
        <div class="p-8">
            <div class="flex items-center justify-center gap-3 mb-8">
                <div class="size-10 rounded-lg bg-primary text-white flex items-center justify-center shadow-lg shadow-primary/30">
                    <span class="material-symbols-outlined">lock_reset</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">HR Connect</h1>
            </div>

            <div class="mb-6 text-center">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Forgot Password?</h2>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Enter your email and we'll send you reset instructions.</p>
            </div>

            <div id="alertMessage" class="hidden px-4 py-3 rounded-lg text-sm font-medium mb-6" role="alert">
                <span class="block sm:inline"></span>
            </div>

            <form id="forgotPasswordForm" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5" for="email">Email Address</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">mail</span>
                        <input class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white h-11 pl-10 pr-4 focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder:text-slate-400 dark:placeholder:text-slate-500 text-sm transition-all" id="email" name="email" placeholder="you@company.com" required type="email"/>
                    </div>
                </div>

                <button id="submitBtn" type="submit" class="w-full h-11 bg-primary hover:bg-blue-700 text-white rounded-lg font-bold text-sm tracking-wide transition-all shadow-md hover:translate-y-px hover:shadow-lg shadow-primary/20 flex items-center justify-center gap-2">
                    Send Reset Link
                    <span class="material-symbols-outlined text-[18px]">send</span>
                </button>
                 <div id="loader" class="hidden flex justify-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                </div>
            </form>
        </div>
        <div class="bg-slate-50 dark:bg-slate-900/50 p-4 border-t border-slate-100 dark:border-slate-700 text-center">
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Remember your password? <a href="/admin/login" class="text-primary font-medium hover:underline">Login here</a>
            </p>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('#forgotPasswordForm').on('submit', function(e) {
            e.preventDefault();
            
            const btn = $('#submitBtn');
            const loader = $('#loader');
            const alert = $('#alertMessage');
            
            btn.addClass('hidden');
            loader.removeClass('hidden');
            alert.addClass('hidden').removeClass('bg-red-100 text-red-700 bg-green-100 text-green-700');

            $.ajax({
                url: 'api/auth_forgot_password.php',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    btn.removeClass('hidden');
                    loader.addClass('hidden');
                    
                    if (response.success) {
                        alert.addClass('bg-green-100 text-green-700').removeClass('hidden');
                        alert.text(response.message);
                        $('#forgotPasswordForm')[0].reset();
                    } else {
                        alert.addClass('bg-red-100 text-red-700').removeClass('hidden');
                        alert.text(response.message || 'An error occurred.');
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
