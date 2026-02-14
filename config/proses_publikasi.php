<?php
session_start();
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    // 1. Ambil Data dan Sanitasi
    $user_id    = $_SESSION['user_id']; 
    $judul      = mysqli_real_escape_string($conn, $_POST['judul']);
    $layanan    = mysqli_real_escape_string($conn, $_POST['layanan']);
    $deskripsi  = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $type       = "Publikasi";
    
    // Generasi Nomor Tiket
    $ticket_no  = "PUB-" . date('Ymd') . "-" . strtoupper(substr(uniqid(), -3));

    // 2. Jalur Folder Upload
    $target_dir  = "../uploads/";
    
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_name   = basename($_FILES["lampiran"]["name"]);
    $file_ext    = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $new_name    = $ticket_no . "." . $file_ext; 
    $target_path = $target_dir . $new_name;
    
    // 3. Validasi Ekstensi File
    $allowed_types = ['pdf', 'docx', 'doc', 'jpg', 'jpeg', 'png'];

    if (in_array($file_ext, $allowed_types)) {
        if (move_uploaded_file($_FILES["lampiran"]["tmp_name"], $target_path)) {
            
            // 4. Query Insert (Didefinisikan SEBELUM mysqli_query)
            $query = "INSERT INTO submissions (ticket_number, user_id, type, title, description, attachment_path, status) 
                      VALUES ('$ticket_no', '$user_id', '$type', '$judul', '$deskripsi', '$target_path', 'Menunggu')";

            // Jalankan query dan arahkan ke halaman cetak jika berhasil
            if (mysqli_query($conn, $query)) {
                header("Location: ../modules_user/cetak_tiket_publikasi.php?ticket=" . $ticket_no);
                exit();
            } else {
                echo "Database Error: " . mysqli_error($conn);
            }
        } else {
            echo "Gagal mengunggah file ke folder uploads.";
        }
    } else {
        echo "Format file tidak diizinkan.";
    }
} else {
    echo "Sesi login tidak valid. Silakan login ulang.";
}
?>