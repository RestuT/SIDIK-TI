<?php
// Pastikan session_start() sudah dipanggil sebelum file ini di-include

/**
 * Helper untuk menangani Cross-Site Request Forgery (CSRF)
 */

// 1. Generate Token Unik per Session
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        // Menggunakan library random bytes yang aman secara kriptografi
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// 2. Verifikasi Token pada POST Request
function verify_csrf_token($post_token) {
    if (!isset($_SESSION['csrf_token']) || empty($post_token)) {
        return false;
    }
    // Gunakan hash_equals untuk mencegah timing attacks
    return hash_equals($_SESSION['csrf_token'], $post_token);
}

// 3. Helper untuk langsung menghentikan proses (Die) jika token gagal
function require_csrf_token() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            // Bisa diganti dengan instruksi HTTP 403 Forbidden
            http_response_code(403);
            die("Error 403: CSRF Token Verification Failed. Request dibatalkan demi keamanan.");
        }
    }
}

