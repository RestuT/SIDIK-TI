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
        if (!$this->db) throw new \Exception("Database required");

        $ticketNo = "MNT-" . date('Ymd') . "-" . strtoupper(substr(uniqid(), -3));
        $attachmentUrl = $this->fileService->uploadAttachment($file, $ticketNo);

        $addedDocRef = $this->db->collection('submissions')->add([
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
        ]);

        return $addedDocRef->id();
    }
}
