<?php
ob_start();
require_once __DIR__ . '/../config/database.php';

// Proteksi akses pengguna
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

// Generate Full Domain for QR URL
$current_domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$base_path = dirname(dirname($_SERVER['PHP_SELF'])); // Removes /modules_user
// Clean base path if it ends with /api
if (substr($base_path, -4) === '/api') {
    $base_path = substr($base_path, 0, -4);
}
// For safety, let's hardcode the path structure relative to domain:
$qr_scan_url = $current_domain . "/scan/asset?id="; 

$asset_id = $_GET['id'] ?? '';
if (empty($asset_id)) {
    die("ID Aset tidak valid.");
}

$assetData = [];
try {
    $docSnap = $db->collection('asset_assignments')->document($asset_id)->snapshot();
    if (!$docSnap->exists()) {
        die("Aset tidak ditemukan.");
    }
    $assetData = $docSnap->data();
    $assetData['id'] = $docSnap->id();
} catch (Exception $e) {
    die("Error mengambil data aset.");
}

// Persiapkan Informasi
$itemName    = $assetData['item_name'] ?? 'Unknown Asset';
$assignedTo  = $assetData['user_name'] ?? ($assetData['assigned_to'] ?? 'Unassigned');
$department  = $assetData['department'] ?? '-';
$category    = $assetData['category'] ?? '-';
$kodeBarang  = $assetData['kode_barang'] ?? ($assetData['item_code'] ?? 'N/A');
$qrLink      = $qr_scan_url . urlencode($assetData['id']);

$assignedAt = isset($assetData['assigned_at']) ? date('M Y', strtotime($assetData['assigned_at'])) : '-';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Label Aset - <?php echo htmlspecialchars($itemName); ?></title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        headline: ["Plus Jakarta Sans"],
                        body: ["Inter"],
                    },
                },
            },
        }
    </script>
    <!-- QRious for Client-Side QR Generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    
    <style>
        body {
            background-color: #f1f5f9; /* slate-100 */
        }
        /* Mode Print - Hanya cetak area kartu, sembunyikan dekorasi background */
        @media print {
            body {
                background-color: transparent;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
            .print-card-wrapper {
                padding: 0;
                margin: 0;
                box-shadow: none !important;
                border: 1px dashed #cbd5e1;
            }
        }
    </style>
</head>
<body class="font-body text-slate-800 antialiased min-h-screen flex flex-col items-center justify-center p-8">

    <!-- Action Toolbar (No Print) -->
    <div class="no-print w-full max-w-sm mb-6 flex items-center justify-between bg-white rounded-2xl px-5 py-3 shadow-sm border border-slate-200">
        <button onclick="window.close()" class="text-slate-500 hover:text-slate-800 flex items-center gap-1 text-sm font-bold transition">
            <span class="material-symbols-outlined text-lg">arrow_back</span>
            Tutup
        </button>
        <button onclick="window.print()" class="bg-indigo-600 text-white rounded-xl px-4 py-2 text-sm font-black uppercase tracking-widest hover:bg-indigo-700 transition shadow-md shadow-indigo-200 flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">print</span>
            Cetak Stiker
        </button>
    </div>

    <!-- The Printable Asset Tag Card -->
    <!-- Ukuran disesuaikan untuk stiker vertikal (approx 240px x 380px) -->
    <div class="print-card-wrapper bg-white rounded-[2rem] shadow-2xl overflow-hidden flex flex-col relative" style="width: 280px; height: 420px; border: 1px solid #e2e8f0;">
        
        <!-- Header Section (White Strip) -->
        <div class="bg-white px-6 py-4 flex flex-col items-center justify-center border-b border-slate-100 shrink-0">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white">
                    <span class="material-symbols-outlined text-[20px]">verified_user</span>
                </div>
                <span class="font-headline font-black text-[16px] uppercase tracking-tighter text-slate-800">SIDIK-TI</span>
            </div>
            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.3em]">Smart Inventory</p>
        </div>

        <!-- Main Body (Indigo Background) -->
        <div class="flex-1 bg-indigo-600 p-6 flex flex-col items-center justify-between text-white relative">
            <!-- Decorative circle -->
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-indigo-500 rounded-full opacity-50"></div>
            <div class="absolute -left-4 bottom-12 w-16 h-16 bg-indigo-400 rounded-full opacity-30"></div>

            <div class="relative z-10 w-full flex flex-col items-center">
                <!-- QR Code Container -->
                <div class="bg-white p-4 rounded-[2rem] shadow-xl mb-6">
                    <canvas id="qrcode-canvas" class="w-36 h-36"></canvas>
                </div>

                <!-- Asset Primary Details -->
                <div class="text-center w-full space-y-1 mb-4">
                    <p class="text-[10px] font-black uppercase tracking-widest text-indigo-200">Nama Barang</p>
                    <h2 class="font-headline font-extrabold text-[18px] leading-tight line-clamp-2">
                        <?php echo htmlspecialchars($itemName); ?>
                    </h2>
                </div>

                <!-- Secondary Details Grid -->
                <div class="w-full grid grid-cols-1 gap-4 pt-4 border-t border-indigo-500/50">
                    <div class="flex flex-col items-center">
                        <p class="text-[9px] font-black uppercase tracking-widest text-indigo-200 mb-1">Kode Barang</p>
                        <p class="text-[12px] font-mono font-bold bg-indigo-800/50 px-3 py-1 rounded-full border border-indigo-400/30">
                            <?php echo htmlspecialchars($kodeBarang); ?>
                        </p>
                    </div>

                    <div class="flex justify-between items-end gap-2 px-2">
                        <div class="flex-1 min-w-0">
                            <p class="text-[8px] font-black uppercase tracking-widest text-indigo-300">Pemilik</p>
                            <p class="text-[11px] font-bold truncate"><?php echo htmlspecialchars($assignedTo); ?></p>
                        </div>
                        <div class="flex-1 min-w-0 text-right">
                            <p class="text-[8px] font-black uppercase tracking-widest text-indigo-300">Departemen</p>
                            <p class="text-[11px] font-bold truncate"><?php echo htmlspecialchars($department); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Small Info -->
            <div class="relative z-10 w-full flex justify-between items-center opacity-60 text-[8px] font-mono mt-4">
                <span>ID: <?php echo substr($assetData['id'], 0, 8); ?></span>
                <span>EST: <?php echo $assignedAt; ?></span>
            </div>
        </div>
    </div>
    
    <div class="no-print mt-6 max-w-sm text-center border-t border-slate-200 pt-6">
        <p class="text-xs text-slate-500 font-bold mb-2">Petunjuk Pencetakan:</p>
        <p class="text-[10px] text-slate-400 leading-relaxed uppercase tracking-wider">Gunakan kertas sticker berukuran A4 yang telah dipotong, atau kertas kartu tebal. Aktifkan opsi "Background Graphics" pada pengaturan print browser Anda untuk hasil terbaik.</p>
    </div>

    <!-- Script QR Generation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var qr = new QRious({
                element: document.getElementById('qrcode-canvas'),
                value: '<?php echo $qrLink; ?>',
                size: 200, // Ukuran di kanvas lebih besar agar jernih, nanti di downscale CSS (w-24 h-24)
                level: 'H', // Error correction tinggi agar aman jika kotor
                background: 'white',
                foreground: '#0f172a' // slate-900 (Gelap pekat)
            });
        });
    </script>
</body>
</html>
