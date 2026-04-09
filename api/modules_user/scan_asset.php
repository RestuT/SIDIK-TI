<?php
ob_start();
/**
 * SIDIK-TI - Web Portal Verifikasi Asset (Public / No Login)
 * Halaman ini diakses via pindaian QR Code pada stiker aset fisik.
 * HARUS menggunakan koneksi Firestore mandiri, TANPA database.php
 * karena halaman ini bersifat publik (tanpa sesi login).
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Dotenv\Dotenv;

// Load .env jika ada (lokal)
if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
}

// Inisialisasi Firestore secara mandiri
$db = null;
try {
    $factory = new Factory;
    $serviceAccountJson = getenv('FIREBASE_SERVICE_ACCOUNT_JSON');
    $projectId          = getenv('FIREBASE_PROJECT_ID');
    $privateKey         = str_replace('\\n', "\n", getenv('FIREBASE_PRIVATE_KEY') ?: '');
    $clientEmail        = getenv('FIREBASE_CLIENT_EMAIL');

    if ($serviceAccountJson) {
        $factory = $factory->withServiceAccount(json_decode($serviceAccountJson, true));
    } elseif ($projectId && $privateKey && $clientEmail) {
        $factory = $factory->withServiceAccount([
            'type'         => 'service_account',
            'project_id'   => $projectId,
            'private_key'  => $privateKey,
            'client_email' => $clientEmail,
        ]);
    } elseif (file_exists(__DIR__ . '/../../firebase-auth.json')) {
        $factory = $factory->withServiceAccount(__DIR__ . '/../../firebase-auth.json');
    }

    $firestore = $factory->createFirestore();
    $db = $firestore->database();
} catch (Exception $e) {
    $db = null;
}

// ----------------------------------------
$asset_id = $_GET['id'] ?? '';
$data = [];
$error = null;

function getConditionCode($start_date, $type, $man_code) {
    if (!empty($man_code)) return (int)$man_code;
    if (stripos($type ?? '', 'software') !== false) return 1;
    if (empty($start_date)) return 1;

    $months_used = (time() - strtotime($start_date)) / (30.4375 * 86400);
    if ($months_used < 24) return 1;
    if ($months_used < 48) return 2;
    return 3;
}

if (!$db) {
    $error = "Koneksi database tidak tersedia saat ini. Coba lagi beberapa saat.";
} elseif (empty($asset_id)) {
    $error = "QR Code tidak mencantumkan ID Aset yang valid.";
} else {
    try {
        $docSnap = $db->collection('asset_assignments')->document($asset_id)->snapshot();
        if (!$docSnap->exists()) {
            $error = "Aset tidak ditemukan dalam sistem (ID: " . htmlspecialchars($asset_id) . ").";
        } else {
            $data = $docSnap->data();
            $data['id'] = $docSnap->id();
        }
    } catch (Exception $e) {
        $error = "Error memuat data: " . $e->getMessage();
    }
}

// ---- Kalkulasi Kondisi & Depresiasi ----
if (empty($error) && !empty($data)) {
    $statusAset = $data['status'] ?? 'Active';
    $condCode = getConditionCode(
        $data['assigned_at'] ?? '',
        $data['category'] ?? 'hardware',
        $data['latest_condition_code'] ?? null
    );

    if ($statusAset === 'Disposed' || $statusAset === 'Pending Disposal') $condCode = 4;
    elseif ($statusAset === 'Maintenance') $condCode = 5;

    $c_style = match($condCode) {
        1 => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'check_circle', 'Kondisi Baik', 'from-emerald-700 to-emerald-500'],
        2 => ['bg-orange-50 text-orange-700 border-orange-200', 'warning', 'Rusak Ringan / Menua', 'from-orange-600 to-amber-500'],
        3 => ['bg-red-50 text-red-700 border-red-200', 'error', 'Rusak Berat / Limit Usia', 'from-red-600 to-red-400'],
        4 => ['bg-slate-100 text-slate-500 border-slate-300', 'delete', 'Sudah Di-Disposal', 'from-slate-700 to-slate-500'],
        5 => ['bg-indigo-50 text-indigo-700 border-indigo-200', 'build', 'Sedang Maintenance', 'from-indigo-700 to-blue-500'],
        default => ['bg-slate-100 text-slate-500 border-slate-300', 'help', 'Status Tidak Diketahui', 'from-slate-600 to-slate-400']
    };

    // Depresiasi
    $hargaBeli = (float)($data['price_reference'] ?? $data['original_price'] ?? 0);
    $sisaBulan = 48; $nilaiBuku = $hargaBeli; $pct_used = 0;
    if (stripos($data['category'] ?? '', 'software') === false && !empty($data['assigned_at'])) {
        $m_passed = (time() - strtotime($data['assigned_at'])) / (30.4375 * 86400);
        $m_passed = max(0, round($m_passed));
        $sisaBulan = max(0, 48 - $m_passed);
        $nilaiBuku = max(0, $hargaBeli - ($hargaBeli / 48) * $m_passed);
        $pct_used  = min(100, ($m_passed / 48) * 100);
    }
    
    // Nama pemegang aset
    $namaAset = $data['user_name'] ?? ($data['assigned_to'] ?? 'N/A');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Aset - SIDIK-TI</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { headline: ["Plus Jakarta Sans"], body: ["Inter"] } } } }</script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
        @keyframes popUp { from { opacity: 0; transform: scale(0.95) translateY(12px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        .card-anim { animation: popUp 0.55s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
        .card-anim:nth-child(1) { animation-delay: 0.05s; }
        .card-anim:nth-child(2) { animation-delay: 0.13s; }
        .card-anim:nth-child(3) { animation-delay: 0.21s; }
        .card-anim:nth-child(4) { animation-delay: 0.29s; }
    </style>
</head>
<body class="bg-slate-100 font-body text-slate-800 antialiased min-h-screen">

<?php if ($error): ?>
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="bg-white max-w-sm w-full p-8 rounded-[2rem] shadow-xl text-center border border-red-100">
            <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-5">
                <span class="material-symbols-outlined text-5xl text-red-400">warning</span>
            </div>
            <h2 class="font-headline font-black text-xl mb-2">Gagal Memindai</h2>
            <p class="text-xs text-slate-500 leading-relaxed"><?php echo htmlspecialchars($error); ?></p>
            <div class="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-2xl text-left text-xs text-amber-700 space-y-1">
                <p class="font-bold">💡 Pastikan:</p>
                <ul class="list-disc ml-4 space-y-1">
                    <li>QR code dipindai secara penuh dan jelas</li>
                    <li>Koneksi internet aktif</li>
                    <li>Stiker aset adalah milik inventaris SIDIK-TI</li>
                </ul>
            </div>
        </div>
    </div>
<?php else: ?>

    <!-- HERO HEADER -->
    <header class="bg-gradient-to-br <?php echo $c_style[3]; ?> text-white pt-12 pb-24 px-5 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle at 80% 20%, white 1px, transparent 1px); background-size: 28px 28px;"></div>
        <div class="max-w-lg mx-auto relative z-10 text-center flex flex-col items-center">
            <div class="inline-flex items-center gap-1.5 bg-white/20 backdrop-blur-sm px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest mb-6">
                <span class="material-symbols-outlined text-sm">verified_user</span>
                SIDIK-TI &bull; ASSET SCAN
            </div>
            <div class="w-20 h-20 bg-white/20 backdrop-blur-md border border-white/30 rounded-3xl flex items-center justify-center mb-4 shadow-xl">
                <span class="material-symbols-outlined text-4xl"><?php echo $c_style[1]; ?></span>
            </div>
            <h1 class="font-headline text-2xl font-black tracking-tight leading-tight">
                <?php echo htmlspecialchars($data['item_name'] ?? 'Unknown Asset'); ?>
            </h1>
            <p class="inline-block text-white/80 text-xs font-bold uppercase tracking-widest bg-white/10 px-3 py-1 rounded-full mt-3">
                <?php echo htmlspecialchars($data['category'] ?? '-'); ?>
            </p>
        </div>
    </header>

    <!-- CONTENT -->
    <main class="max-w-lg mx-auto px-4 -mt-12 pb-12 space-y-4">

        <!-- STATUS CARD -->
        <div class="card-anim bg-white p-5 rounded-[1.5rem] shadow-lg border border-slate-100 flex items-center justify-between">
            <div>
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Status Kondisi (Sensus)</p>
                <p class="font-headline font-black text-lg text-slate-800"><?php echo $c_style[2]; ?></p>
            </div>
            <div class="<?php echo $c_style[0]; ?> w-12 h-12 rounded-xl flex items-center justify-center border shadow-sm shrink-0">
                <span class="material-symbols-outlined text-2xl"><?php echo $c_style[1]; ?></span>
            </div>
        </div>

        <!-- PEMILIK CARD -->
        <div class="card-anim bg-white p-5 rounded-[1.5rem] shadow-sm border border-slate-100">
            <div class="flex items-center gap-2 border-b border-slate-50 pb-3 mb-4">
                <span class="material-symbols-outlined text-slate-400 text-lg">account_circle</span>
                <h3 class="font-headline font-black text-xs text-slate-700 uppercase tracking-widest">Informasi Kepemilikan</h3>
            </div>
            <div class="space-y-3">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Pemegang Aset</p>
                    <p class="font-bold text-slate-800"><?php echo htmlspecialchars($namaAset); ?></p>
                </div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Departemen</p>
                    <p class="font-bold text-slate-800"><?php echo htmlspecialchars($data['department'] ?? '-'); ?></p>
                </div>
                <?php if (!empty($data['kode_barang'])): ?>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Kode Barang</p>
                    <p class="font-mono font-bold text-slate-600 text-sm"><?php echo htmlspecialchars($data['kode_barang']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- DEPRESIASI CARD (hardware only) -->
        <?php if (stripos($data['category'] ?? '', 'software') === false && $hargaBeli > 0): ?>
        <div class="card-anim bg-white p-5 rounded-[1.5rem] shadow-sm border border-slate-100">
            <div class="flex items-center gap-2 border-b border-slate-50 pb-3 mb-4">
                <span class="material-symbols-outlined text-slate-400 text-lg">monitoring</span>
                <h3 class="font-headline font-black text-xs text-slate-700 uppercase tracking-widest">Depresiasi Nilai (PMK 72)</h3>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Nilai Perolehan</p>
                    <p class="font-bold text-sm text-slate-800">Rp <?php echo number_format($hargaBeli, 0, ',', '.'); ?></p>
                </div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Nilai Buku Saat Ini</p>
                    <?php if ($sisaBulan <= 0): ?>
                        <p class="font-black text-sm text-rose-500">Residu (Habis)</p>
                    <?php else: ?>
                        <p class="font-black text-sm text-indigo-600">Rp <?php echo number_format($nilaiBuku, 0, ',', '.'); ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-span-2 pt-3 border-t border-slate-50">
                    <div class="flex justify-between text-[9px] font-black uppercase tracking-wider mb-2">
                        <span class="text-slate-500">Terpakai: <?php echo round($pct_used); ?>%</span>
                        <span class="text-slate-400">Sisa: <?php echo $sisaBulan; ?> / 48 bln</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="h-full rounded-full <?php echo $pct_used >= 100 ? 'bg-red-400' : ($pct_used >= 75 ? 'bg-orange-400' : 'bg-emerald-400'); ?>" style="width:<?php echo round($pct_used); ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- FOOTER META -->
        <div class="card-anim bg-slate-100 border border-dashed border-slate-300 p-4 rounded-xl flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-slate-400 text-sm">badge</span>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Asset Document ID</p>
            </div>
            <p class="text-[9px] font-mono text-slate-500 break-all select-all"><?php echo htmlspecialchars($data['id']); ?></p>
        </div>

        <div class="text-center py-4">
            <p class="text-[10px] text-slate-400 font-medium">SIDIK-TI &bull; Dipindai <?php echo date('d M Y, H:i'); ?> WIB</p>
        </div>

    </main>
<?php endif; ?>
</body>
</html>
