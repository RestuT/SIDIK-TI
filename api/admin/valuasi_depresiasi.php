<?php
ob_start();
session_start();

require_once __DIR__ . '/../config/database.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_user.php");
    exit();
}

$msg = "";

// --- LOGIKA SIMPAN SYSTEM SETTINGS ---
if (isset($_POST['save_system_settings'])) {
    $margin_pengadaan = (float)($_POST['margin_pengadaan'] ?? 5);
    $pajak            = (float)($_POST['pajak'] ?? 11);
    $nilai_sisa       = (float)($_POST['nilai_sisa'] ?? 0); // PMK 72 default 0

    try {
        $db->collection('system_settings')->document('margin_pengadaan')->set(['setting_value' => $margin_pengadaan]);
        $db->collection('system_settings')->document('pajak')->set(['setting_value' => $pajak]);
        $db->collection('system_settings')->document('nilai_sisa')->set(['setting_value' => $nilai_sisa]);
        $msg = "success";
    } catch (Exception $e) {
        $msg = "error";
    }
}

// --- AMBIL SYSTEM SETTINGS ---
$settings = [
    'margin_pengadaan' => 5,
    'pajak'            => 11,
    'nilai_sisa'       => 0,
];
try {
    $settings_docs = $db->collection('system_settings')->documents();
    foreach ($settings_docs as $doc) {
        if ($doc->exists() && isset($settings[$doc->id()])) {
            $settings[$doc->id()] = (float)($doc->data()['setting_value'] ?? $settings[$doc->id()]);
        }
    }
} catch (Exception $e) { /* default */ }
?>

<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Valuasi & Depresiasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: "#3525cd", "primary-container": "#4f46e5" },
                    fontFamily: { headline: ["Plus Jakarta Sans"], body: ["Inter"] }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; align-middle; }
        .fill-1 { font-variation-settings: 'FILL' 1; }
    </style>
</head>
<body class="bg-slate-50 font-body text-slate-800 antialiased overflow-x-hidden min-h-screen">

    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>

    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen flex flex-col">
        <header class="flex items-center justify-between px-4 md:px-8 py-6 border-b border-slate-200 bg-white sticky top-0 z-10">
            <div>
                <h1 class="font-headline text-2xl font-black text-slate-900">Manajemen Valuasi &amp; Depresiasi</h1>
                <p class="text-xs text-slate-500 font-medium mt-1">Konfigurasi nilai buku, perpajakan, dan masa susut aset instansi.</p>
            </div>
        </header>

        <div class="p-4 md:p-8 max-w-5xl mx-auto w-full space-y-8">

            <?php if($msg === 'success'): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-8 py-5 rounded-3xl flex items-center gap-4 shadow-sm">
                    <span class="material-symbols-outlined text-2xl fill-1">check_circle</span>
                    <div class="flex flex-col">
                        <p class="font-headline font-bold text-sm uppercase">Berhasil Disimpan!</p>
                        <p class="text-[10px] font-medium opacity-70 mt-1">Konfigurasi BOP, perpajakan, dan depresiasi telah diperbarui.</p>
                    </div>
                </div>
            <?php endif; ?>

            <section class="space-y-6">
                <form action="" method="POST" class="bg-white rounded-[2rem] p-8 md:p-10 border border-slate-200 shadow-sm space-y-8">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Biaya Overhead & Logistik (Eks Markup) -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-orange-500">local_shipping</span>
                                Biaya Administrasi & Overhead (%)
                            </label>
                            <input type="number" step="0.1" min="0" max="100" id="inp_margin" name="margin_pengadaan"
                                value="<?php echo htmlspecialchars($settings['margin_pengadaan']); ?>"
                                oninput="updatePreview()" required
                                class="w-full bg-orange-50 border-2 border-orange-100 rounded-2xl p-4 font-black text-xl text-orange-600 outline-none focus:ring-2 focus:ring-orange-300 transition text-center">
                            <p class="text-[10px] text-slate-400 ml-2">BOP untuk asuransi, survei vendor & administrasi.</p>
                        </div>

                        <!-- Pajak -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-violet-500">account_balance</span>
                                Pajak / PPN (%)
                            </label>
                            <input type="number" step="0.1" min="0" max="100" id="inp_pajak" name="pajak"
                                value="<?php echo htmlspecialchars($settings['pajak']); ?>"
                                oninput="updatePreview()" required
                                class="w-full bg-violet-50 border-2 border-violet-100 rounded-2xl p-4 font-black text-xl text-violet-600 outline-none focus:ring-2 focus:ring-violet-300 transition text-center">
                            <p class="text-[10px] text-slate-400 ml-2">Tarif pajak pengadaan (biasanya 11%).</p>
                        </div>

                        <!-- Nilai Sisa -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-rose-500">price_change</span>
                                Nilai Sisa/Residu (%)
                            </label>
                            <input type="number" step="0.1" min="0" max="100" id="inp_sisa" name="nilai_sisa"
                                value="<?php echo htmlspecialchars($settings['nilai_sisa']); ?>"
                                oninput="updatePreview()" required
                                class="w-full bg-rose-50 border-2 border-rose-100 rounded-2xl p-4 font-black text-xl text-rose-600 outline-none focus:ring-2 focus:ring-rose-300 transition text-center">
                            <p class="text-[10px] text-slate-400 ml-2">Sisa nilai buku di akhir umur manfaat (PMK 72 = 0%).</p>
                        </div>
                    </div>

                    <!-- ===== LIVE PREVIEW KALKULASI ===== -->
                    <div class="bg-slate-900 rounded-3xl p-8 text-white space-y-6 shadow-xl" id="preview-panel">
                        <div class="flex items-center gap-3 border-b border-slate-700 pb-4">
                            <span class="material-symbols-outlined text-blue-400">monitoring</span>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-300">Simulator Valuasi Cepat (Baseline Aset Rp 10.000.000)</p>
                        </div>

                        <!-- Breakdown Harga Pengadaan -->
                        <div class="space-y-3">
                            <div class="flex justify-between items-center bg-slate-800 p-3 rounded-xl border border-slate-700">
                                <span class="text-xs font-medium text-slate-300">Harga Satuan (Asli)</span>
                                <span class="font-bold text-sm">Rp 10.000.000</span>
                            </div>
                            <div class="flex justify-between items-center px-3">
                                <span class="text-xs font-bold text-orange-400" id="prev-markup-label">+ Biaya Overhead (5%)</span>
                                <span class="font-bold text-sm text-orange-400" id="prev-markup-val">Rp 500.000</span>
                            </div>
                            <div class="flex justify-between items-center px-3">
                                <span class="text-xs font-bold text-violet-400" id="prev-pajak-label">+ PPN (11%)</span>
                                <span class="font-bold text-sm text-violet-400" id="prev-pajak-val">Rp 1.155.000</span>
                            </div>
                            <div class="bg-blue-600 p-4 rounded-xl flex justify-between items-center shadow-md">
                                <div class="flex flex-col">
                                    <span class="text-[9px] font-black uppercase tracking-wider text-blue-200">Total Harga Perolehan (RAB / HEA Final)</span>
                                    <span class="text-2xl font-black" id="prev-total">Rp 11.655.000</span>
                                </div>
                            </div>
                        </div>

                        <!-- Breakdown Depresiasi -->
                        <div class="space-y-3 pt-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 border-l-4 border-slate-500 pl-2">Algoritma Depresiasi PMK 72/2023 (Kel 1 = 4 Tahun)</p>
                            <div class="grid grid-cols-2 gap-3 text-center">
                                <div class="bg-slate-800 rounded-xl p-4 border border-slate-700">
                                    <p class="text-[9px] text-slate-400 uppercase font-black tracking-widest mb-1">Penyusutan Tahunan (Garis Lurus)</p>
                                    <p class="font-black text-rose-400" id="dep-per-year">Rp 2.913.750</p>
                                </div>
                                <div class="bg-slate-800 rounded-xl p-4 border border-slate-700">
                                    <p class="text-[9px] text-slate-400 uppercase font-black tracking-widest mb-1">Target Nilai Residu Buku</p>
                                    <p class="font-black text-emerald-400" id="dep-salvage">Rp 0</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end">
                        <button type="submit" name="save_system_settings" class="px-8 py-4 bg-primary text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-primary-container transition active:scale-95 flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">admin_panel_settings</span>
                            Terapkan Konfigurasi
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </main>

    <script>
    const SAMPLE_PRICE = 10000000;
    function fmt(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }

    function updatePreview() {
        const margin = parseFloat(document.getElementById('inp_margin').value) || 0;
        const pajak  = parseFloat(document.getElementById('inp_pajak').value)  || 0;
        const sisa   = parseFloat(document.getElementById('inp_sisa').value)   || 0;

        const afterMarkup  = SAMPLE_PRICE * (1 + margin / 100);
        const markupAmount = afterMarkup - SAMPLE_PRICE;
        const total        = afterMarkup * (1 + pajak / 100);
        const pajakAmount  = total - afterMarkup;

        document.getElementById('prev-markup-label').textContent = `+ Biaya Administrasi & Overhead (${margin}%)`;
        document.getElementById('prev-markup-val').textContent   = fmt(markupAmount);
        document.getElementById('prev-pajak-label').textContent  = `+ PPN (${pajak}%)`;
        document.getElementById('prev-pajak-val').textContent    = fmt(pajakAmount);
        document.getElementById('prev-total').textContent        = fmt(total);

        const salvage = total * (sisa / 100);
        const perYear = (total - salvage) / 4;

        document.getElementById('dep-salvage').textContent  = fmt(salvage);
        document.getElementById('dep-per-year').textContent = fmt(perYear);
    }
    updatePreview();
    </script>
</body>
</html>
