<?php
ob_start();
/**
 * SIDIK-TI Dummy Data Seeder Khusus Sensus & HEA
 * Eksekusi file ini sekali via browser untuk mendemonstrasikan status Aset dan perhitungan HEA.
 */

require_once __DIR__ . '/config/database.php';

echo "<h1>SIDIK-TI Seeder (Sensus & Depresiasi)</h1>";
echo "<p>Starting data population to Cloud Firestore...</p>";

// 1. Ambil 1 User ID Pertama (sebagai pemilik aset)
$userId = "DUMMY_USER_123";
$userDept = "IT Department";
$userName = "Testing User";
try {
    $users = $db->collection('users')->limit(1)->documents();
    foreach ($users as $u) {
        $userId = $u->id();
        $userDept = $u['department'] ?? 'IT Department';
        $userName = $u['full_name'] ?? 'Testing User';
    }
} catch (Exception $e) {}

// 2. Buat Dummy Aset - ASET HARDWARE BARU (Usia Pemakaian Masih Muda, Kondisi Baik)
try {
    $db->collection('asset_assignments')->add([
        'user_id' => $userId,
        'item_name' => 'Dell Latitude 7420 (Kantor Pusat)',
        'serial_number' => 'SN-DL7420-001X',
        'category' => 'Laptop',
        'assigned_at' => date('Y-m-d H:i:s', strtotime('-5 months')), // Usia 5 Bulan (Baru)
        'status' => 'Active',
        'user_name' => $userName,
        'department' => $userDept,
        'original_price' => 15000000,
        'tkdn_pct' => 40.0, // TKDN 40% (Koef Preferensi 10%)
        'price_reference' => 13500000, // Harga Capitalized setelah HEA diskon 10%
        'kode_barang' => 'IT-2024-LAP-001',
        'latest_condition_code' => 1 // Baik
    ]);
    echo "<p>✅ Aset Baru (Kondisi 1 - Hijau) seeded.</p>";
} catch (Exception $e) { echo $e->getMessage(); }

// 3. Buat Dummy Aset - ASET HARDWARE WARNING (Usia Pemakaian 3 Tahun, Rusak Ringan)
try {
    $db->collection('asset_assignments')->add([
        'user_id' => $userId,
        'item_name' => 'Printer Epson L3210 (Tinta Kering)',
        'serial_number' => 'SN-EPS-0988R',
        'category' => 'Printer',
        'assigned_at' => date('Y-m-d H:i:s', strtotime('-30 months')), // Usia 30 Bulan (Kuning)
        'status' => 'Active',
        'user_name' => $userName,
        'department' => $userDept,
        'original_price' => 2500000,
        'tkdn_pct' => 0,
        'price_reference' => 2500000, 
        'kode_barang' => 'IT-2021-PRN-002',
        'latest_condition_code' => 2 // Automatis Rusak Ringan
    ]);
    echo "<p>✅ Aset Menua (Kondisi 2 - Kuning) seeded.</p>";
} catch (Exception $e) { echo $e->getMessage(); }

// 4. Buat Dummy Aset - ASET KHUSUS SOFTWARE (Tidak terdepresiasi)
try {
    $db->collection('asset_assignments')->add([
        'user_id' => $userId,
        'item_name' => 'Adobe Creative Cloud 2024 (Subs)',
        'serial_number' => 'LICENSE-ADOBE-XXXX',
        'category' => 'Software',
        'assigned_at' => date('Y-m-d H:i:s', strtotime('-2 months')),
        'status' => 'Active',
        'user_name' => $userName,
        'department' => $userDept,
        'original_price' => 5000000,
        'tkdn_pct' => 0, 
        'price_reference' => 5000000,
        'kode_barang' => 'IT-2024-AST-003',
        'latest_condition_code' => 1
    ]);
    echo "<p>✅ Aset Software (Biaya Rutin) seeded.</p>";
} catch (Exception $e) { echo $e->getMessage(); }

echo "<h2>🎉 Data dummy khusus Sensus & Valuasi siap!</h2>";
echo "<p><a href='admin/sensus_barang.php'>Kembali ke Dashboard Sensus Admin</a></p>";
?>
