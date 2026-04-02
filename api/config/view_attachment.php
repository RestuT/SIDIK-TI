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
    // 1. Cek dari koleksi 'attachments'
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
    
    // 2. Fallback: jika berupa file path lokal (sistem lama)
    $subQuery = $db->collection('submissions')->where('ticket_number', '=', $ticket_id)->limit(1)->documents();
    $subDoc = null;
    foreach ($subQuery as $sub) {
        $subDoc = $sub->data();
    }
    
    if ($subDoc && isset($subDoc['attachment_path'])) {
        $path = $subDoc['attachment_path'];
        // Jika path tidak mengandung format base64, coba render lokal (misal: xampp)
        if (strpos($path, '../uploads/') !== false && file_exists(__DIR__ . '/' . $path)) {
            $real_path = __DIR__ . '/' . $path;
            header("Content-Type: " . mime_content_type($real_path));
            readfile($real_path);
            exit();
        }
        
        // Redirect jika ini Firebase URL yang tidak sempat terhapus
        if (strpos($path, 'http') === 0) {
            header("Location: " . $path);
            exit();
        }
    }
    
    die("Lampiran tidak ditemukan atau file rusak.");

} catch (Exception $e) {
    die("Error membaca lampiran: " . htmlspecialchars($e->getMessage()));
}
?>
