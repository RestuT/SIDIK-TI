<?php
session_start();
include 'database.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Akses tertolak");
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Pastikan ID valid
    if ($id <= 0) {
        die("ID tidak valid");
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM inventory WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../admin/inventory.php?status=deleted");
    } else {
        echo "Error 500: Terjadi kesalahan saat menghapus stok gudang. Hubungi Administrator: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
} else {
    header("Location: ../admin/inventory.php");
}
?>