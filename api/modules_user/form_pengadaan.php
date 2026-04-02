<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$userSnap = $db->collection('users')->document($user_id)->snapshot();
$user_data = $userSnap->exists() ? $userSnap->data() : [];

// --- LOGIKA INTEGRASI INVENTORY ---
$pre_category = "";
$pre_item_name = "";

if (isset($_GET['from_inv'])) {
    $inv_id = $_GET['from_inv'];
    $invSnap = $db->collection('inventory')->document($inv_id)->snapshot();
    $inv_data = $invSnap->exists() ? $invSnap->data() : null;
    
    if ($inv_data) {
        $pre_category = strtolower($inv_data['category'] ?? ''); 
        $pre_item_name = $inv_data['item_name'] ?? '';
    }
}

$current_year = date('Y');

// Ambil sisa budget khusus departemen user yang sedang login
$my_dept = $user_data['department'] ?? '';
$sisa_dept = 0;
if (!empty($my_dept)) {
    $budget_docs = $db->collection('budget_config')
        ->where('department', '=', $my_dept)
        ->where('fiscal_year', '=', (int)$current_year)
        ->limit(1)
        ->documents();
    
    foreach ($budget_docs as $doc) {
        $b = $doc->data();
        $sisa_dept = ($b['total_limit'] ?? 0) - ($b['used_amount'] ?? 0);
    }
}
?>

<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Procurement Request</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#f59e0b", // Amber/Orange 500
                        "primary-container": "#fff7ed",
                        "primary-fixed": "#ffed65",
                        "surface": "#f8fafc",
                        "on-surface": "#0f172a",
                        "on-surface-variant": "#64748b",
                        "outline-variant": "#e2e8f0",
                        "error": "#ef4444",
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
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        .fill-1 { font-variation-settings: 'FILL' 1; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f8fafc; }
        ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
</head>
<body class="bg-surface font-body text-on-surface antialiased overflow-x-hidden min-h-screen">

    <?php include __DIR__ . '/../includes/navbar_user.php'; ?>

    <main class="max-w-[1240px] mx-auto px-6 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            
            <!-- Left Panel: Context & Guidelines -->
            <div class="lg:col-span-5 space-y-8">
                <div>
                    <h2 class="font-headline text-4xl font-extrabold text-on-surface tracking-tight leading-tight uppercase italic underline decoration-primary/30 underline-offset-8">Procurement <span class="text-primary italic">Request</span></h2>
                    <p class="text-on-surface-variant font-medium mt-6 leading-relaxed italic">Ajukan kebutuhan aset dan infrastruktur TI unit Anda melalui E-Catalog terintegrasi untuk proses budgeting yang lebih transparan dan efisien.</p>
                </div>

                <!-- Step Card -->
                <div class="bg-white p-8 rounded-[2.5rem] border border-outline-variant/5 shadow-2xl shadow-slate-200/50 space-y-6 relative overflow-hidden group">
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
                                <p class="text-[11px] text-on-surface-variant leading-tight italic">Sistem akan menghitung PPN 11% dan membandingkannya dengan limit Anda.</p>
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

                <!-- Budget Info Card -->
                <div class="bg-slate-900 p-8 rounded-[2.5rem] text-white flex items-center justify-between group cursor-default transition-all shadow-xl shadow-slate-200 border border-slate-800">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-primary rounded-2xl flex items-center justify-center text-white shadow-lg shadow-primary/20">
                            <span class="material-symbols-outlined fill-1">account_balance_wallet</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Fiscal Limit Available</p>
                            <p class="text-lg font-black font-headline text-primary tracking-tight">Rp <?php echo number_format($sisa_dept, 0, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Form Area -->
            <div class="lg:col-span-7">
                <form action="../config/proses_pengadaan.php" method="POST" enctype="multipart/form-data" class="bg-white p-10 rounded-[3rem] border border-outline-variant/5 shadow-2xl shadow-slate-200/50 space-y-8 relative overflow-hidden">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <!-- Form Accent Line -->
                    <div class="absolute top-0 left-0 w-2 h-full bg-gradient-to-b from-primary to-primary-dark"></div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 leading-none">Identitas Pemohon</label>
                            <div class="relative group">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-primary transition-colors text-lg">person</span>
                                <input class="block w-full pl-12 pr-4 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-slate-500 italic cursor-not-allowed text-sm" 
                                    value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" disabled type="text"/>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 leading-none">Divisi / Dept Unit</label>
                            <div class="relative group">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-primary transition-colors text-lg">apartment</span>
                                <input class="block w-full pl-12 pr-4 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-slate-500 italic cursor-not-allowed text-sm" 
                                    value="<?php echo htmlspecialchars($user_data['department'] ?? ''); ?>" disabled type="text"/>
                            </div>
                        </div>
                    </div>

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
                                <input name="title" id="title" required placeholder="Contoh: Dell Latitude 5420" value="<?php echo htmlspecialchars($pre_item_name); ?>"
                                    class="block w-full pl-12 pr-4 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-4 focus:ring-primary/10 transition-all text-sm" type="text"/>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 leading-none">Volume / Jumlah</label>
                            <div class="relative group">
                                <input type="number" name="qty" id="qty" value="1" min="1" oninput="calculateEstimasi()"
                                    class="block w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-4 focus:ring-primary/10 transition-all text-sm"/>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 leading-none">Harga Satuan (HPS)</label>
                            <div class="relative group">
                                <input type="number" name="base_price" id="base_price" required oninput="calculateEstimasi()"
                                    class="block w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-4 focus:ring-primary/10 transition-all text-sm" placeholder="RP 0"/>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 leading-none">Urgensi</label>
                            <div class="relative group">
                                <select name="urgency" class="block w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-primary appearance-none outline-none focus:ring-4 focus:ring-primary/10 transition-all text-sm">
                                    <option value="Normal">NORMAL</option>
                                    <option value="Penting">URGENT</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 leading-none">Spesifikasi Detail & Justifikasi</label>
                        <div class="relative group">
                            <span class="material-symbols-outlined absolute left-4 top-6 text-primary text-xl">description</span>
                            <textarea name="description" id="description" required rows="4" placeholder="Jelaskan spesifikasi detail barang (Merk, Tipe, Warna) dan alasan kebutuhan operasional..." 
                                class="block w-full pl-12 pr-4 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-on-surface-variant leading-relaxed outline-none focus:ring-4 focus:ring-primary/10 transition-all text-sm min-h-[140px]"></textarea>
                        </div>
                    </div>

                    <!-- Modern File Upload (Matches Maintenance) -->
                    <div class="space-y-4">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 leading-none text-primary">Lampiran Bukti / KAK (Wajib)</label>
                        <div class="relative group">
                            <input type="file" name="attachment" id="file-upload" accept=".jpg, .jpeg, .png, .pdf" required 
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" onchange="updateFileName()">
                            <div id="dropzone" class="border-2 border-dashed border-primary/20 rounded-3xl p-10 text-center bg-primary/5 group-hover:border-primary/50 group-hover:bg-primary-container transition-all duration-300 flex flex-col items-center justify-center gap-3">
                                <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center text-primary shadow-sm border border-primary/10 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">add_photo_alternate</span>
                                </div>
                                <div>
                                    <p id="file-label" class="text-sm font-bold text-on-surface leading-tight">Klik atau Seret Berkas Di Sini</p>
                                    <p class="text-[10px] text-slate-400 font-medium mt-1 uppercase tracking-tighter italic">PDF, PNG Berwarna atau JPG (Maks 2MB)</p>
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

                    <!-- Total Estimasi Bento -->
                    <div class="bg-primary p-10 rounded-[2.5rem] text-white flex flex-col justify-center relative overflow-hidden shadow-2xl shadow-primary/30">
                        <div class="absolute top-0 right-0 p-10 opacity-20 pointer-events-none -rotate-12 group-hover:rotate-0 transition-transform">
                            <span class="material-symbols-outlined text-8xl">shopping_cart_checkout</span>
                        </div>
                        <p class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-1 leading-none">Total Pengajuan Akumulatif (Net)</p>
                        <h2 id="display_estimasi" class="text-3xl font-black font-headline tracking-tighter shrink-0 italic">Rp 0</h2>
                        <p class="text-[9px] text-white/40 mt-3 font-bold uppercase italic leading-tight">*Kalkulasi otomatis (Nilai Satuan * Volume) + PPN 11% + ME 5%</p>
                        <input type="hidden" name="estimasi" id="estimasi" value="0">
                    </div>

                    <div class="pt-4">
                        <button type="submit" name="submit_pengadaan" class="w-full bg-secondary text-white font-headline font-black py-5 rounded-2xl shadow-xl shadow-slate-200 hover:bg-primary hover:-translate-y-1 transition-all active:scale-[0.98] uppercase tracking-[0.2em] text-xs flex items-center justify-center gap-3">
                            <span class="material-symbols-outlined text-lg fill-1">send</span>
                            Kirim Pengajuan Pengadaan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
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

    function calculateEstimasi() {
        const qty = parseInt(document.getElementById('qty').value) || 0;
        const basePrice = parseFloat(document.getElementById('base_price').value) || 0;
        
        // Total = Dasar * 1.16
        const subtotal = qty * basePrice;
        const total = subtotal * 1.16;
        
        document.getElementById('estimasi').value = Math.round(total);
        document.getElementById('display_estimasi').innerText = "Rp " + Math.round(total).toLocaleString('id-ID');
        
        const sisaBudget = <?php echo $sisa_dept; ?>;
        const display = document.getElementById('display_estimasi');
        if (total > sisaBudget && sisaBudget > 0) {
            display.classList.add('text-black');
            display.parentElement.classList.replace('bg-primary', 'bg-error');
        } else {
            display.classList.remove('text-black');
            display.parentElement.classList.replace('bg-error', 'bg-primary');
        }
    }

    function updateFileName() {
        const input = document.getElementById('file-upload');
        const displayInfo = document.getElementById('file-name-info');
        const displayText = document.getElementById('file-name-display');
        const label = document.getElementById('file-label');
        const dropzone = document.getElementById('dropzone');

        if (input.files.length > 0) {
            displayText.innerText = input.files[0].name;
            displayInfo.classList.remove('hidden');
            label.innerText = "Berkas Dipilih";
            dropzone.classList.replace('bg-primary/5', 'bg-primary-container');
        }
    }

    window.onload = function() {
        if(document.getElementById('base_price').value > 0) calculateEstimasi();
    };
    </script>
</body>
</html>
