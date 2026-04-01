<?php
session_start();
include 'database.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Akses tertolak");
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $docRef = $db->collection('inventory')->document($id);
    $snapshot = $docRef->snapshot();

    if ($snapshot->exists()) {
        $docRef->delete();
        header("Location: ../admin/inventory.php?status=deleted");
        exit();
    } else {
        header("Location: ../admin/inventory.php?status=error");
        exit();
    }
} else {
    header("Location: ../admin/inventory.php");
}
?>