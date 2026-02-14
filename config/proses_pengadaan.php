<?php
session_start();
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    // 1. Ambil Data dan Sanitasi
    $user_id    = $_SESSION['user_id']; 
    $judul      = mysqli_real_escape_string($conn, $_POST['judul']);
    $estimasi   = mysqli_real_escape_string($conn, $_POST['estimasi']);
    $deskripsi  = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $urgensi    = mysqli_real_escape_string($conn, $_POST['urgensi']);
    
    // Generasi Nomor Tiket
    $ticket_no  = "PRO-" . date('Ymd') . "-" . strtoupper(substr(uniqid(), -3));
    $target_dir = "../uploads/";

    // 2. Logika Upload
    // Cek apakah folder uploads ada, jika tidak buat otomatis
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_name   = basename($_FILES["lampiran"]["name"]);
    $file_ext    = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $new_name    = $ticket_no . "." . $file_ext;
    $target_path = $target_dir . $new_name;

    // 3. Validasi dan Eksekusi
    if (move_uploaded_file($_FILES["lampiran"]["tmp_name"], $target_path)) {
        
        // PINDAHKAN QUERY KE SINI (Sebelum mysqli_query)
        $query = "INSERT INTO submissions (ticket_number, user_id, type, title, description, urgency, attachment_path, status) 
                  VALUES ('$ticket_no', '$user_id', 'Pengadaan', '$judul', '$deskripsi', '$urgensi', '$target_path', 'Menunggu')";

        // 4. Jalankan Query dan Redirect
        if (mysqli_query($conn, $query)) {
            // Alihkan langsung ke cetak tiket pengadaan sesuai permintaan Anda
            header("Location: ../modules_user/cetak_tiket_pengadaan.php?ticket=" . $ticket_no);
            exit();
        } else {
            echo "Error Database: " . mysqli_error($conn);
        }
    } else {
        echo "Gagal mengunggah lampiran ke folder uploads.";
    }
} else {
    echo "Sesi tidak valid. Silakan login kembali.";
}
?>