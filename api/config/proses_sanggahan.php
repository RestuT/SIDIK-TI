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
        $success = false;
        
        if ($db) {
            try {
                $submissionRef = $db->collection('submissions')->document($id);
                $submissionSnap = $submissionRef->snapshot();

                if (!$submissionSnap->exists()) {
                    die("Pengajuan tidak ditemukan.");
                }

                $data = $submissionSnap->data();
                if (($data['user_id'] ?? '') !== $user_id) {
                    die("Akses ditolak.");
                }

                $submissionRef->update([
                    ['path' => 'status', 'value' => 'Menunggu'],
                    ['path' => 'appeal_reason', 'value' => $reason],
                    ['path' => 'is_appealed', 'value' => 1]
                ]);
                $success = true;
            } catch (Exception $e) {
                $db = null; // Terjadi error di Firestore, lanjut ke MySQL jika ada
            }
        }
        
        if (!$db && isset($conn)) {
            $id_e = mysqli_real_escape_string($conn, $id);
            $uid_e = mysqli_real_escape_string($conn, $user_id);
            $reason_e = mysqli_real_escape_string($conn, $reason);

            $res = mysqli_query($conn, "SELECT * FROM submissions WHERE id = '$id_e' LIMIT 1");
            if ($row = mysqli_fetch_assoc($res)) {
                if ($row['user_id'] !== $user_id) {
                    die("Akses ditolak.");
                }

                $update = mysqli_query($conn, "UPDATE submissions SET status = 'Menunggu', appeal_reason = '$reason_e', is_appealed = 1 WHERE id = '$id_e'");
                if ($update) {
                    $success = true;
                } else {
                    die("Error 500: Terjadi kesalahan pada proses banding (MySQL).");
                }
            } else {
                die("Pengajuan tidak ditemukan.");
            }
        }

        if ($success) {
            header("Location: ../modules_user/dashboard_audit.php?msg=banding_terkirim");
            exit();
        } else if (!isset($conn) && !$db) {
            die("Error 500: Koneksi database tidak tersedia.");
        }
        
    } catch (Exception $e) {
        die("Error 500: Terjadi kesalahan pada proses banding. " . $e->getMessage());
    }
} else {
    header("Location: ../modules_user/dashboard_audit.php");
}
