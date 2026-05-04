<?php
ob_start();
require_once __DIR__ . '/../config/database.php';

// Proteksi: minimal harus login (bisa admin atau user)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$back_url = $is_admin ? '../admin/inventory.php' : 'assets_user.php';

// Ambil settings dari Firestore untuk nilai awal kalkulasi
$margin_pengadaan = 5;
$pajak            = 11;
$nilai_sisa       = 10;
if ($db) {
    try {
        $sys_docs = $db->collection('system_settings')->documents();
        foreach ($sys_docs as $doc) {
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

if (!$db && isset($conn) && $conn) {
    $res = mysqli_query($conn, "SELECT * FROM system_settings");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            switch ($row['setting_key']) {
                case 'margin_pengadaan': $margin_pengadaan = (float)$row['setting_value']; break;
                case 'pajak':            $pajak            = (float)$row['setting_value']; break;
                case 'nilai_sisa':       $nilai_sisa       = (float)$row['setting_value']; break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php 
        $pageTitle = 'SIDIK-TI | Asset Market Analysis';
        $base_url = '../';
        include __DIR__ . '/../includes/head_meta.php'; 
    ?>
</head>
<body class="bg-surface text-on-surface selection:bg-primary/20 pb-24 md:pb-0 transition-colors duration-300">

    <!-- TopAppBar -->
    <header class="fixed top-0 w-full flex justify-between items-center px-6 h-16 bg-white/80 backdrop-blur-xl z-50 shadow-sm border-b border-indigo-50">
        <div class="flex items-center gap-4">
            <a href="<?php echo $back_url; ?>" class="material-symbols-outlined text-slate-500 p-2 hover:bg-slate-50 transition-all rounded-full">arrow_back</a>
            <div class="flex items-center gap-2">
                <img src="../assets/img/logo.png" alt="Logo" class="h-8 md:h-10 w-auto">
                <span class="text-xl font-extrabold bg-gradient-to-br from-indigo-600 to-indigo-400 bg-clip-text text-transparent">SIDIK-TI</span>
            </div>
        </div>
        <div class="hidden md:flex items-center space-x-8">
            <span class="text-slate-400 font-bold text-xs uppercase tracking-widest italic">Asset Market Insight</span>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-100">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 live-dot"></span>
                <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest" id="sync-badge">Live</span>
            </div>
            <span class="text-indigo-600 font-black text-[10px] uppercase tracking-widest bg-indigo-50 px-3 py-1 rounded-full">Pricing Module</span>
        </div>
    </header>

    <main class="pt-32 pb-32 px-4 md:px-10 max-w-7xl mx-auto space-y-24">

        <!-- Hero -->
        <div class="max-w-4xl space-y-6">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-indigo-50 text-indigo-700 font-semibold text-xs uppercase tracking-[0.2em]">
                <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                Market Intelligence
            </div>
            <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight text-on-surface leading-tight">
                Analisis Depresiasi &amp; <span class="bg-gradient-to-r from-primary to-indigo-400 bg-clip-text text-transparent italic tracking-tighter">Valuasi Aset TI</span>
            </h1>
            <p class="text-on-surface-variant text-lg md:text-xl font-medium max-w-2xl leading-relaxed">
                Memahami bagaimana nilai infrastruktur digital Anda berubah seiring waktu untuk perencanaan anggaran yang lebih strategis.
            </p>
        </div>

        <!-- ===== INTERACTIVE CALCULATOR ===== -->
        <div class="bg-primary p-1 rounded-[3rem] shadow-2xl">
            <div class="bg-primary rounded-[2.8rem] p-8 md:p-12 text-white grid grid-cols-1 md:grid-cols-2 gap-12 items-start">
                <div class="space-y-6">
                    <h3 class="text-3xl font-bold italic tracking-tight">Depreciation Calculator</h3>
                    <p class="opacity-70 text-sm leading-relaxed">Masukkan perkiraan harga beli awal untuk melihat proyeksi nilai aset Anda setelah satu tahun pemakaian. Kalkulasi menggunakan konfigurasi <strong>Biaya Overhead + PPN</strong> terkini dari sistem.</p>

                    <div class="space-y-4">
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold opacity-60">Rp</span>
                            <input type="number" id="inputPrice" value="10000000" oninput="calculateDepreciation()"
                                class="w-full pl-12 pr-6 py-4 bg-white/10 border-2 border-white/20 rounded-2xl outline-none focus:border-white focus:bg-white/20 transition-all font-black text-2xl">
                        </div>
                        <select id="inputType" onchange="calculateDepreciation()"
                            class="w-full px-6 py-4 bg-white/10 border-2 border-white/20 rounded-2xl outline-none focus:border-white appearance-none font-bold">
                            <option value="laptop" class="text-on-surface">Laptop / PC (Umur 4 Tahun)</option>
                            <option value="printer" class="text-on-surface">Printer (Umur 4 Tahun)</option>
                            <option value="router" class="text-on-surface">Network/Router (Umur 5 Tahun)</option>
                            <option value="server" class="text-on-surface">Server (Umur 5 Tahun)</option>
                            <option value="software" class="text-on-surface">Software/Lisensi (Umur 3 Tahun)</option>
                        </select>

                        <!-- Stress Factor Slider -->
                        <div class="space-y-3 pt-2">
                            <div class="flex justify-between items-center px-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-white/50">Stress Factor (Usage Intensity)</label>
                                <span id="multiplierValue" class="text-sm font-black text-primary bg-white px-3 py-0.5 rounded-full">1.0x</span>
                            </div>
                            <input type="range" id="inputMultiplier" min="0.5" max="2.5" step="0.1" value="1.0" 
                                oninput="updateMultiplier(this.value)"
                                class="w-full h-1.5 bg-white/20 rounded-lg appearance-none cursor-pointer accent-white">
                            <div class="flex justify-between items-center text-[8px] font-black uppercase tracking-widest text-white/30 px-1">
                                <span>Low (Office)</span>
                                <span>High (Field)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Config Info Box -->
                    <div class="bg-white/10 rounded-2xl p-5 space-y-2 text-sm border border-white/10">
                        <p class="text-white/50 text-[10px] font-black uppercase tracking-widest mb-3">Konfigurasi Aktif (Real-Time)</p>
                        <div class="flex justify-between">
                            <span class="text-white/70">Biaya Overhead</span>
                            <span class="font-black text-orange-300" id="conf-margin"><?php echo $margin_pengadaan; ?>%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-white/70">PPN</span>
                            <span class="font-black text-violet-300" id="conf-pajak"><?php echo $pajak; ?>%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-white/70">Nilai Sisa</span>
                            <span class="font-black text-rose-300" id="conf-sisa"><?php echo $nilai_sisa; ?>%</span>
                        </div>
                    </div>
                </div>

                <!-- Result Panel -->
                <div class="space-y-4">
                    <div class="bg-white/10 backdrop-blur-md rounded-3xl p-8 border border-white/10 flex flex-col items-center justify-center space-y-2 relative overflow-hidden">
                        <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/5 rounded-full animate-float"></div>
                        <p class="text-[10px] font-black uppercase tracking-widest opacity-60">Estimasi Nilai Tahun Depan</p>
                        <h4 id="resultValue" class="text-4xl md:text-5xl font-black italic tracking-tighter">Rp 7.750.000</h4>
                        <div class="inline-flex items-center gap-2 px-3 py-1 bg-rose-500/20 text-rose-200 rounded-full text-[10px] font-black mt-4">
                            <span class="material-symbols-outlined text-sm">trending_down</span>
                            <span id="resultDrop">TERDEPRESIASI 22.5%</span>
                        </div>
                    </div>

                    <!-- Breakdown Card -->
                    <div class="bg-white/10 rounded-3xl p-6 border border-white/10 space-y-3 text-sm">
                        <p class="text-[10px] font-black uppercase tracking-widest text-white/40">Breakdown Harga Beli</p>
                        <div class="flex justify-between">
                            <span class="text-white/70">Harga Dasar</span>
                            <span class="font-bold" id="dep-base">Rp 10.000.000</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-orange-300" id="dep-markup-lbl">+ Biaya Overhead (5%)</span>
                            <span class="font-bold text-orange-300" id="dep-markup-val">Rp 500.000</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-violet-300" id="dep-pajak-lbl">+ PPN (11%)</span>
                            <span class="font-bold text-violet-300" id="dep-pajak-val">Rp 1.155.000</span>
                        </div>
                        <div class="border-t border-white/10 pt-2 flex justify-between">
                            <span class="font-black">Harga Beli Aktual</span>
                            <span class="font-black text-white" id="dep-purchase">Rp 11.655.000</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-rose-300">Nilai Sisa (<span id="dep-sisa-lbl"><?php echo $nilai_sisa; ?></span>%)</span>
                            <span class="font-bold text-rose-300" id="dep-salvage">Rp 1.165.500</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Segments -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="group p-8 rounded-[2.5rem] bg-white border border-outline-variant/10 shadow-sm hover:shadow-2xl transition-all duration-500">
                <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-primary flex items-center justify-center mb-8 group-hover:rotate-12 transition-transform">
                    <span class="material-symbols-outlined text-3xl">laptop_mac</span>
                </div>
                <h3 class="text-2xl font-bold mb-4">Laptop / PC</h3>
                <div class="text-rose-600 font-black text-sm mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined">chart_data</span>
                    Penurunan 15% – 30%
                </div>
                <p class="text-on-surface-variant text-sm leading-relaxed mb-6 italic">Perangkat yang paling cepat terdepresiasi karena siklus pembaruan CPU/GPU tahunan yang sangat ketat.</p>
                <div class="space-y-4">
                    <div class="p-4 bg-slate-50 rounded-2xl"><p class="text-[10px] font-black text-outline uppercase mb-1">Kondisi Baru</p><p class="text-xs font-medium">Turun ~15-20% saat model baru rilis.</p></div>
                    <div class="p-4 bg-slate-50 rounded-2xl"><p class="text-[10px] font-black text-outline uppercase mb-1">Nilai Bekas</p><p class="text-xs font-medium">Harganya bisa anjlok hingga 40% tergantung kondisi baterai.</p></div>
                </div>
            </div>

            <div class="group p-8 rounded-[2.5rem] bg-white border border-outline-variant/10 shadow-sm hover:shadow-2xl transition-all duration-500">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-8 group-hover:rotate-12 transition-transform">
                    <span class="material-symbols-outlined text-3xl">print</span>
                </div>
                <h3 class="text-2xl font-bold mb-4">Printer</h3>
                <div class="text-rose-600 font-black text-sm mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined">chart_data</span>
                    Penurunan 10% – 20%
                </div>
                <p class="text-on-surface-variant text-sm leading-relaxed mb-6 italic">Siklus hidup produk yang lebih lambat. Produsen fokus mengambil untung dari penjualan tinta/consumables.</p>
                <div class="space-y-4">
                    <div class="p-4 bg-slate-50 rounded-2xl"><p class="text-[10px] font-black text-outline uppercase mb-1">Harga Unit</p><p class="text-xs font-medium">Sangat stabil di pasar unit baru. Hanya turun ~10% setahun.</p></div>
                    <div class="p-4 bg-slate-50 rounded-2xl"><p class="text-[10px] font-black text-outline uppercase mb-1">Risiko Bekas</p><p class="text-xs font-medium">Kekhawatiran pada kondisi print head membuat harga unit bekas turun tajam.</p></div>
                </div>
            </div>

            <div class="group p-8 rounded-[2.5rem] bg-white border border-outline-variant/10 shadow-sm hover:shadow-2xl transition-all duration-500">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-8 group-hover:rotate-12 transition-transform">
                    <span class="material-symbols-outlined text-3xl">wifi_tethering</span>
                </div>
                <h3 class="text-2xl font-bold mb-4">Network/Router</h3>
                <div class="text-rose-600 font-black text-sm mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined">chart_data</span>
                    Penurunan 5% – 15%
                </div>
                <p class="text-on-surface-variant text-sm leading-relaxed mb-6 italic">Perangkat "pasang dan lupakan". Teknologinya bertahan lebih lama selama standar WiFi 6/7 masih relevan.</p>
                <div class="space-y-4">
                    <div class="p-4 bg-slate-50 rounded-2xl"><p class="text-[10px] font-black text-outline uppercase mb-1">Faktor Stabilitas</p><p class="text-xs font-medium">Harga tidak banyak berubah kecuali ada standar WiFi baru yang rilis massal.</p></div>
                    <div class="p-4 bg-slate-50 rounded-2xl"><p class="text-[10px] font-black text-outline uppercase mb-1">Teknologi Lama</p><p class="text-xs font-medium">Stok lama biasanya didiskon besar saat transisi standar teknologi.</p></div>
                </div>
            </div>
        </div>

        <!-- Factors & Tips -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <div class="space-y-8">
                <div class="flex items-center gap-4">
                    <div class="w-1 bg-primary h-8 rounded-full"></div>
                    <h3 class="text-2xl font-bold italic tracking-tight">Faktor Utama yang Mempengaruhi Harga</h3>
                </div>
                <div class="space-y-6">
                    <div class="flex gap-6">
                        <div class="shrink-0 w-12 h-12 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center justify-center text-primary"><span class="material-symbols-outlined">calendar_month</span></div>
                        <div><p class="font-bold text-on-surface">Siklus Rilis Produk</p><p class="text-sm text-on-surface-variant leading-relaxed">Harga turun drastis 1-2 bulan sebelum model penerusnya diluncurkan.</p></div>
                    </div>
                    <div class="flex gap-6">
                        <div class="shrink-0 w-12 h-12 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center justify-center text-primary"><span class="material-symbols-outlined">currency_exchange</span></div>
                        <div><p class="font-bold text-on-surface">Kurs Mata Uang</p><p class="text-sm text-on-surface-variant leading-relaxed">Penguatan/pelemahan Rupiah terhadap USD sangat berpengaruh karena sebagian besar aset TI adalah barang impor.</p></div>
                    </div>
                    <div class="flex gap-6">
                        <div class="shrink-0 w-12 h-12 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center justify-center text-primary"><span class="material-symbols-outlined">receipt_long</span></div>
                        <div><p class="font-bold text-on-surface">Tarif Pajak (PPN)</p><p class="text-sm text-on-surface-variant leading-relaxed">Perubahan tarif PPN langsung mempengaruhi harga beli efektif aset. Konfigurasi aktif: <strong id="info-pajak"><?php echo $pajak; ?>%</strong>.</p></div>
                    </div>
                </div>
            </div>

            <div class="bg-indigo-950 rounded-[3rem] p-10 text-white flex flex-col justify-center space-y-6 relative overflow-hidden">
                <div class="absolute -bottom-10 -right-10 w-64 h-64 bg-primary/20 rounded-full blur-3xl"></div>
                <span class="material-symbols-outlined text-5xl text-primary">verified</span>
                <h3 class="text-3xl font-bold italic">The "Sweet Spot" Strategy</h3>
                <p class="text-indigo-200/80 leading-relaxed font-medium">
                    Membeli perangkat baru di usia 9-12 bulan setelah rilis adalah pilihan paling cerdas.
                    Harganya sudah turun cukup jauh (~20-25%), namun teknologinya masih sangat mumpuni untuk digunakan hingga 3-5 tahun ke depan.
                </p>
                <div class="pt-4">
                    <a href="<?php echo $is_admin ? '../admin/dashboard_admin.php' : 'dashboard_user.php'; ?>"
                        class="inline-flex items-center gap-3 px-8 py-4 bg-primary text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:shadow-xl hover:shadow-primary/30 transition-all">
                        Kembali ke Dashboard
                        <span class="material-symbols-outlined text-lg">home</span>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer class="py-12 text-center text-[10px] font-black uppercase tracking-[0.4em] text-slate-400 border-t border-indigo-50">
        © 2026 Sidik-TI Market Intelligence • Economic Valuation Dept.
    </footer>

    <script>
    // =====================================================
    // Config dari server (akan diperbarui via API)
    // =====================================================
    let marginPct = <?php echo $margin_pengadaan; ?>;
    let pajakPct  = <?php echo $pajak; ?>;
    let sisaPct   = <?php echo $nilai_sisa; ?>;

    const fmt = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');

    // Umur ekonomis berdasarkan tipe (tahun)
    function usefulYears(type) {
        if (type === 'software') return 3;
        if (['router', 'server'].includes(type)) return 5;
        return 4; // laptop, printer, dll
    }

    // =====================================================
    // KALKULATOR UTAMA
    // =====================================================
    function calculateDepreciation() {
        const basePrice = parseFloat(document.getElementById('inputPrice').value) || 0;
        const type      = document.getElementById('inputType').value;
        const multiplier = parseFloat(document.getElementById('inputMultiplier').value) || 1.0;
        const years     = usefulYears(type);

        // Harga beli = base × (1+markup) × (1+pajak)
        const afterMarkup   = basePrice * (1 + marginPct / 100);
        const markupAmount  = afterMarkup - basePrice;
        const pajakAmount   = afterMarkup * (pajakPct / 100);
        const purchasePrice = afterMarkup * (1 + pajakPct / 100);

        // Depresiasi garis lurus 1 tahun DENGAN MULTIPLIER
        // Efektif 1 tahun (12 bulan) menjadi (12 * multiplier) bulan
        const effectiveMonths = 12 * multiplier;
        const salvage    = purchasePrice * (sisaPct / 100);
        const depPerMonth = (purchasePrice - salvage) / (years * 12);
        const afterYear1 = purchasePrice - (depPerMonth * effectiveMonths);
        const dropPct    = ((purchasePrice - afterYear1) / purchasePrice * 100).toFixed(1);

        // Update result panel
        document.getElementById('resultValue').textContent = fmt(Math.max(salvage, afterYear1));
        document.getElementById('resultDrop').textContent  = `TERDEPRESIASI ${dropPct}%`;

        // Update breakdown
        document.getElementById('dep-base').textContent          = fmt(basePrice);
        document.getElementById('dep-markup-lbl').textContent    = `+ Biaya Overhead (${marginPct}%)`;
        document.getElementById('dep-markup-val').textContent    = fmt(markupAmount);
        document.getElementById('dep-pajak-lbl').textContent     = `+ PPN (${pajakPct}%)`;
        document.getElementById('dep-pajak-val').textContent     = fmt(pajakAmount);
        document.getElementById('dep-purchase').textContent      = fmt(purchasePrice);
        document.getElementById('dep-sisa-lbl').textContent      = sisaPct;
        document.getElementById('dep-salvage').textContent       = fmt(salvage);
    }

    // =====================================================
    // REAL-TIME FETCH
    // =====================================================
    async function fetchLatestSettings() {
        try {
            const res  = await fetch('../config/get_settings.php?_=' + Date.now());
            const json = await res.json();
            if (json.status !== 'ok') return;

            let changed = false;
            if (typeof json.margin_pengadaan === 'number' && json.margin_pengadaan !== marginPct) { marginPct = json.margin_pengadaan; changed = true; }
            if (typeof json.pajak === 'number' && json.pajak !== pajakPct)                         { pajakPct  = json.pajak;            changed = true; }
            if (typeof json.nilai_sisa === 'number' && json.nilai_sisa !== sisaPct)                { sisaPct   = json.nilai_sisa;       changed = true; }

            if (changed) {
                // Update config info box
                document.getElementById('conf-margin').textContent = marginPct + '%';
                document.getElementById('conf-pajak').textContent  = pajakPct  + '%';
                document.getElementById('conf-sisa').textContent   = sisaPct   + '%';
                document.getElementById('info-pajak').textContent  = pajakPct  + '%';
                calculateDepreciation();

                const badge = document.getElementById('sync-badge');
                badge.textContent = 'Updated!';
                setTimeout(() => badge.textContent = 'Live', 2000);
            }
        } catch (e) {
            console.warn('[SIDIK-TI] Settings fetch error:', e);
        }
    }

    // Init
    function updateMultiplier(val) {
        document.getElementById('multiplierValue').textContent = parseFloat(val).toFixed(1) + 'x';
        calculateDepreciation();
    }

    calculateDepreciation();
    fetchLatestSettings();
    setInterval(fetchLatestSettings, 60000);
    </script>
</body>
</html>
