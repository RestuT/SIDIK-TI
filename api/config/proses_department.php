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
    
    // Check for duplicate
    $check = $db->collection('departments')->where('nama_dept', '=', $nama)->limit(1)->documents();
    
    if ($check->isEmpty()) {
        $db->collection('departments')->add([
            'nama_dept' => $nama,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        header("Location: ../admin/manage_departments.php?status=added");
    } else {
        header("Location: ../admin/manage_departments.php?status=error");
    }
} else {
    header("Location: ../admin/manage_departments.php");
}
?>
