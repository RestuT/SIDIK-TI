<?php

require_once __DIR__ . '/database.php';

// Proteksi akses hanya untuk admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Akses tertolak");
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $deleted = false;

    if ($db && !is_numeric($id)) {
        try {
            $docRef = $db->collection('departments')->document($id);
            if ($docRef->snapshot()->exists()) {
                $docRef->delete();
                $deleted = true;
            }
        } catch (Exception $e) { $db = null; }
    }

    if (!$db && $conn) {
        $id_int = intval($id);
        $check = mysqli_query($conn, "SELECT id FROM departments WHERE id = $id_int");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "DELETE FROM departments WHERE id = $id_int");
            $deleted = true;
        }
    }

    if ($deleted) {
        header("Location: ../admin/manage_departments.php?status=deleted");
    } else {
        header("Location: ../admin/manage_departments.php?status=error");
    }
    exit();
} else {
    header("Location: ../admin/manage_departments.php");
}
