<?php
session_start();
require_once __DIR__ . '/database.php';

// Pastikan yang menghapus adalah seorang admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Akses tertolak: Anda bukan Administrator.");
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $userRef = $db->collection('users')->document($id);
    $userSnap = $userRef->snapshot();

    if (!$userSnap->exists()) {
        die("User tidak ditemukan.");
    }

    $user_data = $userSnap->data();

    if ($user_data && ($user_data['role'] ?? '') === 'admin') {
        die("Error: Anda tidak diperkenankan menghapus akun Administrator.");
    }

    // Eksekusi Hapus User
    $userRef->delete();
    header("Location: ../admin/manage_users.php?status=deleted");
    exit();
} else {
    header("Location: ../admin/manage_users.php");
}
?>
