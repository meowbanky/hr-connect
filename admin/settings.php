<?php
session_start();
require_once __DIR__ . '/../includes/settings.php';

// Access Control (Simple check for now, can be expanded to role_id later)
// if (!isset($_SESSION['user_id'])) {
//     header("Location: ../candidate_registration/login.php");
//     exit;
// }

$message = '';
$messageType = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currency_symbol = $_POST['currency_symbol'] ?? '';
    // $currency_code = $_POST['currency_code'] ?? 'NGN'; // Optional
    $theme_color = $_POST['theme_color'] ?? '';
    $enable_payment = isset($_POST['enable_payment']) ? '1' : '0';
    
    if ($currency_symbol && $theme_color) {
        try {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            // Update Currency
            $stmt->execute([$currency_symbol, 'currency_symbol']);
            // Update Color
            $stmt->execute([$theme_color, 'theme_color']);
            // Update Payment
            $stmt->execute([$enable_payment, 'enable_payment']);
            
            $message = "Settings updated successfully!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Error updating settings: " . $e->getMessage();
            $messageType = "error";
        }
    } else {
        $message = "Please fill in all fields.";
        $messageType = "error";
    }
}

// Fetch current values
$current_symbol = get_setting('currency_symbol', '₦');
$current_color = get_setting('theme_color', '#1919e6');
$is_payment_enabled = is_payment_enabled();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - HR Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script> <!-- Quick Tailwind for Admin -->
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
    <?php echo get_theme_css(); ?>
</head>
<body class="bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-gray-100 min-h-screen">

<div class="max-w-4xl mx-auto px-4 py-12">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold">Admin Settings</h1>
        <a href="../job_board_&_candidate_portal/index.php" class="text-primary hover:underline font-medium">← Back to Job Board</a>
    </div>

    <!-- Feedback Message -->
    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-8">
        <form method="POST" action="">
            
            <!-- General Settings Section -->
            <div class="mb-8 pb-8 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-xl font-semibold mb-6 flex items-center gap-2">
                    <span class="w-2 h-8 bg-primary rounded-full"></span>
                    General Preferences
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Currency Symbol -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Currency Symbol</label>
                        <div class="relative">
                            <input type="text" name="currency_symbol" value="<?php echo htmlspecialchars($current_symbol); ?>" 
                                class="w-full h-12 px-4 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-lg font-mono"
                                placeholder="$" required>
                            <p class="text-xs text-gray-500 mt-2">Example: ₦, $, £, €, etc.</p>
                        </div>
                    </div>

                    <!-- Theme Color -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Theme Color</label>
                        <div class="flex items-center gap-4">
                            <input type="color" name="theme_color" value="<?php echo htmlspecialchars($current_color); ?>"
                                class="h-12 w-24 p-1 rounded-lg border border-gray-300 dark:border-gray-600 cursor-pointer">
                            <span class="text-sm font-mono text-gray-500"><?php echo htmlspecialchars($current_color); ?></span>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Pick a brand color for buttons, links, and accents.</p>
                    </div>
                </div>
                </div>
            </div>

            <!-- Payment Settings -->
            <div class="mb-8 pb-8 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-xl font-semibold mb-6 flex items-center gap-2">
                    <span class="w-2 h-8 bg-green-500 rounded-full"></span>
                    Payment Settings
                </h2>
                <div class="flex items-center gap-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="enable_payment" value="1" class="sr-only peer" <?php echo $is_payment_enabled ? 'checked' : ''; ?>>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">Enable Application Payment Fee</span>
                    </label>
                </div>
                <p class="text-xs text-gray-500 mt-2">If disabled, candidates will skip the payment step and submit directly.</p>
            </div>

            <!-- Preview Card -->
            <div class="mb-8">
                 <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Preview</label>
                 <div class="p-6 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900 flex flex-col md:flex-row gap-6 items-center justify-between">
                     <div>
                         <h3 class="text-lg font-bold text-gray-900 dark:text-white">Senior Product Designer</h3>
                         <p class="text-primary font-medium mt-1">Design Department</p>
                     </div>
                     <div class="flex gap-3">
                         <span class="px-3 py-1 bg-primary/10 text-primary text-xs font-bold rounded-full">Full-time</span>
                         <button type="button" class="px-6 py-2 bg-primary text-white font-bold rounded-lg shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity">
                             Apply Now
                         </button>
                     </div>
                 </div>
            </div>

            <!-- Submit Action -->
            <div class="flex justify-end pt-4">
                <button type="submit" class="bg-primary hover:opacity-90 text-white font-bold py-3 px-8 rounded-lg shadow-xl shadow-primary/20 transition-all transform hover:-translate-y-0.5">
                    Save Changes
                </button>
            </div>
            
        </form>
    </div>
</div>

</body>
</html>
