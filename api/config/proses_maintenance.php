<?php

require_once __DIR__ . '/database.php'; // Mengambil koneksi database
require_once __DIR__ . '/csrf_helper.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    require_csrf_token();

    // 1. Ambil Data dan Sanitasi Input
    $user_id    = $_SESSION['user_id']; 
    $judul      = $_POST['judul']; // Nama/Merk Barang
    $layanan    = $_POST['layanan']; // Jenis Perangkat
    $deskripsi  = $_POST['deskripsi']; // Detail Keluhan
    $type       = "Maintenance"; // Penanda tipe pengajuan
    
    // Fetch user info for denormalization
    $userRef = $db->collection('users')->document($user_id);
    $userSnap = $userRef->snapshot();
    $user_name = "Unknown";
    $department = "-";
    
    if ($userSnap->exists()) {
        $userData = $userSnap->data();
        $user_name = $userData['username'] ?? 'Unknown';
        $department = $userData['dept'] ?? '-';
    }

    // 2. Generasi Nomor Tiket (Prefix MNT untuk Maintenance)
    $ticket_no  = "MNT-" . date('Ymd') . "-" . strtoupper(substr(uniqid(), -3));
    
    // Fallback: Gunakan /tmp di Vercel, atau folder lokal
    $is_vercel  = getenv('VERCEL') === '1';
    $target_dir = $is_vercel ? sys_get_temp_dir() . "/" : "../uploads/";

    // Cek apakah folder uploads ada, jika tidak buat otomatis
    if (!$is_vercel && !is_dir($target_dir)) {
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
            
            // --- Integrasi Database-Encoded Storage untuk Vercel ---
            $final_attachment_url = $target_path; // Default ke local
            if ($is_vercel) {
                try {
                    $fileContent = file_get_contents($target_path);
                    $base64 = base64_encode($fileContent);
                    
                    $db->collection('attachments')->document($ticket_no)->set([
                        'data' => $base64,
                        'mime_type' => $mime,
                        'file_name' => $file_name,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    $final_attachment_url = "../config/view_attachment.php?id=" . $ticket_no;
                    unlink($target_path); // Hapus sampah /tmp
                } catch (Exception $e) {
                    if (stripos($e->getMessage(), 'Document size') !== false) {
                        die("Gagal Upload: Berkas terlalu besar, harap gunakan fitur kompres gambar terlebih dahulu!");
                    }
                    die("Gagal upload file ke database storage: " . $e->getMessage());
                }
            }

            // 4. Query Insert ke tabel submissions di Firestore
            $addedDocRef = $db->collection('submissions')->add([
                'ticket_number' => $ticket_no,
                'user_id' => $user_id,
                'user_name' => $user_name,
                'department' => $department,
                'type' => $type,
                'title' => $judul,
                'description' => $deskripsi,
                'attachment_path' => $final_attachment_url,
                'status' => 'Menunggu',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $auto_id = $addedDocRef->id();

            header("Location: ../modules_user/cetak_tiket_maintenance.php?id=" . $auto_id);
            exit();
        } else {
            echo "Gagal mengunggah dokumentasi barang.";
        }
    } else {
        echo "Format file tidak didukung. Gunakan JPG, PNG, atau PDF.";
    }
} else {
    echo "Sesi tidak valid. Silakan login kembali.";
}
// Closing tag removed to prevent header output
