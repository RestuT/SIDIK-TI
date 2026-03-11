<?php
session_start();
include 'database.php';
include 'csrf_helper.php';

// Proteksi: Hanya Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Akses tertolak");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_dept'])) {
    
    // Verifikasi Token CSRF
    require_csrf_token();
    
    $nama = trim($_POST['nama_dept']);
    
    // Cegah input kosong
    if (empty($nama)) {
        header("Location: ../admin/manage_departments.php?status=error");
        exit();
    }
    
    // Insert Data (Prepared Statement untuk anti-SQLi)
    $stmt = mysqli_prepare($conn, "INSERT INTO departments (nama_dept) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "s", $nama);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../admin/manage_departments.php?status=added");
    } else {
        // Bisa jadi error karena constraint UNIQUE duplicate
        header("Location: ../admin/manage_departments.php?status=error");
    }
    
    mysqli_stmt_close($stmt);
} else {
    header("Location: ../admin/manage_departments.php");
}
?>
