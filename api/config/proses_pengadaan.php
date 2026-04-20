<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/csrf_helper.php';

use App\Services\ProcurementService;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    require_csrf_token();

    $user_id = $_SESSION['user_id'];
    $userData = [];

    if ($db && !is_numeric($user_id)) {
        try {
            $userSnap = $db->collection('users')->document($user_id)->snapshot();
            if ($userSnap->exists()) $userData = $userSnap->data();
        } catch (Exception $e) { $db = null; }
    }
    
    if (!$db && $conn) {
        $res = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
        if ($res) $userData = mysqli_fetch_assoc($res);
    }

    if (empty($userData)) die("User tidak ditemukan.");

    $my_dept = $userData['department'] ?? '';
    $user_name = $userData['full_name'] ?? '';

    $procurementService = new ProcurementService($db, $conn);
    $settings = ['margin_pengadaan' => 5, 'pajak' => 11];

    if ($db) {
        try {
            $sys_docs = $db->collection('system_settings')->documents();
            foreach ($sys_docs as $doc) {
                if ($doc->id() === 'margin_pengadaan') $settings['margin_pengadaan'] = (float)$doc->get('setting_value');
                if ($doc->id() === 'pajak') $settings['pajak'] = (float)$doc->get('setting_value');
            }
        } catch (Exception $e) { $db = null; }
    }
    
    if (!$db && $conn) {
        $res = mysqli_query($conn, "SELECT * FROM system_settings");
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                if ($row['setting_key'] === 'margin_pengadaan') $settings['margin_pengadaan'] = (float)$row['setting_value'];
                if ($row['setting_key'] === 'pajak') $settings['pajak'] = (float)$row['setting_value'];
            }
        }
    }

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

        $auto_id = null;
        if ($db) {
            $subDocs = $db->collection('submissions')->where('ticket_number', '=', $ticket_no)->documents();
            foreach ($subDocs as $doc) { $auto_id = $doc->id(); break; }
        } else if ($conn) {
            $res = mysqli_query($conn, "SELECT id FROM submissions WHERE ticket_number = '".mysqli_real_escape_string($conn, $ticket_no)."'");
            if ($res) $row = mysqli_fetch_assoc($res);
            $auto_id = $row['id'] ?? null;
        }

        header("Location: ../modules_user/cetak_tiket_pengadaan.php?id=" . $auto_id);
        exit();

    } catch (Exception $e) {
        die("Gagal memproses pengajuan: " . $e->getMessage());
    }
} else {
    echo "Sesi tidak valid. Silakan login kembali.";
}
