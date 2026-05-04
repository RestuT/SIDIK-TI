<?php
ob_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_data = [];
$pre_category = "";
$pre_item_name = "";
$current_year = date('Y');
$sisa_dept = 0;
$margin_pengadaan = 5;
$pajak = 11;
$procurement_templates = [];

if ($db) {
    try {
        $userSnap = $db->collection('users')->document($user_id)->snapshot();
        $user_data = $userSnap->exists() ? $userSnap->data() : [];
        if (isset($_GET['from_inv'])) {
            $invSnap = $db->collection('inventory')->document($_GET['from_inv'])->snapshot();
            if ($invSnap->exists()) {
                $inv_data = $invSnap->data();
                $pre_category = strtolower($inv_data['category'] ?? '');
                $pre_item_name = $inv_data['item_name'] ?? '';
            }
        }
        $my_dept = $user_data['department'] ?? '';
        if (!empty($my_dept)) {
            $budget_docs = $db->collection('budget_config')->where('department', '=', $my_dept)->documents();
            foreach ($budget_docs as $doc) {
                $b = $doc->data();
                if ((string)($b['fiscal_year'] ?? '') === (string)$current_year) {
                    $sisa_dept = ((float)($b['total_limit'] ?? 0)) - ((float)($b['used_amount'] ?? 0));
                    break;
                }
            }
        }
        $sys_docs = $db->collection('system_settings')->documents();
        foreach ($sys_docs as $doc) {
            if (!$doc->exists()) continue;
            $val = $doc->data()['setting_value'] ?? null;
            if ($val === null) continue;
            if ($doc->id() === 'margin_pengadaan') $margin_pengadaan = (float)$val;
            if ($doc->id() === 'pajak') $pajak = (float)$val;
        }
        $templates_docs = $db->collection('procurement_templates')->orderBy('category', 'ASC')->documents();
        foreach($templates_docs as $doc) {
            $t = $doc->data(); $t['id'] = $doc->id();
            $procurement_templates[] = $t;
        }
    } catch (Exception $e) { $db = null; }
}

if (!$db && $conn) {
    $uid_e = mysqli_real_escape_string($conn, $user_id);
    $res_user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$uid_e' LIMIT 1");
    if ($u_row = mysqli_fetch_assoc($res_user)) $user_data = $u_row;
    if (isset($_GET['from_inv'])) {
        $inv_id_e = mysqli_real_escape_string($conn, $_GET['from_inv']);
        $res_inv = mysqli_query($conn, "SELECT * FROM inventory WHERE id = '$inv_id_e' LIMIT 1");
        if ($inv_row = mysqli_fetch_assoc($res_inv)) {
            $pre_category = str_replace(['Laptop/PC','Printer/Scanner','Jaringan/WiFi'], ['laptop','printer','network'], $inv_row['category']);
            $pre_item_name = $inv_row['item_name'];
        }
    }
    $my_dept = $user_data['department'] ?? '';
    if (!empty($my_dept)) {
        $dept_e = mysqli_real_escape_string($conn, $my_dept);
        $res_budget = mysqli_query($conn, "SELECT * FROM budget_config WHERE department = '$dept_e' AND fiscal_year = '$current_year' LIMIT 1");
        if ($b_row = mysqli_fetch_assoc($res_budget)) {
            $sisa_dept = (float)$b_row['total_limit'] - (float)$b_row['used_amount'];
        }
    }
    $res_set = mysqli_query($conn, "SELECT * FROM system_settings");
    while ($row = mysqli_fetch_assoc($res_set)) {
        if ($row['setting_key'] === 'margin_pengadaan') $margin_pengadaan = (float)$row['setting_value'];
        if ($row['setting_key'] === 'pajak') $pajak = (float)$row['setting_value'];
    }
    $res_tmpl = mysqli_query($conn, "SELECT * FROM procurement_templates ORDER BY category ASC");
    while ($row = mysqli_fetch_assoc($res_tmpl)) { $procurement_templates[] = $row; }
}
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
            <div class="lg:col-span-5 space-y-8">
                <div>
                    <h2 class="font-headline text-4xl font-extrabold text-on-surface tracking-tight leading-none uppercase italic underline decoration-primary/30 underline-offset-8">Procurement <span class="text-primary italic">Request</span></h2>
                    <p class="text-on-surface-variant font-medium mt-6 leading-relaxed italic">Ajukan kebutuhan aset dan infrastruktur TI unit Anda melalui E-Catalog terintegrasi untuk proses budgeting yang lebih transparan dan efisien.</p>
                </div>
                <div class="bg-white p-8 rounded-[2.5rem] border border-outline-variant/5 shadow-2xl shadow-slate-200/50 space-y-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-5 text-primary"><span class="material-symbols-outlined text-[120px]">shopping_cart</span></div>
                    <h3 class="font-headline font-bold text-on-surface flex items-center gap-2"><span class="material-symbols-outlined text-primary">analytics</span>Panduan Pengadaan</h3>
                    <div class="space-y-4">
                        <div class="flex gap-4 p-4 rounded-2xl hover:bg-slate-50 transition-colors"><span class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-black text-xs shrink-0">1</span><div><p class="text-sm font-bold text-on-surface">Pilih Katalog / Template</p></div></div>
                        <div class="flex gap-4 p-4 rounded-2xl hover:bg-slate-50 transition-colors"><span class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-black text-xs shrink-0">2</span><div><p class="text-sm font-bold text-on-surface">Validasi Budgeting</p></div></div>
                    </div>
                </div>
                <div class="bg-slate-900 p-8 rounded-[2.5rem] text-white flex items-center justify-between shadow-xl shadow-slate-200 border border-slate-800">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-primary rounded-2xl flex items-center justify-center text-white shadow-lg shadow-primary/20"><span class="material-symbols-outlined fill-1">account_balance_wallet</span></div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Fiscal Limit Available</p>
                            <p class="text-lg font-black font-headline text-primary tracking-tight">Rp <?php echo number_format($sisa_dept, 0, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-7">
                <form action="../actions/proses_pengadaan.php" method="POST" enctype="multipart/form-data" class="bg-white p-10 rounded-[3rem] border border-outline-variant/5 shadow-2xl shadow-slate-200/50 space-y-8 relative overflow-hidden">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="qty" id="qty_hidden" value="1">
                    <input type="hidden" name="base_price" id="base_price_hidden" value="0">
                    <div class="absolute top-0 left-0 w-2 h-full bg-gradient-to-b from-primary to-amber-300"></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Identitas Pemohon</label>
                            <input class="block w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-slate-500 italic cursor-not-allowed text-sm" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" disabled type="text"/>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Divisi / Dept Unit</label>
                            <input class="block w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-slate-500 italic cursor-not-allowed text-sm" value="<?php echo htmlspecialchars($user_data['department'] ?? ''); ?>" disabled type="text"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 font-bold">Pilih Katalog / Template</label>
                            <select id="template_id" name="template_id" onchange="applyTemplate()" class="block w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-4 focus:ring-primary/10 transition-all text-sm">
                                <option value="">-- Layanan Manual / Kostum --</option>
                                <?php foreach($procurement_templates as $t) { echo "<option value='".$t['id']."' data-desc='".htmlspecialchars($t['specification'] ?? '')."' data-price='".($t['base_price'] ?? 0)."'>[".strtoupper($t['category'] ?? '')."] ".($t['product_name'] ?? '')."</option>"; } ?>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Nama Perangkat / Item</label>
                            <input name="title" id="title" required value="<?php echo htmlspecialchars($pre_item_name); ?>" class="block w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-4 focus:ring-primary/10 transition-all text-sm" type="text"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2"><label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Volume/Jumlah</label><input type="number" id="qty" value="1" min="1" oninput="syncAndCalculate()" class="block w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-4 focus:ring-primary/10 transition-all text-sm"/></div>
                        <div class="space-y-2"><label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Harga Satuan (HPS)</label><input type="number" id="base_price" required oninput="syncAndCalculate()" class="block w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-4 focus:ring-primary/10 transition-all text-sm" placeholder="Rp 0"/></div>
                        <div class="space-y-2"><label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Urgensi</label><select name="urgency" class="block w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-primary appearance-none outline-none focus:ring-4 focus:ring-primary/10 transition-all text-sm"><option value="Normal">NORMAL</option><option value="Penting">URGENT</option></select></div>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Spesifikasi Detail & Justifikasi</label>
                        <textarea name="description" id="description" required rows="4" class="block w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-on-surface-variant leading-relaxed outline-none focus:ring-4 focus:ring-primary/10 transition-all text-sm min-h-[140px]"></textarea>
                    </div>
                    <div class="space-y-4">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 text-primary">Lampiran Bukti / KAK (Wajib)</label>
                        <input type="file" name="attachment" id="file-upload" accept=".jpg,.jpeg,.png,.pdf" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                    </div>
                    <div class="bg-slate-900 rounded-[2.5rem] text-white overflow-hidden shadow-2xl shadow-slate-300/30" id="estimasi-panel">
                        <div class="bg-primary px-8 pt-8 pb-4"><h2 id="display_estimasi" class="text-3xl font-black font-headline tracking-tighter italic">Rp 0</h2></div>
                        <div class="px-8 py-6 space-y-3" id="breakdown-panel">
                            <div class="flex justify-between items-center text-sm"><span class="text-slate-400">Subtotal (<span id="lbl_qty">1</span>×<span id="lbl_hps">Rp 0</span>)</span><span class="font-bold text-white" id="disp_subtotal">Rp 0</span></div>
                            <div class="flex justify-between items-center text-sm"><span class="text-orange-300" id="lbl_markup">+ Biaya Overhead (<?php echo $margin_pengadaan; ?>%)</span><span class="font-bold text-orange-300" id="disp_markup">Rp 0</span></div>
                            <div class="flex justify-between items-center text-sm"><span class="text-violet-300" id="lbl_pajak">+ PPN (<?php echo $pajak; ?>%)</span><span class="font-bold text-violet-300" id="disp_pajak">Rp 0</span></div>
                            <div class="border-t border-white/10 pt-3 flex justify-between items-center"><span class="text-[10px] font-black text-white/40 uppercase tracking-widest">TOTAL AKHIR</span><span class="font-black text-primary text-xl font-headline" id="disp_total">Rp 0</span></div>
                            <div id="budget-warning" class="hidden mt-2 p-3 bg-red-500/20 border border-red-500/30 rounded-2xl flex items-center gap-2"><span class="material-symbols-outlined text-red-400 text-lg">warning</span><p class="text-[11px] font-bold text-red-300">Total melebihi sisa anggaran departemen!</p></div>
                        </div>
                        <input type="hidden" name="estimasi" id="estimasi" value="0">
                    </div>
                    <button type="submit" id="btn-submit" name="submit_pengadaan" class="w-full bg-on-surface text-white font-headline font-black py-5 rounded-2xl shadow-xl hover:bg-primary hover:-translate-y-1 transition-all active:scale-[0.98] uppercase tracking-[0.2em] text-xs flex items-center justify-center gap-3">Kirim Pengajuan Pengadaan</button>
                </form>
            </div>
        </div>
    </main>
    <script>
    let marginPct = <?php echo $margin_pengadaan; ?>;
    let pajakPct = <?php echo $pajak; ?>;
    const sisaBudget = <?php echo $sisa_dept; ?>;
    const fmt = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');
    function calculateEstimasi() {
        const qty = parseInt(document.getElementById('qty').value) || 0;
        const basePrice = parseFloat(document.getElementById('base_price').value) || 0;
        document.getElementById('qty_hidden').value = qty;
        document.getElementById('base_price_hidden').value = basePrice;
        const subtotal = qty * basePrice;
        const afterMarkup = subtotal * (1 + marginPct / 100);
        const markupAmount = afterMarkup - subtotal;
        const pajakAmount = afterMarkup * (pajakPct / 100);
        const total = afterMarkup * (1 + pajakPct / 100);
        document.getElementById('lbl_qty').textContent = qty;
        document.getElementById('lbl_hps').textContent = fmt(basePrice);
        document.getElementById('disp_subtotal').textContent = fmt(subtotal);
        document.getElementById('disp_markup').textContent = fmt(markupAmount);
        document.getElementById('disp_pajak').textContent = fmt(pajakAmount);
        document.getElementById('disp_total').textContent = fmt(total);
        document.getElementById('display_estimasi').textContent = fmt(total);
        document.getElementById('estimasi').value = Math.round(total);
        const warn = document.getElementById('budget-warning');
        const header = document.querySelector('#estimasi-panel .bg-primary') || document.querySelector('#estimasi-panel .bg-red-600');
        const btnSubmit = document.getElementById('btn-submit');
        if (total > sisaBudget) { 
            warn.classList.remove('hidden'); 
            header.classList.replace('bg-primary', 'bg-red-600'); 
            btnSubmit.disabled = true;
            btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
            btnSubmit.classList.remove('hover:bg-primary', 'hover:-translate-y-1', 'active:scale-[0.98]');
        }
        else { 
            warn.classList.add('hidden'); 
            header.classList.replace('bg-red-600', 'bg-primary'); 
            btnSubmit.disabled = false;
            btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
            btnSubmit.classList.add('hover:bg-primary', 'hover:-translate-y-1', 'active:scale-[0.98]');
        }
    }
    function syncAndCalculate() { calculateEstimasi(); }
    function applyTemplate() {
        const select = document.getElementById('template_id');
        const option = select.options[select.selectedIndex];
        if (option.value !== "") {
            document.getElementById('description').value = option.getAttribute('data-desc');
            document.getElementById('base_price').value = option.getAttribute('data-price');
            document.getElementById('title').value = option.text.substring(option.text.indexOf(']') + 2);
            calculateEstimasi();
        }
    }
    window.addEventListener('DOMContentLoaded', () => { if (document.getElementById('base_price').value > 0) calculateEstimasi(); });

    // Client-Side Image Compression Script for Vercel/Firestore Optimizations
    const fileInput = document.getElementById('file-upload');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            if (file.type.match(/image.*/)) {
                const MAX_SIZE_KB = 350; 
                if (file.size / 1024 > MAX_SIZE_KB) {
                    const reader = new FileReader();
                    reader.readAsDataURL(file);
                    reader.onload = function(event) {
                        const img = new Image();
                        img.src = event.target.result;
                        img.onload = function() {
                            const canvas = document.createElement('canvas');
                            const ctx = canvas.getContext('2d');
                            
                            const MAX_WIDTH = 1200;
                            const MAX_HEIGHT = 1200;
                            let width = img.width;
                            let height = img.height;

                            if (width > height && width > MAX_WIDTH) {
                                height *= MAX_WIDTH / width;
                                width = MAX_WIDTH;
                            } else if (height > MAX_HEIGHT) {
                                width *= MAX_HEIGHT / height;
                                height = MAX_HEIGHT;
                            }

                            canvas.width = width;
                            canvas.height = height;
                            ctx.drawImage(img, 0, 0, width, height);

                            let quality = 0.7;
                            let dataUrl = canvas.toDataURL('image/jpeg', quality);
                            
                            let iter = 0;
                            while(dataUrl.length > 500000 && iter < 3) {
                                quality -= 0.15;
                                dataUrl = canvas.toDataURL('image/jpeg', quality);
                                iter++;
                            }

                            fetch(dataUrl)
                                .then(res => res.blob())
                                .then(blob => {
                                    const compressedFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", {
                                        type: 'image/jpeg',
                                        lastModified: Date.now()
                                    });

                                    const dataTransfer = new DataTransfer();
                                    dataTransfer.items.add(compressedFile);
                                    fileInput.files = dataTransfer.files;
                                    
                                    console.log("SIDIK-TI: File pengadaan dikompres ke " + (compressedFile.size/1024).toFixed(0) + "KB.");
                                });
                        }
                    }
                }
            }
        });
    }
    </script>
</body>
</html>
