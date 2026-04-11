<?php
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$user_id  = $_SESSION['user_id'];
$userSnap = $db->collection('users')->document($user_id)->snapshot();
$user_data = $userSnap->exists() ? $userSnap->data() : [];

// --- Integrasi Inventory dari query param ---
$pre_category  = "";
$pre_item_name = "";

if (isset($_GET['from_inv'])) {
    $inv_id   = $_GET['from_inv'];
    $invSnap  = $db->collection('inventory')->document($inv_id)->snapshot();
    $inv_data = $invSnap->exists() ? $invSnap->data() : null;
    if ($inv_data) {
        $pre_category  = strtolower($inv_data['category'] ?? '');
        $pre_item_name = $inv_data['item_name'] ?? '';
    }
}

$current_year = date('Y');

// Sisa budget departemen
$my_dept  = $user_data['department'] ?? '';
$sisa_dept = 0;
if (!empty($my_dept)) {
    $budget_docs = $db->collection('budget_config')
        ->where('department', '=', $my_dept)
        ->documents();
    foreach ($budget_docs as $doc) {
        $b = $doc->data();
        if ((string)($b['fiscal_year'] ?? '') === (string)$current_year) {
            $sisa_dept = ((float)($b['total_limit'] ?? 0)) - ((float)($b['used_amount'] ?? 0));
            break;
        }
    }
}

// Ambil konfigurasi dari Firestore (nilai awal saat render)
$margin_pengadaan = 5;
$pajak            = 11;
try {
    $sys_docs = $db->collection('system_settings')->documents();
    foreach ($sys_docs as $doc) {
        if (!$doc->exists()) continue;
        $val = $doc->data()['setting_value'] ?? null;
        if ($val === null) continue;
        if ($doc->id() === 'margin_pengadaan') $margin_pengadaan = (float)$val;
        if ($doc->id() === 'pajak')            $pajak            = (float)$val;
    }
} catch (Exception $e) { /* pakai default */ }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <?php 
        $pageTitle = 'SIDIK-TI | Form Pengadaan';
        $base_url = '../';
        include __DIR__ . '/../includes/head_meta.php'; 
    ?>
</head>
<body class="bg-surface font-body text-on-surface antialiased overflow-x-hidden min-h-screen pb-24 md:pb-0 transition-colors duration-300">

    <?php include __DIR__ . '/../includes/navbar_user.php'; ?>

    <main class="max-w-[1240px] mx-auto px-6 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            
            <!-- Left Panel -->
            <div class="lg:col-span-5 space-y-8">
                <div>
                    <h2 class="font-headline text-4xl font-extrabold text-on-surface tracking-tight leading-tight uppercase italic underline decoration-primary/30 underline-offset-8">Procurement <span class="text-primary italic">Request</span></h2>
                    <p class="text-on-surface-variant font-medium mt-6 leading-relaxed italic">Ajukan kebutuhan aset dan infrastruktur TI unit Anda melalui E-Catalog terintegrasi untuk proses budgeting yang lebih transparan dan efisien.</p>
                </div>

                <!-- Panduan -->
                <div class="bg-white p-8 rounded-[2.5rem] border border-outline-variant/5 shadow-2xl shadow-slate-200/50 space-y-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-5 text-primary">
                        <span class="material-symbols-outlined text-[120px]">shopping_cart</span>
                    </div>
                    <h3 class="font-headline font-bold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">analytics</span>
                        Panduan Pengadaan
                    </h3>
                    <div class="space-y-4">
                        <div class="flex gap-4 p-4 rounded-2xl hover:bg-slate-50 transition-colors">
                            <span class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-black text-xs shrink-0">1</span>
                            <div>
                                <p class="text-sm font-bold text-on-surface">Pilih Katalog / Template</p>
                                <p class="text-[11px] text-on-surface-variant leading-tight italic">Gunakan template untuk memuat spek standar dan harga e-catalog.</p>
                            </div>
                        </div>
                        <div class="flex gap-4 p-4 rounded-2xl hover:bg-slate-50 transition-colors">
                            <span class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-black text-xs shrink-0">2</span>
                            <div>
                                <p class="text-sm font-bold text-on-surface">Validasi Budgeting</p>
                                <p class="text-[11px] text-on-surface-variant leading-tight italic">Sistem otomatis menyertakan Biaya Overhead + PPN murni untuk kalkulasi perbandingan limit departemen Anda.</p>
                            </div>
                        </div>
                        <div class="flex gap-4 p-4 rounded-2xl hover:bg-slate-50 transition-colors">
                            <span class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-black text-xs shrink-0">3</span>
                            <div>
                                <p class="text-sm font-bold text-on-surface">Lampirkan Dasar Kebutuhan</p>
                                <p class="text-[11px] text-on-surface-variant leading-tight italic">Wajib melampirkan foto barang atau dokumen KAK (Kerangka Acuan Kerja).</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Budget Info -->
                <div class="bg-slate-900 p-8 rounded-[2.5rem] text-white flex items-center justify-between shadow-xl shadow-slate-200 border border-slate-800">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-primary rounded-2xl flex items-center justify-center text-white shadow-lg shadow-primary/20">
                            <span class="material-symbols-outlined fill-1">account_balance_wallet</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Fiscal Limit Available</p>
                            <p class="text-lg font-black font-headline text-primary tracking-tight">Rp <?php echo number_format($sisa_dept, 0, ',', '.'); ?></p>
                        </div>
                    </div>
                    <!-- Realtime sync indicator -->
                    <div class="flex items-center gap-1.5 text-slate-500">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 live-dot"></span>
                        <span class="text-[9px] font-black uppercase tracking-widest">Live</span>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Form -->
            <div class="lg:col-span-7">
                <form action="../config/proses_pengadaan.php" method="POST" enctype="multipart/form-data"
                    class="bg-white p-10 rounded-[3rem] border border-outline-variant/5 shadow-2xl shadow-slate-200/50 space-y-8 relative overflow-hidden">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="qty" id="qty_hidden" value="1">
                    <input type="hidden" name="base_price" id="base_price_hidden" value="0">
                    
                    <div class="absolute top-0 left-0 w-2 h-full bg-gradient-to-b from-primary to-amber-300"></div>

                    <!-- Identitas -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 leading-none">Identitas Pemohon</label>
                            <div class="relative group">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-lg">person</span>
                                <input class="block w-full pl-12 pr-4 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-slate-500 italic cursor-not-allowed text-sm"
                                    value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" disabled type="text"/>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 leading-none">Divisi / Dept Unit</label>
                            <div class="relative group">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-lg">apartment</span>
                                <input class="block w-full pl-12 pr-4 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-slate-500 italic cursor-not-allowed text-sm"
                                    value="<?php echo htmlspecialchars($user_data['department'] ?? ''); ?>" disabled type="text"/>
                            </div>
                        </div>
                    </div>

                    <!-- Template & Nama -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 leading-none font-bold">Pilih Katalog / Template</label>
                            <div class="relative group">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-primary text-xl">category</span>
                                <select id="template_id" name="template_id" onchange="applyTemplate()"
                                    class="block w-full pl-12 pr-10 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-4 focus:ring-primary/10 appearance-none transition-all text-sm">
                                    <option value="">-- Layanan Manual / Kostum --</option>
                                    <?php 
                                    $templates_docs = $db->collection('procurement_templates')->orderBy('category', 'ASC')->documents();
                                    foreach($templates_docs as $doc) {
                                        $t = $doc->data();
                                        echo "<option value='".$doc->id()."' data-desc='".htmlspecialchars($t['specification'] ?? '')."' data-price='".($t['base_price'] ?? 0)."'>[".strtoupper($t['category'] ?? '')."] ".($t['product_name'] ?? '')."</option>";
                                    }
                                    ?>
                                </select>
                                <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none">expand_more</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 leading-none">Nama Perangkat / Item</label>
                            <div class="relative group">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-primary text-xl">label</span>
                                <input name="title" id="title" required placeholder="Contoh: Dell Latitude 5420"
                                    value="<?php echo htmlspecialchars($pre_item_name); ?>"
                                    class="block w-full pl-12 pr-4 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-4 focus:ring-primary/10 transition-all text-sm" type="text"/>
                            </div>
                        </div>
                    </div>

                    <!-- Qty, Harga, Urgensi -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 leading-none">Volume / Jumlah</label>
                            <input type="number" id="qty" value="1" min="1" oninput="syncAndCalculate()"
                                class="block w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-4 focus:ring-primary/10 transition-all text-sm"/>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 leading-none">Harga Satuan (HPS)</label>
                            <input type="number" id="base_price" required oninput="syncAndCalculate()"
                                class="block w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-4 focus:ring-primary/10 transition-all text-sm" placeholder="Rp 0"/>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 leading-none">Urgensi</label>
                            <select name="urgency" class="block w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-primary appearance-none outline-none focus:ring-4 focus:ring-primary/10 transition-all text-sm">
                                <option value="Normal">NORMAL</option>
                                <option value="Penting">URGENT</option>
                            </select>
                        </div>
                    </div>

                    <!-- Deskripsi -->
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 leading-none">Spesifikasi Detail &amp; Justifikasi</label>
                        <div class="relative group">
                            <span class="material-symbols-outlined absolute left-4 top-6 text-primary text-xl">description</span>
                            <textarea name="description" id="description" required rows="4"
                                placeholder="Jelaskan spesifikasi detail barang (Merk, Tipe, Warna) dan alasan kebutuhan operasional..."
                                class="block w-full pl-12 pr-4 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-on-surface-variant leading-relaxed outline-none focus:ring-4 focus:ring-primary/10 transition-all text-sm min-h-[140px]"></textarea>
                        </div>
                    </div>

                    <!-- File Upload -->
                    <div class="space-y-4">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 leading-none text-primary">Lampiran Bukti / KAK (Wajib)</label>
                        <div class="relative group">
                            <input type="file" name="attachment" id="file-upload" accept=".jpg,.jpeg,.png,.pdf" required
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" onchange="updateFileName()">
                            <div id="dropzone" class="border-2 border-dashed border-primary/20 rounded-3xl p-10 text-center bg-primary/5 group-hover:border-primary/50 group-hover:bg-primary-container transition-all duration-300 flex flex-col items-center justify-center gap-3">
                                <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center text-primary shadow-sm border border-primary/10 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">add_photo_alternate</span>
                                </div>
                                <div>
                                    <p id="file-label" class="text-sm font-bold text-on-surface leading-tight">Klik atau Seret Berkas Di Sini</p>
                                    <p class="text-[10px] text-slate-400 font-medium mt-1 uppercase tracking-tighter italic">PDF, PNG Berwarna atau JPG (Maks 700KB)</p>
                                </div>
                                <div id="file-name-info" class="hidden animate-in fade-in slide-in-from-top-2 duration-300 mt-2">
                                    <div class="px-4 py-2 bg-primary/20 rounded-full border border-primary/30 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-sm text-primary">check_circle</span>
                                        <span id="file-name-display" class="text-[10px] font-bold text-primary truncate max-w-[200px]">document.pdf</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== BREAKDOWN ESTIMASI REAL-TIME ===== -->
                    <div class="bg-slate-900 rounded-[2.5rem] text-white overflow-hidden shadow-2xl shadow-slate-300/30" id="estimasi-panel">
                        <!-- Header -->
                        <div class="bg-primary px-8 pt-8 pb-4">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-[10px] font-black text-white/60 uppercase tracking-widest">Total Pengajuan Akumulatif</p>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white/60 live-dot"></span>
                                    <span class="text-[9px] font-black text-white/60 uppercase tracking-widest" id="settings-sync-label">Synced</span>
                                </div>
                            </div>
                            <h2 id="display_estimasi" class="text-3xl font-black font-headline tracking-tighter italic">Rp 0</h2>
                        </div>
                        <!-- Breakdown -->
                        <div class="px-8 py-6 space-y-3" id="breakdown-panel">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-slate-400">Subtotal (<span id="lbl_qty">1</span>×<span id="lbl_hps">Rp 0</span>)</span>
                                <span class="font-bold text-white" id="disp_subtotal">Rp 0</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-orange-300" id="lbl_markup">+ Biaya Overhead (5%)</span>
                                <span class="font-bold text-orange-300" id="disp_markup">Rp 0</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-violet-300" id="lbl_pajak">+ PPN (11%)</span>
                                <span class="font-bold text-violet-300" id="disp_pajak">Rp 0</span>
                            </div>
                            <div class="border-t border-white/10 pt-3 flex justify-between items-center">
                                <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">TOTAL AKHIR</span>
                                <span class="font-black text-primary text-xl font-headline" id="disp_total">Rp 0</span>
                            </div>
                            <!-- Budget warning -->
                            <div id="budget-warning" class="hidden mt-2 p-3 bg-red-500/20 border border-red-500/30 rounded-2xl flex items-center gap-2">
                                <span class="material-symbols-outlined text-red-400 text-lg">warning</span>
                                <p class="text-[11px] font-bold text-red-300">Total melebihi sisa anggaran departemen!</p>
                            </div>
                        </div>
                        <input type="hidden" name="estimasi" id="estimasi" value="0">
                    </div>

                    <div class="pt-4">
                        <button type="submit" name="submit_pengadaan"
                            class="w-full bg-on-surface text-white font-headline font-black py-5 rounded-2xl shadow-xl hover:bg-primary hover:-translate-y-1 transition-all active:scale-[0.98] uppercase tracking-[0.2em] text-xs flex items-center justify-center gap-3">
                            <span class="material-symbols-outlined text-lg fill-1">send</span>
                            Kirim Pengajuan Pengadaan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
    // =====================================================
    // STATE: Diinisialisasi dari server, lalu diupdate API
    // =====================================================
    let marginPct   = <?php echo $margin_pengadaan; ?>;
    let pajakPct    = <?php echo $pajak; ?>;
    const sisaBudget = <?php echo $sisa_dept; ?>;

    const fmt = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');

    // =====================================================
    // KALKULASI UTAMA (formula: Qty × HPS × (1+margin) × (1+pajak))
    // =====================================================
    function calculateEstimasi() {
        const qty       = parseInt(document.getElementById('qty').value) || 0;
        const basePrice = parseFloat(document.getElementById('base_price').value) || 0;

        // Sync hidden fields untuk server
        document.getElementById('qty_hidden').value        = qty;
        document.getElementById('base_price_hidden').value = basePrice;

        const subtotal     = qty * basePrice;
        const afterMarkup  = subtotal * (1 + marginPct / 100);
        const markupAmount = afterMarkup - subtotal;
        const pajakAmount  = afterMarkup * (pajakPct / 100);
        const total        = afterMarkup * (1 + pajakPct / 100);

        // Update summary display
        document.getElementById('lbl_qty').textContent       = qty;
        document.getElementById('lbl_hps').textContent       = fmt(basePrice);
        document.getElementById('disp_subtotal').textContent = fmt(subtotal);
        document.getElementById('lbl_markup').textContent    = `+ Biaya Overhead (${marginPct}%)`;
        document.getElementById('disp_markup').textContent   = fmt(markupAmount);
        document.getElementById('lbl_pajak').textContent     = `+ PPN (${pajakPct}%)`;
        document.getElementById('disp_pajak').textContent    = fmt(pajakAmount);
        document.getElementById('disp_total').textContent    = fmt(total);
        document.getElementById('display_estimasi').textContent = fmt(total);
        document.getElementById('estimasi').value            = Math.round(total);

        // Budget warning
        const warn    = document.getElementById('budget-warning');
        const panel   = document.getElementById('estimasi-panel');
        const header  = panel.querySelector('.bg-primary');
        if (total > sisaBudget && sisaBudget > 0) {
            warn.classList.remove('hidden');
            header.classList.replace('bg-primary', 'bg-red-600');
        } else {
            warn.classList.add('hidden');
            header.classList.replace('bg-red-600', 'bg-primary');
        }
    }

    function syncAndCalculate() { calculateEstimasi(); }

    // =====================================================
    // TEMPLATE AUTO-FILL
    // =====================================================
    function applyTemplate() {
        const select = document.getElementById('template_id');
        const option = select.options[select.selectedIndex];
        if (option.value !== "") {
            document.getElementById('description').value  = option.getAttribute('data-desc');
            document.getElementById('base_price').value   = option.getAttribute('data-price');
            document.getElementById('title').value        = option.text.substring(option.text.indexOf(']') + 2);
            calculateEstimasi();
        }
    }

    // =====================================================
    // REAL-TIME FETCH SETTINGS (polling 60 detik)
    // =====================================================
    async function fetchLatestSettings() {
        try {
            const res  = await fetch('../config/get_settings.php?_=' + Date.now());
            const json = await res.json();
            if (json.status === 'ok') {
                let updated = false;
                if (typeof json.margin_pengadaan === 'number' && json.margin_pengadaan !== marginPct) {
                    marginPct = json.margin_pengadaan;
                    updated   = true;
                }
                if (typeof json.pajak === 'number' && json.pajak !== pajakPct) {
                    pajakPct = json.pajak;
                    updated  = true;
                }
                if (updated) {
                    calculateEstimasi();
                    const label = document.getElementById('settings-sync-label');
                    if (label) {
                        label.textContent = 'Updated!';
                        setTimeout(() => label.textContent = 'Synced', 2000);
                    }
                }
            }
        } catch (e) {
            console.warn('[SIDIK-TI] Gagal memuat konfigurasi settings:', e);
        }
    }

    // =====================================================
    // FILE UPLOAD
    // =====================================================
    function updateFileName() {
        const input    = document.getElementById('file-upload');
        const info     = document.getElementById('file-name-info');
        const text     = document.getElementById('file-name-display');
        const label    = document.getElementById('file-label');
        const dropzone = document.getElementById('dropzone');

        if (input.files.length > 0) {
            const file = input.files[0];
            if (file.size > 716800) {
                alert("Maksimal ukuran file adalah 700KB. Harap kompres file Anda.");
                input.value = "";
                info.classList.add('hidden');
                label.innerText = "Klik atau Seret Berkas Di Sini";
                dropzone.classList.replace('bg-primary-container', 'bg-primary/5');
                return;
            }
            text.innerText = file.name;
            info.classList.remove('hidden');
            label.innerText = "Berkas Dipilih";
            dropzone.classList.replace('bg-primary/5', 'bg-primary-container');
        }
    }

    // =====================================================
    // INIT
    // =====================================================
    window.addEventListener('DOMContentLoaded', () => {
        // Langsung fetch latest settings
        fetchLatestSettings();
        // Lalu polling setiap 60 detik
        setInterval(fetchLatestSettings, 60000);
        // Hitung awal jika ada nilai dari template (from_inv)
        if (document.getElementById('base_price').value > 0) calculateEstimasi();
    });
    </script>
</body>
</html>
