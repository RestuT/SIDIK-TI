<?php
require_once __DIR__ . '/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Akses ditolak. Silakan login.");
}

$ticket_id = $_GET['id'] ?? '';

if (empty($ticket_id)) {
    die("Ticket ID tidak valid.");
}

try {
    // 1. Cek dari koleksi 'attachments' jika Firestore aktif
    if ($db) {
        try {
            $attachRef = $db->collection('attachments')->document($ticket_id);
            $attachSnap = $attachRef->snapshot();

            if ($attachSnap->exists()) {
                $doc = $attachSnap->data();
                $base64 = $doc['data'] ?? '';
                $mime = $doc['mime_type'] ?? 'application/octet-stream';
                $filename = $doc['file_name'] ?? 'attachment';

                if (!empty($base64)) {
                    $data = base64_decode($base64);
                    header("Content-Type: $mime");
                    header("Content-Disposition: inline; filename=\"$filename\"");
                    header("Content-Length: " . strlen($data));
                    echo $data;
                    exit();
                }
            }
        } catch (Exception $e) {
            // Firestore error, fallback ke database relasional
        }
    }
    
    // 2. Fallback: ambil path lampiran dari tabel submissions (baik Firestore maupun MySQL)
    $attachment_path = null;
    
    if ($db) {
        try {
            $subQuery = $db->collection('submissions')->where('ticket_number', '=', $ticket_id)->limit(1)->documents();
            foreach ($subQuery as $sub) {
                $subDoc = $sub->data();
                $attachment_path = $subDoc['attachment_path'] ?? null;
            }
        } catch (Exception $e) {
            // Lanjut ke MySQL
        }
    }
    
    if (empty($attachment_path) && $conn) {
        $ticket_id_e = mysqli_real_escape_string($conn, $ticket_id);
        $res = mysqli_query($conn, "SELECT attachment_path FROM submissions WHERE ticket_number = '$ticket_id_e' LIMIT 1");
        if ($row = mysqli_fetch_assoc($res)) {
            $attachment_path = $row['attachment_path'];
        }
    }
    
    if (!empty($attachment_path)) {
        // Jika path berupa file lokal (misal: ../../uploads/...)
        $filename = basename($attachment_path);
        $real_path = __DIR__ . '/../../uploads/' . $filename;
        
        if (file_exists($real_path)) {
            header("Content-Type: " . mime_content_type($real_path));
            header("Content-Disposition: inline; filename=\"$filename\"");
            header("Content-Length: " . filesize($real_path));
            readfile($real_path);
            exit();
        }
        
        // Redirect jika ini Firebase URL yang tidak sempat terhapus
        if (strpos($attachment_path, 'http') === 0) {
            header("Location: " . $attachment_path);
            exit();
        }
    }
    
    die("Lampiran tidak ditemukan atau file rusak.");

} catch (Exception $e) {
    die("Error membaca lampiran: " . htmlspecialchars($e->getMessage()));
}
// Closing tag removed to prevent header output
