<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/csrf_helper.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    require_csrf_token();

    // 1. Ambil Data dan Sanitasi Input
    $user_id    = $_SESSION['user_id']; 
    $judul      = $_POST['title'] ?? '';
    $deskripsi  = $_POST['description'] ?? '';
    $urgensi    = $_POST['urgency'] ?? '';
    $qty        = max(1, (int)($_POST['qty'] ?? 1));
    $base_price = (float)($_POST['base_price'] ?? 0);

    $current_year = date('Y');
    
    // Ambil departemen user
    $userSnap = $db->collection('users')->document($user_id)->snapshot();
    if (!$userSnap->exists()) {
        die("User tidak ditemukan.");
    }
    $userData  = $userSnap->data();
    $my_dept   = $userData['department'] ?? '';
    $user_name = $userData['full_name'] ?? '';

    use App\Services\ProcurementService;
    $procurementService = new ProcurementService($db);

    // 2. Fetch System Settings
    $settings = [
        'margin_pengadaan' => 5,
        'pajak'            => 11
    ];
    try {
        $sys_docs = $db->collection('system_settings')->documents();
        foreach ($sys_docs as $doc) {
            if ($doc->id() === 'margin_pengadaan') $settings['margin_pengadaan'] = (float)$doc->get('setting_value');
            if ($doc->id() === 'pajak')            $settings['pajak'] = (float)$doc->get('setting_value');
        }
    } catch (Exception $e) { }

    // 3. Submit Procurement via Service
    try {
        $ticket_no = $procurementService->submitRequest($user_id, [
            'title'       => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'urgency'     => $_POST['urgency'] ?? 'Sedang',
            'qty'         => $_POST['qty'] ?? 1,
            'base_price'  => $_POST['base_price'] ?? 0,
            'department'  => $my_dept,
            'user_name'   => $user_name
        ], $_FILES['attachment'] ?? null, $settings);

        // Fetch the auto_id for the redirect
        $subDocs = $db->collection('submissions')->where('ticket_number', '==', $ticket_no)->documents();
        $auto_id = null;
        foreach ($subDocs as $doc) { $auto_id = $doc->id(); break; }

        header("Location: ../modules_user/cetak_tiket_pengadaan.php?id=" . $auto_id);
        exit();

    } catch (Exception $e) {
        die("Gagal memproses pengajuan: " . $e->getMessage());
    }
} else {
    echo "Sesi tidak valid. Silakan login kembali.";
}
} else {
    echo "Sesi tidak valid. Silakan login kembali.";
}
// Closing tag removed to prevent header output
