<?php
session_start();
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] === 'admin') {
    $id = (int)$_POST['id'];
    $change = (int)$_POST['change'];
    $action = $_POST['action'];

    if ($action === 'add') {
        $stmt = mysqli_prepare($conn, "UPDATE inventory SET stock = stock + ? WHERE id = ?");
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE inventory SET stock = GREATEST(0, stock - ?) WHERE id = ?");
    }
    mysqli_stmt_bind_param($stmt, "ii", $change, $id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../admin/inventory.php?pesan=update_berhasil");
    } else {
        die("Error 500: Terjadi kesalahan saat memproses data.");
    }
} else {
    header("Location: ../auth/login_admin.php");
}
?>