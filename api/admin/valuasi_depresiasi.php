<?php
ob_start();

require_once __DIR__ . '/../config/database.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_user.php");
    exit();
}

$msg = "";

// --- LOGIKA SIMPAN SYSTEM SETTINGS (UPSERT) ---
if (isset($_POST['save_system_settings'])) {
    $margin_pengadaan = (float)($_POST['margin_pengadaan'] ?? 5);
    $pajak            = (float)($_POST['pajak'] ?? 11);
    $nilai_sisa       = (float)($_POST['nilai_sisa'] ?? 0); 

    if ($db) {
        try {
            $db->collection('system_settings')->document('margin_pengadaan')->set(['setting_value' => $margin_pengadaan]);
            $db->collection('system_settings')->document('pajak')->set(['setting_value' => $pajak]);
            $db->collection('system_settings')->document('nilai_sisa')->set(['setting_value' => $nilai_sisa]);
            $msg = "success";
        } catch (Exception $e) { $db = null; }
    }
    
    if (!$db && $conn) {
        $settings_to_save = [
            'margin_pengadaan' => $margin_pengadaan,
            'pajak' => $pajak,
            'nilai_sisa' => $nilai_sisa
        ];
        foreach ($settings_to_save as $key => $val) {
            $key_e = mysqli_real_escape_string($conn, $key);
            $check = mysqli_query($conn, "SELECT setting_key FROM system_settings WHERE setting_key = '$key_e'");
            if (mysqli_num_rows($check) > 0) {
                mysqli_query($conn, "UPDATE system_settings SET setting_value = '$val' WHERE setting_key = '$key_e'");
            } else {
                mysqli_query($conn, "INSERT INTO system_settings (setting_key, setting_value) VALUES ('$key_e', '$val')");
            }
        }
        $msg = "success";
    }
}

// --- AMBIL SYSTEM SETTINGS ---
$settings = [
    'margin_pengadaan' => 5,
    'pajak'            => 11,
    'nilai_sisa'       => 0,
];
if ($db) {
    try {
        $settings_docs = $db->collection('system_settings')->documents();
        foreach ($settings_docs as $doc) {
            if ($doc->exists() && isset($settings[$doc->id()])) {
                $settings[$doc->id()] = (float)($doc->data()['setting_value'] ?? $settings[$doc->id()]);
            }
        }
    } catch (Exception $e) { $db = null; }
}

if (!$db && $conn) {
    $res = mysqli_query($conn, "SELECT * FROM system_settings");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            if (isset($settings[$row['setting_key']])) {
                $settings[$row['setting_key']] = (float)$row['setting_value'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <?php 
        $pageTitle = 'SIDIK-TI | Valuasi & Depresiasi';
        $base_url = '../';
        include __DIR__ . '/../includes/head_meta.php'; 
    ?>
</head>
<body class="bg-slate-50 font-body text-slate-800 antialiased overflow-x-hidden min-h-screen">

    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>

    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen flex flex-col">
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 md:px-8 py-4 md:py-5 border-b border-outline-variant/10 sticky top-0 bg-surface/80 backdrop-blur-xl z-30">
            <div>
                <h1 class="font-headline text-2xl font-black text-slate-900 italic uppercase">Valuasi <span class="text-primary italic">&amp; Depresiasi</span></h1>
                <p class="text-xs text-slate-500 font-medium mt-1">Konfigurasi nilai buku, perpajakan, dan masa susut aset instansi.</p>
            </div>
        </header>

        <div class="p-4 md:p-8 max-w-5xl mx-auto w-full space-y-8">
            <?php if($msg === 'success'): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-8 py-5 rounded-3xl flex items-center gap-4 shadow-sm animate-in fade-in slide-in-from-top-2">
                    <span class="material-symbols-outlined text-2xl fill-1">check_circle</span>
                    <div class="flex flex-col">
                        <p class="font-headline font-bold text-sm uppercase">Berhasil Disimpan!</p>
                        <p class="text-[10px] font-medium opacity-70 mt-1">Konfigurasi BOP, perpajakan, dan depresiasi telah diperbarui.</p>
                    </div>
                </div>
            <?php endif; ?>

            <section class="space-y-6">
                <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="POST" class="bg-white rounded-[2rem] p-8 md:p-10 border border-slate-200 shadow-sm space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-orange-500">local_shipping</span>
                                Admin & Overhead (%)
                            </label>
                            <input type="number" step="0.1" min="0" max="100" id="inp_margin" name="margin_pengadaan" value="<?php echo htmlspecialchars($settings['margin_pengadaan']); ?>" oninput="updatePreview()" required class="w-full bg-orange-50 border-2 border-orange-100 rounded-2xl p-4 font-black text-xl text-orange-600 focus:ring-2 focus:ring-orange-300 outline-none transition text-center">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-violet-500">account_balance</span>
                                Pajak / PPN (%)
                            </label>
                            <input type="number" step="0.1" min="0" max="100" id="inp_pajak" name="pajak" value="<?php echo htmlspecialchars($settings['pajak']); ?>" oninput="updatePreview()" required class="w-full bg-violet-50 border-2 border-violet-100 rounded-2xl p-4 font-black text-xl text-violet-600 focus:ring-2 focus:ring-violet-300 outline-none transition text-center">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-rose-500">price_change</span>
                                Nilai Residu (%)
                            </label>
                            <input type="number" step="0.1" min="0" max="100" id="inp_sisa" name="nilai_sisa" value="<?php echo htmlspecialchars($settings['nilai_sisa']); ?>" oninput="updatePreview()" required class="w-full bg-rose-50 border-2 border-rose-100 rounded-2xl p-4 font-black text-xl text-rose-600 focus:ring-2 focus:ring-rose-300 outline-none transition text-center">
                        </div>
                    </div>

                    <div class="bg-slate-900 rounded-3xl p-8 text-white space-y-6 shadow-xl" id="preview-panel">
                        <div class="flex items-center gap-3 border-b border-slate-700 pb-4">
                            <span class="material-symbols-outlined text-blue-400">monitoring</span>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-300">Simulator Valuasi (Baseline Rp 10.000.000)</p>
                        </div>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center bg-slate-800 p-3 rounded-xl border border-slate-700">
                                <span class="text-xs font-medium text-slate-300">Harga Satuan</span>
                                <span class="font-bold text-sm">Rp 10.000.000</span>
                            </div>
                            <div class="flex justify-between items-center px-3">
                                <span class="text-xs font-bold text-orange-400" id="prev-markup-label">+ Overhead</span>
                                <span class="font-bold text-sm text-orange-400" id="prev-markup-val">Rp 500.000</span>
                            </div>
                            <div class="flex justify-between items-center px-3">
                                <span class="text-xs font-bold text-violet-400" id="prev-pajak-label">+ PPN</span>
                                <span class="font-bold text-sm text-violet-400" id="prev-pajak-val">Rp 1.155.000</span>
                            </div>
                            <div class="bg-primary p-4 rounded-xl flex justify-between items-center shadow-md">
                                <div class="flex flex-col">
                                    <span class="text-[9px] font-black uppercase tracking-wider text-blue-200">Total Harga Perolehan</span>
                                    <span class="text-2xl font-black" id="prev-total">Rp 11.655.000</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end">
                        <button type="submit" name="save_system_settings" class="px-8 py-4 bg-primary text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl hover:scale-105 transition active:scale-95 flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">admin_panel_settings</span> Terapkan Konfigurasi
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
        const total        = afterMarkup * (1 + pajak / 100);
        document.getElementById('prev-markup-label').textContent = `+ Overhead (${margin}%)`;
        document.getElementById('prev-markup-val').textContent   = fmt(afterMarkup - SAMPLE_PRICE);
        document.getElementById('prev-pajak-label').textContent  = `+ PPN (${pajak}%)`;
        document.getElementById('prev-pajak-val').textContent    = fmt(total - afterMarkup);
        document.getElementById('prev-total').textContent        = fmt(total);
    }
    updatePreview();
    </script>
</body>
</html>
