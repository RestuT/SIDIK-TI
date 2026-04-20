<?php
namespace App\Services;

use App\Core\BaseService;

/**
 * Asset Intelligence Service
 * Handles calculation and monitoring of asset health and lifecycle.
 */
class AssetService extends BaseService {

    /**
     * Hitung Depresiasi & Utilisasi (PMK 72/2023 with Stress Factor)
     */
    public function calculateDepreciation($item_name, $category, $assigned_date, $inv_prices, $settings, $custom_price = null, $multiplier = 1.0) {
        if (stripos($category, 'Software') !== false || stripos($category, 'Aplikasi') !== false) {
            return [
                'type' => 'software',
                'current' => 0, 
                'purchase' => ($custom_price ?: 0), 
                'salvage' => false, 
                'pct_used' => 0,
                'auto_condition' => 1,
                'effective_months' => 0
            ];
        }

        $base_price = ($custom_price && $custom_price > 0) ? $custom_price : ($inv_prices[$item_name] ?? 0);
        if ($base_price == 0 || !$assigned_date) return null;

        $margin_pct = (float)($settings['margin_pengadaan'] ?? 5);
        $pajak_pct  = (float)($settings['pajak'] ?? 11);
        $salvage_pct= (float)($settings['nilai_sisa'] ?? 10);

        // Calculate Purchase Price with tax and margin
        $after_markup   = $base_price * (1 + $margin_pct / 100);
        $purchase_price = $after_markup * (1 + $pajak_pct / 100);
        $salvage_value  = $purchase_price * ($salvage_pct / 100);

        $useful_life_months = 48; // Default
        if (stripos($category, 'Server') !== false || stripos($category, 'Networking') !== false) {
            $useful_life_months = 60;
        }

        $assigned_time  = strtotime($assigned_date);
        $now            = time();
        $actual_months  = max(0, ($now - $assigned_time) / (30.4375 * 24 * 3600));
        
        // Apply Stress Factor (Multiplier)
        $effective_months = $actual_months * (float)$multiplier;

        $pct_used = min(100, ($effective_months / $useful_life_months) * 100);
        $auto_condition = 1;
        if ($pct_used > 50 && $pct_used <= 75) $auto_condition = 2;
        if ($pct_used > 75) $auto_condition = 3;

        if ($actual_months <= 0) {
            return ['type' => 'hardware', 'current' => $purchase_price, 'purchase' => $purchase_price, 'salvage' => false, 'pct_used' => 0, 'auto_condition' => 1, 'effective_months' => 0];
        }

        $depreciation_per_month = ($purchase_price - $salvage_value) / $useful_life_months;
        $current_value          = $purchase_price - ($depreciation_per_month * $effective_months);

        if ($current_value <= $salvage_value || $effective_months >= $useful_life_months) {
            return ['type' => 'hardware', 'current' => $salvage_value, 'purchase' => $purchase_price, 'salvage' => true, 'pct_used' => 100, 'auto_condition' => 3, 'effective_months' => $effective_months];
        }

        return ['type' => 'hardware', 'current' => $current_value, 'purchase' => $purchase_price, 'salvage' => false, 'pct_used' => $pct_used, 'auto_condition' => $auto_condition, 'effective_months' => $effective_months];
    }

    /**
     * Get lifecycle recommendation
     */
    public function getRecommendation($util_pct, $phys_cond_code) {
        if ($util_pct >= 90) {
            if ($phys_cond_code == 1) return ['label' => 'Ext. Lifecycle', 'class' => 'bg-indigo-500/10 text-indigo-400', 'desc' => 'Hardware masih kuat walau usia tua.'];
            return ['label' => 'Replace (EoL)', 'class' => 'bg-rose-500/10 text-rose-400', 'desc' => 'Usia habis dan kondisi fisik menurun.'];
        }
        if ($phys_cond_code >= 3 && $util_pct < 50) return ['label' => 'Analisis Kerusakan', 'class' => 'bg-amber-500/10 text-amber-400', 'desc' => 'Rusak sebelum waktunya, cek beban kerja.'];
        if ($util_pct >= 75) return ['label' => 'Prep Replacement', 'class' => 'bg-orange-500/10 text-orange-400', 'desc' => 'Mendekati limit masa manfaat.'];
        return ['label' => 'Optimal', 'class' => 'bg-emerald-500/10 text-emerald-400', 'desc' => 'Aset dalam siklus hidup ideal.'];
    }

    /**
     * Update asset multiplier
     */
    public function updateMultiplier($asset_id, $multiplier) {
        $multiplier = (float)$multiplier;
        if ($multiplier < 0.1) $multiplier = 0.1;

        if ($this->db && !is_numeric($asset_id)) {
            // Firestore
            $this->db->collection('asset_assignments')->document($asset_id)->update([
                ['path' => 'utilization_multiplier', 'value' => $multiplier]
            ]);
            $batches = $this->db->collection('sensus_batches')->where('status', '=', 'Active')->documents();
            foreach ($batches as $b) {
                $tasks = $this->db->collection('sensus_tasks')->where('batch_id', '=', $b->id())->where('asset_id', '=', $asset_id)->documents();
                foreach ($tasks as $t) { $t->reference()->update([['path' => 'multiplier', 'value' => $multiplier]]); }
            }
        } else if ($this->conn) {
            // MySQL
            $m = (float)$multiplier;
            \mysqli_query($this->conn, "UPDATE asset_assignments SET utilization_multiplier = $m WHERE id = " . \intval($asset_id));
            \mysqli_query($this->conn, "UPDATE sensus_tasks SET multiplier = $m WHERE asset_id = " . \intval($asset_id) . " AND status = 'Pending'");
        } else {
            throw new \Exception("Database required");
        }
    }

    /**
     * Request asset disposal (penghapusan)
     */
    public function requestDisposal($assetId, $reason, $assetData) {
        $reason_esc = \mysqli_real_escape_string($this->conn, $reason);
        $now = $this->now();

        if ($this->db && !is_numeric($assetId)) {
            $this->db->collection('asset_assignments')->document($assetId)->update([['path' => 'status', 'value' => 'Pending Disposal']]);
            $ticketCounterRef = $this->db->collection('system_counters')->document('submissions');
            return $this->db->runTransaction(function ($transaction) use ($ticketCounterRef, $assetData, $reason, $assetId) {
                $counterSnap = $transaction->snapshot($ticketCounterRef);
                $new_val = ($counterSnap->exists() ? ($counterSnap->get('latest') ?? 0) : 0) + 1;
                $transaction->set($ticketCounterRef, ['latest' => $new_val], ['merge' => true]);
                $ticket_number = 'DIS-' . date('Y') . '-' . str_pad($new_val, 4, '0', STR_PAD_LEFT);
                $transaction->set($this->db->collection('submissions')->newDocument(), [
                    'ticket_number' => $ticket_number, 'user_id' => $assetData['user_id'] ?? 'Admin',
                    'type' => 'Penghapusan', 'title' => 'Penggantian: ' . $assetData['item_name'],
                    'description' => "Penghapusan unit SN: " . ($assetData['serial_number'] ?? '-') . ". Alasan: " . $reason,
                    'status' => 'Menunggu', 'urgency' => 'Tinggi', 'created_at' => $this->now(),
                    'estimasi' => $assetData['price_reference'] ?? 0, 'department' => $assetData['department'] ?? 'Unknown',
                    'attachment_path' => '', 'disposal_asset_id' => $assetId
                ]);
                return $ticket_number;
            });
        } else if ($this->conn) {
            mysqli_query($this->conn, "UPDATE asset_assignments SET status = 'Pending Disposal' WHERE id = " . intval($assetId));
            $res_count = mysqli_query($this->conn, "SELECT COUNT(*) as c FROM submissions WHERE type = 'Penghapusan'");
            $count = mysqli_fetch_assoc($res_count)['c'] + 1;
            $ticket_number = 'DIS-' . date('Y') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            $user_id = mysqli_real_escape_string($this->conn, $assetData['user_id'] ?? 'Admin');
            $item_name = mysqli_real_escape_string($this->conn, $assetData['item_name']);
            $sn = mysqli_real_escape_string($this->conn, $assetData['serial_number'] ?? '-');
            $dept = mysqli_real_escape_string($this->conn, $assetData['department'] ?? 'Unknown');
            $price = (float)($assetData['price_reference'] ?? 0);
            
            $sql = "INSERT INTO submissions (ticket_number, user_id, type, title, description, status, urgency, created_at, estimasi, department, disposal_asset_id) 
                    VALUES ('$ticket_number', '$user_id', 'Penghapusan', 'Penggantian: $item_name', 'Penghapusan unit SN: $sn. Alasan: $reason_esc', 'Menunggu', 'Tinggi', '$now', $price, '$dept', $assetId)";
            mysqli_query($this->conn, $sql);
            return $ticket_number;
        }
        throw new \Exception("Database required");
    }

    /**
     * Finalize disposal by Admin
     */
    public function finalizeDisposal($assetId) {
        $now = $this->now();
        if ($this->db && !is_numeric($assetId)) {
            $this->db->collection('asset_assignments')->document($assetId)->update([
                ['path' => 'status', 'value' => 'Disposed'],
                ['path' => 'disposed_at', 'value' => $now]
            ]);
        } else if ($this->conn) {
            mysqli_query($this->conn, "UPDATE asset_assignments SET status = 'Disposed', disposed_at = '$now' WHERE id = " . intval($assetId));
        } else {
            throw new \Exception("Database required");
        }
    }
}
