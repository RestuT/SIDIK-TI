<?php

require_once __DIR__ . '/database.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Akses tertolak");
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $deleted = false;

    if ($db && !is_numeric($id)) {
        try {
            $docRef = $db->collection('inventory')->document($id);
            if ($docRef->snapshot()->exists()) {
                $docRef->delete();
                $deleted = true;
            }
        } catch (Exception $e) { $db = null; }
    }

    if (!$db && $conn) {
        $id_int = intval($id);
        $check = mysqli_query($conn, "SELECT id FROM inventory WHERE id = $id_int");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "DELETE FROM inventory WHERE id = $id_int");
            $deleted = true;
        }
    }

    if ($deleted) {
        header("Location: ../admin/inventory.php?status=deleted");
    } else {
        header("Location: ../admin/inventory.php?status=error");
    }
    exit();
} else {
    header("Location: ../admin/inventory.php");
}
