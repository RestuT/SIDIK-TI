<?php
namespace App\Services;

use App\Core\BaseService;

/**
 * Budget Management Service
 * Handles budget validation and atomic expenditure updates.
 */
class BudgetService extends BaseService {

    /**
     * Get remaining budget for a department in a specific year
     */
    public function getBudgetInfo($dept, $year) {
        if (!$this->db) throw new \Exception("Database required");
        $query = $this->db->collection('budget_config')
                          ->where('department', '=', $dept)
                          ->documents();
        
        foreach ($query as $doc) {
            $data = $doc->data();
            if ((string)($data['fiscal_year'] ?? '') === (string)$year) {
                return [
                    'doc'       => $doc,
                    'limit'     => (float)($data['total_limit'] ?? 0),
                    'used'      => (float)($data['used_amount'] ?? 0),
                    'remaining' => ((float)($data['total_limit'] ?? 0)) - ((float)($data['used_amount'] ?? 0))
                ];
            }
        }
        return null; // Budget not configured
    }

    /**
     * Increment budget usage atomically
     */
    public function incrementUsage($budgetDocRef, $amount) {
        if (!$this->db) throw new \Exception("Database required");
        
        $this->db->runTransaction(function ($transaction) use ($budgetDocRef, $amount) {
            $transaction->update($budgetDocRef, [
                ['path' => 'used_amount', 'value' => \Google\Cloud\Firestore\FieldValue::increment($amount)]
            ]);
        });
    }
}
