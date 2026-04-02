<?php

require_once __DIR__ . '/database.php';
use Google\Cloud\Firestore\FieldValue;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $id = $_POST['id'];
    $change = (int)$_POST['change'];
    $action = $_POST['action'];

    try {
        $inventoryRef = $db->collection('inventory')->document($id);
        
        if ($action === 'add') {
            $inventoryRef->update([
                ['path' => 'stock', 'value' => FieldValue::increment($change)]
            ]);
        } else {
            // Firestore increment accepts negative numbers, but we want to ensure it doesn't go below 0.
            // For a robust implementation, we'd use a transaction if we really care about the 0 limit exactly.
            // Here, we can fetch first or just increment with a negative.
            $snap = $inventoryRef->snapshot();
            if ($snap->exists()) {
                $currentStock = (int)($snap->data()['stock'] ?? 0);
                $newStock = max(0, $currentStock - $change);
                $inventoryRef->update([
                    ['path' => 'stock', 'value' => $newStock]
                ]);
            }
        }

        header("Location: ../admin/inventory.php?pesan=update_berhasil");
        exit();
    } catch (Exception $e) {
        die("Error 500: Terjadi kesalahan saat memproses data: " . $e->getMessage());
    }
} else {
    header("Location: ../auth/login_admin.php");
}
?>
