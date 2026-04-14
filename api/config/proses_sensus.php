require_once __DIR__ . '/database.php';
require_once __DIR__ . '/csrf_helper.php';

use App\Services\SensusService;
use App\Services\AssetService;

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $sensusService = new SensusService($db);
    $assetService  = new AssetService($db);

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

        $sensusService->finalizeTask($task_id, $asset_id, $_POST['notes'] ?? '');

        header("Location: ../admin/sensus_barang.php?status=task_finalized");
        exit();
    }

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
        if (empty($asset_id)) die("Missing Asset ID");
        $assetSnap = $db->collection('asset_assignments')->document($asset_id)->snapshot();
        if (!$assetSnap->exists()) die("Asset Not Found");
        
        $assetService->requestDisposal($asset_id, $_POST['disposal_reason'] ?? '', $assetSnap->data());
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

    header("Location: ../admin/sensus_barang.php");
    exit();
}