<?php
session_start();
include 'database.php';

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

    $stmt = mysqli_prepare($conn, "INSERT INTO inventory (item_name, category, stock, satuan, min_stock, price_reference) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssisid", $item_name, $category, $stock, $satuan, $min_stock, $price);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../admin/inventory.php?status=success");
        exit();
    } else {
        die("Error 500: Terjadi kesalahan saat memproses data.");
    }
}
?>