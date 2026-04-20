<?php
/**
 * API: get_settings.php
 * Mengembalikan konfigurasi sistem (margin, pajak, depresiasi) sebagai JSON.
 * Digunakan halaman yang membutuhkan nilai real-time tanpa reload halaman.
 *
 * Response:
 *   margin_pengadaan  — markup / overhead biaya administrasi (%)
 *   pajak             — tarif PPN / pajak yang berlaku (%)
 *   nilai_sisa        — salvage value di akhir umur ekonomis aset (%)
 *   status            — 'ok' | 'error'
 *   _fetched_at       — unix timestamp pengambilan data (untuk cache-busting)
 */

require_once __DIR__ . '/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

// Default values
$margin_pengadaan = 5;
$pajak            = 11;
$nilai_sisa       = 10;
$status           = 'ok';

if ($db) {
    try {
        $docs = $db->collection('system_settings')->documents();
        foreach ($docs as $doc) {
            if (!$doc->exists()) continue;
            $val = $doc->data()['setting_value'] ?? null;
            if ($val === null) continue;
            switch ($doc->id()) {
                case 'margin_pengadaan': $margin_pengadaan = (float)$val; break;
                case 'pajak':            $pajak            = (float)$val; break;
                case 'nilai_sisa':       $nilai_sisa       = (float)$val; break;
            }
        }
    } catch (Exception $e) { $db = null; }
}

if (!$db && $conn) {
    $res = mysqli_query($conn, "SELECT * FROM system_settings");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            switch ($row['setting_key']) {
                case 'margin_pengadaan': $margin_pengadaan = (float)$row['setting_value']; break;
                case 'pajak':            $pajak            = (float)$row['setting_value']; break;
                case 'nilai_sisa':       $nilai_sisa       = (float)$row['setting_value']; break;
            }
        }
    } else { $status = 'error'; }
}

echo json_encode([
    'margin_pengadaan' => $margin_pengadaan,
    'pajak'            => $pajak,
    'nilai_sisa'       => $nilai_sisa,
    'status'           => $status,
    '_fetched_at'      => time(),
]);
