<?php

require_once __DIR__ . '/database.php';

// Pastikan yang menghapus adalah seorang admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Akses tertolak: Anda bukan Administrator.");
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $deleted = false;
    $is_admin = false;

    if ($db && !is_numeric($id)) {
        try {
            $userRef = $db->collection('users')->document($id);
            $userSnap = $userRef->snapshot();
            if ($userSnap->exists()) {
                $user_data = $userSnap->data();
                if (($user_data['role'] ?? '') === 'admin') {
                    $is_admin = true;
                } else {
                    $userRef->delete();
                    $deleted = true;
                }
            }
        } catch (Exception $e) { $db = null; }
    }

    if (!$db && $conn) {
        $id_esc = mysqli_real_escape_string($conn, $id);
        $res = mysqli_query($conn, "SELECT role FROM users WHERE id = '$id_esc'");
        if ($res && mysqli_num_rows($res) > 0) {
            $row = mysqli_fetch_assoc($res);
            if ($row['role'] === 'admin') {
                $is_admin = true;
            } else {
                mysqli_query($conn, "DELETE FROM users WHERE id = '$id_esc'");
                $deleted = true;
            }
        }
    }

    if ($is_admin) {
        die("Error: Anda tidak diperkenankan menghapus akun Administrator.");
    }

    if ($deleted) {
        header("Location: ../admin/manage_users.php?status=deleted");
    } else {
        header("Location: ../admin/manage_users.php?status=error");
    }
    exit();
} else {
    header("Location: ../admin/manage_users.php");
}
