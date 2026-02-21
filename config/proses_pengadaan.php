<?php
session_start();
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    // 1. Ambil Data dan Sanitasi Input
    $user_id    = $_SESSION['user_id']; 
    $judul      = mysqli_real_escape_string($conn, $_POST['judul']);
    $estimasi   = (float)$_POST['estimasi']; // Pastikan dalam format angka/float
    $deskripsi  = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $urgensi    = mysqli_real_escape_string($conn, $_POST['urgensi']);
    
    // 2. Validasi Anggaran (SOP: Cek ketersediaan pagu sebelum proses)
    $budget_query = mysqli_query($conn, "SELECT * FROM budget_config WHERE fiscal_year = 2026");
    $budget_data = mysqli_fetch_assoc($budget_query);
    $sisa_anggaran = $budget_data['total_limit'] - $budget_data['used_amount'];

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

    // 4. Validasi Upload dan Eksekusi Database
    if (move_uploaded_file($_FILES["lampiran"]["tmp_name"], $target_path)) {
        
        // Memulai Transaksi Database agar data sinkron
        mysqli_begin_transaction($conn);

        try {
            // A. Insert data pengajuan ke submissions
            $query_sub = "INSERT INTO submissions (ticket_number, user_id, type, title, description, urgency, attachment_path, status) 
                          VALUES ('$ticket_no', '$user_id', 'Pengadaan', '$judul', '$deskripsi', '$urgensi', '$target_path', 'Menunggu')";
            mysqli_query($conn, $query_sub);

            // B. Update pemakaian anggaran (SOP: Reservasi anggaran sementara)
            $query_budget = "UPDATE budget_config SET used_amount = used_amount + $estimasi WHERE fiscal_year = 2026";
            mysqli_query($conn, $query_budget);

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