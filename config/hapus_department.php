<?php
session_start();
include 'database.php';

// Proteksi akses hanya untuk admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Akses tertolak");
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Validasi ID sebelum di eksekusi
    if ($id <= 0) {
        die("ID tidak valid");
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM departments WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../admin/manage_departments.php?status=deleted");
    } else {
        // Handle error: (Biasanya jika data digunakan sebagai referensi foreign key di table lain, tapi saat ini sifat departemen adalah string statis/free-text).
        echo "Error 500: Terjadi kesalahan saat menghapus departemen. Lapor pada administrator: ".mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
} else {
    header("Location: ../admin/manage_departments.php");
}
?>
