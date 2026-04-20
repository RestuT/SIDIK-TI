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
        $now = $this->now();
        
        if ($this->db && !is_numeric($userId)) {
            // Firestore
            $batchRef = $this->db->collection('sensus_batches')->newDocument();
            $batchRef->set(['batch_name' => $name, 'created_at' => $now, 'status' => 'Active', 'created_by' => $userId]);
            $batchId = $batchRef->id();
            $assets = $this->db->collection('asset_assignments')->where('status', '=', 'Active')->documents();
            $count = 0;
            foreach ($assets as $doc) {
                $data = $doc->data();
                if (empty($data['user_id'])) continue;
                $this->db->collection('sensus_tasks')->add([
                    'batch_id' => $batchId, 'asset_id' => $doc->id(), 'user_id' => $data['user_id'],
                    'user_name' => $data['user_name'] ?? 'Unknown', 'item_name' => $data['item_name'],
                    'category' => $data['category'] ?? 'Hardware', 'assigned_at' => $data['assigned_at'] ?? $now,
                    'department' => $data['department'] ?? 'Unknown', 'multiplier' => (float)($data['utilization_multiplier'] ?? 1.0),
                    'status' => 'Pending', 'created_at' => $now
                ]);
                $count++;
            }
            return ['id' => $batchId, 'count' => $count];
        } else if ($this->conn) {
            // MySQL
            $name_esc = mysqli_real_escape_string($this->conn, $name);
            $user_esc = mysqli_real_escape_string($this->conn, $userId);
            mysqli_query($this->conn, "INSERT INTO sensus_batches (batch_name, created_at, status, created_by) VALUES ('$name_esc', '$now', 'Active', '$user_esc')");
            $batchId = mysqli_insert_id($this->conn);
            
            $res_assets = mysqli_query($this->conn, "SELECT * FROM asset_assignments WHERE status = 'Active'");
            $count = 0;
            while ($row = mysqli_fetch_assoc($res_assets)) {
                if (empty($row['user_id'])) continue;
                $asset_id = (int)$row['id'];
                $uid = mysqli_real_escape_string($this->conn, $row['user_id']);
                $uname = mysqli_real_escape_string($this->conn, $row['user_name'] ?? 'Unknown');
                $iname = mysqli_real_escape_string($this->conn, $row['item_name']);
                $cat = mysqli_real_escape_string($this->conn, $row['category'] ?? 'Hardware');
                $dept = mysqli_real_escape_string($this->conn, $row['department'] ?? 'Unknown');
                $mult = (float)($row['utilization_multiplier'] ?? 1.0);
                $assigned = $row['assigned_at'] ?? $now;
                
                $sql = "INSERT INTO sensus_tasks (batch_id, asset_id, user_id, user_name, item_name, category, assigned_at, department, multiplier, status, created_at) 
                        VALUES ($batchId, $asset_id, '$uid', '$uname', '$iname', '$cat', '$assigned', '$dept', $mult, 'Pending', '$now')";
                mysqli_query($this->conn, $sql);
                $count++;
            }
            return ['id' => $batchId, 'count' => $count];
        }
        throw new \Exception("Database required");
    }

    /**
     * Submit user report
     */
    public function submitReport($taskId, $pct, $notes) {
        $now = $this->now();
        if ($this->db && !is_numeric($taskId)) {
            $taskRef = $this->db->collection('sensus_tasks')->document($taskId);
            $taskRef->update([
                ['path' => 'report_pct', 'value' => (float)$pct],
                ['path' => 'report_notes', 'value' => $notes],
                ['path' => 'report_date', 'value' => $now],
                ['path' => 'status', 'value' => 'Reported']
            ]);
        } else if ($this->conn) {
            $notes_esc = mysqli_real_escape_string($this->conn, $notes);
            $pct_val = (float)$pct;
            $tid = intval($taskId);
            mysqli_query($this->conn, "UPDATE sensus_tasks SET report_pct = $pct_val, report_notes = '$notes_esc', report_date = '$now', status = 'Reported' WHERE id = $tid");
        } else {
            throw new \Exception("Database required");
        }
    }

    /**
     * Finalize task by Admin
     */
    public function finalizeTask($taskId, $assetId, $pct, $notes) {
        $now = $this->now();
        $code = 3; 
        if ($pct >= 85) $code = 1;
        elseif ($pct >= 65) $code = 2;

        if ($this->db && !is_numeric($taskId)) {
            $this->db->collection('asset_assignments')->document($assetId)->update([
                ['path' => 'latest_condition_code', 'value' => $code],
                ['path' => 'latest_condition_pct', 'value' => (float)$pct]
            ]);
            $this->db->collection('sensus_tasks')->document($taskId)->update([
                ['path' => 'final_pct', 'value' => (float)$pct],
                ['path' => 'final_notes', 'value' => $notes],
                ['path' => 'final_date', 'value' => $now],
                ['path' => 'status', 'value' => 'Finalized']
            ]);
        } else if ($this->conn) {
            $notes_esc = mysqli_real_escape_string($this->conn, $notes);
            $pct_val = (float)$pct;
            $tid = intval($taskId);
            $aid = intval($assetId);
            mysqli_query($this->conn, "UPDATE asset_assignments SET latest_condition_code = $code, latest_condition_pct = $pct_val WHERE id = $aid");
            mysqli_query($this->conn, "UPDATE sensus_tasks SET final_pct = $pct_val, final_notes = '$notes_esc', final_date = '$now', status = 'Finalized' WHERE id = $tid");
        } else {
            throw new \Exception("Database required");
        }
    }
}
