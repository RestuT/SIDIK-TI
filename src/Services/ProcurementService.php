<?php
namespace App\Services;

use App\Core\BaseService;

/**
 * Procurement Service
 * Handles departmental procurement requests with budget validation.
 */
class ProcurementService extends BaseService {

    private $budgetService;
    private $fileService;

    public function __construct($db = null, $conn = null) {
        parent::__construct($db, $conn);
        $this->budgetService = new BudgetService($db, $conn);
        $this->fileService   = new FileService($db, $conn);
    }

    public function calculateTotalCost($qty, $basePrice, $settings) {
        $margin    = (float)($settings['margin_pengadaan'] ?? 5);
        $pajak     = (float)($settings['pajak'] ?? 11);
        $after_markup = (float)$basePrice * (1 + $margin / 100);
        $purchase_price = $after_markup * (1 + $pajak / 100);
        return round((int)$qty * $purchase_price);
    }

    public function submitRequest($userId, $data, $file, $settings) {
        $qty       = max(1, (int)($data['qty'] ?? 1));
        $basePrice = (float)($data['base_price'] ?? 0);
        $totalCost = $this->calculateTotalCost($qty, $basePrice, $settings);
        $fiscalYear = date('Y');
        
        $budgetInfo = $this->budgetService->getBudgetInfo($data['department'], $fiscalYear);
        if (!$budgetInfo) {
            throw new \Exception("Gagal: Anggaran untuk departemen " . $data['department'] . " tahun $fiscalYear belum dikonfigurasi.");
        }
        if ($totalCost > $budgetInfo['remaining']) {
            throw new \Exception("Gagal: Estimasi harga (Rp " . number_format($totalCost) . ") melebihi sisa anggaran.");
        }

        $ticketNo = "PRO-" . date('Ymd') . "-" . strtoupper(substr(uniqid(), -3));
        $attachmentUrl = $this->fileService->uploadAttachment($file, $ticketNo);
        $now = $this->now();

        if ($this->db && (!isset($budgetInfo['id']) || !is_numeric($budgetInfo['id']))) {
            try {
                $this->db->runTransaction(function ($transaction) use ($budgetInfo, $totalCost, $ticketNo, $userId, $data, $attachmentUrl, $qty, $basePrice, $settings, $now) {
                    $transaction->update($budgetInfo['doc']->reference(), [['path' => 'used_amount', 'value' => \Google\Cloud\Firestore\FieldValue::increment($totalCost)]]);
                    $transaction->set($this->db->collection('submissions')->newDocument(), [
                        'ticket_number' => $ticketNo, 'user_id' => $userId, 'user_name' => $data['user_name'] ?? 'Unknown',
                        'department' => $data['department'], 'type' => 'Pengadaan', 'title' => $data['title'] ?? 'Procurement',
                        'description' => $data['description'] ?? '', 'urgency' => $data['urgency'] ?? 'Sedang',
                        'attachment_path' => $attachmentUrl, 'status' => 'Menunggu', 'estimasi' => $totalCost,
                        'qty' => (int)$qty, 'base_price' => (float)$basePrice, 'margin_snapshot' => (float)($settings['margin_pengadaan'] ?? 5),
                        'pajak_snapshot' => (float)($settings['pajak'] ?? 11), 'created_at' => $now
                    ]);
                });
                return $ticketNo;
            } catch (\Exception $e) { $this->db = null; }
        }

        if (!$this->db && $this->conn) {
            $budgetId = $budgetInfo['id'];
            mysqli_query($this->conn, "UPDATE budget_config SET used_amount = used_amount + $totalCost WHERE id = $budgetId");
            $ticket_e = mysqli_real_escape_string($this->conn, $ticketNo);
            $user_e = mysqli_real_escape_string($this->conn, $userId);
            $uname_e = mysqli_real_escape_string($this->conn, $data['user_name'] ?? 'Unknown');
            $dept_e = mysqli_real_escape_string($this->conn, $data['department']);
            $title_e = mysqli_real_escape_string($this->conn, $data['title'] ?? 'Procurement');
            $desc_e = mysqli_real_escape_string($this->conn, $data['description'] ?? '');
            $urgency_e = mysqli_real_escape_string($this->conn, $data['urgency'] ?? 'Sedang');
            $attach_e = mysqli_real_escape_string($this->conn, $attachmentUrl);
            $margin = (float)($settings['margin_pengadaan'] ?? 5);
            $pajak = (float)($settings['pajak'] ?? 11);

            $sql = "INSERT INTO submissions (ticket_number, user_id, user_name, department, type, title, description, urgency, attachment_path, status, estimasi, qty, base_price, margin_snapshot, pajak_snapshot, created_at) 
                    VALUES ('$ticket_e', '$user_e', '$uname_e', '$dept_e', 'Pengadaan', '$title_e', '$desc_e', '$urgency_e', '$attach_e', 'Menunggu', $totalCost, $qty, $basePrice, $margin, $pajak, '$now')";
            if (mysqli_query($this->conn, $sql)) return $ticketNo;
        }

        throw new \Exception("Database required");
    }
}
