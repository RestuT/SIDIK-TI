<?php
include '../config/database.php';

echo "<h2>SIDIK-TI | System Settings Initialization</h2>";

// 1. Buat Tabel system_settings
$sql = "CREATE TABLE IF NOT EXISTS `system_settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn, $sql)) {
    echo "<p style='color: green;'>✅ Tabel system_settings berhasil diverifikasi/dibuat.</p>";
} else {
    die("<p style='color: red;'>❌ Gagal membuat tabel: " . mysqli_error($conn) . "</p>");
}

// 2. Insert Default Values
$defaults = [
    'app_name' => 'SIDIK-TI',
    'admin_email' => 'admin@sidik-ti.com',
    'system_version' => 'v2.4.0-PRO'
];

foreach ($defaults as $key => $val) {
    $check = mysqli_query($conn, "SELECT * FROM system_settings WHERE setting_key = '$key'");
    if (mysqli_num_rows($check) == 0) {
        $stmt = mysqli_prepare($conn, "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $key, $val);
        mysqli_stmt_execute($stmt);
        echo "<p>🔹 Default setting '$key' berhasil ditambahkan.</p>";
    }
}

echo "<hr><p><b>Setup Selesai.</b> Silakan kembali ke halaman Settings.</p>";
echo "<a href='../admin/settings.php'>Kembali ke Settings</a>";
?>
