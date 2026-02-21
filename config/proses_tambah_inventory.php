<?php
session_start();
include 'database.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_user.php");
    exit();
}

if (isset($_POST['tambah_item'])) {
    $item_name  = mysqli_real_escape_string($conn, $_POST['item_name']);
    $category   = mysqli_real_escape_string($conn, $_POST['category']);
    $stock      = mysqli_real_escape_string($conn, $_POST['stock']);
    $satuan     = mysqli_real_escape_string($conn, $_POST['satuan']);
    $min_stock  = mysqli_real_escape_string($conn, $_POST['min_stock']);
    $price      = mysqli_real_escape_string($conn, $_POST['price']);

    $query = "INSERT INTO inventory (item_name, category, stock, satuan, min_stock, price_reference) 
              VALUES ('$item_name', '$category', '$stock', '$satuan', '$min_stock', '$price')";

    if (mysqli_query($conn, $query)) {
        header("Location: ../admin/inventory.php?status=success");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>