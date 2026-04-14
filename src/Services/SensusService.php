<?php
namespace App\Services;

use App\Core\BaseService;

/**
 * Sensus Management Service
 * Handles census batch lifecycle and task management.
 */
class SensusService extends BaseService {

    /**
     * Start a new census batch
     */
    public function startBatch($name, $userId) {
        if (!$this->db) throw new \Exception("Database required");
        
        // 1. Create Batch
        $batchRef = $this->db->collection('sensus_batches')->newDocument();
        $batchRef->set([
            'batch_name' => $name,
            'created_at' => $this->now(),
            'status'     => 'Active',
            'created_by' => $userId
        ]);
        $batchId = $batchRef->id();

        // 2. Generate Tasks
        $assets = $this->db->collection('asset_assignments')->where('status', '=', 'Active')->documents();
        $count = 0;
        foreach ($assets as $doc) {
            $data = $doc->data();
            if (empty($data['user_id'])) continue;

            $this->db->collection('sensus_tasks')->add([
                'batch_id'   => $batchId,
                'asset_id'   => $doc->id(),
                'user_id'    => $data['user_id'],
                'user_name'  => $data['user_name'] ?? 'Unknown',
                'item_name'  => $data['item_name'],
                'category'   => $data['category'] ?? 'Hardware',
                'assigned_at'=> $data['assigned_at'] ?? $this->now(),
                'department' => $data['department'] ?? 'Unknown',
                'multiplier' => (float)($data['utilization_multiplier'] ?? 1.0),
                'status'     => 'Pending',
                'created_at' => $this->now()
            ]);
            $count++;
        }
        return ['id' => $batchId, 'count' => $count];
    }

    /**
     * Submit user report
     */
    public function submitReport($taskId, $pct, $notes) {
        if (!$this->db) throw new \Exception("Database required");
        $taskRef = $this->db->collection('sensus_tasks')->document($taskId);
        $taskRef->update([
            ['path' => 'report_pct', 'value' => (float)$pct],
            ['path' => 'report_notes', 'value' => $notes],
            ['path' => 'report_date', 'value' => $this->now()],
            ['path' => 'status', 'value' => 'Reported']
        ]);
    }

    /**
     * Finalize task by Admin
     */
    public function finalizeTask($taskId, $assetId, $pct, $notes) {
        if (!$this->db) throw new \Exception("Database required");
        
        $code = 3; 
        if ($pct >= 85) $code = 1;
        elseif ($pct >= 65) $code = 2;

        // 1. Update Inventory
        $this->db->collection('asset_assignments')->document($asset_id)->update([
            ['path' => 'latest_condition_code', 'value' => $code],
            ['path' => 'latest_condition_pct', 'value' => (float)$pct]
        ]);

        // 2. Update Task
        $this->db->collection('sensus_tasks')->document($taskId)->update([
            ['path' => 'final_pct', 'value' => (float)$pct],
            ['path' => 'final_notes', 'value' => $notes],
            ['path' => 'final_date', 'value' => $this->now()],
            ['path' => 'status', 'value' => 'Finalized']
        ]);
    }
}
