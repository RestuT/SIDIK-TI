<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/csrf_helper.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    require_csrf_token();

    // 1. Ambil Data dan Sanitasi Input
    $user_id    = $_SESSION['user_id']; 
    $judul      = $_POST['title'] ?? '';
    $deskripsi  = $_POST['description'] ?? '';
    $urgensi    = $_POST['urgency'] ?? '';
    $qty        = max(1, (int)($_POST['qty'] ?? 1));
    $base_price = (float)($_POST['base_price'] ?? 0);

    $current_year = date('Y');
    
    // Ambil departemen user
    $userSnap = $db->collection('users')->document($user_id)->snapshot();
    if (!$userSnap->exists()) {
        die("User tidak ditemukan.");
    }
    $userData  = $userSnap->data();
    $my_dept   = $userData['department'] ?? '';
    $user_name = $userData['full_name'] ?? '';

    // 2. SERVER-SIDE: Ambil margin & pajak terbaru dari Firestore (anti-manipulasi)
    $margin_pengadaan = 5;   // default markup (%)
    $pajak            = 11;  // default PPN (%)
    try {
        $sys_docs = $db->collection('system_settings')->documents();
        foreach ($sys_docs as $doc) {
            if (!$doc->exists()) continue;
            $val = $doc->data()['setting_value'] ?? null;
            if ($val === null) continue;
            if ($doc->id() === 'margin_pengadaan') $margin_pengadaan = (float)$val;
            if ($doc->id() === 'pajak')            $pajak            = (float)$val;
        }
    } catch (Exception $e) { /* pakai default */ }

    // 3. SERVER-SIDE: Hitung ulang estimasi yang valid
    //    Formula: Qty × HargaSatuan × (1 + margin/100) × (1 + pajak/100)
    $estimasi_server = round($qty * $base_price * (1 + $margin_pengadaan / 100) * (1 + $pajak / 100));

    // 4. Validasi Anggaran Spesifik Departemen
    $budgetQuery = $db->collection('budget_config')
        ->where('department', '=', $my_dept)
        ->documents();

    $budget_doc    = null;
    $sisa_anggaran = 0;
    foreach ($budgetQuery as $doc) {
        $b = $doc->data();
        if ((string)($b['fiscal_year'] ?? '') === (string)$current_year) {
            $budget_doc    = $doc;
            $sisa_anggaran = ((float)($b['total_limit'] ?? 0)) - ((float)($b['used_amount'] ?? 0));
            break;
        }
    }

    if ($estimasi_server > $sisa_anggaran) {
        die("Gagal: Estimasi harga (Rp " . number_format($estimasi_server, 0, ',', '.') . ") melebihi sisa anggaran yang tersedia (Rp " . number_format($sisa_anggaran, 0, ',', '.') . ") untuk departemen " . htmlspecialchars($my_dept) . ".");
    }

    // 5. Generasi Nomor Tiket dan Setup Folder Upload
    $ticket_no  = "PRO-" . date('Ymd') . "-" . strtoupper(substr(uniqid(), -3));
    
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

    // 6. Validasi Ekstensi & MIME File
    $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
    if (!in_array($file_ext, $allowed_ext)) {
        die("Gagal: Format file tidak didukung. Harap unggah file PDF, JPG, atau PNG.");
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES["attachment"]["tmp_name"]);
    finfo_close($finfo);
    
    $allowed_mime_types = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!in_array($mime, $allowed_mime_types)) {
        die("Gagal: Tipe file tidak valid (MIME Type).");
    }

    if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_path)) {
        
        $final_attachment_url = $target_path;
        if ($is_vercel) {
            try {
                $fileContent = file_get_contents($target_path);
                $base64      = base64_encode($fileContent);
                
                $db->collection('attachments')->document($ticket_no)->set([
                    'data'       => $base64,
                    'mime_type'  => $mime,
                    'file_name'  => $file_name,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                $final_attachment_url = "../config/view_attachment.php?id=" . $ticket_no;
                unlink($target_path);
            } catch (Exception $e) {
                if (stripos($e->getMessage(), 'Document size') !== false) {
                    die("Gagal Upload: Berkas terlalu besar, harap gunakan fitur kompres gambar terlebih dahulu!");
                }
                die("Gagal upload file ke database storage: " . $e->getMessage());
            }
        }

        try {
            $auto_id = null;
            $db->runTransaction(function ($transaction) use ($db, $budget_doc, $estimasi_server, $ticket_no, $user_id, $judul, $deskripsi, $urgensi, $final_attachment_url, $user_name, $my_dept, $qty, $base_price, $margin_pengadaan, $pajak, &$auto_id) {
                // A. Update pemakaian anggaran
                $transaction->update($budget_doc->reference(), [
                    ['path' => 'used_amount', 'value' => \Google\Cloud\Firestore\FieldValue::increment($estimasi_server)]
                ]);

                // B. Create submission dengan audit trail harga
                $subRef  = $db->collection('submissions')->newDocument();
                $auto_id = $subRef->id();
                $transaction->create($subRef, [
                    'ticket_number'    => $ticket_no,
                    'user_id'          => $user_id,
                    'user_name'        => $user_name,
                    'department'       => $my_dept,
                    'type'             => 'Pengadaan',
                    'title'            => $judul,
                    'description'      => $deskripsi,
                    'urgency'          => $urgensi,
                    'attachment_path'  => $final_attachment_url,
                    'status'           => 'Menunggu',
                    'estimasi'         => $estimasi_server,
                    // Audit trail: nilai saat pengajuan dibuat
                    'qty'              => $qty,
                    'base_price'       => $base_price,
                    'margin_snapshot'  => $margin_pengadaan,
                    'pajak_snapshot'   => $pajak,
                    'created_at'       => date('Y-m-d H:i:s')
                ]);
            });

            header("Location: ../modules_user/cetak_tiket_pengadaan.php?id=" . $auto_id);
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
// Closing tag removed to prevent header output
