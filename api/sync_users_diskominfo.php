<?php
/**
 * User Department Sync
 * Updates user departments to match the new DISKOMINFO structure.
 */

require_once __DIR__ . '/config/database.php';

echo "<h1>Syncing Users to DISKOMINFO</h1>";

// Distribution Map
$mapping = [
    'IT Department' => 'Bidang Aplikasi Informatika (Aptika)',
    'Finance & Accounting' => 'Sekretariat',
    'General Affairs' => 'Sekretariat',
    'Marketing & Sales' => 'Bidang Informasi dan Komunikasi Publik (IKP)',
    'Human Resource' => 'Sekretariat'
];

foreach ($mapping as $old => $new) {
    $stmt = mysqli_prepare($conn, "UPDATE users SET department = ? WHERE department = ?");
    mysqli_stmt_bind_param($stmt, "ss", $new, $old);
    mysqli_stmt_execute($stmt);
}

echo "<p>✅ Users department synced.</p>";
echo "<p><a href='index.php'>Go to Dashboard</a></p>";
?>
