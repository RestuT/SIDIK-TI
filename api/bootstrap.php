<?php
ob_start();
/**
 * SIDIK-TI Bootstrap / Auto-Prepend File
 *
 * File ini di-load SEBELUM file PHP manapun dieksekusi (via auto_prepend_file).
 * Fungsinya:
 *  1. Aktifkan output buffering agar whitespace/BOM tidak bocor sebelum session_start()
 *  2. Set header default yang aman
 *
 * Konfigurasi: lihat api/.user.ini
 */

// Aktifkan output buffering jika belum aktif
if (!ob_get_level()) {
    ob_start();
}
