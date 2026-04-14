<?php
namespace App\Services;

use App\Core\BaseService;

/**
 * File & Media Service
 * Handles file uploads, MIME validation, and Base64 Firestore storage for Vercel.
 */
class FileService extends BaseService {

    protected $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
    protected $allowed_mime = ['application/pdf', 'image/jpeg', 'image/png'];

    /**
     * Upload an attachment
     * @param array $file $_FILES['input_name']
     * @param string $ticketNo Unique identifier for the file
     * @return string Final path or URL
     */
    public function uploadAttachment($file, $ticketNo) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception("Gagal mengunggah lampiran. File tidak valid.");
        }

        $file_name = basename($file["name"]);
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // 1. Validate Extension
        if (!in_array($file_ext, $this->allowed_ext)) {
            throw new \Exception("Format file tidak didukung (PDF, JPG, PNG saja).");
        }

        // 2. Validate MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file["tmp_name"]);
        finfo_close($finfo);
        if (!in_array($mime, $this->allowed_mime)) {
            throw new \Exception("Tipe file (MIME) tidak valid atau berbahaya.");
        }

        $is_vercel  = getenv('VERCEL') === '1';
        $target_dir = $is_vercel ? sys_get_temp_dir() . "/" : "../uploads/";
        if (!$is_vercel && !is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $new_name    = $ticketNo . "." . $file_ext;
        $target_path = $target_dir . $new_name;

        if (!move_uploaded_file($file["tmp_name"], $target_path)) {
            throw new \Exception("Gagal memindahkan file ke direktori tujuan.");
        }

        // 3. Handle Vercel (Base64 to Firestore)
        if ($is_vercel) {
            try {
                $fileContent = file_get_contents($target_path);
                $base64      = base64_encode($fileContent);
                
                $this->db->collection('attachments')->document($ticketNo)->set([
                    'data'       => $base64,
                    'mime_type'  => $mime,
                    'file_name'  => $file_name,
                    'created_at' => $this->now()
                ]);
                
                unlink($target_path);
                return "../config/view_attachment.php?id=" . $ticketNo;
            } catch (\Exception $e) {
                if (stripos($e->getMessage(), 'Document size') !== false) {
                    throw new \Exception("Berkas terlalu besar, limit Firestore 1MB.");
                }
                throw $e;
            }
        }

        return $target_path;
    }
}
