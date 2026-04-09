<?php
ob_start();
/**
 * SIDIK-TI - Web Portal Verifikasi Asset (Public)
 * Halaman ini diakses melalui pindaian QR Code yang tertempel di stiker aset.
 */

require_once __DIR__ . '/../config/database.php';

// Pastikan DB instance masuk
if (!isset($db)) {
    die("Koneksi database tidak tersedia.");
}

$asset_id = $_GET['id'] ?? '';
$data = [];
$error = null;

function getCondition($start_date, $type, $price, $man_code, $man_date) {
    if (!empty($man_code)) return (int)$man_code;
    if ($type === 'software') return 1; 
    if (empty($start_date) || empty($price)) return 1;

    $date_start = new DateTime($start_date);
    $date_now = new DateTime();
    $interval = $date_start->diff($date_now);
    $months_used = ($interval->y * 12) + $interval->m;
    
    if ($months_used < 24) return 1;
    if ($months_used >= 24 && $months_used < 48) return 2;
    return 3;
}

if (empty($asset_id)) {
    $error = "QR Code tidak mencantumkan ID Aset.";
} else {
    try {
        $docSnap = $db->collection('asset_assignments')->document($asset_id)->snapshot();
        if (!$docSnap->exists()) {
            $error = "Aset tidak ditemukan dalam sistem (Invalid ID).";
        } else {
            $data = $docSnap->data();
            $data['id'] = $docSnap->id();
        }
    } catch (Exception $e) {
        $error = "Error mengambil data dari database: " . $e->getMessage();
    }
}

// Analisis Kondisi & Status
$statusAset = $data['status'] ?? 'Unknown';
if (empty($error)) {
    $condCode = getCondition(
        $data['assigned_at'] ?? '', 
        $data['category'] ?? 'hardware', 
        $data['price'] ?? 0, 
        $data['latest_condition_code'] ?? null, 
        $data['latest_condition_date'] ?? null
    );

    // Bikin konfigurasi Tampilan
    $is_disposed = ($statusAset === 'Disposed' || $statusAset === 'Pending Disposal');
    $is_maintenance = ($statusAset === 'Maintenance');
    
    // Override manual jika disposed
    if ($is_disposed) {
        $condCode = 4;
    } elseif ($is_maintenance) {
        $condCode = 5;
    }

    $c_style = match($condCode) {
        1 => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'check_circle', 'Kondisi Baik', 'from-emerald-700 to-emerald-500'],
        2 => ['bg-orange-50 text-orange-700 border-orange-200', 'warning', 'Rusak Ringan / Menua', 'from-orange-600 to-amber-500'],
        3 => ['bg-red-50 text-red-700 border-red-200', 'error', 'Rusak Berat / Limit Usia', 'from-red-600 to-red-400'],
        4 => ['bg-slate-100 text-slate-500 border-slate-300', 'delete', 'Sudah Di-Disposal', 'from-slate-700 to-slate-500'],
        5 => ['bg-indigo-50 text-indigo-700 border-indigo-200', 'build', 'Sedang Maintenance', 'from-indigo-700 to-blue-500'],
        default => ['bg-slate-100 text-slate-500 border-slate-300', 'help', 'Unknown', 'from-slate-600 to-slate-400']
    };
    
    // Depresiasi Logik (Display purposes)
    $hargaBeli = $data['price'] ?? 0;
    $sisaBulan = 48; $nilaiBuku = $hargaBeli;
    if ($data['category'] !== 'software' && !empty($data['assigned_at'])) {
        $st = new DateTime($data['assigned_at']);
        $nw = new DateTime();
        $diff = $st->diff($nw);
        $m_passed = ($diff->y * 12) + $diff->m;
        
        $sisaBulan = max(0, 48 - $m_passed);
        $penyusutan_per_bulan = $hargaBeli / 48;
        $nilaiBuku = max(0, $hargaBeli - ($penyusutan_per_bulan * $m_passed));
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Aset - SIDIK-TI</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { headline: ["Plus Jakarta Sans"], body: ["Inter"] } } } }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; align-items:center;}
        @keyframes popUp { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        .card-anim { animation: popUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    </style>
</head>
<body class="bg-surface font-body text-slate-800 antialiased min-h-screen bg-slate-50">

<?php if($error): ?>
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="bg-white max-w-sm w-full p-8 rounded-[2rem] shadow-xl text-center border border-red-100">
            <div class="w-20 h-20 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6">
                <span class="material-symbols-outlined text-4xl">warning</span>
            </div>
            <h2 class="font-headline font-black text-xl mb-2 text-slate-800">Gagal Memindai</h2>
            <p class="text-xs text-slate-500 leading-relaxed"><?php echo htmlspecialchars($error); ?></p>
        </div>
    </div>
<?php else: ?>
    <!-- HERO HEADER -->
    <header class="bg-gradient-to-br <?php echo $c_style[3]; ?> text-white pt-12 pb-24 px-5 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle at 80% 20%, white 1px, transparent 1px); background-size: 32px 32px;"></div>
        <div class="max-w-lg mx-auto relative z-10 flex flex-col items-center text-center">
            
            <div class="inline-flex items-center gap-1.5 bg-white/20 backdrop-blur-sm px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest mb-6">
                <span class="material-symbols-outlined text-[14px]">verified_user</span>
                SIDIK-TI VERIFIED ASSET
            </div>

            <div class="w-20 h-20 bg-white/20 backdrop-blur-md border border-white/30 rounded-3xl flex items-center justify-center mb-4 shadow-lg">
                <span class="material-symbols-outlined text-4xl"><?php echo $c_style[1]; ?></span>
            </div>
            
            <h1 class="font-headline text-2xl font-black tracking-tight leading-tight">
                <?php echo htmlspecialchars($data['item_name'] ?? 'Unknown Asset'); ?>
            </h1>
            <p class="text-white/80 text-xs font-bold mt-1 uppercase tracking-widest bg-white/10 px-3 py-1 rounded-full mt-3">
                <?php echo htmlspecialchars($data['category'] ?? '-'); ?>
            </p>

        </div>
    </header>

    <!-- CONTENT BODY -->
    <main class="max-w-lg mx-auto px-4 -mt-12 pb-12 space-y-4">
        
        <!-- CARD: KONDISI & STATUS -->
        <div class="card-anim bg-white p-5 rounded-[1.5rem] shadow-lg shadow-slate-200/50 border border-slate-100 flex items-center justify-between">
            <div>
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Status Sensus Saat Ini</p>
                <p class="font-headline font-black text-lg text-slate-800 leading-tight">
                    <?php echo $c_style[2]; ?>
                </p>
            </div>
            <div class="<?php echo $c_style[0]; ?> w-12 h-12 rounded-xl flex items-center justify-center border shadow-sm">
                <span class="material-symbols-outlined text-2xl"><?php echo $c_style[1]; ?></span>
            </div>
        </div>

        <!-- CARD: PEMILIK / LOKASI -->
        <div class="card-anim bg-white p-5 rounded-[1.5rem] shadow-sm border border-slate-100" style="animation-delay: 0.1s;">
            <div class="flex items-center gap-2 border-b border-slate-50 pb-3 mb-4">
                <span class="material-symbols-outlined text-slate-400 text-lg">account_circle</span>
                <h3 class="font-headline font-black text-xs text-slate-700 uppercase tracking-widest">Informasi Kepemilikan</h3>
            </div>
            
            <div class="space-y-4">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Assigned To (Pemegang)</p>
                    <p class="font-bold text-sm text-slate-800">
                        <?php echo htmlspecialchars($data['assigned_to'] ?? 'Unassigned'); ?>
                    </p>
                </div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Department & Division</p>
                    <p class="font-bold text-sm text-slate-800">
                        <?php echo htmlspecialchars($data['department'] ?? 'Corporate'); ?> / 
                        <span class="text-slate-500 font-medium"><?php echo htmlspecialchars($data['division'] ?? '-'); ?></span>
                    </p>
                </div>
            </div>
        </div>

        <!-- CARD: DEPRESIASI & FINANCIAL (Only if hardware) -->
        <?php if($data['category'] !== 'software'): ?>
        <div class="card-anim bg-white p-5 rounded-[1.5rem] shadow-sm border border-slate-100" style="animation-delay: 0.2s;">
            <div class="flex items-center gap-2 border-b border-slate-50 pb-3 mb-4">
                <span class="material-symbols-outlined text-slate-400 text-lg">monitoring</span>
                <h3 class="font-headline font-black text-xs text-slate-700 uppercase tracking-widest">Depresiasi (PMK 72)</h3>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Nilai Perolehan</p>
                    <p class="font-bold text-sm text-slate-800">Rp <?php echo number_format($hargaBeli, 0, ',', '.'); ?></p>
                </div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Nilai Buku Berjalan</p>
                    <?php if($sisaBulan <= 0): ?>
                        <p class="font-black text-sm text-rose-500 tracking-tight">Limit Residu (Habis)</p>
                    <?php else: ?>
                        <p class="font-black text-sm text-indigo-600 tracking-tight">Rp <?php echo number_format($nilaiBuku, 0, ',', '.'); ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-span-2 pt-3 border-t border-slate-50">
                    <div class="flex justify-between text-[10px] font-black uppercase tracking-wider mb-2">
                        <span class="text-slate-500">Sisa Umur: <?php echo $sisaBulan; ?> bln</span>
                        <span class="text-slate-400">Total: 48 bln</span>
                    </div>
                    <?php $pct_used = min(100, max(0, ((48 - $sisaBulan) / 48) * 100)); ?>
                    <div class="w-full bg-slate-100 rounded-full h-1.5">
                        <div class="h-full rounded-full transition-all bg-emerald-400" style="width:<?php echo $pct_used; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- META IDENTIFIER -->
        <div class="card-anim bg-slate-100 p-4 rounded-xl flex items-center justify-between border border-slate-200 border-dashed" style="animation-delay: 0.3s;">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Asset Doc ID</p>
            <p class="text-[9px] font-mono font-medium text-slate-500 break-all select-all">
                <?php echo htmlspecialchars($data['id']); ?>
            </p>
        </div>

    </main>

<?php endif; ?>
</body>
</html>
