<?php
// admin/generate_token.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

// Security Check: Superadmin Only
// Since we don't have role-based middleware properly set up for 'superadmin' string check in session yet,
// we will assume $_SESSION['user_role'] must be 'superadmin'.
// Note: User must ensure a user is actually assigned this role in DB.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'superadmin') {
    // If not superadmin, maybe redirect or show error.
    // However, user just created the role. They might need to manually update their user in DB to test.
    // For now, let's enforce it.
    header('Location: /admin/dashboard.php?error=unauthorized');
    exit;
}

$pageTitle = 'Generate Update Token';

?>
<!DOCTYPE html>
<html lang="en" class="<?php echo ($_SESSION['theme_mode'] ?? '') === 'dark' ? 'dark' : 'light'; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $pageTitle; ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
<link href="/assets/css/style.css" rel="stylesheet">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "primary": "var(--color-primary, #3b2bee)",
          },
          fontFamily: {
            "display": ["Inter", "sans-serif"]
          }
        },
      },
      plugins: [
        function({ addBase, theme }) {
            addBase({
                ':root': {
                    '--color-primary': '<?php echo get_theme_color(); ?>',
                },
            });
        }
      ]
    }
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>body { font-family: Inter, sans-serif; }</style>
<?php echo get_theme_css(); ?>
</head>

<body class="bg-gray-50 dark:bg-slate-900 flex min-h-screen">
<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

<main class="flex-1 flex flex-col h-screen overflow-hidden">
    <?php include __DIR__ . '/../includes/admin_header.php'; ?>

    <div class="flex-1 overflow-y-auto p-6 md:p-8">
        <div class="max-w-2xl mx-auto flex flex-col gap-6">
            
            <div class="flex flex-col gap-2">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Generate Update Token</h1>
                <p class="text-slate-500 text-sm">Create a secure, one-time token for staff validation.</p>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <form id="tokenForm" class="flex flex-col gap-4">
                    <input type="hidden" name="action" value="generate_token">
                    
                    <div class="flex flex-col gap-2 relative" id="staffSearchContainer">
                        <label class="font-semibold text-slate-700 dark:text-slate-300">Select Staff Member</label>
                        <input type="text" id="staffSearch" class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-primary focus:border-primary transition-colors" placeholder="Type name to search..." autocomplete="off">
                        <input type="hidden" name="staff_id" id="staffId" required>
                        
                        <!-- Search Results Dropdown -->
                        <div id="searchResults" class="hidden absolute top-full left-0 right-0 mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-lg shadow-lg z-50 max-h-60 overflow-y-auto">
                            <!-- Results populated via JS -->
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                         <label class="font-semibold text-slate-700 dark:text-slate-300">Validity</label>
                         <p class="text-sm text-slate-500">Token will be valid for 30 minutes and can be used once.</p>
                    </div>
                    
                    <button type="submit" class="mt-2 w-full bg-primary text-white font-bold py-3 rounded-lg hover:brightness-110 transition-all">
                        Generate Token
                    </button>
                </form>
            </div>
            
            <!-- Result Area -->
            <div id="tokenResult" class="hidden bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-6 text-center">
                <p class="text-green-800 dark:text-green-300 mb-2 font-medium">Token Generated Successfully</p>
                <div class="flex items-center justify-center gap-3">
                    <code id="tokenDisplay" class="text-3xl font-mono font-bold text-slate-900 dark:text-white bg-white dark:bg-slate-900 px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 tracking-wider"></code>
                    <button onclick="copyToken()" class="text-slate-500 hover:text-primary transition-colors" title="Copy">
                        <span class="material-symbols-outlined">content_copy</span>
                    </button>
                </div>
                <p class="text-xs text-slate-500 mt-3">Share this token with the admin. It expires in 30 minutes.</p>
            </div>

        </div>
    </div>
</main>

<script>
// Fetch Staff for Search
let staffData = [];

$(document).ready(function() {
    $.get('/api/staff_actions.php?action=fetch', function(response) {
        if(response.success) {
            staffData = response.data;
        }
    }, 'json');
});

// Search Logic
$('#staffSearch').on('input', function() {
    const query = $(this).val().toLowerCase();
    const resultsContainer = $('#searchResults');
    resultsContainer.empty().addClass('hidden');
    
    // Clear hidden ID if specific match isn't selected
    if ($('#staffId').val() && $(this).val() !== $('#staffSearch').data('selected-name')) {
         $('#staffId').val('');
    }

    if (query.length < 1) return;

    const matches = staffData.filter(staff => 
        staff.full_name.toLowerCase().includes(query) || 
        staff.department.toLowerCase().includes(query)
    );

    if (matches.length > 0) {
        matches.slice(0, 10).forEach(staff => { // Limit to 10 results
             const item = $(`
                <div class="px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer border-b border-slate-100 dark:border-slate-700 last:border-0 transition-colors" onclick="selectStaff(${staff.id}, '${staff.full_name.replace(/'/g, "\\'")}', '${staff.department.replace(/'/g, "\\'")}')">
                    <p class="font-medium text-slate-800 dark:text-slate-200">${staff.full_name}</p>
                    <p class="text-xs text-slate-500">${staff.department}</p>
                </div>
            `);
            resultsContainer.append(item);
        });
        resultsContainer.removeClass('hidden');
    } else {
         resultsContainer.append('<div class="px-4 py-3 text-sm text-slate-500 italic">No staff found</div>').removeClass('hidden');
    }
});

// Hide results when clicking outside
$(document).on('click', function(e) {
    if (!$(e.target).closest('#staffSearchContainer').length) {
        $('#searchResults').addClass('hidden');
    }
});

function selectStaff(id, name, department) {
    $('#staffId').val(id);
    $('#staffSearch').val(`${name} (${department})`).data('selected-name', `${name} (${department})`);
    $('#searchResults').addClass('hidden');
}

// Generate Token
$('#tokenForm').on('submit', function(e) {
    e.preventDefault();
    
    if (!$('#staffId').val()) {
        Swal.fire('Error', 'Please select a valid staff member from the list', 'warning');
        return;
    }

    const btn = $(this).find('button[type="submit"]');
    const originalText = btn.text();
    
    btn.prop('disabled', true).text('Generating...');
    $('#tokenResult').addClass('hidden');
    
    $.post('/api/staff_actions.php', $(this).serialize(), function(response) {
        if(response.success) {
            $('#tokenDisplay').text(response.token);
            $('#tokenResult').removeClass('hidden');
            Swal.fire({
                icon: 'success',
                title: 'Token Generated',
                text: 'Secure token created successfully.',
                timer: 1500,
                showConfirmButton: false
            });
        } else {
            Swal.fire('Error', response.message || 'Failed to generate token', 'error');
        }
    }, 'json').fail(function() {
        Swal.fire('Error', 'Server connection failed', 'error');
    }).always(function() {
        btn.prop('disabled', false).text(originalText);
    });
});

function copyToken() {
    const token = document.getElementById('tokenDisplay').innerText;
    navigator.clipboard.writeText(token).then(() => {
        const icon = event.currentTarget.querySelector('span');
        icon.innerText = 'check';
        setTimeout(() => icon.innerText = 'content_copy', 2000);
    });
}
</script>
</body>
</html>
