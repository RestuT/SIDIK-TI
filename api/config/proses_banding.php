<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/csrf_helper.php';

if (isset($_POST['kirim_banding']) && isset($_SESSION['user_id'])) {
    require_csrf_token();

    $id = $_POST['submission_id'];
    $reason = $_POST['appeal_reason'];
    $user_id = $_SESSION['user_id'];

    try {
        $submissionRef = $db->collection('submissions')->document($id);
        $snap = $submissionRef->snapshot();
        
        if ($snap->exists() && $snap->data()['user_id'] == $user_id) {
            $submissionRef->update([
                ['path' => 'status', 'value' => 'Menunggu'],
                ['path' => 'appeal_reason', 'value' => $reason],
                ['path' => 'is_appealed', 'value' => 1]
            ]);

            header("Location: ../modules_user/dashboard_audit.php?msg=banding_terkirim");
            exit();
        } else {
            die("Error 403: Akses ditolak atau pengajuan tidak ditemukan.");
        }
    } catch (Exception $e) {
        die("Error 500: Terjadi kesalahan pada proses banding: " . $e->getMessage());
    }
}
?>
