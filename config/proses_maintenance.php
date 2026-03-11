<?php
session_start();
include 'database.php'; // Mengambil koneksi database
include 'csrf_helper.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    require_csrf_token();

    // 1. Ambil Data dan Sanitasi Input
    $user_id    = $_SESSION['user_id']; 
    $judul      = mysqli_real_escape_string($conn, $_POST['judul']); // Nama/Merk Barang
    $layanan    = mysqli_real_escape_string($conn, $_POST['layanan']); // Jenis Perangkat
    $deskripsi  = mysqli_real_escape_string($conn, $_POST['deskripsi']); // Detail Keluhan
    $type       = "Maintenance"; // Penanda tipe pengajuan
    
    // 2. Generasi Nomor Tiket (Prefix MNT untuk Maintenance)
    $ticket_no  = "MNT-" . date('Ymd') . "-" . strtoupper(substr(uniqid(), -3));
    $target_dir = "../uploads/";

    // Cek apakah folder uploads ada, jika tidak buat otomatis
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // 3. Logika Upload Dokumentasi Barang
    $file_name   = basename($_FILES["lampiran"]["name"]);
    $file_ext    = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $new_name    = $ticket_no . "." . $file_ext;
    $target_path = $target_dir . $new_name;

    // Validasi Ekstensi File (SOP: Gambar atau PDF)
    $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];

    if (in_array($file_ext, $allowed_types)) {
        
        // Validasi tambahan: Pastikan file benar-benar gambar atau PDF menggunakan mime type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES["lampiran"]["tmp_name"]);
        finfo_close($finfo);
        
        $allowed_mime_types = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!in_array($mime, $allowed_mime_types)) {
            die("Gagal: Tipe file tidak valid (MIME Type). File terindikasi sebagai file berbahaya.");
        }

        if (move_uploaded_file($_FILES["lampiran"]["tmp_name"], $target_path)) {
            
            // 4. Query Insert ke tabel submissions menggunakan PREPARED STATEMENTS
            $stmt = mysqli_prepare($conn, "INSERT INTO submissions (ticket_number, user_id, type, title, description, attachment_path, status) VALUES (?, ?, ?, ?, ?, ?, 'Menunggu')");
            mysqli_stmt_bind_param($stmt, "sissss", $ticket_no, $user_id, $type, $judul, $deskripsi, $target_path);

            if (mysqli_stmt_execute($stmt)) {
                // UBAH BARIS INI: Dari dashboard_audit.php ke cetak_tiket_maintenance.php
                header("Location: ../modules_user/cetak_tiket_maintenance.php?ticket=" . $ticket_no);
                exit();
            } else {
                die("Error 500: Terjadi kesalahan saat memproses data, silakan lapor pada Admin.");
            }
            // ... kode setelahnya ...
        } else {
            echo "Gagal mengunggah dokumentasi barang.";
        }
    } else {
        echo "Format file tidak didukung. Gunakan JPG, PNG, atau PDF.";
    }
} else {
    echo "Sesi tidak valid. Silakan login kembali.";
}
?>