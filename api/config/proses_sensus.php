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
    
    if (empty($asset_id)) {
        die("Invalid Asset ID");
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
