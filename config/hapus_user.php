<?php
session_start();
include 'database.php';

// Pastikan yang menghapus adalah seorang admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Akses tertolak: Anda bukan Administrator.");
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    if ($id <= 0) {
        die("Invalid ID");
    }

    // Sebagai proteksi ekstra ganda: 
    // Pastikan admin tidak bisa menghapus sesama admin atau akun miliknya sendiri.
    $check_role = mysqli_prepare($conn, "SELECT role FROM users WHERE id = ?");
    mysqli_stmt_bind_param($check_role, "i", $id);
    mysqli_stmt_execute($check_role);
    $res = mysqli_stmt_get_result($check_role);
    $user_data = mysqli_fetch_assoc($res);

    if ($user_data && $user_data['role'] === 'admin') {
        die("Error: Anda tidak diperkenankan menghapus akun Administrator.");
    }

    // Eksekusi Hapus User
    $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ? AND role = 'user'");
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../admin/manage_users.php?status=deleted");
    } else {
        echo "Error 500: Terjadi kesalahan saat menghapus data pengguna. Kemungkinan sedang digunakan sebagai referensi data transaksi (Constraint FK). Hubungi IT Dept.";
    }
    
    mysqli_stmt_close($stmt);
} else {
    header("Location: ../admin/manage_users.php");
}
?>
