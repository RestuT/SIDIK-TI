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

// =====================================================
// HELPER: Hitung Depresiasi Garis Lurus (PMK 72/2023)
// =====================================================
function calculateDepreciation($item_name, $category, $assigned_date, $inv_prices, $margin_pct, $pajak_pct, $salvage_pct, $custom_price = null) {
    if (stripos($category, 'Software') !== false || stripos($category, 'Aplikasi') !== false) {
        return [
            'type' => 'software',
            'current' => 0, 
            'purchase' => ($custom_price ?: 0), 
            'salvage' => false, 
            'pct_used' => 0,
            'auto_condition' => 1
        ];
    }

    $base_price = ($custom_price && $custom_price > 0) ? $custom_price : ($inv_prices[$item_name] ?? 0);
    if ($base_price == 0 || !$assigned_date) return null;

    $purchase_price = $base_price;
    $salvage_value  = 0; 
    $useful_life_months = 48; 

    $assigned_time  = strtotime($assigned_date);
    $now            = time();
    $months_used    = max(0, ($now - $assigned_time) / (30.4375 * 24 * 3600));

    $pct_used = min(100, ($months_used / $useful_life_months) * 100);
    $auto_condition = 1;
    if ($pct_used > 50 && $pct_used <= 75) $auto_condition = 2;
    if ($pct_used > 75) $auto_condition = 3;

    if ($months_used <= 0) {
        return ['type' => 'hardware', 'current' => $purchase_price, 'purchase' => $purchase_price, 'salvage' => false, 'pct_used' => 0, 'auto_condition' => 1];
    }

    $depreciation_per_month = ($purchase_price - $salvage_value) / $useful_life_months;
    $current_value          = $purchase_price - ($depreciation_per_month * $months_used);

    if ($current_value <= $salvage_value || $months_used >= $useful_life_months) {
        return ['type' => 'hardware', 'current' => $salvage_value, 'purchase' => $purchase_price, 'salvage' => true, 'pct_used' => 100, 'auto_condition' => 3];
    }

    return ['type' => 'hardware', 'current' => $current_value, 'purchase' => $purchase_price, 'salvage' => false, 'pct_used' => $pct_used, 'auto_condition' => $auto_condition];
}
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
    </style>
</head>
<body class="pb-24 md:pb-0">
    <?php include __DIR__ . '/../includes/navbar_user.php'; ?>

    <main class="max-w-7xl mx-auto px-6 md:px-10 py-10 space-y-12">

        <!-- Page Header -->
        <section class="flex flex-col lg:flex-row lg:items-end justify-between gap-8 pb-4 border-b border-outline/10">
            <div class="space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-primary/10 text-primary rounded-full text-[10px] font-black uppercase tracking-widest border border-primary/5">
                    <span class="material-symbols-outlined text-[14px]">auto_graph</span>
                    Inventory Analytics
                </div>
                <h1 class="text-6xl font-extrabold text-on-surface tracking-tighter leading-none italic uppercase">Aset <span class="text-primary italic">Saya</span></h1>
                <p class="text-on-surface-variant max-w-lg font-medium text-sm leading-relaxed opacity-80">Pelacakan perangkat IT personal di bawah tanggung jawab Anda (Page <?php echo $page; ?>).</p>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-3 px-5 py-3 rounded-2xl glass-card">
                    <span class="w-3 h-3 rounded-full bg-emerald-500 live-dot"></span>
                    <span class="text-[11px] font-black text-on-surface uppercase tracking-widest" id="sync-status">Live Valuation Active</span>
                </div>
                <a href="asset_market_analysis.php" class="inline-flex items-center gap-4 px-6 py-4 rounded-2xl obsidian-panel hover:bg-surface-low transition-all group">
                    <div class="w-12 h-12 rounded-xl bg-highlight-indigo text-primary flex items-center justify-center group-hover:rotate-12 transition-transform">
                        <span class="material-symbols-outlined text-2xl">analytics</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-black text-on-surface-variant/50 uppercase tracking-widest block leading-none mb-1">Market Insight</span>
                        <span class="text-sm font-bold text-on-surface group-hover:text-primary transition-colors">Analisis Depresiasi</span>
                    </div>
                    <span class="material-symbols-outlined text-outline ml-2 group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </a>
            </div>
        </section>

        <!-- Stats Grid -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="p-8 rounded-3xl obsidian-panel hover:scale-[1.02] transition-all group">
                <div class="flex justify-between items-center mb-8">
                    <div class="w-16 h-16 bg-highlight-indigo text-primary rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-4xl">inventory_2</span>
                    </div>
                </div>
                <h3 class="text-5xl font-black text-on-surface tracking-tighter"><?php echo $stat_total; ?></h3>
                <p class="text-on-surface-variant font-bold text-xs uppercase tracking-widest mt-2 opacity-60">Total Item Terdaftar</p>
            </div>
            
            <div class="p-8 rounded-3xl obsidian-panel border-emerald-500/10 hover:border-emerald-500/30 hover:scale-[1.02] transition-all group">
                <div class="flex justify-between items-center mb-8">
                    <div class="w-16 h-16 bg-highlight-emerald text-emerald-500 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-4xl">verified</span>
                    </div>
                    <span class="px-3 py-1 bg-highlight-emerald text-emerald-500 text-[10px] font-black uppercase tracking-widest rounded-full">Optimal</span>
                </div>
                <h3 class="text-5xl font-black text-on-surface tracking-tighter"><?php echo $stat_active; ?></h3>
                <p class="text-on-surface-variant font-bold text-xs uppercase tracking-widest mt-2 opacity-60">Dalam Kondisi Baik</p>
            </div>

            <div class="p-8 rounded-3xl obsidian-panel border-orange-500/10 hover:border-orange-500/30 hover:scale-[1.02] transition-all group">
                <div class="flex justify-between items-center mb-8">
                    <div class="w-16 h-16 bg-highlight-orange text-orange-500 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-4xl">hardware</span>
                    </div>
                </div>
                <h3 class="text-5xl font-black text-on-surface tracking-tighter"><?php echo $stat_maintenance; ?></h3>
                <p class="text-on-surface-variant font-bold text-xs uppercase tracking-widest mt-2 opacity-60">Maintenance Issues</p>
            </div>
        </section>

        <!-- Asset Table Card -->
        <section class="obsidian-panel rounded-[2rem] overflow-hidden p-2">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1000px]">
                    <thead>
                        <tr class="text-on-surface-variant/40 text-[11px] uppercase tracking-[0.25em] border-b border-outline/5">
                            <th class="px-8 py-6 font-black">Perangkat IT</th>
                            <th class="px-8 py-6 font-black">Informasi Teknis</th>
                            <th class="px-8 py-6 font-black">Status & Sensus</th>
                            <th class="px-8 py-6 font-black text-right">Harga Perolehan</th>
                            <th class="px-8 py-6 font-black text-right">Valuasi Terkini</th>
                            <th class="px-8 py-6 font-black text-center">Life Cycle</th>
                        </tr>
                    </thead>
                    <tbody id="asset-tbody" class="divide-y divide-outline/5">
                        <?php if(count($asset_list) > 0): ?>
                            <?php foreach($asset_list as $row):
                                $specific_price = isset($row['price_reference']) ? (float)$row['price_reference'] : null;
                                $dep_info = calculateDepreciation(
                                    $row['item_name']   ?? '',
                                    $row['category']    ?? '',
                                    $row['assigned_at'] ?? '',
                                    $inventory_prices,
                                    $margin_pengadaan,
                                    $pajak,
                                    $nilai_sisa_pct,
                                    $specific_price
                                );
                                $pct_used = $dep_info ? $dep_info['pct_used'] : 0;
                            ?>
                            <tr class="group hover:bg-surface-low transition-colors asset-row"
                                data-item="<?php echo htmlspecialchars($row['item_name'] ?? ''); ?>"
                                data-cat="<?php echo htmlspecialchars($row['category'] ?? ''); ?>"
                                data-date="<?php echo htmlspecialchars($row['assigned_at'] ?? ''); ?>"
                                data-base="<?php echo $specific_price ?? ($inventory_prices[$row['item_name'] ?? ''] ?? 0); ?>">

                                <td class="px-8 py-8">
                                    <div class="flex items-center gap-5">
                                        <div class="w-14 h-14 rounded-2xl bg-highlight-indigo flex items-center justify-center text-primary group-hover:scale-105 transition-transform border border-primary/5 shadow-inner">
                                            <span class="material-symbols-outlined text-3xl">
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
                                            <span class="font-bold text-on-surface text-base block truncate max-w-[220px]" title="<?php echo htmlspecialchars($row['item_name'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($row['item_name'] ?? ''); ?>
                                            </span>
                                            <span class="text-[10px] font-black uppercase tracking-[0.15em] text-on-surface-variant/50 mt-1.5 block leading-none">ID: <?php echo htmlspecialchars($row['id'] ?? '-'); ?></span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-8 py-8">
                                    <div class="flex flex-col">
                                        <span class="text-on-surface font-semibold text-sm"><?php echo htmlspecialchars($row['category'] ?? ''); ?></span>
                                        <span class="text-on-surface-variant/60 font-mono text-[11px] mt-2 uppercase tracking-widest flex items-center gap-1.5">
                                            <span class="w-1 h-1 rounded-full bg-outline-variant"></span>
                                            <?php echo htmlspecialchars($row['serial_number'] ?? '-'); ?>
                                        </span>
                                    </div>
                                </td>

                                <td class="px-8 py-8">
                                    <div class="flex flex-col items-start gap-3">
                                        <?php 
                                        $rowStatus   = $row['status'] ?? '';
                                        $condCode    = max((int)($row['latest_condition_code'] ?? 1), (int)($dep_info['auto_condition'] ?? 1));

                                        if($rowStatus == 'Disposed' || $rowStatus == 'Pending Disposal') {
                                            $statusClass = "bg-surface-low text-on-surface-variant/40 border-outline/10";
                                            $kondisiLabel = "Dihapus";
                                        } else {
                                            if($condCode == 1) {
                                                $statusClass = "bg-highlight-emerald text-emerald-500 border-emerald-500/10";
                                                $kondisiLabel = "Lulus Sensus";
                                            } elseif($condCode == 2) {
                                                $statusClass = "bg-highlight-orange text-orange-500 border-orange-500/10";
                                                $kondisiLabel = "Butuh Atensi";
                                            } else {
                                                $statusClass = "bg-highlight-rose text-rose-500 border-rose-500/10";
                                                $kondisiLabel = "Rusak Berat";
                                            }
                                        }
                                        ?>
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-black border uppercase tracking-widest leading-none <?php echo $statusClass; ?>">
                                                <?php echo $kondisiLabel; ?>
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <a href="cetak_label_aset.php?id=<?php echo urlencode($row['id'] ?? ''); ?>" target="_blank" class="w-9 h-9 flex items-center justify-center bg-highlight-indigo text-primary rounded-xl hover:bg-primary hover:text-white transition-all border border-primary/5" title="Print QR Label">
                                                <span class="material-symbols-outlined text-lg">qr_code_2</span>
                                            </a>

                                            <?php if($rowStatus !== 'Disposed' && $rowStatus !== 'Pending Disposal' && $rowStatus !== 'Maintenance'): ?>
                                                <?php if($condCode == 3): ?>
                                                    <a href="form_maintenance.php?prefill_asset=<?php echo urlencode($row['id'] ?? ''); ?>&action=disposal" class="px-4 py-2 bg-highlight-rose text-rose-500 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-rose-500 hover:text-white transition-all">
                                                        Disposal
                                                    </a>
                                                <?php elseif($condCode == 2): ?>
                                                    <a href="form_maintenance.php?prefill_asset=<?php echo urlencode($row['id'] ?? ''); ?>&action=maintenance" class="px-4 py-2 bg-highlight-orange text-orange-500 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-orange-500 hover:text-white transition-all">
                                                        Repair
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-8 py-8 text-right">
                                    <?php if($dep_info): ?>
                                        <span class="font-bold text-on-surface text-sm">Rp <?php echo number_format($dep_info['purchase'], 0, ',', '.'); ?></span>
                                    <?php else: ?>
                                        <span class="text-on-surface-variant/30 italic text-sm">N/A</span>
                                    <?php endif; ?>
                                </td>

                                <td class="px-8 py-8 text-right">
                                    <?php if($dep_info): ?>
                                        <?php if($dep_info['salvage']): ?>
                                            <div class="flex flex-col items-end">
                                                <span class="font-black text-rose-500 text-sm">Rp <?php echo number_format($dep_info['current'], 0, ',', '.'); ?></span>
                                                <span class="text-[9px] font-black uppercase tracking-widest text-rose-400 opacity-60 mt-1">Nilai Residu</span>
                                            </div>
                                        <?php else: ?>
                                            <span class="font-black text-primary text-sm">Rp <?php echo number_format($dep_info['current'], 0, ',', '.'); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-on-surface-variant/30 italic text-sm">N/A</span>
                                    <?php endif; ?>
                                </td>

                                <td class="px-8 py-8">
                                    <div class="flex flex-col items-center gap-2.5 min-w-[120px]">
                                        <div class="w-full bg-surface-low rounded-full h-2.5 overflow-hidden p-0.5 border border-outline/5 relative group">
                                            <div class="h-full rounded-full transition-all duration-1000 <?php echo $pct_used >= 100 ? 'bg-rose-500' : ($pct_used >= 75 ? 'bg-orange-500' : 'bg-emerald-500'); ?>"
                                                style="width:<?php echo round($pct_used); ?>%"></div>
                                        </div>
                                        <span class="text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]"><?php echo round($pct_used); ?>% Expired</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-8 py-32 text-center">
                                    <div class="flex flex-col items-center gap-6 text-on-surface-variant/20">
                                        <span class="material-symbols-outlined text-8xl">inventory</span>
                                        <p class="font-black text-xl uppercase tracking-[0.3em]">No Assets Found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="p-6 border-t border-outline/5">
                <?php renderPagination($page, $hasMore, 'assets_user.php'); ?>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../includes/bottom_nav_user.php'; ?>

    <script>
    let currentMargin = <?php echo $margin_pengadaan; ?>;
    let currentPajak  = <?php echo $pajak; ?>;
    let currentSisa   = <?php echo $nilai_sisa_pct; ?>;
    const fmt = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');

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

        const purchaseCell = row.querySelector('.dep-purchase-cell');
        if (purchaseCell) purchaseCell.innerHTML = `<span class="font-bold text-on-surface text-sm">${fmt(purchase)}</span>`;
        const currentCell = row.querySelector('.dep-current-cell');
        if (currentCell) {
            if (isSalvage) {
                currentCell.innerHTML = `<div class="flex flex-col items-end"><span class="font-black text-rose-500 text-sm">${fmt(current)}</span><span class="text-[9px] font-black uppercase tracking-widest text-rose-400 opacity-60 mt-1">Nilai Residu</span></div>`;
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
                if (label) { label.textContent = 'Updated!'; setTimeout(() => label.textContent = 'Live Valuation Active', 2500); }
            }
        } catch (e) { }
    }
    fetchAndSync();
    setInterval(fetchAndSync, 60000);
    </script>
</body>
</html>
