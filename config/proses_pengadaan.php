<?php
session_start();
include 'database.php';
include 'csrf_helper.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    require_csrf_token();

    // 1. Ambil Data dan Sanitasi Input
    $user_id    = $_SESSION['user_id']; 
    $judul      = mysqli_real_escape_string($conn, $_POST['judul']);
    $estimasi   = (float)$_POST['estimasi']; // Pastikan dalam format angka/float
    $deskripsi  = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $urgensi    = mysqli_real_escape_string($conn, $_POST['urgensi']);
    
    $current_year = date('Y');
    
    // Ambil departemen user
    $get_dept = mysqli_query($conn, "SELECT department FROM users WHERE id = '$user_id'");
    $dept_data = mysqli_fetch_assoc($get_dept);
    $my_dept = $dept_data['department'];

    // 2. Validasi Anggaran Spesifik Departemen
    $stmt_budget = mysqli_prepare($conn, "SELECT total_limit, used_amount FROM budget_config WHERE fiscal_year = ? AND department = ?");
    mysqli_stmt_bind_param($stmt_budget, "is", $current_year, $my_dept);
    mysqli_stmt_execute($stmt_budget);
    $budget_query = mysqli_stmt_get_result($stmt_budget);
    $budget_data = mysqli_fetch_assoc($budget_query);
    $sisa_anggaran = ($budget_data['total_limit'] ?? 0) - ($budget_data['used_amount'] ?? 0);

    if ($estimasi > $sisa_anggaran) {
        die("Gagal: Estimasi harga melebihi sisa anggaran tersedia (Rp " . number_format($sisa_anggaran, 0, ',', '.') . ").");
    }

    // 3. Generasi Nomor Tiket dan Setup Folder Upload
    $ticket_no  = "PRO-" . date('Ymd') . "-" . strtoupper(substr(uniqid(), -3));
    $target_dir = "../uploads/";

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_name   = basename($_FILES["lampiran"]["name"]);
    $file_ext    = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $new_name    = $ticket_no . "." . $file_ext;
    $target_path = $target_dir . $new_name;

    // 4. Validasi Ekstensi File dan Eksekusi Database
    $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
    
    if (!in_array($file_ext, $allowed_ext)) {
        die("Gagal: Format file tidak didukung. Harap unggah file PDF, JPG, atau PNG.");
    }
    
    // Validasi tambahan: Pastikan file benar-benar gambar atau PDF menggunakan mime type (opsional tapi disarankan)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES["lampiran"]["tmp_name"]);
    finfo_close($finfo);
    
    $allowed_mime_types = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!in_array($mime, $allowed_mime_types)) {
        die("Gagal: Tipe file tidak valid (MIME Type). File terindikasi sebagai file berbahaya.");
    }

    if (move_uploaded_file($_FILES["lampiran"]["tmp_name"], $target_path)) {
        
        // Memulai Transaksi Database agar data sinkron
        mysqli_begin_transaction($conn);

        try {
            // A. Insert data pengajuan ke submissions dengan Prepared Statements (termasuk nilai estimasi)
            $stmt_sub = mysqli_prepare($conn, "INSERT INTO submissions (ticket_number, user_id, type, title, description, urgency, attachment_path, status, estimasi) VALUES (?, ?, 'Pengadaan', ?, ?, ?, ?, 'Menunggu', ?)");
            mysqli_stmt_bind_param($stmt_sub, "sissssd", $ticket_no, $user_id, $judul, $deskripsi, $urgensi, $target_path, $estimasi);
            mysqli_stmt_execute($stmt_sub);

            // B. Update pemakaian anggaran (SOP: Reservasi anggaran sementara untuk Dept terkait)
            $stmt_update_budget = mysqli_prepare($conn, "UPDATE budget_config SET used_amount = used_amount + ? WHERE fiscal_year = ? AND department = ?");
            mysqli_stmt_bind_param($stmt_update_budget, "dis", $estimasi, $current_year, $my_dept);
            mysqli_stmt_execute($stmt_update_budget);

            // Komit transaksi
            mysqli_commit($conn);

            // Redirect ke halaman cetak tiket
            header("Location: ../modules_user/cetak_tiket_pengadaan.php?ticket=" . $ticket_no);
            exit();

        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo "Gagal menyimpan data: " . $e->getMessage();
        }
    } else {
        echo "Gagal mengunggah lampiran. Pastikan file valid.";
    }
} else {
    echo "Sesi tidak valid. Silakan login kembali.";
}
?>