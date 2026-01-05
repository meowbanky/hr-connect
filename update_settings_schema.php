<?php
require_once __DIR__ . '/config/db.php';

try {
    // Create settings table
    $sql = "CREATE TABLE IF NOT EXISTS `settings` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `setting_key` varchar(50) NOT NULL,
      `setting_value` text,
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "Settings table created/verified.\n";

    // Seed Defaults
    $defaults = [
        'currency_code' => 'NGN',
        'currency_symbol' => '₦',
        'theme_color' => '#1919e6'
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($defaults as $key => $val) {
        $stmt->execute([$key, $val]);
        if ($stmt->rowCount() > 0) {
            echo "Inserted default: $key = $val\n";
        } else {
            echo "Default exists: $key\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
