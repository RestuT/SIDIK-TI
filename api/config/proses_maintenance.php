<?php

use App\Services\MaintenanceService;

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/csrf_helper.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    require_csrf_token();

    // 1. Ambil Data dan Sanitasi Input
    $user_id    = $_SESSION['user_id']; 
    $judul      = $_POST['judul'] ?? 'Maintenance';
    $deskripsi  = $_POST['deskripsi'] ?? '';
    
    // Fetch user info for denormalization
    $userRef = $db->collection('users')->document($user_id);
    $userSnap = $userRef->snapshot();
    $user_name = "Unknown";
    $department = "-";
    
    if ($userSnap->exists()) {
        $userData = $userSnap->data();
        $user_name = $userData['username'] ?? 'Unknown';
        $department = $userData['dept'] ?? '-';
    }

    $maintenanceService = new MaintenanceService($db);

    // 2. Submit Maintenance via Service
    try {
        $auto_id = $maintenanceService->submitRequest($user_id, [
            'title'       => $judul,
            'description' => $deskripsi,
            'department'  => $department,
            'user_name'   => $user_name
        ], $_FILES['lampiran'] ?? null);

        header("Location: ../modules_user/cetak_tiket_maintenance.php?id=" . $auto_id);
        exit();

    } catch (Exception $e) {
        die("Gagal memproses pengajuan: " . $e->getMessage());
    }
} else {
    echo "Sesi tidak valid. Silakan login kembali.";
}
?>
