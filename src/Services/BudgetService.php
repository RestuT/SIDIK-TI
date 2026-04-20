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
        if ($this->db) {
            try {
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
            } catch (\Exception $e) { $this->db = null; }
        }
        
        if (!$this->db && $this->conn) {
            $dept_e = \mysqli_real_escape_string($this->conn, $dept);
            $year_e = \mysqli_real_escape_string($this->conn, $year);
            $res = \mysqli_query($this->conn, "SELECT * FROM budget_config WHERE department = '$dept_e' AND fiscal_year = '$year_e' LIMIT 1");
            if ($res && \mysqli_num_rows($res) > 0) {
                $data = \mysqli_fetch_assoc($res);
                return [
                    'id'        => $data['id'],
                    'limit'     => (float)$data['total_limit'],
                    'used'      => (float)$data['used_amount'],
                    'remaining' => (float)$data['total_limit'] - (float)$data['used_amount']
                ];
            }
        }
        return null; // Budget not configured
    }

    /**
     * Increment budget usage atomically
     */
    public function incrementUsage($budgetId, $amount) {
        $amount = (float)$amount;
        if ($this->db && !is_numeric($budgetId)) {
            $this->db->runTransaction(function ($transaction) use ($budgetId, $amount) {
                $transaction->update($this->db->collection('budget_config')->document($budgetId), [
                    ['path' => 'used_amount', 'value' => \Google\Cloud\Firestore\FieldValue::increment($amount)]
                ]);
            });
        } else if ($this->conn) {
            $id = intval($budgetId);
            \mysqli_query($this->conn, "UPDATE budget_config SET used_amount = used_amount + $amount WHERE id = $id");
        } else {
            throw new \Exception("Database required");
        }
    }
}
