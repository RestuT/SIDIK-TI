<?php
session_start();
include 'database.php';

// Proteksi Admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] === 'admin') {
    
    // Sanitasi Input
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $stock     = (int)$_POST['stock'];
    $satuan    = mysqli_real_escape_string($conn, $_POST['satuan']);
    // Query Tambah Data
    $query = "INSERT INTO inventory (item_name, stock, satuan) VALUES ('$item_name', '$stock', '$satuan')";

    if (mysqli_query($conn, $query)) {
        // Berhasil, kembali ke halaman inventory
        header("Location: ../admin/inventory.php?pesan=item_ditambahkan");
        exit();
    } else {
        // Gagal
        echo "Error: " . mysqli_error($conn);
    }

} else {
    // Jika akses ilegal
    header("Location: ../auth/login_admin.php");
    exit();
}
?>