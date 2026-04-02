<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/csrf_helper.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    require_csrf_token();

    // 1. Ambil Data dan Sanitasi Input
    $user_id    = $_SESSION['user_id']; 
    $judul      = $_POST['title'] ?? '';
    $estimasi   = (float)($_POST['estimasi'] ?? 0);
    $deskripsi  = $_POST['description'] ?? '';
    $urgensi    = $_POST['urgency'] ?? '';
    
    $current_year = date('Y');
    
    // Ambil departemen user
    $userSnap = $db->collection('users')->document($user_id)->snapshot();
    if (!$userSnap->exists()) {
        die("User tidak ditemukan.");
    }
    $userData = $userSnap->data();
    $my_dept = $userData['department'] ?? '';
    $user_name = $userData['full_name'] ?? '';

    // 2. Validasi Anggaran Spesifik Departemen
    $budgetQuery = $db->collection('budget_config')
        ->where('department', '=', $my_dept)
        ->documents();

    $budget_doc = null;
    $sisa_anggaran = 0;
    foreach ($budgetQuery as $doc) {
        $b = $doc->data();
        // Cek secara string maupun integer
        if ((string)($b['fiscal_year'] ?? '') === (string)$current_year) {
            $budget_doc = $doc;
            $sisa_anggaran = ((float)($b['total_limit'] ?? 0)) - ((float)($b['used_amount'] ?? 0));
            break;
        }
    }

    if ($estimasi > $sisa_anggaran) {
        die("Gagal: Estimasi harga melebihi sisa anggaran tersedia (Rp " . number_format($sisa_anggaran, 0, ',', '.') . ") untuk departemen " . htmlspecialchars($my_dept) . ".");
    }

    // 3. Generasi Nomor Tiket dan Setup Folder Upload
    $ticket_no  = "PRO-" . date('Ymd') . "-" . strtoupper(substr(uniqid(), -3));
    
    // Fallback: Gunakan /tmp di Vercel, atau folder lokal
    $is_vercel  = getenv('VERCEL') === '1';
    $target_dir = $is_vercel ? sys_get_temp_dir() . "/" : "../uploads/";

    if (!$is_vercel && !is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (!isset($_FILES["attachment"]) || $_FILES["attachment"]["error"] !== UPLOAD_ERR_OK) {
         die("Gagal mengunggah lampiran. Pastikan file valid.");
    }

    $file_name   = basename($_FILES["attachment"]["name"]);
    $file_ext    = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $new_name    = $ticket_no . "." . $file_ext;
    $target_path = $target_dir . $new_name;

    // 4. Validasi Ekstensi File
    $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
    if (!in_array($file_ext, $allowed_ext)) {
        die("Gagal: Format file tidak didukung. Harap unggah file PDF, JPG, atau PNG.");
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES["attachment"]["tmp_name"]);
    finfo_close($finfo);
    
    $allowed_mime_types = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!in_array($mime, $allowed_mime_types)) {
        die("Gagal: Tipe file tidak valid (MIME Type).");
    }

    if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_path)) {
        
        // --- Integrasi Firebase Storage untuk Serverless ---
        $final_attachment_url = $target_path; // Default ke local
        if ($is_vercel) {
            try {
                // Di Vercel, kita perlu push file dari /tmp ke Firebase Storage agar permanen,
                // karena /tmp akan musnah setelah response selesai.
                global $factory;
                $storage = $factory->createStorage();
                $bucket = $storage->getBucket();
                $fileSource = fopen($target_path, 'r');
                $object = $bucket->upload($fileSource, ['name' => 'pengadaan/' . $new_name]);
                
                // Gunakan URL yang bisa diakses publik secara permanen
                $final_attachment_url = $object->signedUrl(new \DateTime('+10 years'));
                unlink($target_path); // Hapus sampah /tmp
            } catch (Exception $e) {
                if (stripos($e->getMessage(), 'does not exist') !== false) {
                    die("Gagal Upload: Mohon aktifkan layanan 'Firebase Storage' di Google Firebase Console aplikasi Anda terlebih dahulu!");
                }
                die("Gagal upload Firebase Storage: " . $e->getMessage());
            }
        }

        try {
            // Executing in a Firestore transaction to ensure budget consistency
            $db->runTransaction(function ($transaction) use ($db, $budget_doc, $estimasi, $ticket_no, $user_id, $judul, $deskripsi, $urgensi, $final_attachment_url, $user_name, $my_dept) {
                // A. Update pemakaian anggaran
                $transaction->update($budget_doc->reference(), [
                    ['path' => 'used_amount', 'value' => \Google\Cloud\Firestore\FieldValue::increment($estimasi)]
                ]);

                // B. Create submission
                $subRef = $db->collection('submissions')->document();
                $transaction->create($subRef, [
                    'ticket_number' => $ticket_no,
                    'user_id' => $user_id,
                    'user_name' => $user_name, // Denormalization
                    'department' => $my_dept,   // Denormalization
                    'type' => 'Pengadaan',
                    'title' => $judul,
                    'description' => $deskripsi,
                    'urgency' => $urgensi,
                    'attachment_path' => $final_attachment_url,
                    'status' => 'Menunggu',
                    'estimasi' => $estimasi,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            });

            header("Location: ../modules_user/cetak_tiket_pengadaan.php?ticket=" . $ticket_no);
            exit();

        } catch (Exception $e) {
            echo "Gagal menyimpan data: " . $e->getMessage();
        }
    } else {
        echo "Gagal memindahkan file yang diunggah.";
    }
} else {
    echo "Sesi tidak valid. Silakan login kembali.";
}
?>
