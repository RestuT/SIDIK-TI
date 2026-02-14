<?php
session_start();

// Menghapus semua data session
$_SESSION = array();

// Menghancurkan session
session_destroy();

// Mengarahkan kembali ke halaman login atau landing page
header("Location: ../index.php?pesan=logout_berhasil");
exit();
?>