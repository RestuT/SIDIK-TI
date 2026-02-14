<?php
session_start();
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] === 'admin') {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $change = (int)$_POST['change'];
    $action = $_POST['action'];

    if ($action === 'add') {
        $query = "UPDATE inventory SET stock = stock + $change WHERE id = '$id'";
    } else {
        $query = "UPDATE inventory SET stock = GREATEST(0, stock - $change) WHERE id = '$id'";
    }

    if (mysqli_query($conn, $query)) {
        header("Location: ../admin/inventory.php?pesan=update_berhasil");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    header("Location: ../auth/login_admin.php");
}
?>