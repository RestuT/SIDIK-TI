<?php
session_start();
include 'database.php';

// Proteksi akses hanya untuk admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Akses tertolak");
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $docRef = $db->collection('departments')->document($id);
    $snapshot = $docRef->snapshot();

    if ($snapshot->exists()) {
        $docRef->delete();
        header("Location: ../admin/manage_departments.php?status=deleted");
        exit();
    } else {
        header("Location: ../admin/manage_departments.php?status=error");
        exit();
    }
} else {
    header("Location: ../admin/manage_departments.php");
}
?>
