<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/csrf_helper.php';

use App\Services\SensusService;
use App\Services\AssetService;

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$asset_id = $_POST['asset_id'] ?? $_GET['asset_id'] ?? '';
$batch_id = $_POST['batch_id'] ?? $_GET['batch_id'] ?? '';
$task_id = $_POST['task_id'] ?? $_GET['task_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $sensusService = new SensusService($db, $conn);
    $assetService  = new AssetService($db, $conn);
    $now = date('Y-m-d H:i:s');

    // --- ACTION: START BATCH (Admin Only) ---
    if ($action === 'start_batch') {
        if ($_SESSION['role'] !== 'admin') die("Unauthorized");
        $batch_name = $_POST['batch_name'] ?? ('Sensus ' . date('F Y'));
        $result = $sensusService->startBatch($batch_name, $_SESSION['user_id']);
        header("Location: ../admin/sensus_barang.php?status=batch_started&count=" . $result['count']);
        exit();
    }

    // --- ACTION: SUBMIT REPORT (User/Staff/Kabid/Admin) ---
    if ($action === 'submit_report') {
        if (empty($task_id)) die("Invalid Task ID");
        $sensusService->submitReport($task_id, $_POST['condition_pct'], $_POST['notes'] ?? '');
        header("Location: ../modules_user/sensus_dashboard_user.php?status=reported");
        exit();
    }

    // --- ACTION: FINALIZE TASK (Admin Only) ---
    if ($action === 'finalize_task') {
        if ($_SESSION['role'] !== 'admin') die("Unauthorized");
        if (empty($task_id) || empty($asset_id)) die("Missing IDs");
        $sensusService->finalizeTask($task_id, $asset_id, $_POST['condition_pct'], $_POST['notes'] ?? '');
        header("Location: ../admin/sensus_barang.php?status=task_finalized");
        exit();
    }

    // --- ACTION: INSPECT (SENSUS - Direct from Admin) ---
    if ($action === 'inspect') {
        if (empty($asset_id)) die("Missing Asset ID");
        $pct = (float)$_POST['condition_pct'];
        $notes = $_POST['notes'] ?? '';
        $code = ($pct >= 85) ? 1 : (($pct >= 65) ? 2 : 3);
        $uid = $_SESSION['user_id'];

        if ($db && !is_numeric($asset_id)) {
            $assetRef = $db->collection('asset_assignments')->document($asset_id);
            $db->collection('asset_inspections')->add([
                'asset_id' => $asset_id, 'inspection_date' => $now, 'condition_code' => $code,
                'condition_pct' => $pct, 'inspector_id' => $uid, 'notes' => $notes
            ]);
            $assetRef->update([['path' => 'latest_condition_code', 'value' => $code], ['path' => 'latest_condition_pct', 'value' => $pct]]);
        } else if ($conn) {
            $notes_e = mysqli_real_escape_string($conn, $notes);
            $uid_e = mysqli_real_escape_string($conn, $uid);
            mysqli_query($conn, "INSERT INTO asset_inspections (asset_id, inspection_date, condition_code, condition_pct, inspector_id, notes) 
                                 VALUES (".intval($asset_id).", '$now', $code, $pct, '$uid_e', '$notes_e')");
            mysqli_query($conn, "UPDATE asset_assignments SET latest_condition_code=$code, latest_condition_pct=$pct WHERE id=".intval($asset_id));
        }
        header("Location: ../admin/sensus_barang.php?status=success_inspect");
        exit();
    }

    // --- ACTION: REQUEST DISPOSAL ---
    if ($action === 'request_disposal') {
        if (empty($asset_id)) die("Missing Asset ID");
        $assetData = [];
        if ($db && !is_numeric($asset_id)) {
            $assetSnap = $db->collection('asset_assignments')->document($asset_id)->snapshot();
            if ($assetSnap->exists()) $assetData = $assetSnap->data();
        } else if ($conn) {
            $res = mysqli_query($conn, "SELECT * FROM asset_assignments WHERE id = ".intval($asset_id));
            if ($res) $assetData = mysqli_fetch_assoc($res);
        }
        if (empty($assetData)) die("Asset Not Found");
        $assetService->requestDisposal($asset_id, $_POST['disposal_reason'] ?? '', $assetData);
        header("Location: ../admin/sensus_barang.php?status=success_disposal");
        exit();
    }

    // --- ACTION: UPDATE MULTIPLIER (Admin Only) ---
    if ($action === 'update_multiplier') {
        if ($_SESSION['role'] !== 'admin') die("Unauthorized");
        if (empty($asset_id)) die("Missing Asset ID");
        try {
            $assetService->updateMultiplier($asset_id, $_POST['multiplier']);
            http_response_code(200); echo "OK";
        } catch (\Exception $e) {
            http_response_code(500); echo $e->getMessage();
        }
        exit();
    }

    header("Location: ../admin/sensus_barang.php?status=finalized");
    exit();
}

header("Location: ../admin/sensus_barang.php");
exit();