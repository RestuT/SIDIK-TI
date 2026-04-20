<?php
namespace App\Services;

use App\Core\BaseService;

/**
 * Maintenance Service
 * Handles repair request submissions and lifecycle tracking.
 */
class MaintenanceService extends BaseService {

    private $fileService;

    public function __construct($db = null, $conn = null) {
        parent::__construct($db, $conn);
        $this->fileService = new FileService($db, $conn);
    }

    /**
     * Submit a maintenance request
     */
    public function submitRequest($userId, $data, $file) {
        $ticketNo = "MNT-" . date('Ymd') . "-" . strtoupper(substr(uniqid(), -3));
        $attachmentUrl = $this->fileService->uploadAttachment($file, $ticketNo);

        $payload = [
            'ticket_number'   => $ticketNo,
            'user_id'         => $userId,
            'user_name'       => $data['user_name'] ?? 'Unknown',
            'department'      => $data['department'] ?? 'Unknown',
            'type'            => 'Maintenance',
            'title'           => $data['title'] ?? 'Maintenance',
            'description'     => $data['description'] ?? '',
            'attachment_path' => $attachmentUrl,
            'status'          => 'Menunggu',
            'created_at'      => $this->now()
        ];

        if ($this->db) {
            try {
                $addedDocRef = $this->db->collection('submissions')->add($payload);
                return $addedDocRef->id();
            } catch (\Exception $e) {
                if (!$this->conn) throw $e;
                $this->db = null;
            }
        }

        if ($this->conn) {
            $cols = implode(", ", array_keys($payload));
            $params = [];
            foreach ($payload as $val) {
                if ($val === null) $params[] = "NULL";
                else $params[] = "'" . mysqli_real_escape_string($this->conn, $val) . "'";
            }
            $vals = implode(", ", $params);
            
            $sql = "INSERT INTO submissions ($cols) VALUES ($vals)";
            if (mysqli_query($this->conn, $sql)) {
                return mysqli_insert_id($this->conn);
            }
            throw new \Exception("Database error: " . mysqli_error($this->conn));
        }

        throw new \Exception("No database connection available");
    }
}
