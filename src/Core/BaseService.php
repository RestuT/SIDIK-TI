<?php
namespace App\Core;

/**
 * Base Service Class for SIDIK-TI
 * Handles standard database and session context and common utilities.
 */
abstract class BaseService {
    protected $db;   // Firestore Instance
    protected $conn; // MySQL Instance (Optional)

    public function __construct($db = null, $conn = null) {
        $this->db = $db;
        $this->conn = $conn;
    }

    /**
     * Helper to get current server date-time
     */
    protected function now() {
        return date('Y-m-d H:i:s');
    }
}
