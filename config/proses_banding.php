<?php
session_start();
include 'database.php';
include 'csrf_helper.php';

if (isset($_POST['kirim_banding']) && isset($_SESSION['user_id'])) {
    require_csrf_token();

    $id = $_POST['submission_id'];
    $reason = $_POST['appeal_reason'];
    $user_id = $_SESSION['user_id'];

    // Update status ke 'Menunggu', is_appealed jadi 1, dan simpan alasan banding secara Prepared Statements
    $stmt = mysqli_prepare($conn, "UPDATE submissions SET status = 'Menunggu', appeal_reason = ?, is_appealed = 1 WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ssi", $reason, $id, $user_id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../modules_user/dashboard_audit.php?msg=banding_terkirim");
        exit();
    } else {
        die("Error 500: Terjadi kesalahan pada proses banding.");
    }
}