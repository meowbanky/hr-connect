<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Candidate Portal - Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="/assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet"/>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-text-main dark:text-gray-100 min-h-screen flex flex-col transition-colors duration-200">
<!-- Top Navigation -->
<header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-slate-200 dark:border-slate-800 bg-white dark:bg-[#1a1a2e] px-6 lg:px-10 py-3 sticky top-0 z-50">
    <div class="flex items-center gap-3 text-text-main dark:text-white">
        <div class="size-8 flex items-center justify-center bg-primary/10 dark:bg-primary/20 rounded-lg text-primary">
            <span class="material-symbols-outlined text-[20px]">person_search</span>
        </div>
        <h2 class="text-text-main dark:text-white text-lg font-bold leading-tight tracking-[-0.015em]">Candidate Portal</h2>
    </div>
    <button class="flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-9 px-4 bg-primary/10 hover:bg-primary/20 text-primary dark:text-blue-300 text-sm font-bold leading-normal transition-colors">
        <span class="truncate">Help Center</span>
    </button>
</header>
<!-- Main Content Area -->
<main class="flex-1 flex flex-col items-center justify-center p-4 lg:p-8">
    <div class="w-full max-w-[1100px] bg-white dark:bg-[#1a1a2e] rounded-2xl shadow-xl border border-slate-100 dark:border-slate-800 overflow-hidden flex flex-col lg:flex-row min-h-[600px]">
        <!-- Left Panel: Visual/Marketing -->
        <div class="relative w-full lg:w-5/12 hidden lg:flex flex-col justify-between p-8 bg-slate-900 text-white overflow-hidden">
            <!-- Background Image with Overlay -->
            <div class="absolute inset-0 z-0">
                <img alt="Background office image" class="w-full h-full object-cover opacity-60 mix-blend-overlay" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDwnjwgYgERrmIaw0nIRR214wmdkcha0xdaQkkoSVhJbZxjzlKCtMEeetu2v68NalUPLs9Z3v-nIBffqpv--nWEhgho-HG_ofkLmc7YxY_zlvq8pvlx3avAy_MwvM2EzYAgnO0S0TNZqvEwbz04_WAVo-J5WwA5uOsli-VpZEz8QCxVKiAel0PNXCMdpW-Sc6C9bbzW9Si_LJ7VcRKd3uJ-RYcCa47HCk6p793z1u5Q4flUFXmhkhJ09d4cIKgjFFbrpkQMibLIlKA"/>
                <div class="absolute inset-0 bg-gradient-to-br from-primary/90 to-purple-900/90 mix-blend-multiply"></div>
            </div>
            <!-- Content -->
            <div class="relative z-10 h-full flex flex-col justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-sm border border-white/10 text-xs font-medium text-white mb-6">
                        <span class="material-symbols-outlined text-[14px]">stars</span>
                        Top Employer 2024
                    </div>
                    <h2 class="text-3xl font-bold leading-tight mb-4">Join the future of work today.</h2>
                    <p class="text-blue-100 text-lg leading-relaxed">Create your profile once and apply to thousands of top-tier jobs with a single click. Track your applications in real-time.</p>
                </div>
                <!-- Avatars -->
                <div class="space-y-4">
                    <div class="flex items-center gap-4">
                        <div class="flex -space-x-3">
                            <img alt="User 1" class="w-10 h-10 rounded-full border-2 border-slate-900 object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAqa25mOqMvOtzfAwUHcm3cdZdu1hpenwUoLX4c1d6K9BVHvB2JLlEYv5kFS_U1ph9flyA6mpNDvqs0q05PNVU8pZnZAIPKnFVfOD1f3u4dUSCpaZth39s8FTazMZgLBy0sUDFQ91VyWq4-D2XKGbuATiW-rCGdHFgT5qsf0GbhiOwRhH2tZy5qGqsINzpC_IZnivFuq25sZLV1HEfp1HGwLp3d2QjtgPRkKGlivPV8i4od9DESUlvCYUydcQUN1A-6fmRbwS3wCy0"/>
                            <img alt="User 2" class="w-10 h-10 rounded-full border-2 border-slate-900 object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAbJvPGZp1iOchmMlzDvPWz9yVBSQN2pQgf9J29ZAr9re-g-NIGHJbYWOiPLV5S9sJxiDtjCZGNxkwyoQ7SfFz-rPvxj6yMCzz77XzaOWeg4n3A8TWnkW7iHC-smmKMOEW48UUOpB19aYoU7Fh85qTSj5UyxifNBvXJ1MdS30q8fVek38ZQwBzFzc42hdr2fgjh3J6jNeYiOQx3DOBk3hBGzh5EtoVeRsbvi8DOtP5iFspgu9hymehzsXwNVnohBuWw4vxlV5N1Wfo"/>
                            <img alt="User 3" class="w-10 h-10 rounded-full border-2 border-slate-900 object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAH2hoSE-Lod2oE7Vunj8r-GauRbl8_L08RsCHYXnWxFnf1d_33sD9jHjIQtqm51k5vcjR5jlHeRuVH4r76Ae0QWXCrbtIoN_bPEWsKAiCsFdjukaTS865lAdisRuNW1s63iU-yaOraxIgSb7qgoS-DlCIgTMfN9VVCQmdMlWSMZW1Fjduriz6JPySgN4suwxgiB0acq1_D_bbJngkX8ii69FPw2kg_pOSb2P8LL26-eGQdYhIRbQI_7HTOVKwXkm-q4np5m0XWqKjc"/>
                        </div>
                        <p class="text-sm font-medium text-white">Join 10,000+ candidates hired this month.</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Right Panel: Form -->
        <div class="w-full lg:w-7/12 p-6 md:p-10 lg:p-12 flex flex-col">
            <div class="max-w-[480px] mx-auto w-full flex-1 flex flex-col justify-center">
                <!-- Header -->
                <div class="text-center mb-8">
                    <h1 class="text-2xl md:text-3xl font-bold text-text-main dark:text-white mb-2">Welcome to Candidate Portal</h1>
                    <p class="text-text-secondary dark:text-slate-400">Manage your profile and track your applications</p>
                </div>

                <div id="alertMessage" class="hidden px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"></span>
                </div>

                <!-- Tabs -->
                <div class="mb-8">
                    <div class="flex border-b border-slate-200 dark:border-slate-700">
                        <a href="/login" class="flex-1 pb-3 pt-2 text-center border-b-2 border-transparent text-text-secondary dark:text-slate-400 hover:text-primary transition-colors font-bold text-sm">
                            Log In
                        </a>
                        <div class="flex-1 pb-3 pt-2 text-center border-b-2 border-primary text-primary dark:text-blue-400 font-bold text-sm relative">
                            Register
                        </div>
                    </div>
                </div>
                <!-- Form -->
                <form id="registerForm" class="space-y-5">
                    <!-- Name Field -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-text-main dark:text-gray-200 text-sm font-medium leading-normal" for="fullname">Full Name</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">person</span>
                            <input class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-background-light dark:bg-slate-800 text-text-main dark:text-white h-12 pl-11 pr-4 focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder:text-slate-400 dark:placeholder:text-slate-500 text-sm font-normal transition-all" id="fullname" name="fullname" placeholder="Jane Doe" required="" type="text"/>
                        </div>
                    </div>
                    <!-- Email Field -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-text-main dark:text-gray-200 text-sm font-medium leading-normal" for="email">Email Address</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">mail</span>
                            <input class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-background-light dark:bg-slate-800 text-text-main dark:text-white h-12 pl-11 pr-4 focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder:text-slate-400 dark:placeholder:text-slate-500 text-sm font-normal transition-all" id="email" name="email" placeholder="jane@example.com" required="" type="email"/>
                        </div>
                    </div>
                    <!-- Password Field -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-text-main dark:text-gray-200 text-sm font-medium leading-normal" for="password">Password</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">lock</span>
                            <input class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-background-light dark:bg-slate-800 text-text-main dark:text-white h-12 pl-11 pr-12 focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder:text-slate-400 dark:placeholder:text-slate-500 text-sm font-normal transition-all" id="password" name="password" placeholder="Create a password" required="" type="password" oninput="checkPasswordStrength(this.value)"/>
                            <button class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors" type="button" onclick="togglePassword('password')">
                                <span class="material-symbols-outlined text-[20px]">visibility</span>
                            </button>
                        </div>
                        <!-- Password Strength Bars -->
                        <div class="flex gap-1 mt-1">
                            <div id="strength-1" class="h-1 flex-1 rounded-full bg-slate-200 dark:bg-slate-700 transition-colors duration-300"></div>
                            <div id="strength-2" class="h-1 flex-1 rounded-full bg-slate-200 dark:bg-slate-700 transition-colors duration-300"></div>
                            <div id="strength-3" class="h-1 flex-1 rounded-full bg-slate-200 dark:bg-slate-700 transition-colors duration-300"></div>
                            <div id="strength-4" class="h-1 flex-1 rounded-full bg-slate-200 dark:bg-slate-700 transition-colors duration-300"></div>
                        </div>
                        <p class="text-xs text-slate-400 dark:text-slate-500">Must be at least 8 characters.</p>
                    </div>
                    <!-- Confirm Password Field -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-text-main dark:text-gray-200 text-sm font-medium leading-normal" for="confirm-password">Confirm Password</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">lock_reset</span>
                            <input class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-background-light dark:bg-slate-800 text-text-main dark:text-white h-12 pl-11 pr-4 focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder:text-slate-400 dark:placeholder:text-slate-500 text-sm font-normal transition-all" id="confirm-password" name="confirm_password" placeholder="Confirm your password" required="" type="password"/>
                        </div>
                    </div>
                    <!-- Terms Checkbox -->
                    <div class="flex items-start gap-3 pt-2">
                        <div class="flex items-center h-5">
                            <input class="w-4 h-4 border border-slate-300 dark:border-slate-600 rounded bg-white dark:bg-slate-800 text-primary focus:ring-primary/20 focus:ring-offset-0" id="terms" name="terms" type="checkbox" required/>
                        </div>
                        <label class="text-sm text-text-secondary dark:text-slate-400 leading-tight" for="terms">
                            I agree to the <a class="text-primary font-medium hover:underline" href="#">Terms of Service</a> and <a class="text-primary font-medium hover:underline" href="#">Privacy Policy</a>.
                        </label>
                    </div>
                    <!-- Submit Button -->
                    <button id="submitBtn" class="w-full h-12 bg-primary hover:bg-blue-700 text-white rounded-lg font-bold text-sm tracking-wide transition-colors shadow-lg shadow-primary/20 flex items-center justify-center gap-2" type="submit">
                        Create Account
                        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                    </button>
                    <!-- Loader -->
                    <div id="loader" class="hidden flex justify-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                    </div>
                </form>
                <!-- Divider -->
                <div class="relative flex py-6 items-center">
                    <div class="flex-grow border-t border-slate-200 dark:border-slate-700"></div>
                    <span class="flex-shrink-0 mx-4 text-xs font-medium text-slate-400 uppercase tracking-widest">Or continue with</span>
                    <div class="flex-grow border-t border-slate-200 dark:border-slate-700"></div>
                </div>
                <!-- Social Login -->
                <div class="grid grid-cols-2 gap-4">
                    <button class="flex items-center justify-center gap-2 h-11 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-text-main dark:text-white text-sm font-medium transition-colors">
                        <svg class="w-5 h-5" fill="none" viewbox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M21.8055 10.0415H21V10H12V14H17.6515C16.827 16.3285 14.6115 18 12 18C8.6865 18 6 15.3135 6 12C6 8.6865 8.6865 6 12 6C13.5295 6 14.921 6.577 15.9805 7.5195L18.809 4.691C17.023 3.0265 14.634 2 12 2C6.4775 2 2 6.4775 2 12C2 17.5225 6.4775 22 12 22C17.5225 22 22 17.5225 22 12C22 11.3295 21.931 10.675 21.8055 10.0415Z" fill="#FFC107"></path><path d="M3.15302 7.3455L6.43852 9.755C7.32752 7.554 9.48052 6 12.0005 6C13.5295 6 14.921 6.577 15.9805 7.5195L18.809 4.691C17.023 3.0265 14.634 2 12.0005 2C8.15902 2 4.82802 4.1685 3.15302 7.3455Z" fill="#FF3D00"></path><path d="M12.0005 22C14.6605 22 17.0715 20.9455 18.8655 19.2435L15.6115 16.711C14.5805 17.545 13.3345 18 12.0005 18C9.35652 18 7.11452 16.29 6.29152 13.918L3.06452 16.417C4.72152 19.689 8.08152 22 12.0005 22Z" fill="#4CAF50"></path><path d="M21.8055 10.0415H21V10H12V14H17.6515C17.257 15.108 16.546 16.0755 15.6115 16.711L18.8655 19.2435C20.726 17.5255 21.8685 15.019 21.9845 12.222C21.995 11.9675 22.0005 11.7115 22.0005 11.4545C22.0005 10.975 21.935 10.5025 21.8055 10.0415Z" fill="#1976D2"></path></svg>
                        Google
                    </button>
                    <button class="flex items-center justify-center gap-2 h-11 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-text-main dark:text-white text-sm font-medium transition-colors">
                        <svg class="w-5 h-5 text-[#0077b5]" fill="currentColor" viewbox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20.447 20.452H16.892V14.881C16.892 13.553 16.865 11.848 15.043 11.848C13.194 11.848 12.911 13.291 12.911 14.786V20.452H9.354V9.006H12.768V10.57H12.816C13.291 9.669 14.453 8.719 16.184 8.719C19.789 8.719 20.447 11.091 20.447 14.159V20.452ZM5.337 7.433C4.196 7.433 3.274 6.507 3.274 5.367C3.274 4.227 4.196 3.3 5.337 3.3C6.476 3.3 7.401 4.227 7.401 5.367C7.401 6.507 6.476 7.433 5.337 7.433ZM3.562 20.452H7.116V9.006H3.562V20.452ZM22.225 0H1.771C0.792 0 0 0.774 0 1.729V22.271C0 23.227 0.792 24 1.771 24H22.222C23.2 24 24 23.227 24 22.271V1.729C24 0.774 23.2 0 22.222 0H22.225Z"></path></svg>
                        LinkedIn
                    </button>
                </div>
                <div class="mt-8 text-center lg:hidden">
                    <p class="text-sm text-text-secondary dark:text-slate-400">
                        Already have an account? 
                        <a class="text-primary font-bold hover:underline" href="login.php">Log In</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer / Copyright -->
    <div class="w-full max-w-[1100px] mt-8 flex flex-col md:flex-row justify-between items-center text-xs text-text-secondary dark:text-slate-500">
        <p>© 2024 HR Management Systems Inc. All rights reserved.</p>
        <div class="flex gap-4 mt-2 md:mt-0">
            <a class="hover:text-primary transition-colors" href="#">Privacy</a>
            <a class="hover:text-primary transition-colors" href="#">Terms</a>
            <a class="hover:text-primary transition-colors" href="#">Cookies</a>
        </div>
    </div>
</main>
<script>
    function togglePassword(id) {
        var x = document.getElementById(id);
        if (x.type === "password") {
            x.type = "text";
        } else {
            x.type = "password";
        }
    }

    function checkPasswordStrength(password) {
        const bars = [
            document.getElementById('strength-1'),
            document.getElementById('strength-2'),
            document.getElementById('strength-3'),
            document.getElementById('strength-4')
        ];

        // Reset
        bars.forEach(bar => {
            bar.className = 'h-1 flex-1 rounded-full bg-slate-200 dark:bg-slate-700 transition-colors duration-300';
        });

        if (password.length === 0) return;

        // Calculate strength
        let strength = 0;
        if (password.length >= 8) strength++; // Min length
        if (password.match(/[0-9]/)) strength++; // Number
        if (password.match(/[^a-zA-Z0-9]/)) strength++; // Symbol
        if (password.match(/[A-Z]/)) strength++; // Uppercase

        // Cap at 4
        if (password.length > 0 && password.length < 8) strength = 1; // At least show one red bar if typing
        if (strength > 4) strength = 4;

        // Colors
        const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500'];
        const color = colors[strength - 1];

        for (let i = 0; i < strength; i++) {
            bars[i].className = `h-1 flex-1 rounded-full ${color} transition-colors duration-300`;
        }
    }

    $(document).ready(function() {
        $('#registerForm').on('submit', function(e) {
            e.preventDefault();
            
            // Password Match Check (redundant if using backend validation, but good for UX)
            const pwd = $('#password').val();
            const confirm = $('#confirm-password').val();
            const alert = $('#alertMessage');

            if (pwd !== confirm) {
                alert.addClass('bg-red-100 border-red-400 text-red-700').removeClass('hidden bg-green-100 border-green-400 text-green-700');
                alert.find('span').text('Passwords do not match.');
                return;
            }

            // UI States
            const btn = $('#submitBtn');
            const loader = $('#loader');
            
            btn.addClass('hidden');
            loader.removeClass('hidden');
            alert.addClass('hidden').removeClass('bg-red-100 border-red-400 text-red-700 bg-green-100 border-green-400 text-green-700');

            $.ajax({
                url: '../api/auth_register.php',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        alert.addClass('bg-green-100 border-green-400 text-green-700').removeClass('hidden');
                        alert.find('span').text('Success! Redirecting to login...');
                        setTimeout(function() {
                            window.location.href = 'login.php';
                        }, 1500);
                    } else {
                        btn.removeClass('hidden');
                        loader.addClass('hidden');
                        alert.addClass('bg-red-100 border-red-400 text-red-700').removeClass('hidden');
                        alert.find('span').text(response.message);
                    }
                },
                error: function() {
                    btn.removeClass('hidden');
                    loader.addClass('hidden');
                    alert.addClass('bg-red-100 border-red-400 text-red-700').removeClass('hidden');
                    alert.find('span').text('An error occurred. Please try again.');
                }
            });
        });
    });
</script>
</body>
</html>
