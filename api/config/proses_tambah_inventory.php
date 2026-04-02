<?php

require_once __DIR__ . '/database.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_user.php");
    exit();
}

if (isset($_POST['tambah_item'])) {
    $item_name  = $_POST['item_name'];
    $category   = $_POST['category'];
    $stock      = (int)$_POST['stock'];
    $satuan     = $_POST['satuan'];
    $min_stock  = (int)$_POST['min_stock'];
    $price      = (float)$_POST['price'];

    try {
        $db->collection('inventory')->add([
            'item_name' => $item_name,
            'category' => $category,
            'stock' => $stock,
            'satuan' => $satuan,
            'min_stock' => $min_stock,
            'price_reference' => $price,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        header("Location: ../admin/inventory.php?status=success");
        exit();
    } catch (Exception $e) {
        die("Error 500: Terjadi kesalahan saat memproses data: " . $e->getMessage());
    }
}
?>
