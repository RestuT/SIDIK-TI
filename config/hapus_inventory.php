<?php
session_start();
include 'database.php';

// Proteksi Admin: Hanya role admin yang boleh menghapus
if (isset($_GET['id']) && $_SESSION['role'] === 'admin') {
    
    $id = mysqli_real_escape_string($conn, $_GET['id']);

    // Query Hapus Permanen
    $query = "DELETE FROM inventory WHERE id = '$id'";

    if (mysqli_query($conn, $query)) {
        // Berhasil dihapus, arahkan kembali dengan pesan sukses
        header("Location: ../admin/inventory.php?pesan=hapus_sukses");
        exit();
    } else {
        // Gagal karena relasi database atau error lainnya
        echo "Gagal menghapus produk: " . mysqli_error($conn);
    }

} else {
    // Jika mencoba akses tanpa login admin
    header("Location: ../auth/login_admin.php");
    exit();
}
?>