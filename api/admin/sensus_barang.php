<?php
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_admin.php");
    exit();
}

// Fetch all Asset Assignments
$assetsRef = $db->collection('asset_assignments');
$documents = $assetsRef->orderedBy('assigned_at', 'DESC')->documents();

$assets = [];
$stat_baik = 0;
$stat_ringan = 0;
$stat_berat = 0;

function getCondition($asset) {
    if (stripos($asset['category'] ?? '', 'Software') !== false) return 1;
    $manual = (int)($asset['latest_condition_code'] ?? 1);
    if (!isset($asset['assigned_at'])) return $manual;

    $useful_life_months = 48; // PMK 72/2023 Hardware
    $months_used = max(0, (time() - strtotime($asset['assigned_at'])) / (30.4375 * 24 * 3600));
    $pct = min(100, ($months_used / $useful_life_months) * 100);
    
    $auto = 1;
    if ($pct > 50 && $pct <= 75) $auto = 2;
    if ($pct > 75) $auto = 3;
    
    return max($manual, $auto);
}

foreach ($documents as $doc) {
    if ($doc->exists()) {
        $a = $doc->data();
        $a['id'] = $doc->id();
        $assets[] = $a;

        $cond = getCondition($a);
        if ($cond == 1) $stat_baik++;
        elseif ($cond == 2) $stat_ringan++;
        elseif ($cond == 3) $stat_berat++;
    }
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Sensus & Inspeksi Aset</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#3525cd",
                        "primary-container": "#4f46e5",
                        "background": "#f7f9fb",
                        "surface": "#ffffff",
                        "on-surface": "#191c1e",
                        "on-surface-variant": "#464555",
                        "outline": "#c7c4d8",
                        "error": "#ba1a1a",
                        "error-container": "#ffdad6"
                    },
                    fontFamily: {
                        "headline": ["Plus Jakarta Sans"],
                        "body": ["Inter"],
                    },
                    borderRadius: {"DEFAULT": "1rem", "lg": "2rem", "xl": "3rem", "full": "9999px"},
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; align-middle; }
    </style>
</head>
<body class="bg-background font-body text-on-surface antialiased min-h-screen">

    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>

    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen flex flex-col">
        <!-- Header Bar -->
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 md:px-8 py-4 md:py-6 border-b border-outline/10 sticky top-0 bg-surface/80 backdrop-blur-xl z-10">
            <div>
                <h1 class="font-headline text-2xl md:text-3xl font-extrabold text-on-surface tracking-tight">Sensus & Inspeksi Aset</h1>
                <p class="text-xs text-on-surface-variant font-medium mt-1">Lacak kondisi riil aset dan kalkulasi nilai depresiasi terkini.</p>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="window.print()" class="px-5 py-2.5 bg-surface text-on-surface border border-outline/20 font-headline font-bold rounded-2xl shadow-sm hover:shadow-md transition-all flex items-center gap-2 text-sm">
                    <span class="material-symbols-outlined text-lg">print</span> Print Report
                </button>
            </div>
        </header>

        <div class="p-4 md:p-8 space-y-8">
            <!-- Stats -->
            <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Condition Baik -->
                <div class="bg-surface p-6 rounded-3xl shadow-sm border border-emerald-100 hover:shadow-md transition-all flex flex-col justify-between relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-50 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
                    <div class="relative z-10 flex justify-between items-start">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-100/50 text-emerald-600 flex justify-center items-center">
                            <span class="material-symbols-outlined text-2xl">verified</span>
                        </div>
                        <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-widest rounded-full border border-emerald-100">Siap Pakai</span>
                    </div>
                    <div class="relative z-10 mt-6">
                        <h3 class="text-4xl font-black text-emerald-950 mb-1"><?php echo $stat_baik; ?></h3>
                        <p class="text-sm font-bold text-emerald-700/70 uppercase tracking-widest">Kondisi Baik (>84%)</p>
                    </div>
                </div>

                <!-- Condition Ringan -->
                <div class="bg-surface p-6 rounded-3xl shadow-sm border border-orange-100 hover:shadow-md transition-all flex flex-col justify-between relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-orange-50 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
                    <div class="relative z-10 flex justify-between items-start">
                        <div class="w-12 h-12 rounded-2xl bg-orange-100/50 text-orange-600 flex justify-center items-center">
                            <span class="material-symbols-outlined text-2xl">build_circle</span>
                        </div>
                        <span class="px-3 py-1 bg-orange-50 text-orange-600 text-[10px] font-black uppercase tracking-widest rounded-full border border-orange-100">Maintenance</span>
                    </div>
                    <div class="relative z-10 mt-6">
                        <h3 class="text-4xl font-black text-orange-950 mb-1"><?php echo $stat_ringan; ?></h3>
                        <p class="text-sm font-bold text-orange-700/70 uppercase tracking-widest">Rusak Ringan (65-84%)</p>
                    </div>
                </div>

                <!-- Condition Berat -->
                <div class="bg-surface p-6 rounded-3xl shadow-sm border border-red-100 hover:shadow-md transition-all flex flex-col justify-between relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-red-50 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
                    <div class="relative z-10 flex justify-between items-start">
                        <div class="w-12 h-12 rounded-2xl bg-red-100/50 text-red-600 flex justify-center items-center">
                            <span class="material-symbols-outlined text-2xl">delete_forever</span>
                        </div>
                        <span class="px-3 py-1 bg-red-50 text-red-600 text-[10px] font-black uppercase tracking-widest rounded-full border border-red-100">Disposal</span>
                    </div>
                    <div class="relative z-10 mt-6">
                        <h3 class="text-4xl font-black text-red-950 mb-1"><?php echo $stat_berat; ?></h3>
                        <p class="text-sm font-bold text-red-700/70 uppercase tracking-widest">Rusak Berat (<65%)</p>
                    </div>
                </div>
            </section>

            <!-- Table -->
            <section class="bg-surface rounded-3xl border border-outline/10 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-outline/10 text-[10px] text-on-surface-variant font-black uppercase tracking-widest">
                                <th class="px-6 py-5">Kode / Item</th>
                                <th class="px-6 py-5">Pengguna & Departemen</th>
                                <th class="px-6 py-5 text-center">Status Kondisi</th>
                                <th class="px-6 py-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline/5">
                            <?php foreach($assets as $row): 
                                $cond = getCondition($row);
                                $statusAsset = $row['status'] ?? 'Active'; // can be 'Disposed' atau 'Pending Disposal'
                                
                                if ($cond == 1) {
                                    $colorClass = "bg-emerald-50 text-emerald-700 border-emerald-200";
                                    $label = "Baik";
                                    $icon = "check_circle";
                                } elseif ($cond == 2) {
                                    $colorClass = "bg-orange-50 text-orange-700 border-orange-200";
                                    $label = "Rusak Ringan";
                                    $icon = "warning";
                                } else {
                                    $colorClass = "bg-red-50 text-red-700 border-red-200";
                                    $label = "Rusak Berat";
                                    $icon = "error";
                                }

                                if ($statusAsset === 'Disposed') {
                                    $colorClass = "bg-slate-100 text-slate-500 border-slate-300";
                                    $label = "Sudah Dihapus";
                                    $icon = "delete";
                                }
                            ?>
                            <tr class="group hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-xl bg-slate-100 flex justify-center items-center text-slate-500 group-hover:bg-primary/10 group-hover:text-primary transition-colors">
                                            <span class="material-symbols-outlined text-xl">
                                                <?php echo in_array($row['category']??'', ['Laptop','Monitor']) ? 'laptop_mac' : 'devices'; ?>
                                            </span>
                                        </div>
                                        <div>
                                            <p class="font-bold text-on-surface flex items-center gap-2">
                                                <?php echo htmlspecialchars($row['item_name']); ?>
                                            </p>
                                            <p class="text-xs font-mono text-on-surface-variant/60 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($row['kode_barang'] ?? $row['serial_number'] ?? '-'); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <p class="font-bold text-sm text-on-surface"><?php echo htmlspecialchars($row['user_name'] ?? 'Unknown'); ?></p>
                                    <p class="text-xs text-on-surface-variant uppercase tracking-widest mt-0.5"><?php echo htmlspecialchars($row['department'] ?? 'Unknown Dept'); ?></p>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black uppercase tracking-wider border <?php echo $colorClass; ?>">
                                        <span class="material-symbols-outlined text-sm"><?php echo $icon; ?></span>
                                        <?php echo $label; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-5 text-right space-x-2">
                                    <?php if($statusAsset !== 'Disposed'): ?>
                                        <button onclick="bukaModalSensus('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars(addslashes($row['item_name'])); ?>')" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-primary hover:text-white text-slate-600 transition-colors rounded-xl font-bold text-xs uppercase tracking-widest">
                                            <span class="material-symbols-outlined text-sm">fact_check</span> Inspeksi
                                        </button>
                                        
                                        <?php if($cond == 3): ?>
                                            <button onclick="bukaModalDisposal('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars(addslashes($row['department']??'')); ?>')" class="inline-flex items-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-600 hover:text-white text-red-600 border border-red-200 transition-colors rounded-xl font-bold text-xs uppercase tracking-widest">
                                                <span class="material-symbols-outlined text-sm">recycling</span> Ajukan Disposal
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">Aset Tidak Aktif</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- MODAL SENSUS -->
        <div id="modalSensus" class="fixed inset-0 bg-slate-900/60 hidden items-center justify-center z-[100] p-6 backdrop-blur-sm transition-all">
            <div class="bg-surface rounded-3xl max-w-md w-full p-8 shadow-2xl relative">
                <button onclick="tutupModalSensus()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500">
                    <span class="material-symbols-outlined text-sm">close</span>
                </button>
                <div class="mb-6 flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary/10 text-primary flex items-center justify-center rounded-xl">
                        <span class="material-symbols-outlined">health_and_safety</span>
                    </div>
                    <h3 class="font-headline font-black text-xl italic text-on-surface">Form <span class="text-primary italic">Inspeksi</span></h3>
                </div>

                <form action="../config/proses_sensus.php" method="POST" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="asset_id" id="sensus_asset_id">
                    <input type="hidden" name="action" value="inspect">

                    <p class="text-sm font-bold text-on-surface-variant bg-slate-50 p-4 rounded-2xl border border-slate-100 mb-2" id="sensus_asset_name"></p>

                    <div>
                        <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-widest mb-2">Persentase Kondisi Fisik / Fungsi (%)</label>
                        <div class="relative">
                            <input type="number" name="condition_pct" min="0" max="100" required placeholder="Contoh: 80" class="w-full pl-6 pr-12 py-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 outline-none font-black text-2xl text-on-surface">
                            <span class="absolute right-6 top-1/2 -translate-y-1/2 font-black text-xl text-slate-300">%</span>
                        </div>
                        <p class="text-[9px] font-bold text-on-surface-variant/50 uppercase tracking-widest mt-2 line-clamp-2 leading-relaxed">Panduan Sistem: <br> >84% = Baik, 65-84% = Rusak Ringan, <65% = Rusak Berat</p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-widest mb-2">Catatan Kerusakan / Observasi</label>
                        <textarea name="notes" rows="3" placeholder="Opsional..." class="w-full p-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 outline-none font-medium text-sm text-on-surface-variant"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-primary text-white font-black py-4 rounded-2xl shadow-lg shadow-primary/20 hover:bg-primary-container transition active:scale-95 uppercase tracking-widest">
                        Simpan Evaluasi Sensus
                    </button>
                </form>
            </div>
        </div>

        <!-- MODAL DISPOSAL (AJUKAN PENGHAPUSAN VERSI WORKFLOW) -->
        <div id="modalDisposal" class="fixed inset-0 bg-slate-900/60 hidden items-center justify-center z-[100] p-6 backdrop-blur-sm transition-all">
            <div class="bg-surface rounded-3xl max-w-md w-full p-8 shadow-2xl relative">
                <button onclick="tutupModalDisposal()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500">
                    <span class="material-symbols-outlined text-sm">close</span>
                </button>
                <div class="mb-6 flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-100 text-red-600 flex items-center justify-center rounded-xl">
                        <span class="material-symbols-outlined">warning</span>
                    </div>
                    <h3 class="font-headline font-black text-xl italic text-on-surface">Ajukan <span class="text-red-500 italic">Disposal</span></h3>
                </div>

                <div class="bg-orange-50 text-orange-800 p-4 rounded-2xl text-xs font-bold leading-relaxed mb-6 border border-orange-200">
                    Sistem akan membuat tiket Pengajuan Penghapusan & Penggantian ke Departemen secara otomatis yang mengunci aset ini untuk ditarik.
                </div>

                <form action="../config/proses_sensus.php" method="POST" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="asset_id" id="disposal_asset_id">
                    <input type="hidden" name="department" id="disposal_dept">
                    <input type="hidden" name="action" value="request_disposal">

                    <div>
                        <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-widest mb-2">Alasan Penghapusan</label>
                        <textarea name="disposal_reason" required rows="3" placeholder="Contoh: Mesin mati total, penggantian sparepart melebihi nilai ekonomis..." class="w-full p-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-red-500/20 outline-none font-medium text-sm text-on-surface-variant"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-red-600 text-white font-black py-4 rounded-2xl shadow-lg shadow-red-600/20 hover:bg-red-700 transition active:scale-95 uppercase tracking-widest">
                        Kirim Tiket Pengajuan Khusus
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        function flexModal(id) {
            const el = document.getElementById(id);
            if(el.classList.contains('hidden')) el.classList.replace('hidden', 'flex');
            else el.classList.replace('flex', 'hidden');
        }
        function bukaModalSensus(id, name) {
            document.getElementById('sensus_asset_id').value = id;
            document.getElementById('sensus_asset_name').innerText = "Target: " + name;
            flexModal('modalSensus');
        }
        function tutupModalSensus() { flexModal('modalSensus'); }
        
        function bukaModalDisposal(id, dept) {
            document.getElementById('disposal_asset_id').value = id;
            document.getElementById('disposal_dept').value = dept;
            flexModal('modalDisposal');
        }
        function tutupModalDisposal() { flexModal('modalDisposal'); }
    </script>
</body>
</html>
