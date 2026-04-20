<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/csrf_helper.php';

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
    
    $exists = false;
    $now = date('Y-m-d H:i:s');

    if ($db) {
        try {
            $check = $db->collection('departments')->where('nama_dept', '=', $nama)->limit(1)->documents();
            if (!$check->isEmpty()) {
                $exists = true;
            } else {
                $db->collection('departments')->add(['nama_dept' => $nama, 'created_at' => $now]);
            }
        } catch (Exception $e) { $db = null; }
    }

    if (!$db && $conn) {
        $nama_esc = mysqli_real_escape_string($conn, $nama);
        $check = mysqli_query($conn, "SELECT id FROM departments WHERE nama_dept = '$nama_esc' LIMIT 1");
        if (mysqli_num_rows($check) > 0) {
            $exists = true;
        } else {
            mysqli_query($conn, "INSERT INTO departments (nama_dept, created_at) VALUES ('$nama_esc', '$now')");
        }
    }

    if ($exists) {
        header("Location: ../admin/manage_departments.php?status=error");
    } else {
        header("Location: ../admin/manage_departments.php?status=added");
    }
    exit();
} else {
    header("Location: ../admin/manage_departments.php");
}
