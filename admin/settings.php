<?php
session_start();
// Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff')) {
    header('Location: /admin/login');
    exit;
}

require_once __DIR__ . '/../includes/settings.php';
$pageTitle = 'Admin Settings';

$message = '';
$messageType = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $currency_symbol = $_POST['currency_symbol'] ?? '';
    $theme_color = $_POST['theme_color'] ?? '';
    $enable_payment = isset($_POST['enable_payment']) ? '1' : '0';
    
    if ($currency_symbol && $theme_color) {
        try {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            
            // Handle Logo Upload
            if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../assets/logo/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileInfo = pathinfo($_FILES['company_logo']['name']);
                $extension = strtolower($fileInfo['extension']);
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'svg', 'ico', 'webp'];
                
                if (in_array($extension, $allowedExtensions)) {
                    $newFileName = 'logo_' . time() . '.' . $extension;
                    $targetPath = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $targetPath)) {
                        $dbPath = '/assets/logo/' . $newFileName;
                        
                        // Check if setting exists, if not insert, else update (using simplified logic assuming keys exist or insert on duplicate)
                        // For simplicity, we assume generic update or insert if missing
                        $check = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'company_logo'");
                        $check->execute();
                        if($check->rowCount() > 0) {
                             $stmt->execute([$dbPath, 'company_logo']);
                        } else {
                             $ins = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                             $ins->execute(['company_logo', $dbPath]);
                        }
                    } else {
                        $message = "Failed to move uploaded logo.";
                        $messageType = "error";
                    }
                } else {
                    $message = "Invalid file type. Allowed: JPG, PNG, SVG, WEBP.";
                     $messageType = "error";
                }
            }

            // Update Currency
            $stmt->execute([$currency_symbol, 'currency_symbol']);
            // Update Color
            $stmt->execute([$theme_color, 'theme_color']);
            // Update Payment
            $stmt->execute([$enable_payment, 'enable_payment']);
            
            if(!$message) {
                $message = "Settings updated successfully!";
                $messageType = "success";
            }
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
$current_logo = get_setting('company_logo', '/assets/images/logo.png'); // Default fallback
$is_payment_enabled = is_payment_enabled();

?>
<!DOCTYPE html>
<html lang="en" class="<?php echo isset($_SESSION['theme_mode']) && $_SESSION['theme_mode'] === 'dark' ? 'dark' : 'light'; ?>">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Admin Settings - HR Connect</title>
    <?php if($current_logo && file_exists(__DIR__ . '/..' . $current_logo)): ?>
        <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($current_logo); ?>">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link href="/assets/css/style.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; font-size: 24px; line-height: 1; }
        /* Fix SweetAlert2 Buttons visibility conflict */
        div:where(.swal2-container) button:where(.swal2-styled).swal2-confirm {
            background-color: #3b82f6 !important; /* Blue-500 */
            color: white !important;
        }
        div:where(.swal2-container) button:where(.swal2-styled).swal2-cancel {
            background-color: #64748b !important; /* Slate-500 */
            color: white !important;
        }
    </style>
     <?php echo get_theme_css(); ?>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-800 dark:text-slate-100 flex h-screen overflow-hidden font-display antialiased">
    
    <!-- Sidebar -->
    <?php include_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full overflow-hidden relative">
        <!-- Header -->
        <?php include_once __DIR__ . '/../includes/admin_header.php'; ?>

        <div class="flex-1 overflow-y-auto p-4 md:p-10 flex flex-col gap-6 md:gap-8">
            <div class="w-full max-w-[1000px] mx-auto flex flex-col gap-6 md:gap-8">
                
                <!-- Page Header -->
                <div>
                     <h1 class="text-2xl md:text-3xl lg:text-4xl font-black tracking-tight text-slate-900 dark:text-white mb-2">Platform Settings</h1>
                     <p class="text-slate-500 dark:text-slate-400 text-sm md:text-base">Configure global preferences, departments, and hiring attributes.</p>
                </div>

                <!-- Feedback Message -->
                <?php if ($message): ?>
                    <div class="p-4 rounded-lg flex items-center gap-2 <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'; ?>">
                         <span class="material-symbols-outlined"><?php echo $messageType === 'success' ? 'check_circle' : 'error'; ?></span>
                         <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data" class="flex flex-col gap-8">
                    
                    <!-- Branding Settings -->
                    <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-6">
                            <span class="material-symbols-outlined text-pink-500">branding_watermark</span>
                            Branding
                        </h2>
                        <div class="flex flex-col md:flex-row gap-6 items-start">
                            <div class="w-32 h-32 rounded-lg border-2 border-dashed border-slate-300 dark:border-slate-700 flex items-center justify-center overflow-hidden bg-slate-50 dark:bg-slate-900/50 relative group">
                                <img src="<?php echo htmlspecialchars($current_logo); ?>" alt="Logo" class="max-w-full max-h-full object-contain">
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-medium pointer-events-none">
                                    Current
                                </div>
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Company Logo</label>
                                <input type="file" name="company_logo" accept=".jpg,.jpeg,.png,.svg,.webp,.ico"
                                    class="block w-full text-sm text-slate-500 dark:text-slate-400
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-full file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-primary/10 file:text-primary
                                    hover:file:bg-primary/20
                                    transition-all cursor-pointer">
                                <p class="mt-2 text-xs text-slate-500">Recommended size: 200x200px. Formats: PNG, JPG, SVG.</p>
                            </div>
                        </div>
                    </div>

                    <!-- General Settings -->
                    <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-6">
                            <span class="material-symbols-outlined text-primary">tune</span>
                            General Preferences
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Currency -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Currency Symbol</label>
                                <input type="text" name="currency_symbol" value="<?php echo htmlspecialchars($current_symbol); ?>" 
                                    class="w-full h-12 px-4 rounded-lg border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-lg font-mono"
                                    placeholder="$" required>
                            </div>
                            <!-- Theme Color -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Theme Color</label>
                                <div class="flex items-center gap-4">
                                    <input type="color" name="theme_color" value="<?php echo htmlspecialchars($current_color); ?>"
                                        class="h-12 w-24 p-1 rounded-lg border border-slate-300 dark:border-slate-700 cursor-pointer bg-transparent">
                                    <span class="text-sm font-mono text-slate-500"><?php echo htmlspecialchars($current_color); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Settings -->
                    <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6">
                         <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-6">
                            <span class="material-symbols-outlined text-green-500">payments</span>
                            Payment Gateway
                        </h2>
                        <div class="flex items-center gap-4 p-4 rounded-lg bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800">
                             <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enable_payment" value="1" class="sr-only peer" <?php echo $is_payment_enabled ? 'checked' : ''; ?>>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                <span class="ml-3 text-sm font-medium text-slate-900 dark:text-slate-300">Enable Application Payment Fee</span>
                            </label>
                        </div>
                        <p class="text-xs text-slate-500 mt-2 px-1">If disabled, candidates will skip the payment step and submit directly.</p>
                    </div>
                
                    <div class="flex justify-end">
                        <button type="submit" name="save_settings" class="bg-primary hover:opacity-90 text-white font-bold py-3 px-8 rounded-lg shadow-xl shadow-primary/20 transition-all transform hover:-translate-y-0.5">
                            Save Global Preferences
                        </button>
                    </div>
                </form>

                <!-- Dynamic Attributes Management (AJAX) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    <!-- Departments -->
                    <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-4">
                            <span class="material-symbols-outlined text-purple-500">apartment</span>
                            Departments
                        </h2>
                        <ul class="space-y-2 mb-4 max-h-64 overflow-y-auto pr-2 custom-scrollbar" id="departmentsList">
                            <?php
                                $depts = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($depts as $dept):
                            ?>
                            <li class="group flex items-center justify-between bg-slate-50 dark:bg-slate-900/50 p-3 rounded-lg border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition-colors">
                                <span class="font-medium text-slate-700 dark:text-slate-200 w-full mr-2" 
                                      id="dept-name-<?php echo $dept['id']; ?>"
                                      onclick="editItem('department', <?php echo $dept['id']; ?>, '<?php echo addslashes($dept['name']); ?>')"><?php echo htmlspecialchars($dept['name']); ?></span>
                                <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                     <button type="button" onclick="editItem('department', <?php echo $dept['id']; ?>, '<?php echo addslashes($dept['name']); ?>')" class="text-blue-500 p-1 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                     <button type="button" onclick="deleteItem('department', <?php echo $dept['id']; ?>)" class="text-red-500 p-1 hover:bg-red-50 dark:hover:bg-red-900/20 rounded">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input type="text" id="newDeptName" placeholder="Add Department..." class="w-full sm:flex-1 rounded-lg border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 h-10 px-4 text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                            <button type="button" onclick="addItem('department')" class="w-full sm:w-auto bg-purple-600 hover:bg-purple-700 text-white px-4 rounded-lg text-sm font-bold shadow-md shadow-purple-500/20 h-10">Add</button>
                        </div>
                    </div>

                    <!-- Employment Types -->
                    <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-4">
                            <span class="material-symbols-outlined text-blue-500">work</span>
                            Employment Types
                        </h2>
                        <ul class="space-y-2 mb-4 max-h-64 overflow-y-auto pr-2 custom-scrollbar" id="empTypesList">
                            <?php
                                $types = $pdo->query("SELECT * FROM employment_types ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($types as $type):
                            ?>
                             <li class="group flex items-center justify-between bg-slate-50 dark:bg-slate-900/50 p-3 rounded-lg border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition-colors">
                                <span class="font-medium text-slate-700 dark:text-slate-200 w-full mr-2"
                                       id="type-name-<?php echo $type['id']; ?>"
                                       onclick="editItem('employment_type', <?php echo $type['id']; ?>, '<?php echo addslashes($type['name']); ?>')"><?php echo htmlspecialchars($type['name']); ?></span>
                                <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button type="button" onclick="editItem('employment_type', <?php echo $type['id']; ?>, '<?php echo addslashes($type['name']); ?>')" class="text-blue-500 p-1 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                     <button type="button" onclick="deleteItem('employment_type', <?php echo $type['id']; ?>)" class="text-red-500 p-1 hover:bg-red-50 dark:hover:bg-red-900/20 rounded">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="flex flex-col sm:flex-row gap-2">
                             <input type="text" id="newEmpTypeName" placeholder="Add Type..." class="w-full sm:flex-1 rounded-lg border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 h-10 px-4 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                             <button type="button" onclick="addItem('employment_type')" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-4 rounded-lg text-sm font-bold shadow-md shadow-blue-500/20 h-10">Add</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        </div>
        <!-- Footer -->
        <?php include_once __DIR__ . '/../includes/admin_footer.php'; ?>
    </main>

<script>
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    });

    function addItem(type) {
        const inputId = type === 'department' ? 'newDeptName' : 'newEmpTypeName';
        const nameInput = document.getElementById(inputId);
        const name = nameInput.value;
        
        if (!name) return Toast.fire({ icon: 'warning', title: 'Please enter a name' });

        fetch('../api/admin_settings_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add&type=${type}&name=${encodeURIComponent(name)}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Toast.fire({ icon: 'success', title: 'Added successfully' }).then(() => location.reload());
            } else {
                Toast.fire({ icon: 'error', title: data.message });
            }
        });
    }

    function editItem(type, id, oldName) {
        Swal.fire({
            title: 'Edit Name',
            input: 'text',
            inputValue: oldName,
            showCancelButton: true,
            confirmButtonText: 'Save',
            confirmButtonColor: '#3b82f6',
            inputValidator: (value) => {
                if (!value) return 'You need to write something!'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('../api/admin_settings_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=edit&type=${type}&id=${id}&name=${encodeURIComponent(result.value)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Toast.fire({ icon: 'success', title: 'Updated successfully' }).then(() => location.reload());
                    } else {
                        Toast.fire({ icon: 'error', title: data.message });
                    }
                });
            }
        });
    }

    function deleteItem(type, id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "Items using this attribute might be updated or unlinked.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('../api/admin_settings_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete&type=${type}&id=${id}`
                })
                .then(res => res.json())
                .then(data => {
                     if (data.success) {
                        Toast.fire({ icon: 'success', title: 'Deleted successfully' }).then(() => location.reload());
                    } else {
                         Toast.fire({ icon: 'error', title: data.message });
                    }
                });
            }
        })
    }
</script>

</body>
</html>
