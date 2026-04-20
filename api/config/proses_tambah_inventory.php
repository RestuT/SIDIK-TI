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
    $now = date('Y-m-d H:i:s');

    $data = [
        'item_name' => $item_name, 'category' => $category, 'stock' => $stock, 'satuan' => $satuan,
        'min_stock' => $min_stock, 'price_reference' => $price, 'created_at' => $now
    ];

    if ($db) {
        try {
            $db->collection('inventory')->add($data);
            header("Location: ../admin/inventory.php?status=success");
            exit();
        } catch (Exception $e) { $db = null; }
    }

    if (!$db && $conn) {
        $name_e = mysqli_real_escape_string($conn, $item_name);
        $cat_e = mysqli_real_escape_string($conn, $category);
        $sat_e = mysqli_real_escape_string($conn, $satuan);
        
        $sql = "INSERT INTO inventory (item_name, category, stock, satuan, min_stock, price_reference, created_at) 
                VALUES ('$name_e', '$cat_e', $stock, '$sat_e', $min_stock, $price, '$now')";
        if (mysqli_query($conn, $sql)) {
            header("Location: ../admin/inventory.php?status=success");
            exit();
        }
    }
    
    die("Error 500: Terjadi kesalahan saat memproses data.");
}
