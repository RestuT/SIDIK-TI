<?php
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Statistik Aset
$assetAssignmentsRef = $db->collection('asset_assignments')->where('user_id', '=', $user_id);
$stat_total       = $assetAssignmentsRef->count();
$stat_active      = $assetAssignmentsRef->where('status', '=', 'Active')->count();
$stat_maintenance = $assetAssignmentsRef->where('status', '=', 'Maintenance')->count();

// 2. Daftar Aset
$assets_docs = $assetAssignmentsRef->orderBy('assigned_at', 'DESC')->documents();

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
} catch (Exception $e) { /* fallback ke default */ }

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
        // Alur Software: Biaya Operasional Rutin (Habis)
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

    // Untuk HEA / Capitalized price, harga sudah fix = base_price
    $purchase_price = $base_price;
    $salvage_value  = 0; // PMK 72/2023 Nilai Sisa dianggap Rp 0

    // Kelompok 1 (Masa Manfaat 4 Tahun / 48 Bulan)
    $useful_life_months = 48; 

    // Usia dalam bulan
    $assigned_time  = strtotime($assigned_date);
    $now            = time();
    $months_used    = max(0, ($now - $assigned_time) / (30.4375 * 24 * 3600));

    // Hitung Auto-Depreciation Condition
    $pct_used = min(100, ($months_used / $useful_life_months) * 100);
    $auto_condition = 1; // 0-50% Baik
    if ($pct_used > 50 && $pct_used <= 75) $auto_condition = 2; // >50% Warning (Rusak Ringan)
    if ($pct_used > 75) $auto_condition = 3; // >75% Critical (Rusak Berat)

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

$asset_list = [];
foreach ($assets_docs as $doc) {
    $a       = $doc->data();
    $a['id'] = $doc->id();
    $asset_list[] = $a;
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | My Assets</title>
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
                        "primary":                   "#3525cd",
                        "primary-container":         "#4f46e5",
                        "background":                "#f7f9fb",
                        "on-surface":                "#191c1e",
                        "on-surface-variant":        "#464555",
                        "surface-container-lowest":  "#ffffff",
                        "surface-container-low":     "#f2f4f6",
                        "surface-container-high":    "#e6e8ea",
                        "outline-variant":           "#c7c4d8",
                    },
                    fontFamily: {
                        "headline": ["Plus Jakarta Sans"],
                        "body":     ["Inter"],
                    },
                    borderRadius: {"DEFAULT": "1rem", "lg": "2rem", "xl": "3rem", "full": "9999px"},
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3, .font-headline { font-family: 'Plus Jakarta Sans', sans-serif; }

        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }
        .live-dot { animation: blink 1.5s ease-in-out infinite; }

        @keyframes fade-in { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:none} }
        .asset-row { animation: fade-in .3s ease both; }
    </style>
</head>
<body class="bg-background text-on-surface min-h-screen selection:bg-primary/20 pb-24 md:pb-0">
    <?php include __DIR__ . '/../includes/navbar_user.php'; ?>

    <main class="max-w-7xl mx-auto px-6 md:px-10 py-10 space-y-10">

        <!-- Page Header -->
        <section class="flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div class="space-y-1">
                <p class="text-on-surface-variant font-medium tracking-wide text-xs uppercase italic">Inventory Intelligence</p>
                <h1 class="text-5xl font-extrabold text-on-surface tracking-tight leading-none italic">Aset <span class="text-primary italic">Saya</span></h1>
                <p class="text-on-surface-variant max-w-lg font-medium text-sm mt-3 leading-relaxed">Daftar perangkat IT yang saat ini berada di bawah tanggung jawab Anda secara personal.</p>
            </div>
            <div class="flex items-center gap-4">
                <!-- Realtime indicator -->
                <div class="flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-50 border border-emerald-100">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 live-dot"></span>
                    <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest" id="sync-status">Live Valuation</span>
                </div>
                <a href="asset_market_analysis.php" class="inline-flex items-center gap-3 px-6 py-4 bg-white border border-indigo-100 rounded-2xl shadow-sm hover:shadow-xl hover:shadow-indigo-900/5 transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-xl">analytics</span>
                    </div>
                    <div class="flex flex-col items-start pr-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Market Insight</span>
                        <span class="text-sm font-bold text-on-surface">Analisis Harga &amp; Depresiasi</span>
                    </div>
                    <span class="material-symbols-outlined text-slate-300 group-hover:text-primary transition-colors">arrow_forward</span>
                </a>
            </div>
        </section>

        <!-- Stats -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-surface-container-lowest p-8 rounded-3xl shadow-sm border border-transparent hover:border-primary/10 transition-all duration-500">
                <div class="flex justify-between items-start mb-6">
                    <div class="p-4 bg-indigo-50 text-indigo-600 rounded-2xl">
                        <span class="material-symbols-outlined text-3xl">inventory_2</span>
                    </div>
                </div>
                <h3 class="text-4xl font-black text-on-surface"><?php echo $stat_total; ?></h3>
                <p class="text-on-surface-variant font-medium">Total Perangkat</p>
            </div>
            <div class="bg-surface-container-lowest p-8 rounded-3xl shadow-sm border border-transparent hover:border-primary/10 transition-all duration-500">
                <div class="flex justify-between items-start mb-6">
                    <div class="p-4 bg-emerald-50 text-emerald-600 rounded-2xl">
                        <span class="material-symbols-outlined text-3xl">check_circle</span>
                    </div>
                </div>
                <h3 class="text-4xl font-black text-on-surface"><?php echo $stat_active; ?></h3>
                <p class="text-on-surface-variant font-medium">Kondisi Baik</p>
            </div>
            <div class="bg-surface-container-lowest p-8 rounded-3xl shadow-sm border border-transparent hover:border-primary/10 transition-all duration-500">
                <div class="flex justify-between items-start mb-6">
                    <div class="p-4 bg-orange-50 text-orange-600 rounded-2xl">
                        <span class="material-symbols-outlined text-3xl">build</span>
                    </div>
                </div>
                <h3 class="text-4xl font-black text-on-surface"><?php echo $stat_maintenance; ?></h3>
                <p class="text-on-surface-variant font-medium">Perlu Perbaikan</p>
            </div>
        </section>

        <!-- Asset Table -->
        <section class="bg-surface-container-lowest rounded-3xl p-8 shadow-sm border border-outline-variant/10">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-on-surface-variant text-xs uppercase tracking-[0.15em] border-b border-slate-100">
                            <th class="px-6 py-5 font-bold">Perangkat</th>
                            <th class="px-6 py-5 font-bold">Deskripsi</th>
                            <th class="px-6 py-5 font-bold">Penugasan</th>
                            <th class="px-6 py-5 font-bold text-right">Harga Beli</th>
                            <th class="px-6 py-5 font-bold text-right">Nilai Saat Ini</th>
                            <th class="px-6 py-5 font-bold text-center">Kondisi</th>
                        </tr>
                    </thead>
                    <tbody id="asset-tbody" class="divide-y divide-slate-100">
                        <?php if($stat_total > 0): ?>
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
                            <tr class="group hover:bg-slate-50/50 transition-colors asset-row"
                                data-item="<?php echo htmlspecialchars($row['item_name'] ?? ''); ?>"
                                data-cat="<?php echo htmlspecialchars($row['category'] ?? ''); ?>"
                                data-date="<?php echo htmlspecialchars($row['assigned_at'] ?? ''); ?>"
                                data-base="<?php echo $specific_price ?? ($inventory_prices[$row['item_name'] ?? ''] ?? 0); ?>">

                                <td class="px-6 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform">
                                            <span class="material-symbols-outlined">
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
                                        <span class="font-bold text-on-surface w-40 truncate" title="<?php echo htmlspecialchars($row['item_name'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($row['item_name'] ?? ''); ?>
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-6">
                                    <div class="flex flex-col">
                                        <span class="text-on-surface-variant font-medium text-sm"><?php echo htmlspecialchars($row['category'] ?? ''); ?></span>
                                        <span class="text-on-surface-variant opacity-60 font-mono text-[10px] mt-1 uppercase tracking-wider">SN: <?php echo htmlspecialchars($row['serial_number'] ?? '-'); ?></span>
                                    </div>
                                </td>

                                <td class="px-6 py-6 border-r border-slate-50">
                                    <div class="flex flex-col items-start gap-2">
                                        <?php 
                                        $rowStatus   = $row['status'] ?? '';
                                        $condCode    = max((int)($row['latest_condition_code'] ?? 1), (int)($dep_info['auto_condition'] ?? 1));

                                        if($rowStatus == 'Disposed' || $rowStatus == 'Pending Disposal') {
                                            $statusClass = "bg-slate-100 text-slate-500 border-slate-200";
                                            $kondisiLabel = "Aset Dihapus";
                                        } else {
                                            if($condCode == 1) {
                                                $statusClass = "bg-emerald-50 text-emerald-700 border-emerald-100";
                                                $kondisiLabel = "Kondisi Baik";
                                            } elseif($condCode == 2) {
                                                $statusClass = "bg-orange-50 text-orange-700 border-orange-100";
                                                $kondisiLabel = "Maintenance";
                                            } else {
                                                $statusClass = "bg-red-50 text-red-700 border-red-100";
                                                $kondisiLabel = "Rusak Berat";
                                            }
                                        }
                                        ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider <?php echo $statusClass; ?>">
                                            <?php echo $kondisiLabel; ?>
                                        </span>
                                        <span class="text-on-surface-variant text-xs opacity-70">
                                            <span class="material-symbols-outlined text-[10px] align-middle">calendar_month</span>
                                            <?php echo isset($row['assigned_at']) ? date('d M Y', strtotime($row['assigned_at'])) : '-'; ?>
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-6 text-right dep-purchase-cell">
                                    <?php if($dep_info): ?>
                                        <span class="font-bold text-on-surface text-sm">Rp <?php echo number_format($dep_info['purchase'], 0, ',', '.'); ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-300 italic text-sm">N/A</span>
                                    <?php endif; ?>
                                </td>

                                <td class="px-6 py-6 text-right dep-current-cell">
                                    <?php if($dep_info): ?>
                                        <?php if($dep_info['salvage']): ?>
                                            <div class="flex flex-col items-end">
                                                <span class="font-black text-rose-600 text-sm">Rp <?php echo number_format($dep_info['current'], 0, ',', '.'); ?></span>
                                                <span class="text-[9px] font-bold uppercase tracking-widest text-rose-400 bg-rose-50 px-2 rounded-full mt-1">Nilai Residu</span>
                                            </div>
                                        <?php else: ?>
                                            <span class="font-bold text-primary text-sm">Rp <?php echo number_format($dep_info['current'], 0, ',', '.'); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-slate-300 italic text-sm">N/A</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Progress Bar Kondisi / Depresiasi -->
                                <td class="px-6 py-6">
                                <?php if(isset($dep_info['type']) && $dep_info['type'] === 'software'): ?>
                                    <div class="flex flex-col items-center gap-1.5 min-w-[80px]">
                                        <span class="px-2 py-1 bg-indigo-50 text-indigo-600 border border-indigo-100 rounded-lg text-[9px] font-black uppercase tracking-widest text-center leading-tight">
                                            Biaya<br>Rutin
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div class="flex flex-col items-center gap-1.5 min-w-[80px]">
                                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-700 <?php echo $pct_used >= 100 ? 'bg-rose-400' : ($pct_used >= 75 ? 'bg-orange-400' : 'bg-emerald-400'); ?>"
                                                style="width:<?php echo round($pct_used); ?>%"></div>
                                        </div>
                                        <span class="text-[9px] font-black text-on-surface-variant uppercase tracking-wider"><?php echo round($pct_used); ?>% used</span>
                                    </div>
                                <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center gap-3 text-on-surface-variant opacity-40">
                                        <span class="material-symbols-outlined text-6xl">inventory_2</span>
                                        <p class="font-bold">Belum ada aset yang tercatat atas nama Anda.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../includes/bottom_nav_user.php'; ?>

    <script>
    // =====================================================
    // Real-time settings poll & re-hitung depresiasi
    // =====================================================
    let currentMargin = <?php echo $margin_pengadaan; ?>;
    let currentPajak  = <?php echo $pajak; ?>;
    let currentSisa   = <?php echo $nilai_sisa_pct; ?>;

    const fmt = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');

    // Kategori → umur ekonomis (bulan)
    function usefulLife(cat) {
        if (cat === 'Software')                                                  return 36;
        if (['Server','Networking','Router','Network'].includes(cat))             return 60;
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
        if (monthsUsed <= 0) {
            current = purchase; isSalvage = false;
        } else {
            const depPerMonth = (purchase - salvage) / lifeMonths;
            current = purchase - (depPerMonth * monthsUsed);
            isSalvage = current <= salvage || monthsUsed >= lifeMonths;
            if (isSalvage) current = salvage;
        }

        // Update Harga Beli cell
        const purchaseCell = row.querySelector('.dep-purchase-cell');
        if (purchaseCell) purchaseCell.innerHTML = `<span class="font-bold text-on-surface text-sm">${fmt(purchase)}</span>`;

        // Update Nilai Saat Ini cell
        const currentCell = row.querySelector('.dep-current-cell');
        if (currentCell) {
            if (isSalvage) {
                currentCell.innerHTML = `<div class="flex flex-col items-end"><span class="font-black text-rose-600 text-sm">${fmt(current)}</span><span class="text-[9px] font-bold uppercase tracking-widest text-rose-400 bg-rose-50 px-2 rounded-full mt-1">Nilai Residu</span></div>`;
            } else {
                currentCell.innerHTML = `<span class="font-bold text-primary text-sm">${fmt(current)}</span>`;
            }
        }
    }

    function recomputeAllRows() {
        document.querySelectorAll('#asset-tbody tr[data-item]').forEach(recomputeRow);
    }

    async function fetchAndSync() {
        try {
            const res  = await fetch('../config/get_settings.php?_=' + Date.now());
            const json = await res.json();
            if (json.status !== 'ok') return;

            let changed = false;
            if (typeof json.margin_pengadaan === 'number' && json.margin_pengadaan !== currentMargin) {
                currentMargin = json.margin_pengadaan; changed = true;
            }
            if (typeof json.pajak === 'number' && json.pajak !== currentPajak) {
                currentPajak  = json.pajak; changed = true;
            }
            if (typeof json.nilai_sisa === 'number' && json.nilai_sisa !== currentSisa) {
                currentSisa   = json.nilai_sisa; changed = true;
            }

            if (changed) {
                recomputeAllRows();
                const label = document.getElementById('sync-status');
                if (label) {
                    label.textContent = 'Updated!';
                    setTimeout(() => label.textContent = 'Live Valuation', 2500);
                }
            }
        } catch (e) {
            console.warn('[SIDIK-TI] Settings fetch error:', e);
        }
    }

    // Init: fetch langsung dan poll tiap 60 detik
    fetchAndSync();
    setInterval(fetchAndSync, 60000);
    </script>
</body>
</html>
