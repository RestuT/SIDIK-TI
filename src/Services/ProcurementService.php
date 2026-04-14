<?php
namespace App\Services;

use App\Core\BaseService;

/**
 * Procurement Service
 * Handles departmental procurement requests with budget validation and markup math.
 */
class ProcurementService extends BaseService {

    private $budgetService;
    private $fileService;

    public function __construct($db = null, $conn = null) {
        parent::__construct($db, $conn);
        $this->budgetService = new BudgetService($db, $conn);
        $this->fileService   = new FileService($db, $conn);
    }

    /**
     * Calculate total cost including markup and taxes
     */
    public function calculateTotalCost($qty, $basePrice, $settings) {
        $margin    = (float)($settings['margin_pengadaan'] ?? 5);
        $pajak     = (float)($settings['pajak'] ?? 11);
        $after_markup = (float)$basePrice * (1 + $margin / 100);
        $purchase_price = $after_markup * (1 + $pajak / 100);
        return round((int)$qty * $purchase_price);
    }

    /**
     * Submit a procurement request
     */
    public function submitRequest($userId, $data, $file, $settings) {
        if (!$this->db) throw new \Exception("Database required");
        
        $qty       = max(1, (int)($data['qty'] ?? 1));
        $basePrice = (float)($data['base_price'] ?? 0);
        
        // 1. Calculate Estimasi
        $totalCost = $this->calculateTotalCost($qty, $basePrice, $settings);
        
        // 2. Budget Check
        $fiscalYear = date('Y');
        $budgetInfo = $this->budgetService->getBudgetInfo($data['department'], $fiscalYear);
        
        if (!$budgetInfo) {
            throw new \Exception("Gagal: Anggaran untuk departemen " . $data['department'] . " tahun $fiscalYear belum dikonfigurasi.");
        }
        
        if ($totalCost > $budgetInfo['remaining']) {
            throw new \Exception("Gagal: Estimasi harga (Rp " . number_format($totalCost) . ") melebihi sisa anggaran (Rp " . number_format($budgetInfo['remaining']) . ").");
        }

        // 3. File Upload
        $ticketNo = "PRO-" . date('Ymd') . "-" . strtoupper(substr(uniqid(), -3));
        $attachmentUrl = $this->fileService->uploadAttachment($file, $ticketNo);

        // 4. Create Submission & Update Budget (Transaction)
        $this->db->runTransaction(function ($transaction) use ($budgetInfo, $totalCost, $ticketNo, $userId, $data, $attachmentUrl, $qty, $basePrice, $settings) {
            // A. Update Budget
            $transaction->update($budgetInfo['doc']->reference(), [
                ['path' => 'used_amount', 'value' => \Google\Cloud\Firestore\FieldValue::increment($totalCost)]
            ]);

            // B. Create Ticket
            $subRef = $this->db->collection('submissions')->newDocument();
            $transaction->create($subRef, [
                'ticket_number'    => $ticketNo,
                'user_id'          => $userId,
                'user_name'        => $data['user_name'] ?? 'Unknown',
                'department'       => $data['department'],
                'type'             => 'Pengadaan',
                'title'            => $data['title'] ?? 'Procurement',
                'description'      => $data['description'] ?? '',
                'urgency'          => $data['urgency'] ?? 'Sedang',
                'attachment_path'  => $attachmentUrl,
                'status'           => 'Menunggu',
                'estimasi'         => $totalCost,
                'qty'              => (int)$qty,
                'base_price'       => (float)$basePrice,
                'margin_snapshot'  => (float)($settings['margin_pengadaan'] ?? 5),
                'pajak_snapshot'   => (float)($settings['pajak'] ?? 11),
                'created_at'       => $this->now()
            ]);
        });

        return $ticketNo;
    }
}
