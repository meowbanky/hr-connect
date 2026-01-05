<?php
session_start();
require_once __DIR__ . '/../includes/settings.php';
$paymentEnabled = is_payment_enabled();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Candidate Application Form</title>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
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
<style>
    body { font-family: 'Inter', sans-serif; }
    /* Custom scrollbar for cleaner look */
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #d0d0e7; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #a0a0c7; }
    .dark ::-webkit-scrollbar-thumb { background: #33334d; }
    
    /* Step visibility logic */
    .form-step { display: none; }
    .form-step.active { display: block; animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>
<?php echo get_theme_css(); ?>
</head>
<body class="bg-background-light dark:bg-background-dark min-h-screen flex flex-col transition-colors duration-200">
<!-- Top Navigation -->
<header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark px-6 lg:px-10 py-3 sticky top-0 z-50">
    <div class="flex items-center gap-4 text-text-main dark:text-white">
        <div class="size-8 flex items-center justify-center text-primary">
            <span class="material-symbols-outlined text-3xl">work_outline</span>
        </div>
        <h2 class="text-text-main dark:text-white text-lg font-bold leading-tight tracking-[-0.015em]">HR Manager</h2>
    </div>
    <div class="flex flex-1 justify-end gap-8 items-center">
        <div class="hidden md:flex items-center gap-9">
            <a class="text-text-main dark:text-white text-sm font-medium leading-normal hover:text-primary transition-colors" href="../job_board_&_candidate_portal/index.php">Jobs</a>
            <a class="text-text-main dark:text-white text-sm font-medium leading-normal hover:text-primary transition-colors" href="../candidate_application_myapplication/index.php">My Applications</a>
            <a class="text-text-main dark:text-white text-sm font-medium leading-normal hover:text-primary transition-colors" href="#">Profile</a>
        </div>
        <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10 ring-2 ring-white dark:ring-surface-dark shadow-sm bg-gray-200"></div>
    </div>
</header>

<!-- Main Content Layout -->
<main class="flex-grow flex flex-col items-center py-8 px-4 md:px-10">
    <div class="w-full max-w-[960px] flex flex-col gap-6">
        
        <!-- Page Heading & Back Button -->
        <div class="flex flex-wrap justify-between items-start gap-4 p-4">
            <div class="flex min-w-72 flex-col gap-3">
                <h1 class="text-text-main dark:text-white text-3xl md:text-4xl font-black leading-tight tracking-[-0.033em]">Apply for Senior Product Designer</h1>
                <p class="text-text-secondary dark:text-gray-400 text-base font-normal leading-normal">Please complete the form below to submit your candidacy.</p>
            </div>
            <a href="../job_board_&_candidate_portal/index.php" class="flex items-center justify-center gap-2 rounded-lg h-10 px-4 bg-white dark:bg-surface-dark border border-border-light dark:border-border-dark text-text-main dark:text-white text-sm font-bold shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                <span class="material-symbols-outlined text-lg">arrow_back</span>
                <span class="truncate">Back to Job Board</span>
            </a>
        </div>

        <!-- Progress Bar -->
        <div class="bg-surface-light dark:bg-surface-dark rounded-xl p-6 shadow-sm border border-border-light dark:border-border-dark">
            <div class="flex flex-col gap-3">
                <div class="flex gap-6 justify-between items-center">
                    <p class="text-text-main dark:text-white text-base font-bold leading-normal">Application Progress</p>
                    <span id="progressText" class="text-primary text-sm font-bold bg-primary/10 px-2 py-1 rounded">25%</span>
                </div>
                <div class="rounded-full bg-gray-200 dark:bg-gray-700 h-2 overflow-hidden">
                    <div id="progressBar" class="h-full rounded-full bg-primary transition-all duration-500 ease-out" style="width: 25%;"></div>
                </div>
                <div class="flex justify-between text-sm">
                    <p id="stepLabel" class="text-text-secondary dark:text-gray-400 font-medium">Step 1 of 4: Personal Details</p>
                </div>
            </div>
        </div>

        <!-- Main Form Card -->
        <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-lg border border-border-light dark:border-border-dark overflow-hidden">
            <!-- Success Message Modal (Hidden by default) -->
            <div id="successModal" class="hidden absolute inset-0 bg-white/95 z-50 flex flex-col items-center justify-center p-6 text-center animate-[fadeIn_0.5s_ease-out]">
                <div class="mb-4 text-green-500 bg-green-50 p-6 rounded-full">
                    <span class="material-symbols-outlined text-6xl">check_circle</span>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Success!</h2>
                <p class="text-gray-600 dark:text-gray-400 max-w-md mx-auto mb-8 text-lg">Your application has been submitted successfully. We have sent a confirmation email to your inbox.</p>
                <a href="../job_board_&_candidate_portal/index.php" class="bg-primary hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg shadow-lg hover:-translate-y-1 transition-all">
                    Back to Jobs
                </a>
            </div>

            <!-- Upload Progress Modal (Hidden by default) -->
            <div id="uploadModal" class="hidden absolute inset-0 bg-white/95 z-50 flex flex-col items-center justify-center p-6 text-center animate-[fadeIn_0.3s_ease-out]">
                <div class="mb-6 relative">
                    <div class="w-16 h-16 rounded-full border-4 border-gray-100 border-t-primary animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-2xl">cloud_upload</span>
                    </div>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Submitting Application...</h2>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Please wait while we upload your documents.</p>
                
                <div class="w-full max-w-sm bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mb-2 overflow-hidden">
                    <div id="uploadProgressBar" class="bg-primary h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <p id="uploadProgressText" class="text-sm font-bold text-primary">0%</p>
            </div>

            <form id="applicationForm" class="flex flex-col" onsubmit="handleSubmission(event)" novalidate>
                
                <!-- Step 1: Personal Details -->
                <div id="step1" class="form-step active">
                    <div class="p-6 md:p-8 border-b border-border-light dark:border-border-dark">
                        <div class="flex items-center gap-3 mb-6">
                            <span class="material-symbols-outlined text-primary text-2xl">person</span>
                            <h2 class="text-text-main dark:text-white text-2xl font-bold leading-tight">Personal Details</h2>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <label class="flex flex-col flex-1">
                                <p class="text-text-main dark:text-gray-300 text-sm font-semibold pb-2">First Name <span class="text-red-500">*</span></p>
                                <input name="first_name" class="form-input w-full rounded-lg border border-border-light dark:border-border-dark bg-[#f8f8fc] dark:bg-background-dark h-12 px-4 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary placeholder:text-text-secondary/60 transition-all" placeholder="Enter your first name" required>
                            </label>
                            <label class="flex flex-col flex-1">
                                <p class="text-text-main dark:text-gray-300 text-sm font-semibold pb-2">Last Name <span class="text-red-500">*</span></p>
                                <input name="last_name" class="form-input w-full rounded-lg border border-border-light dark:border-border-dark bg-[#f8f8fc] dark:bg-background-dark h-12 px-4 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary placeholder:text-text-secondary/60 transition-all" placeholder="Enter your last name" required>
                            </label>
                            <label class="flex flex-col flex-1">
                                <p class="text-text-main dark:text-gray-300 text-sm font-semibold pb-2">Date of Birth</p>
                                <input name="dob" class="form-input w-full rounded-lg border border-border-light dark:border-border-dark bg-[#f8f8fc] dark:bg-background-dark h-12 px-4 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary placeholder:text-text-secondary/60 transition-all" type="date">
                            </label>
                            <label class="flex flex-col flex-1">
                                <p class="text-text-main dark:text-gray-300 text-sm font-semibold pb-2">Gender</p>
                                <select name="gender" class="form-select w-full rounded-lg border border-border-light dark:border-border-dark bg-[#f8f8fc] dark:bg-background-dark h-12 px-4 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all">
                                    <option value="" disabled selected>Select gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Prefer not to say</option>
                                </select>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Contact Information -->
                <div id="step2" class="form-step">
                    <div class="p-6 md:p-8 border-b border-border-light dark:border-border-dark bg-gray-50/50 dark:bg-white/5">
                        <div class="flex items-center gap-3 mb-6">
                            <span class="material-symbols-outlined text-primary text-2xl">contact_mail</span>
                            <h2 class="text-text-main dark:text-white text-2xl font-bold leading-tight">Contact Information</h2>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <label class="flex flex-col flex-1">
                                <p class="text-text-main dark:text-gray-300 text-sm font-semibold pb-2">Email Address <span class="text-red-500">*</span></p>
                                <input name="email" class="form-input w-full rounded-lg border border-border-light dark:border-border-dark bg-white dark:bg-background-dark h-12 px-4 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary placeholder:text-text-secondary/60 transition-all" placeholder="name@example.com" required type="email">
                            </label>
                            <label class="flex flex-col flex-1">
                                <p class="text-text-main dark:text-gray-300 text-sm font-semibold pb-2">Phone Number <span class="text-red-500">*</span></p>
                                <input name="phone" class="form-input w-full rounded-lg border border-border-light dark:border-border-dark bg-white dark:bg-background-dark h-12 px-4 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary placeholder:text-text-secondary/60 transition-all" placeholder="+234 ..." required type="tel">
                            </label>
                            <label class="flex flex-col flex-1 md:col-span-2">
                                <p class="text-text-main dark:text-gray-300 text-sm font-semibold pb-2">LinkedIn Profile / Portfolio URL</p>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="material-symbols-outlined text-gray-400 text-[20px]">link</span>
                                    </span>
                                    <input name="linkedin" class="form-input w-full rounded-lg border border-border-light dark:border-border-dark bg-white dark:bg-background-dark h-12 pl-10 pr-4 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary placeholder:text-text-secondary/60 transition-all" placeholder="https://linkedin.com/in/..." type="url">
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Professional Summary & Uploads -->
                <div id="step3" class="form-step">
                    <div class="p-6 md:p-8 border-b border-border-light dark:border-border-dark">
                        <div class="flex items-center gap-3 mb-6">
                            <span class="material-symbols-outlined text-primary text-2xl">description</span>
                            <h2 class="text-text-main dark:text-white text-2xl font-bold leading-tight">Professional & Education</h2>
                        </div>
                        <label class="flex flex-col flex-1 mb-6">
                            <p class="text-text-main dark:text-gray-300 text-sm font-semibold pb-2">Professional Bio</p>
                            <textarea name="bio" class="form-textarea w-full rounded-lg border border-border-light dark:border-border-dark bg-[#f8f8fc] dark:bg-background-dark min-h-[120px] p-4 text-text-main dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary placeholder:text-text-secondary/60 resize-y transition-all" placeholder="Tell us briefly about your experience..."></textarea>
                        </label>

                        <!-- Dynamic Education Section -->
                        <div class="border-t border-border-light dark:border-border-dark pt-6">
                            <h3 class="text-lg font-bold text-text-main dark:text-white mb-4">Education History</h3>
                            <div id="educationContainer" class="flex flex-col gap-4">
                                <!-- Dynamic Rows will appear here -->
                            </div>
                            <button type="button" onclick="addEducationRow()" class="mt-4 text-primary font-bold text-sm flex items-center gap-1 hover:underline">
                                <span class="material-symbols-outlined text-lg">add_circle</span> Add Education
                            </button>
                        </div>
                    </div>
                    
                    <div class="p-6 md:p-8 border-b border-border-light dark:border-border-dark bg-gray-50/50 dark:bg-white/5">
                        <div class="flex items-center gap-3 mb-6">
                            <span class="material-symbols-outlined text-primary text-2xl">upload_file</span>
                            <h2 class="text-text-main dark:text-white text-2xl font-bold leading-tight">Upload Credentials</h2>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Resume Upload -->
                            <div class="flex flex-col gap-2">
                                <p class="text-text-main dark:text-gray-300 text-sm font-semibold">Resume / CV <span class="text-red-500">*</span></p>
                                <div id="resumeDropZone" class="group relative flex flex-col items-center justify-center w-full h-48 border-2 border-dashed border-border-light dark:border-border-dark hover:border-primary rounded-xl bg-white dark:bg-background-dark transition-colors cursor-pointer overflow-hidden">
                                     <!-- Default Placeholder -->
                                    <div id="resumePlaceholder" class="flex flex-col items-center justify-center pt-5 pb-6 pointer-events-none transition-opacity duration-200">
                                        <div class="mb-3 p-3 rounded-full bg-blue-50 dark:bg-blue-900/20 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/30 transition-colors">
                                            <span class="material-symbols-outlined text-primary text-3xl">cloud_upload</span>
                                        </div>
                                        <p class="mb-2 text-sm text-text-main dark:text-gray-300"><span class="font-semibold text-primary">Click to upload</span> or drag and drop</p>
                                        <p class="text-xs text-text-secondary dark:text-gray-500">PDF, DOCX (MAX. 5MB)</p>
                                    </div>
                                    <!-- Preview State (Hidden by default) -->
                                    <div id="resumePreview" class="hidden absolute inset-0 w-full h-full bg-white dark:bg-surface-dark z-20 flex-col items-center justify-center p-4">
                                        <div class="mb-2" id="resumeIcon"></div>
                                        <p class="text-sm font-medium text-text-main dark:text-white truncate max-w-full px-4" id="resumeName"></p>
                                        <button type="button" class="mt-2 text-xs text-red-500 hover:text-red-700 underline z-30 relative" onclick="clearFile('resumeInput', 'resumePreview', 'resumePlaceholder'); event.stopPropagation();">Remove file</button>
                                    </div>
                                    
                                    <input id="resumeInput" name="resume" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" type="file" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                </div>
                            </div>
                            
                            <!-- Cover Letter Upload -->
                            <div class="flex flex-col gap-2">
                                <p class="text-text-main dark:text-gray-300 text-sm font-semibold">Cover Letter</p>
                                <div id="coverDropZone" class="group relative flex flex-col items-center justify-center w-full h-48 border-2 border-dashed border-border-light dark:border-border-dark hover:border-primary rounded-xl bg-white dark:bg-background-dark transition-colors cursor-pointer overflow-hidden">
                                    <div id="coverPlaceholder" class="flex flex-col items-center justify-center pt-5 pb-6 pointer-events-none transition-opacity duration-200">
                                        <div class="mb-3 p-3 rounded-full bg-blue-50 dark:bg-blue-900/20 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/30 transition-colors">
                                            <span class="material-symbols-outlined text-primary text-3xl">cloud_upload</span>
                                        </div>
                                        <p class="mb-2 text-sm text-text-main dark:text-gray-300"><span class="font-semibold text-primary">Click to upload</span> or drag and drop</p>
                                        <p class="text-xs text-text-secondary dark:text-gray-500">PDF, DOCX (MAX. 5MB)</p>
                                    </div>
                                     <!-- Preview State -->
                                    <div id="coverPreview" class="hidden absolute inset-0 w-full h-full bg-white dark:bg-surface-dark z-20 flex-col items-center justify-center p-4">
                                        <div class="mb-2" id="coverIcon"></div>
                                        <p class="text-sm font-medium text-text-main dark:text-white truncate max-w-full px-4" id="coverName"></p>
                                        <button type="button" class="mt-2 text-xs text-red-500 hover:text-red-700 underline z-30 relative" onclick="clearFile('coverInput', 'coverPreview', 'coverPlaceholder'); event.stopPropagation();">Remove file</button>
                                    </div>

                                    <input id="coverInput" name="cover_letter" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Certificates Section -->
                        <div class="mt-8 pt-6 border-t border-border-light dark:border-border-dark">
                             <h3 class="text-lg font-bold text-text-main dark:text-white mb-4">Other Certificates & Documents</h3>
                             <div id="certificatesContainer" class="flex flex-col gap-4">
                                <!-- Dynamic Rows will appear here -->
                             </div>
                             <button type="button" onclick="addCertificateRow()" class="mt-4 text-primary font-bold text-sm flex items-center gap-1 hover:underline">
                                <span class="material-symbols-outlined text-lg">add_circle</span> Add Certificate
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Payment -->
                <div id="step4" class="form-step">
                    <div class="p-6 md:p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <span class="material-symbols-outlined text-primary text-2xl">payments</span>
                            <h2 class="text-text-main dark:text-white text-2xl font-bold leading-tight">Application Fee</h2>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900/10 border border-orange-200 dark:border-orange-800 rounded-xl p-5 flex flex-col md:flex-row items-center justify-between gap-6">
                            <div class="flex items-center gap-4">
                                <div class="bg-white p-2 rounded-lg shadow-sm h-14 w-24 flex items-center justify-center overflow-hidden">
                                     <!-- Remita Logo Placeholder -->
                                     <span class="font-bold text-gray-500">Remita</span>
                                </div>
                                <div>
                                    <p class="text-text-main dark:text-white font-bold text-lg">
                                        <?php echo get_currency_symbol(); ?> 2,000.00
                                    </p>
                                    <p class="text-sm text-text-secondary dark:text-orange-200">Standard application processing fee.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-text-secondary dark:text-gray-400">
                                <span class="material-symbols-outlined text-green-600">lock</span>
                                <span>Secured by Remita</span>
                            </div>
                        </div>
                        <div class="mt-4 flex items-start gap-3">
                            <input class="mt-1 w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" id="terms" type="checkbox" required>
                            <label class="text-sm text-text-secondary dark:text-gray-400" for="terms">
                                I hereby confirm that the information provided is true and accurate. I understand that the application fee is non-refundable.
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="p-6 md:p-8 bg-gray-50 dark:bg-surface-dark border-t border-border-light dark:border-border-dark flex flex-col-reverse sm:flex-row items-center justify-between gap-4">
                     <!-- Prev Button -->
                    <button id="prevBtn" type="button" class="hidden w-full sm:w-auto px-6 h-12 rounded-lg border border-border-light dark:border-border-dark text-text-main dark:text-white font-bold hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors" onclick="changeStep(-1)">
                        Previous
                    </button>
                    
                    <!-- Next Button -->
                    <button id="nextBtn" type="button" class="w-full sm:w-auto px-8 h-12 rounded-lg bg-primary text-white font-bold shadow-md hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-900 transition-all flex items-center justify-center gap-2" onclick="changeStep(1)">
                        <span>Next</span>
                        <span class="material-symbols-outlined text-lg">arrow_forward</span>
                    </button>

                    <!-- Payment Button (Hidden Logic) -->
                    <button id="payBtn" type="submit" class="hidden w-full sm:w-auto px-8 h-12 rounded-lg bg-green-600 text-white font-bold shadow-md hover:bg-green-700 transition-all flex items-center justify-center gap-2">
                        <span>Proceed to Payment</span>
                        <span class="material-symbols-outlined text-lg">credit_card</span>
                    </button>
                </div>
            </form>
        </div>
        <p class="text-center text-xs text-text-secondary dark:text-gray-500 pb-8">
            © 2023 HR Management Portal. All rights reserved.
        </p>
    </div>
</main>

<script>
    const isPaymentEnabled = <?php echo $paymentEnabled ? 'true' : 'false'; ?>;
    let currentStep = 1;
    const totalSteps = isPaymentEnabled ? 4 : 3;

    function updateUI() {
        // Toggle Steps
        document.querySelectorAll('.form-step').forEach((el, index) => {
            if (index + 1 === currentStep) {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        });

        // Update Progress Bar
        const progress = (currentStep / totalSteps) * 100;
        document.getElementById('progressBar').style.width = progress + '%';
        document.getElementById('progressText').innerText = Math.round(progress) + '%';
        
        // Update Step Label
        let steps = ['Personal Details', 'Contact Info', 'Professional & Education'];
        if (isPaymentEnabled) steps.push('Payment');
        
        document.getElementById('stepLabel').innerText = `Step ${currentStep} of ${totalSteps}: ${steps[currentStep-1]}`;

        // Button Logic
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const payBtn = document.getElementById('payBtn');

        // Prev Button Visibility
        if (currentStep === 1) {
            prevBtn.classList.add('hidden');
        } else {
            prevBtn.classList.remove('hidden');
        }

        // Next/Pay/Submit Button Logic
        if (currentStep === totalSteps) {
            nextBtn.classList.add('hidden');
            payBtn.classList.remove('hidden');
            
            if (isPaymentEnabled) {
                // Payment Mode
                payBtn.innerHTML = `<span>Proceed to Payment</span><span class="material-symbols-outlined text-lg">credit_card</span>`;
                payBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                payBtn.classList.add('bg-green-600', 'hover:bg-green-700');
                // Check terms logic only if payment step (Step 4)
                if(currentStep === 4) checkFinalValidation();
                else {
                    // If no payment step (Step 3 is final), we might need validation or just enable it
                    payBtn.disabled = false;
                    payBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            } else {
                // Submit Mode (No Payment)
                payBtn.innerHTML = `<span>Submit Application</span><span class="material-symbols-outlined text-lg">send</span>`;
                payBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
                payBtn.classList.add('bg-primary', 'hover:bg-blue-700');
                payBtn.disabled = false;
                payBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            
        } else {
            nextBtn.classList.remove('hidden');
            payBtn.classList.add('hidden');
        }
    }

    function checkFinalValidation() {
        const terms = document.getElementById('terms');
        const payBtn = document.getElementById('payBtn');
        if (terms.checked) {
            payBtn.disabled = false;
            payBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            payBtn.disabled = true;
            payBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    // Attach listener to terms checkbox
    document.getElementById('terms').addEventListener('change', checkFinalValidation);

    function validateStep(step) {
        const activeStepEl = document.getElementById('step' + step);
        const inputs = activeStepEl.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('border-red-500');
                // Basic shake or focus logic could go here
            } else {
                input.classList.remove('border-red-500');
            }
        });

        if (!isValid) {
            alert('Please fill in all required fields.');
        }
        return isValid;
    }

    function changeStep(direction) {
        // If moving forward, validate current step
        if (direction === 1) {
            if (!validateStep(currentStep)) return;
        }

        const newStep = currentStep + direction;
        
        if (newStep >= 1 && newStep <= totalSteps) {
            currentStep = newStep;
            updateUI();
            window.scrollTo(0, 0);
        }
    }

    // Initial Load
    updateUI();
    // Add one initial row for Education
    addEducationRow();

    /* --- File Upload & Drag-n-Drop Logic --- */

    function initFileHandler(inputId, dropZoneId, previewId, placeholderId, nameId, iconId) {
        const input = document.getElementById(inputId);
        const dropZone = document.getElementById(dropZoneId);
        
        // Handle Input Change
        input.addEventListener('change', function(e) {
            handleFile(this.files[0], previewId, placeholderId, nameId, iconId);
        });

        // Drag & Drop Events
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropZone.classList.add('border-primary', 'bg-blue-50', 'dark:bg-blue-900/10');
        }

        function unhighlight(e) {
            dropZone.classList.remove('border-primary', 'bg-blue-50', 'dark:bg-blue-900/10');
        }

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length) {
                input.files = files; // Assign dropped files to input
                handleFile(files[0], previewId, placeholderId, nameId, iconId);
            }
        }
    }

    function handleFile(file, previewId, placeholderId, nameId, iconId) {
        if (!file) return;

        const preview = document.getElementById(previewId);
        const placeholder = document.getElementById(placeholderId);
        const nameEl = document.getElementById(nameId);
        const iconEl = document.getElementById(iconId);

        // Show Name
        nameEl.textContent = file.name;

        // Generate Icon/Preview
        let iconHtml = '';
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                 iconHtml = `<img src="${e.target.result}" class="h-16 w-16 object-cover rounded-lg shadow-sm">`;
                 iconEl.innerHTML = iconHtml;
            }
            reader.readAsDataURL(file);
        } else if (file.type === 'application/pdf') {
             iconHtml = `<span class="material-symbols-outlined text-red-500 text-5xl">picture_as_pdf</span>`;
             iconEl.innerHTML = iconHtml;
        } else {
             iconHtml = `<span class="material-symbols-outlined text-blue-500 text-5xl">description</span>`;
             iconEl.innerHTML = iconHtml;
        }

        // Toggle UI
        placeholder.classList.add('hidden');
        preview.classList.remove('hidden');
        preview.classList.add('flex');
    }

    function clearFile(inputId, previewId, placeholderId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const placeholder = document.getElementById(placeholderId);

        input.value = ''; // Clear input
        preview.classList.add('hidden');
        preview.classList.remove('flex');
        placeholder.classList.remove('hidden');
    }

    // Initialize Handlers
    initFileHandler('resumeInput', 'resumeDropZone', 'resumePreview', 'resumePlaceholder', 'resumeName', 'resumeIcon');
    initFileHandler('coverInput', 'coverDropZone', 'coverPreview', 'coverPlaceholder', 'coverName', 'coverIcon');


    /* --- Dynamic Fields Logic --- */
    
    function addEducationRow() {
        const container = document.getElementById('educationContainer');
        const rowId = 'edu_' + Date.now();
        const html = `
            <div id="${rowId}" class="p-4 rounded-lg bg-gray-50 dark:bg-white/5 border border-border-light dark:border-border-dark relative group animate-[fadeIn_0.3s_ease-out]">
                <button type="button" onclick="removeRow('${rowId}')" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 transition-colors">
                    <span class="material-symbols-outlined text-xl">delete</span>
                </button>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pr-6">
                    <label class="flex flex-col gap-1">
                        <span class="text-xs font-semibold text-text-secondary dark:text-gray-400">School / Institution</span>
                        <input name="edu_school[]" class="form-input text-sm rounded-md border-border-light dark:border-border-dark bg-white dark:bg-background-dark h-10 px-3" required placeholder="e.g. University of Lagos">
                    </label>
                    <label class="flex flex-col gap-1">
                        <span class="text-xs font-semibold text-text-secondary dark:text-gray-400">Qualification</span>
                        <input name="edu_degree[]" class="form-input text-sm rounded-md border-border-light dark:border-border-dark bg-white dark:bg-background-dark h-10 px-3" required placeholder="e.g. BSc Computer Science">
                    </label>
                    <label class="flex flex-col gap-1">
                        <span class="text-xs font-semibold text-text-secondary dark:text-gray-400">Start Date</span>
                        <input name="edu_start[]" type="date" class="form-input text-sm rounded-md border-border-light dark:border-border-dark bg-white dark:bg-background-dark h-10 px-3" required>
                    </label>
                    <label class="flex flex-col gap-1">
                        <span class="text-xs font-semibold text-text-secondary dark:text-gray-400">End Date</span>
                        <input name="edu_end[]" type="date" class="form-input text-sm rounded-md border-border-light dark:border-border-dark bg-white dark:bg-background-dark h-10 px-3">
                    </label>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    }

    function addCertificateRow() {
        const container = document.getElementById('certificatesContainer');
        const rowId = 'cert_' + Date.now();
        const html = `
             <div id="${rowId}" class="flex flex-col sm:flex-row gap-3 items-end p-4 rounded-lg bg-gray-50 dark:bg-white/5 border border-border-light dark:border-border-dark animate-[fadeIn_0.3s_ease-out]">
                <label class="flex-1 flex flex-col gap-1 w-full">
                    <span class="text-xs font-semibold text-text-secondary dark:text-gray-400">Document Name</span>
                    <input name="cert_name[]" class="form-input text-sm rounded-md border-border-light dark:border-border-dark bg-white dark:bg-background-dark h-10 px-3" required placeholder="e.g. PMP Certificate">
                </label>
                 <label class="flex flex-col gap-1 w-full sm:w-auto">
                    <span class="text-xs font-semibold text-text-secondary dark:text-gray-400">Upload File</span>
                    <input name="cert_file[]" type="file" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition-all">
                </label>
                <button type="button" onclick="removeRow('${rowId}')" class="mb-1 p-2 text-gray-400 hover:text-red-500 transition-colors">
                    <span class="material-symbols-outlined text-xl">delete</span>
                </button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    }

    function removeRow(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    function handleSubmission(e) {
        e.preventDefault();
        
        // Final Validation check
        if (currentStep < totalSteps) { 
             // Logic to ensure we don't submit prematurely if steps skipped manually
        }

        const form = document.getElementById('applicationForm');
        const submitBtn = document.getElementById('payBtn');
        const uploadModal = document.getElementById('uploadModal');
        const progressBar = document.getElementById('uploadProgressBar');
        const progressText = document.getElementById('uploadProgressText');
        
        // Prepare Data
        const formData = new FormData(form);
        const urlParams = new URLSearchParams(window.location.search);
        if (!formData.has('job_id') && urlParams.has('job_id')) {
            formData.append('job_id', urlParams.get('job_id'));
        }

        // Show Upload Modal
        uploadModal.classList.remove('hidden');
        progressBar.style.width = '0%';
        progressText.innerText = '0%';

        // XHR for Progress
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../api/submit_application.php', true);

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
                    if (result.success) {
                        // Hide Upload Modal, Show Success Modal
                        uploadModal.classList.add('hidden');
                        document.getElementById('successModal').classList.remove('hidden');
                        window.scrollTo(0,0);
                    } else {
                         throw new Error(result.message || 'Unknown error');
                    }
                } catch (err) {
                    uploadModal.classList.add('hidden');
                    alert('Submission Failed: ' + err.message);
                }
            } else {
                uploadModal.classList.add('hidden');
                alert('Server Error: ' + xhr.statusText);
            }
        };

        xhr.onerror = function() {
            uploadModal.classList.add('hidden');
            alert('An error occurred during network request.');
        };

        xhr.send(formData);
    }
</script>
</body>
</html>
