<?php
/**
 * DISKOMINFO Special Seeder
 * Adjusts departments and budget to match typical DISKOMINFO structure.
 */

include 'config/database.php';

echo "<h1>DISKOMINFO Structure Setup</h1>";

// 1. Truncate existing departments & budgets for fresh start
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0;");
mysqli_query($conn, "TRUNCATE TABLE budget_config;");
mysqli_query($conn, "TRUNCATE TABLE departments;");
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1;");

echo "<p>🗑️ Old data cleared.</p>";

// 2. DISKOMINFO Typical Departments
$diskominfo_depts = [
    'Sekretariat',
    'Bidang Informasi dan Komunikasi Publik (IKP)',
    'Bidang Aplikasi Informatika (Aptika)',
    'Bidang Statistik',
    'Bidang Persandian dan Keamanan Informasi'
];

foreach ($diskominfo_depts as $dept) {
    $stmt = mysqli_prepare($conn, "INSERT INTO departments (nama_dept) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "s", $dept);
    mysqli_stmt_execute($stmt);
}
echo "<p>✅ DISKOMINFO Departments seeded.</p>";

// 3. Specialized Budget for DISKOMINFO (Fiscal 2026)
$fiscal_year = 2026;
$budgets = [
    ['Sekretariat', 350000000],
    ['Bidang Informasi dan Komunikasi Publik (IKP)', 500000000],
    ['Bidang Aplikasi Informatika (Aptika)', 1200000000], // Highest because of servers/dev
    ['Bidang Statistik', 250000000],
    ['Bidang Persandian dan Keamanan Informasi', 450000000]
];

foreach ($budgets as $b) {
    $stmt = mysqli_prepare($conn, "INSERT INTO budget_config (fiscal_year, department, total_limit, used_amount) VALUES (?, ?, ?, 0)");
    mysqli_stmt_bind_param($stmt, "isd", $fiscal_year, $b[0], $b[1]);
    mysqli_stmt_execute($stmt);
}
echo "<p>✅ Financial allocation sync complete.</p>";

echo "<h2>🎉 DISKOMINFO Environment Ready!</h2>";
echo "<p><a href='admin/manage_budget.php'>Verify in Budget Manager</a></p>";
?>
