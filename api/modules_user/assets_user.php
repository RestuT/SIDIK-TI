<?php
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';
require_once __DIR__ . '/../includes/pagination_helper.php';

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- PAGINATION ---
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 25;
$offset = ($page - 1) * $pageSize;

// 1. Statistik Aset (Total for this user)
$assetAssignmentsRef = $db->collection('asset_assignments')->where('user_id', '=', $user_id);
$stat_total       = $assetAssignmentsRef->count();
$stat_active      = $assetAssignmentsRef->where('status', '=', 'Active')->count();
$stat_maintenance = $assetAssignmentsRef->where('status', '=', 'Maintenance')->count();

// 2. Daftar Aset (Paginated)
$assets_docs = $assetAssignmentsRef->orderBy('assigned_at', 'DESC')->offset($offset)->limit($pageSize + 1)->documents();
$all_fetched = [];
foreach ($assets_docs as $doc) {
    if ($doc->exists()) {
        $data       = $doc->data();
        $data['id'] = $doc->id();
        $all_fetched[] = $data;
    }
}
$asset_list = array_slice($all_fetched, 0, $pageSize);
$hasMore = count($all_fetched) > $pageSize;

// 3. User Detail
$userRef   = $db->collection('users')->document($user_id);
$userSnap  = $userRef->snapshot();
$display_name = $userSnap->exists() ? ($userSnap->get('full_name') ?? 'User') : 'User';

// 4. FETCH SYSTEM SETTINGS
$margin_pengadaan = 5;
$nilai_sisa_pct   = 10;
$pajak            = 11;
try {
    $settings_docs = $db->collection('system_settings')->documents();
    foreach ($settings_docs as $doc) {
        if (!$doc->exists()) continue;
        $val = $doc->data()['setting_value'] ?? null;
        if ($val === null) continue;
        switch ($doc->id()) {
            case 'margin_pengadaan': $margin_pengadaan = (float)$val; break;
            case 'nilai_sisa':       $nilai_sisa_pct   = (float)$val; break;
            case 'pajak':            $pajak            = (float)$val; break;
        }
    }
} catch (Exception $e) { }

// 5. Inventory Base Prices
$inventory_prices = [];
try {
    $inv_docs = $db->collection('inventory')->documents();
    foreach ($inv_docs as $doc) {
        if (!$doc->exists()) continue;
        $item = $doc->data();
        $inventory_prices[$item['item_name']] = (float)($item['price_reference'] ?? 0);
    }
} catch (Exception $e) {}

// 6. INITIALIZE SERVICES
use App\Services\AssetService;
$assetService = new AssetService($db);
$system_settings = [
    'margin_pengadaan' => $margin_pengadaan,
    'nilai_sisa'       => $nilai_sisa_pct,
    'pajak'            => $pajak
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
        $pageTitle = 'SIDIK-TI | My Assets';
        $base_url = '../';
        include __DIR__ . '/../includes/head_meta.php'; 
    ?>
    <style>
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }
        .live-dot { animation: blink 1.5s ease-in-out infinite; }
        @keyframes fade-in { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:none} }
        .asset-row { animation: fade-in .3s ease both; }

        /* Dark mode: table rows */
        .dark #asset-tbody tr {
            border-color: rgba(255,255,255,0.04);
        }
        .dark #asset-tbody tr:hover {
            background-color: rgba(255,255,255,0.03);
        }
        .dark #asset-tbody td {
            color: #e2e8f0;
        }
    </style>
</head>
<body class="selection:bg-primary/30 pb-24 md:pb-0">
    <?php include __DIR__ . '/../includes/navbar_user.php'; ?>

    <main class="max-w-7xl mx-auto px-6 md:px-10 py-12 space-y-12">

        <?php
        // Cek Sensus Aktif
        $has_pending_sensus = false;
        try {
            $activeBatchDocs = $db->collection('sensus_batches')->where('status', '=', 'Active')->limit(1)->documents();
            if (!$activeBatchDocs->isEmpty()) {
                foreach ($activeBatchDocs as $b) {
                    $bid = $b->id();
                    $taskDocs = $db->collection('sensus_tasks')
                                   ->where('batch_id', '=', $bid)
                                   ->where('user_id', '=', $user_id)
                                   ->where('status', '=', 'Pending')
                                   ->limit(1)
                                   ->documents();
                    $has_pending_sensus = !$taskDocs->isEmpty();
                }
            }
        } catch (Exception $e) {}

        if ($has_pending_sensus):
        ?>
        <!-- Census Notification Alert -->
        <section class="animate-in fade-in slide-in-from-top-4 duration-700">
            <div class="relative overflow-hidden bg-gradient-to-r from-primary to-primary-container p-1 rounded-[2rem] shadow-2xl shadow-primary/20">
                <div class="bg-surface/10 backdrop-blur-md px-8 py-6 rounded-[1.8rem] flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="flex items-center gap-5">
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-xl rounded-2xl flex items-center justify-center text-white border border-white/20">
                            <span class="material-symbols-outlined text-3xl live-dot">campaign</span>
                        </div>
                        <div>
                            <h3 class="text-white font-headline font-black text-xl italic uppercase">Sensus <span class="text-indigo-200">Aset Aktif</span></h3>
                            <p class="text-white/70 text-sm font-medium">Ada tugas sensus mandiri yang menunggu laporan Anda. Mohon segera divalidasi.</p>
                        </div>
                    </div>
                    <a href="sensus_dashboard_user.php" class="px-8 py-4 bg-white text-primary font-black text-xs uppercase tracking-[0.2em] rounded-2xl hover:bg-opacity-90 transition-all shadow-xl active:scale-95">
                        Mulai Laporan Sekarang
                    </a>
                </div>
                <!-- Micro-decorations -->
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 -mr-16 -mt-16 rounded-full blur-2xl"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-indigo-400/10 -ml-12 -mb-12 rounded-full blur-2xl"></div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Page Header -->
        <section class="flex flex-col lg:flex-row lg:items-end justify-between gap-10 pb-6 border-b border-outline/5">
            <div class="space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-highlight-indigo text-primary rounded-full text-[10px] font-black uppercase tracking-widest border border-primary/10">
                    <span class="material-symbols-outlined text-[14px]">auto_graph</span>
                    Asset Intelligence
                </div>
                <h1 class="text-5xl md:text-6xl font-extrabold text-on-surface tracking-tighter leading-none italic uppercase">Aset <span class="text-primary italic">Saya</span></h1>
                <p class="text-on-surface-variant max-w-lg font-medium text-sm leading-relaxed">Kelola dan pantau perangkat IT di bawah tanggung jawab Anda dengan valuasi real-time.</p>
            </div>
            <div class="flex flex-wrap items-center gap-5">
                <div class="flex items-center gap-3 px-5 py-3 rounded-2xl glass-card border-none bg-surface-low">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 live-dot"></span>
                    <span class="text-[10px] font-black text-on-surface uppercase tracking-widest" id="sync-status">Live Valuation Enabled</span>
                </div>
                <a href="asset_market_analysis.php" class="inline-flex items-center gap-4 px-6 py-4 rounded-3xl obsidian-panel hover:bg-surface-low transition-all group border-primary/10">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-xl">analytics</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-black text-on-surface-variant/40 uppercase tracking-widest block leading-none mb-1 text-left">Market Insight</span>
                        <span class="text-sm font-bold text-on-surface">Analisis Harga</span>
                    </div>
                </a>
            </div>
        </section>

        <!-- Stats Grid -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
            <div class="p-8 rounded-[2rem] obsidian-panel transition-all group relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-primary/5 -mr-12 -mt-12 rounded-full blur-3xl"></div>
                <div class="w-14 h-14 bg-highlight-indigo text-primary rounded-2xl flex items-center justify-center mb-6">
                    <span class="material-symbols-outlined text-3xl">inventory_2</span>
                </div>
                <h3 class="text-4xl font-black text-on-surface tracking-tighter"><?php echo $stat_total; ?></h3>
                <p class="text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em] mt-2">Total Aset</p>
            </div>
            
            <div class="p-8 rounded-[2rem] obsidian-panel border-emerald-500/10 transition-all group relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-500/5 -mr-12 -mt-12 rounded-full blur-3xl"></div>
                <div class="w-14 h-14 bg-highlight-emerald text-emerald-500 rounded-2xl flex items-center justify-center mb-6">
                    <span class="material-symbols-outlined text-3xl">verified</span>
                </div>
                <h3 class="text-4xl font-black text-on-surface tracking-tighter"><?php echo $stat_active; ?></h3>
                <p class="text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em] mt-2">Kondisi Baik</p>
            </div>

            <div class="p-8 rounded-[2rem] obsidian-panel border-orange-500/10 transition-all group relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-orange-500/5 -mr-12 -mt-12 rounded-full blur-3xl"></div>
                <div class="w-14 h-14 bg-highlight-orange text-orange-500 rounded-2xl flex items-center justify-center mb-6">
                    <span class="material-symbols-outlined text-3xl">hardware</span>
                </div>
                <h3 class="text-4xl font-black text-on-surface tracking-tighter"><?php echo $stat_maintenance; ?></h3>
                <p class="text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em] mt-2">Maintenance</p>
            </div>
        </section>

        <!-- Asset Table Section -->
        <section class="obsidian-panel rounded-[2.5rem] overflow-hidden border-outline/5">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[900px]">
                    <thead>
                        <tr class="text-on-surface-variant text-[10px] uppercase tracking-[0.2em] border-b border-outline/5 bg-surface-low/30 dark:bg-white/5 dark:border-white/10">
                            <th class="px-6 py-5 font-black">Perangkat</th>
                            <th class="px-6 py-5 font-black">Details</th>
                            <th class="px-6 py-5 font-black">Status & Actions</th>
                            <th class="px-6 py-5 font-black text-right">Perolehan</th>
                            <th class="px-6 py-5 font-black text-right">Valuasi</th>
                            <th class="px-6 py-5 font-black text-center">Utilisasi</th>
                        </tr>
                    </thead>
                    <tbody id="asset-tbody" class="divide-y divide-outline/5">
                        <?php if(count($asset_list) > 0): ?>
                            <?php foreach($asset_list as $row):
                                $specific_price = isset($row['price_reference']) ? (float)$row['price_reference'] : null;
                                $multiplier     = isset($row['utilization_multiplier']) ? (float)$row['utilization_multiplier'] : 1.0;
                                
                                $dep_info = $assetService->calculateDepreciation(
                                    $row['item_name']   ?? '',
                                    $row['category']    ?? '',
                                    $row['assigned_at'] ?? '',
                                    $inventory_prices,
                                    $system_settings,
                                    $specific_price,
                                    $multiplier
                                );
                                $pct_used = $dep_info ? $dep_info['pct_used'] : 0;
                                $recommendation = $assetService->getRecommendation($pct_used, (int)($row['latest_condition_code'] ?? 1));
                            ?>
                            <tr class="group table-row-hover asset-row"
                                data-item="<?php echo htmlspecialchars($row['item_name'] ?? ''); ?>"
                                data-cat="<?php echo htmlspecialchars($row['category'] ?? ''); ?>"
                                data-date="<?php echo htmlspecialchars($row['assigned_at'] ?? ''); ?>"
                                data-base="<?php echo $specific_price ?? ($inventory_prices[$row['item_name'] ?? ''] ?? 0); ?>">

                                <td class="px-6 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-xl bg-highlight-indigo flex items-center justify-center text-primary group-hover:scale-105 transition-transform border border-primary/5 shadow-inner">
                                            <span class="material-symbols-outlined text-2xl">
                                                <?php
                                                    $cat = $row['category'] ?? '';
                                                    if($cat == 'Laptop')                          echo 'laptop_mac';
                                                    elseif($cat == 'Monitor')                     echo 'monitor';
                                                    elseif($cat == 'Printer')                     echo 'print';
                                                    elseif(in_array($cat, ['Router','Network','Networking'])) echo 'wifi_tethering';
                                                    elseif($cat == 'Server')                      echo 'dns';
                                                    else echo 'devices';
                                                ?>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="font-bold text-on-surface text-sm block truncate max-w-[180px]" title="<?php echo htmlspecialchars($row['item_name'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($row['item_name'] ?? ''); ?>
                                            </span>
                                            <span class="text-[9px] font-black uppercase tracking-widest text-on-surface-variant/40 mt-1 block">ID: <?php echo htmlspecialchars($row['id'] ?? '-'); ?></span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-6">
                                    <div class="flex flex-col">
                                        <span class="text-on-surface font-semibold text-xs"><?php echo htmlspecialchars($row['category'] ?? ''); ?></span>
                                        <span class="text-[10px] font-mono text-on-surface-variant opacity-60 mt-1 uppercase tracking-widest">SN: <?php echo htmlspecialchars($row['serial_number'] ?? '-'); ?></span>
                                    </div>
                                </td>

                                <td class="px-6 py-6">
                                    <div class="flex flex-col items-start gap-2.5">
                                        <?php 
                                        $rowStatus   = $row['status'] ?? '';
                                        $condCode    = max((int)($row['latest_condition_code'] ?? 1), (int)($dep_info['auto_condition'] ?? 1));

                                        if($rowStatus == 'Disposed' || $rowStatus == 'Pending Disposal') {
                                            $statusClass = "bg-surface-high/10 text-on-surface-variant/40 border-outline/5";
                                            $kondisiLabel = "Dihapus";
                                        } else {
                                            if($condCode == 1) {
                                                $statusClass = "bg-highlight-emerald text-emerald-500 border-emerald-500/10";
                                                $kondisiLabel = "Optimal";
                                            } elseif($condCode == 2) {
                                                $statusClass = "bg-highlight-orange text-orange-500 border-orange-500/10";
                                                $kondisiLabel = "Atensi";
                                            } else {
                                                $statusClass = "bg-highlight-rose text-rose-500 border-rose-500/10";
                                                $kondisiLabel = "Rusak";
                                            }
                                        }
                                        ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[9px] font-black border uppercase tracking-widest leading-none <?php echo $statusClass; ?>">
                                            <?php echo $kondisiLabel; ?>
                                        </span>

                                        <div class="flex items-center gap-2">
                                            <a href="cetak_label_aset.php?id=<?php echo urlencode($row['id'] ?? ''); ?>" target="_blank" class="w-8 h-8 flex items-center justify-center bg-highlight-indigo text-primary rounded-lg hover:bg-primary hover:text-white transition-all border border-primary/5" title="Label QR">
                                                <span class="material-symbols-outlined text-base">qr_code_2</span>
                                            </a>
                                            <?php if($rowStatus !== 'Disposed' && $rowStatus !== 'Pending Disposal' && $rowStatus !== 'Maintenance'): ?>
                                                <?php if($condCode == 3): ?>
                                                    <a href="form_maintenance.php?prefill_asset=<?php echo urlencode($row['id'] ?? ''); ?>&action=disposal" class="px-3 py-1.5 bg-highlight-rose text-rose-500 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-rose-500 hover:text-white transition-all">Disposal</a>
                                                <?php elseif($condCode == 2): ?>
                                                    <a href="form_maintenance.php?prefill_asset=<?php echo urlencode($row['id'] ?? ''); ?>&action=maintenance" class="px-3 py-1.5 bg-highlight-orange text-orange-500 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-orange-500 hover:text-white transition-all">Repair</a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-6 text-right">
                                    <?php if($dep_info): ?>
                                        <span class="font-bold text-on-surface text-sm">Rp<?php echo number_format($dep_info['purchase'], 0, ',', '.'); ?></span>
                                    <?php else: ?>
                                        <span class="text-on-surface-variant/20 italic text-xs">N/A</span>
                                    <?php endif; ?>
                                </td>

                                <td class="px-6 py-6 text-right">
                                    <?php if($dep_info): ?>
                                        <?php if($dep_info['salvage']): ?>
                                            <div class="flex flex-col items-end">
                                                <span class="font-black text-rose-500 text-sm">Rp<?php echo number_format($dep_info['current'], 0, ',', '.'); ?></span>
                                                <span class="text-[8px] font-black uppercase tracking-widest text-rose-400 opacity-60 mt-0.5">Nilai Residu</span>
                                            </div>
                                        <?php else: ?>
                                            <span class="font-black text-primary text-sm">Rp<?php echo number_format($dep_info['current'], 0, ',', '.'); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-on-surface-variant/20 italic text-xs">N/A</span>
                                    <?php endif; ?>
                                </td>

                                <td class="px-6 py-6 border-l border-outline/5 bg-surface-low/10">
                                    <div class="flex flex-col items-center gap-3 min-w-[140px]">
                                        <!-- Multiplier Tag -->
                                        <?php if($multiplier != 1.0): ?>
                                            <span class="text-[8px] font-black uppercase tracking-widest text-primary/60 bg-primary/5 px-2 py-0.5 rounded-md border border-primary/10">Stress Factor: <?php echo number_format($multiplier, 1); ?>x</span>
                                        <?php endif; ?>

                                        <!-- Progress Bar with Alert Animation -->
                                        <div class="w-full bg-surface-low rounded-full h-1.5 overflow-hidden border border-outline/5 relative">
                                            <div class="h-full rounded-full transition-all duration-1000 <?php echo $pct_used >= 90 ? 'bg-rose-500 shadow-[0_0_12px_rgba(244,63,94,0.6)] animate-pulse' : ($pct_used >= 75 ? 'bg-orange-500' : 'bg-emerald-500'); ?>"
                                                style="width:<?php echo round($pct_used); ?>%"></div>
                                        </div>

                                        <div class="flex flex-col items-center gap-1">
                                            <span class="text-[9px] font-black text-on-surface uppercase tracking-widest leading-none"><?php echo round($pct_used); ?>% Wear</span>
                                            
                                            <!-- Intelligence Badge -->
                                            <div class="flex flex-col items-center mt-1">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[7px] font-black uppercase tracking-widest <?php echo $recommendation['class']; ?> border border-current/10">
                                                    <?php echo $recommendation['label']; ?>
                                                </span>
                                                <span class="text-[6px] text-on-surface-variant/40 italic font-medium mt-0.5 text-center leading-tight max-w-[100px]"><?php echo $recommendation['desc']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-32 text-center">
                                    <div class="flex flex-col items-center gap-4 text-on-surface-variant/20">
                                        <span class="material-symbols-outlined text-7xl">inventory</span>
                                        <p class="font-black text-lg uppercase tracking-[0.2em]">No Assets Data</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-6 border-t border-outline/5 bg-surface-low/20">
                <?php renderPagination($page, $hasMore, 'assets_user.php'); ?>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../includes/bottom_nav_user.php'; ?>

    <script>
    let currentMargin = <?php echo $margin_pengadaan; ?>;
    let currentPajak  = <?php echo $pajak; ?>;
    let currentSisa   = <?php echo $nilai_sisa_pct; ?>;
    const fmt = n => 'Rp' + Math.round(n).toLocaleString('id-ID');

    function usefulLife(cat) {
        if (cat === 'Software') return 36;
        if (['Server','Networking','Router','Network'].includes(cat)) return 60;
        return 48;
    }

    function recomputeRow(row) {
        const basePrice  = parseFloat(row.dataset.base) || 0;
        const assignDate = row.dataset.date;
        const cat        = row.dataset.cat;
        if (!basePrice || !assignDate) return;

        const purchase   = basePrice * (1 + currentMargin / 100) * (1 + currentPajak / 100);
        const salvage    = purchase  * (currentSisa / 100);
        const lifeMonths = usefulLife(cat);
        const now        = Date.now();
        const assigned   = new Date(assignDate).getTime();
        const monthsUsed = Math.max(0, (now - assigned) / (30.4375 * 24 * 3600 * 1000));
        const pctUsed    = Math.min(100, (monthsUsed / lifeMonths) * 100);

        let current, isSalvage;
        if (monthsUsed <= 0) { current = purchase; isSalvage = false; }
        else {
            const depPerMonth = (purchase - salvage) / lifeMonths;
            current = purchase - (depPerMonth * monthsUsed);
            isSalvage = current <= salvage || monthsUsed >= lifeMonths;
            if (isSalvage) current = salvage;
        }

        const purchaseCell = row.querySelector('td:nth-child(4) span');
        if (purchaseCell) purchaseCell.textContent = fmt(purchase);
        const currentCell = row.querySelector('td:nth-child(5)');
        if (currentCell) {
            if (isSalvage) {
                currentCell.innerHTML = `<div class="flex flex-col items-end"><span class="font-black text-rose-500 text-sm">${fmt(current)}</span><span class="text-[8px] font-black uppercase tracking-widest text-rose-400 opacity-60 mt-0.5">Nilai Residu</span></div>`;
            } else {
                currentCell.innerHTML = `<span class="font-black text-primary text-sm">${fmt(current)}</span>`;
            }
        }
    }

    function recomputeAllRows() { document.querySelectorAll('#asset-tbody tr[data-item]').forEach(recomputeRow); }

    async function fetchAndSync() {
        try {
            const res  = await fetch('../config/get_settings.php?_=' + Date.now());
            const json = await res.json();
            if (json.status !== 'ok') return;
            let changed = false;
            if (typeof json.margin_pengadaan === 'number' && json.margin_pengadaan !== currentMargin) { currentMargin = json.margin_pengadaan; changed = true; }
            if (typeof json.pajak === 'number' && json.pajak !== currentPajak) { currentPajak  = json.pajak; changed = true; }
            if (typeof json.nilai_sisa === 'number' && json.nilai_sisa !== currentSisa) { currentSisa   = json.nilai_sisa; changed = true; }
            if (changed) {
                recomputeAllRows();
                const label = document.getElementById('sync-status');
                if (label) { label.textContent = 'Valuation Updated'; setTimeout(() => label.textContent = 'Live Valuation Enabled', 2500); }
            }
        } catch (e) { }
    }
    fetchAndSync();
    setInterval(fetchAndSync, 60000);
    </script>
</body>
</html>
