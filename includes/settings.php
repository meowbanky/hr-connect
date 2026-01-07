<?php
// includes/settings.php
require_once __DIR__ . '/../config/db.php';

function get_setting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

function get_currency_symbol() {
    return get_setting('currency_symbol', '₦');
}

function get_theme_color() {
    return get_setting('theme_color', '#1919e6');
}

function get_theme_css() {
    $color = get_theme_color();
    // hex to rgb for tailwind var compatibility if needed, but for now simple override
    // We override the specific tailwind primary classes and variables
    return "
    <style>
        :root {
            --color-primary: $color;
        }
        .text-primary { color: $color !important; }
        .bg-primary { background-color: $color !important; }
        .border-primary { border-color: $color !important; }
        .ring-primary { --tw-ring-color: $color !important; }
        /* Hover states - approximate a darker shade or just opacity for simplicity in this global override */
        .hover\:bg-primary:hover { opacity: 0.9; background-color: $color !important; }
        .hover\:text-primary:hover { opacity: 0.8; color: $color !important; }
        
        /* Custom Loader */
        .loader { border-top-color: $color !important; }
    </style>
    ";
}

function format_currency($amount) {
    if (!$amount) return '0';
    $symbol = get_currency_symbol();
    // Format number: 140000 -> 140,000
    // Shorthand logic ($140k) is harder to do generic dynamic without explicit rules, 
    // so we will switch to full number format with commas for accuracy with different currencies.
    // Or we can try a simple K formatter if value > 1000
    
    if ($amount >= 1000) {
        if ($amount >= 1000000) {
            return $symbol . number_format($amount / 1000000, 1) . 'M'; 
        }
        return $symbol . number_format($amount / 1000) . 'k';
    }
    
    return $symbol . number_format($amount);
}

function is_payment_enabled() {
    return get_setting('enable_payment', '1') === '1';
}

function calculateProfileCompletion($candidate) {
    if (!$candidate) return 0;
    
    $filled = 0;
    $total = 9; // Total fields to track
    
    // List of fields to check
    $fields = [
        'date_of_birth',
        'gender',
        'address', // Bio
        'linkedin_profile',
        'resume_path',
        'state_of_origin',
        'lga',
        'highest_qualification'
    ];
    
    foreach ($fields as $field) {
        if (!empty($candidate[$field])) $filled++;
    }
    
    // Zero experience is valid, so custom check
    if (isset($candidate['years_of_experience']) && $candidate['years_of_experience'] !== '') {
        $filled++;
    }
    
    return round(($filled / $total) * 100);
}
?>
