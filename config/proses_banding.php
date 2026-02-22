<?php
session_start();
include 'database.php';

if (isset($_POST['kirim_banding']) && isset($_SESSION['user_id'])) {
    $id = mysqli_real_escape_string($conn, $_POST['submission_id']);
    $reason = mysqli_real_escape_string($conn, $_POST['appeal_reason']);
    $user_id = $_SESSION['user_id'];

    // Update status ke 'Menunggu', is_appealed jadi 1, dan simpan alasan banding
    $query = "UPDATE submissions SET 
              status = 'Menunggu', 
              appeal_reason = '$reason', 
              is_appealed = 1 
              WHERE id = '$id' AND user_id = '$user_id'";

    if (mysqli_query($conn, $query)) {
        header("Location: ../modules_user/dashboard_audit.php?msg=banding_terkirim");
        exit();
    }
}