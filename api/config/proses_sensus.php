<?php
ob_start();

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/csrf_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $action = $_POST['action'] ?? '';
    $asset_id = $_POST['asset_id'] ?? '';
    $batch_id = $_POST['batch_id'] ?? '';
    $task_id = $_POST['task_id'] ?? '';

    // --- ACTION: START BATCH (Admin Only) ---
    if ($action === 'start_batch') {
        if ($_SESSION['role'] !== 'admin') die("Unauthorized");
        
        $batch_name = $_POST['batch_name'] ?? ('Sensus ' . date('F Y'));
        
        // 1. Create Batch Document
        $batchRef = $db->collection('sensus_batches')->newDocument();
        $batchRef->set([
            'batch_name' => $batch_name,
            'created_at' => date('Y-m-d H:i:s'),
            'status'     => 'Active',
            'created_by' => $_SESSION['user_id']
        ]);
        $new_batch_id = $batchRef->id();

        // 2. Generate Tasks for all Active & Assigned Assets
        $assets = $db->collection('asset_assignments')
                     ->where('status', '=', 'Active')
                     ->documents();

        $count = 0;
        foreach ($assets as $doc) {
            $data = $doc->data();
            if (empty($data['user_id'])) continue;

            $db->collection('sensus_tasks')->add([
                'batch_id'   => $new_batch_id,
                'asset_id'   => $doc->id(),
                'user_id'    => $data['user_id'],
                'user_name'  => $data['user_name'] ?? 'Unknown',
                'item_name'  => $data['item_name'],
                'department' => $data['department'] ?? 'Unknown',
                'status'     => 'Pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $count++;
        }

        header("Location: ../admin/sensus_barang.php?status=batch_started&count=$count");
        exit();
    }

    // --- ACTION: SUBMIT REPORT (User/Staff/Kabid/Admin) ---
    if ($action === 'submit_report') {
        if (empty($task_id)) die("Invalid Task ID");
        
        $pct = (float)$_POST['condition_pct'];
        $notes = $_POST['notes'] ?? '';

        $taskRef = $db->collection('sensus_tasks')->document($task_id);
        $taskRef->update([
            ['path' => 'report_pct', 'value' => $pct],
            ['path' => 'report_notes', 'value' => $notes],
            ['path' => 'report_date', 'value' => date('Y-m-d H:i:s')],
            ['path' => 'status', 'value' => 'Reported']
        ]);

        header("Location: ../modules_user/sensus_dashboard_user.php?status=reported");
        exit();
    }

    // --- ACTION: FINALIZE TASK (Admin Only) ---
    if ($action === 'finalize_task') {
        if ($_SESSION['role'] !== 'admin') die("Unauthorized");
        if (empty($task_id) || empty($asset_id)) die("Missing IDs");

        $pct = (float)$_POST['final_pct'];
        $notes = $_POST['final_notes'] ?? '';

        // Tentukan Label SOP
        $code = 3; 
        if ($pct >= 85) $code = 1;
        elseif ($pct >= 65) $code = 2;

        // 1. Update Aset Utama
        $assetRef = $db->collection('asset_assignments')->document($asset_id);
        $assetRef->update([
            ['path' => 'latest_condition_code', 'value' => $code],
            ['path' => 'latest_condition_pct', 'value' => $pct]
        ]);

        // 2. Simpan Log Inspeksi
        $db->collection('asset_inspections')->add([
            'asset_id' => $asset_id,
            'inspection_date' => date('Y-m-d H:i:s'),
            'condition_code' => $code,
            'condition_pct' => $pct,
            'inspector_id' => $_SESSION['user_id'],
            'notes' => "[Sensus Final] " . $notes
        ]);

        // 3. Close Task
        $db->collection('sensus_tasks')->document($task_id)->update([
            ['path' => 'status', 'value' => 'Finalized'],
            ['path' => 'finalized_at', 'value' => date('Y-m-d H:i:s')]
        ]);

        header("Location: ../admin/sensus_barang.php?status=finalized");
        exit();
    }

    if (empty($asset_id) && empty($batch_id) && empty($task_id)) {
        die("Invalid Request Data");
    }

    $assetRef = $db->collection('asset_assignments')->document($asset_id);
    $assetSnap = $assetRef->snapshot();

    if (!$assetSnap->exists()) {
        die("Aset tidak ditemukan.");
    }

    $assetData = $assetSnap->data();

    // 1. ACTION: INSPECT (SENSUS)
    if ($action === 'inspect') {
        $pct = (float)$_POST['condition_pct'];
        $notes = $_POST['notes'] ?? '';

        // Tentukan Label SOP
        $code = 3; // Default Rusak Berat
        if ($pct >= 85) {
            $code = 1; // Baik
        } elseif ($pct >= 65) {
            $code = 2; // Rusak Ringan
        }

        // Simpan log Sensus
        $db->collection('asset_inspections')->add([
            'asset_id' => $asset_id,
            'inspection_date' => date('Y-m-d H:i:s'),
            'condition_code' => $code,
            'condition_pct' => $pct,
            'inspector_id' => $_SESSION['user_id'],
            'notes' => $notes
        ]);

        // Update Aset
        $assetRef->update([
            ['path' => 'latest_condition_code', 'value' => $code],
            ['path' => 'latest_condition_pct', 'value' => $pct]
        ]);

        header("Location: ../admin/sensus_barang.php?status=success_inspect");
        exit();
    }

    // 2. ACTION: REQUEST DISPOSAL
    if ($action === 'request_disposal') {
        $reason = $_POST['disposal_reason'] ?? '';

        // Update status asset menjadi Pending Disposal
        $assetRef->update([
            ['path' => 'status', 'value' => 'Pending Disposal']
        ]);

        // Generate Ticket Number
        $ticketCounterRef = $db->collection('system_counters')->document('submissions');
        $db->runTransaction(function ($transaction) use ($ticketCounterRef, $assetData, $reason, $db, $asset_id) {
            $counterSnap = $transaction->snapshot($ticketCounterRef);
            $current_val = $counterSnap->exists() ? ($counterSnap->get('latest') ?? 0) : 0;
            $new_val = $current_val + 1;

            $transaction->set($ticketCounterRef, ['latest' => $new_val], ['merge' => true]);
            $ticket_number = 'DIS-' . date('Y') . '-' . str_pad($new_val, 4, '0', STR_PAD_LEFT);

            // Create submission ticket
            $newDocRef = $db->collection('submissions')->newDocument();
            $transaction->set($newDocRef, [
                'ticket_number' => $ticket_number,
                'user_id' => $assetData['user_id'], // user is the one losing the asset
                'type' => 'Penghapusan', // We use Penghapusan since it's a disposal type
                'title' => 'Penggantian: ' . $assetData['item_name'],
                'description' => "Penghapusan unit SN: " . ($assetData['serial_number'] ?? '-') . ". Alasan: " . $reason,
                'status' => 'Menunggu',
                'urgency' => 'Tinggi', // Usually high for broken assets
                'created_at' => date('Y-m-d H:i:s'),
                'estimasi' => $assetData['price_reference'] ?? 0,
                'department' => $assetData['department'],
                'attachment_path' => '',
                'disposal_asset_id' => $asset_id // To track which asset this refers to
            ]);
        });

        header("Location: ../admin/sensus_barang.php?status=success_disposal");
        exit();
    }

    header("Location: ../admin/sensus_barang.php");
    exit();
}
