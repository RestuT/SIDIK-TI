<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/csrf_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    require_csrf_token();

    $id = $_POST['submission_id'] ?? '';
    $reason = $_POST['appeal_reason'] ?? '';
    $user_id = $_SESSION['user_id'];

    if (empty($id) || empty($reason)) {
        die("Data tidak lengkap.");
    }

    try {
        $submissionRef = $db->collection('submissions')->document($id);
        $submissionSnap = $submissionRef->snapshot();

        if (!$submissionSnap->exists()) {
            die("Pengajuan tidak ditemukan.");
        }

        $data = $submissionSnap->data();
        
        // Pastikan milik user yang benar
        if (($data['user_id'] ?? '') !== $user_id) {
            die("Akses ditolak.");
        }

        // Update status ke 'Menunggu', is_appealed jadi 1, dan simpan alasan banding
        $submissionRef->update([
            ['path' => 'status', 'value' => 'Menunggu'],
            ['path' => 'appeal_reason', 'value' => $reason],
            ['path' => 'is_appealed', 'value' => 1]
        ]);

        header("Location: ../modules_user/dashboard_audit.php?msg=banding_terkirim");
        exit();
    } catch (Exception $e) {
        die("Error 500: Terjadi kesalahan pada proses banding. " . $e->getMessage());
    }
} else {
    header("Location: ../modules_user/dashboard_audit.php");
}
// Closing tag removed to prevent header output
