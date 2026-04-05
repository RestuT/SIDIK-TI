<?php

require_once __DIR__ . '/database.php';

// Proteksi Admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    
    // Sanitasi Input (Firestore handles escaping)
    $item_name = $_POST['item_name'];
    $stock     = (int)$_POST['stock'];
    $satuan    = $_POST['satuan'];

    try {
        // Query Tambah Data ke Firestore
        $db->collection('inventory')->add([
            'item_name' => $item_name,
            'stock' => $stock,
            'satuan' => $satuan,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Berhasil, kembali ke halaman inventory
        header("Location: ../admin/inventory.php?pesan=item_ditambahkan");
        exit();
    } catch (Exception $e) {
        // Gagal
        echo "Error: " . $e->getMessage();
    }

} else {
    // Jika akses ilegal
    header("Location: ../auth/login_admin.php");
    exit();
}
// Closing tag removed to prevent header output
