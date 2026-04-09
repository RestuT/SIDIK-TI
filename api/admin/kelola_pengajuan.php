<?php
ob_start();

require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../config/csrf_helper.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_admin.php");
    exit();
}

$id = $_GET['id'];
$current_year = date('Y');

// Fetch submission
$submissionRef = $db->collection('submissions')->document($id);
$data = $submissionRef->snapshot()->data();

if (!$data) {
    die("Data tidak ditemukan.");
}

// Fetch user data
$userRef = $db->collection('users')->document($data['user_id']);
$userData = $userRef->snapshot()->data();
$data['full_name'] = $userData['full_name'] ?? 'Unknown';
$data['department'] = $userData['department'] ?? 'Unknown';

// Fetch budget data
$budgetQuery = $db->collection('budget_config')
    ->where('fiscal_year', '=', (int)$current_year)
    ->where('department', '=', $data['department'])
    ->limit(1)
    ->documents();

$data['sisa_pagu'] = 0;
foreach ($budgetQuery as $doc) {
    $b = $doc->data();
    $data['sisa_pagu'] = $b['total_limit'] - $b['used_amount'];
}

// Ambil daftar teknisi
$technicians = $db->collection('users')
    ->where('role', 'in', ['technician', 'admin'])
    ->documents();

if (isset($_POST['update'])) {
    require_csrf_token();
    
    $status = $_POST['status'];
    $pic_id = !empty($_POST['pic_id']) ? $_POST['pic_id'] : null;
    $reason = $_POST['reasoning'];
    $current_year = date('Y');
    
    // HEA & TKDN Logic
    $tkdn_input = (float)($_POST['tkdn_pct'] ?? 0);
    $kp = ($tkdn_input / 100) * 0.25; // Preferensi tertinggi 25% sesuai Perpres
    $estimasi_awal = (float)($data['estimasi'] ?? 0);
    $hea_calculated = (1 - $kp) * $estimasi_awal;
    
    // Menyimpan nilai kapitalisasi asli vs HEA. 
    // Berdasarkan request user, HEA langsung digunakan sebagai referensi depresiasi.
    $capitalized_price = $hea_calculated; 

    // --- LOGIKA OTOMATIS REFUND BUDGET JIKA DITOLAK ---
    if ($status === 'Ditolak' && $data['type'] === 'Pengadaan' && $data['status'] !== 'Ditolak') {
        $biaya = $estimasi_awal;
        $dept_pemohon = $data['department'];
        
        // Find budget doc
        $budgetDocs = $db->collection('budget_config')
            ->where('fiscal_year', '=', (int)$current_year)
            ->where('department', '=', $dept_pemohon)
            ->limit(1)
            ->documents();
            
        foreach ($budgetDocs as $doc) {
            $doc->reference()->update([
                ['path' => 'used_amount', 'value' => \Google\Cloud\Firestore\FieldValue::increment(-$biaya)]
            ]);
        }
    }

    // --- LOGIKA OTOMATIS CREATE ASSET JIKA SELESAI ---
    if ($status === 'Selesai' && $data['type'] === 'Pengadaan') {
        $user_id_target = $data['user_id'];
        $item_name = $data['title'];
        $category = $_POST['category'] ?? 'Devices';
        $serial_number = $_POST['serial_number'] ?? 'SN-PENDING';
        $assigned_at = date('Y-m-d H:i:s');

        // Logic Auto Generate Kode Barang
        $catCodeObj = ["Laptop"=>"LAP", "Printer"=>"PRN", "Monitor"=>"MON", "Network"=>"NET", "Lainnya"=>"OTH"];
        $catCode = $catCodeObj[$category] ?? "AST";
        $deptFull = trim($data['department']);
        $deptParts = explode(' ', $deptFull);
        if (count($deptParts) > 1 && strlen($deptFull) > 4) {
            $deptPrefix = strtoupper(substr($deptParts[0],0,1) . substr($deptParts[1],0,1));
            if(count($deptParts)>2) $deptPrefix .= strtoupper(substr($deptParts[2],0,1));
        } else {
            $deptPrefix = strtoupper(substr($deptFull, 0, 3));
        }
        if(empty($deptPrefix)) $deptPrefix = "UNK";

        $countAssets = 0;
        try {
            $countDocs = $db->collection('asset_assignments')
                ->where('department', '=', $data['department'])
                ->where('category', '=', $category)
                ->documents();
            foreach ($countDocs as $d) { $countAssets++; }
        } catch(Exception $e) {}
        
        $incrementStr = str_pad($countAssets + 1, 3, '0', STR_PAD_LEFT);
        $kode_barang = sprintf("%s-%s-%s-%s", $deptPrefix, date('Y'), $catCode, $incrementStr);

        // Check if already assigned
        $checkAsset = $db->collection('asset_assignments')
            ->where('user_id', '=', $user_id_target)
            ->where('item_name', '=', $item_name)
            ->where('serial_number', '=', $serial_number)
            ->limit(1)
            ->documents();
            
        if ($checkAsset->isEmpty()) {
            $db->collection('asset_assignments')->add([
                'user_id' => $user_id_target,
                'item_name' => $item_name,
                'serial_number' => $serial_number,
                'category' => $category,
                'assigned_at' => $assigned_at,
                'status' => 'Active',
                'user_name' => $data['full_name'],
                'department' => $data['department'],
                'price_reference' => $capitalized_price,
                'original_price' => $estimasi_awal,
                'tkdn_pct' => $tkdn_input,
                'kode_barang' => $kode_barang,
                'latest_condition_code' => 1
            ]);
        } else {
            foreach ($checkAsset as $existingDoc) {
                // If it already exists, update the price
                $existingDoc->reference()->update([
                    ['path' => 'price_reference', 'value' => $capitalized_price],
                    ['path' => 'original_price', 'value' => $estimasi_awal],
                    ['path' => 'tkdn_pct', 'value' => $tkdn_input],
                ]);
            }
        }
    }

    $submissionRef->update([
        ['path' => 'status', 'value' => $status],
        ['path' => 'pic_id', 'value' => $pic_id],
        ['path' => 'admin_reasoning', 'value' => $reason]
    ]);
    
    header("Location: dashboard_admin.php?status=updated");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Validasi Pengadaan - #<?php echo $data['ticket_number']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: "#3525cd", "primary-container": "#4f46e5" }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased overflow-x-hidden min-h-screen">

    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>

    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen">
        <div class="max-w-5xl mx-auto p-6 md:p-10">
        
        <div class="bg-slate-900 p-8 text-white flex justify-between items-center rounded-t-3xl shadow-sm">
            <div>
                <p class="text-blue-400 text-xs font-black uppercase tracking-widest mb-1">Detail Pengajuan Barang</p>
                <h2 class="text-3xl font-black italic"><?php echo $data['ticket_number']; ?></h2>
            </div>
            <div class="text-right">
                <span class="px-4 py-2 rounded-xl bg-blue-600 text-xs font-bold uppercase"><?php echo $data['status']; ?></span>
            </div>
        </div>

        <div class="bg-white p-8 grid grid-cols-1 md:grid-cols-2 gap-8 shadow-sm rounded-b-3xl">
            <div class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Item & Spesifikasi</label>
                    <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100">
                        <p class="font-bold text-slate-800 text-lg mb-2"><?php echo $data['title']; ?></p>
                        <p class="text-sm text-slate-500 leading-relaxed"><?php echo $data['description']; ?></p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-orange-50 p-4 rounded-2xl border border-orange-100">
                        <p class="text-[10px] font-black text-orange-400 uppercase mb-1">Urgensi</p>
                        <p class="font-bold text-orange-700"><?php echo $data['urgency']; ?></p>
                    </div>
                    <div class="bg-blue-50 p-4 rounded-2xl border border-blue-100">
                        <p class="text-[10px] font-black text-blue-400 uppercase mb-1">Sisa Pagu <?php echo date('Y'); ?></p>
                        <p class="font-bold text-blue-700 text-sm">Rp <?php echo number_format((float)$data['sisa_pagu'], 0, ',', '.'); ?></p>
                    </div>
                </div>

                <!-- KALKULATOR HEA & TKDN -->
                <div class="bg-indigo-50 border border-indigo-100 rounded-3xl p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="material-symbols-outlined text-indigo-500">calculate</span>
                        <h3 class="text-indigo-900 font-bold text-sm uppercase tracking-widest">Kalkulator Valuasi HEA (TKDN)</h3>
                    </div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center bg-white p-3 rounded-xl border border-indigo-50 shadow-sm">
                            <span class="text-xs font-bold text-slate-500">Harga Penawaran (HP)</span>
                            <span class="font-black text-slate-800" id="hpVal" data-hp="<?php echo (float)($data['estimasi'] ?? 0); ?>">
                                Rp <?php echo number_format((float)($data['estimasi'] ?? 0), 0, ',', '.'); ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center bg-primary p-4 rounded-xl shadow-md text-white">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-bold text-indigo-200 uppercase tracking-widest">Hasil Evaluasi Akhir</span>
                                <span class="font-black text-xl italic" id="heaVal">Rp <?php echo number_format((float)($data['estimasi'] ?? 0), 0, ',', '.'); ?></span>
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] text-indigo-200 block">Dasar Depresiasi</span>
                                <span class="text-xs font-bold" id="kpVal">KP: 0%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if(!empty($data['attachment_path'])): ?>
                <a href="<?php echo $data['attachment_path']; ?>" target="_blank" class="block text-center p-4 bg-slate-800 text-white rounded-2xl font-bold hover:bg-blue-600 transition">
                    <i class="fa-solid fa-file-pdf mr-2"></i> Lihat Dokumen KAK / Justifikasi
                </a>
                <?php endif; ?>
            </div>
            
            <form action="" method="POST" class="space-y-6 border-l border-slate-100 pl-0 md:pl-8">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <?php if(($data['is_appealed'] ?? 0) == 1): ?>
                <div class="bg-red-50 p-6 rounded-3xl border border-red-100 mb-6">
                    <h4 class="text-xs font-black text-red-600 uppercase mb-2 tracking-widest italic">
                        <i class="fa-solid fa-circle-exclamation"></i> Pengajuan ini adalah Aju Banding
                    </h4>
                    <p class="text-sm text-gray-700 font-medium">"<?php echo htmlspecialchars($data['appeal_reason'] ?? ''); ?>"</p>
                </div>
                <?php endif; ?>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tentukan Status</label>
                    <select name="status" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl font-bold outline-none focus:ring-4 focus:ring-blue-100">
                        <option value="Menunggu" <?php echo $data['status'] == 'Menunggu' ? 'selected' : ''; ?>>Menunggu Validasi</option>
                        <option value="Proses" <?php echo $data['status'] == 'Proses' ? 'selected' : ''; ?>>Setujui & Proses</option>
                        <option value="Selesai" <?php echo $data['status'] == 'Selesai' ? 'selected' : ''; ?>>Selesai / Barang Diterima</option>
                        <option value="Ditolak" <?php echo $data['status'] == 'Ditolak' ? 'selected' : ''; ?>>Tolak Pengajuan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Bobot TKDN Vendor (%)</label>
                    <div class="relative">
                        <input id="tkdnInput" name="tkdn_pct" type="number" min="0" max="100" value="0" placeholder="Porsi dalam negeri misal: 40" class="w-full pl-5 pr-12 py-4 bg-indigo-50/50 border border-indigo-100 rounded-2xl font-bold text-indigo-900 outline-none focus:ring-4 focus:ring-indigo-100"/>
                        <span class="absolute right-6 top-1/2 -translate-y-1/2 text-indigo-300 font-black">%</span>
                    </div>
                    <p class="text-[9px] text-slate-400 mt-1 uppercase tracking-widest">Akan dikalikan preferensi tertinggi 25% (Perpres 12/2021).</p>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tugaskan Petugas (PIC)</label>
                    <select name="pic_id" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl font-bold outline-none focus:ring-4 focus:ring-blue-100">
                        <option value="">-- Pilih PIC Pengadaan --</option>
                        <?php foreach($technicians as $doc): 
                            $t = $doc->data(); $tid = $doc->id(); ?>
                            <option value="<?php echo $tid; ?>" <?php echo ($data['pic_id'] ?? '') == $tid ? 'selected' : ''; ?>>
                                <?php echo $t['full_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Pilih Kategori Aset</label>
                        <select name="category" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl font-bold outline-none focus:ring-4 focus:ring-blue-100">
                            <option value="Laptop" <?php echo strpos($data['title'], 'Laptop') !== false ? 'selected' : ''; ?>>Laptop / PC</option>
                            <option value="Software">Software / Aplikasi Khusus</option>
                            <option value="Printer" <?php echo strpos($data['title'], 'Printer') !== false ? 'selected' : ''; ?>>Printer / Scanner</option>
                            <option value="Monitor" <?php echo strpos($data['title'], 'Monitor') !== false ? 'selected' : ''; ?>>Monitor</option>
                            <option value="Network" <?php echo strpos($data['title'], 'Router') !== false ? 'selected' : ''; ?>>Networking</option>
                            <option value="Lainnya">Lainnya / Peripheral</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Input Nomor Seri (S/N)</label>
                        <input name="serial_number" required placeholder="Contoh: SN-D420-XXXX" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl font-bold outline-none focus:ring-4 focus:ring-blue-100" type="text"/>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Catatan Verifikasi (Reasoning)</label>
                    <textarea name="reasoning" rows="4" placeholder="Berikan alasan atau instruksi tambahan..." class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl font-medium outline-none focus:ring-4 focus:ring-blue-100"><?php echo htmlspecialchars($data['admin_reasoning'] ?? ''); ?></textarea>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" name="update" class="flex-1 bg-blue-600 text-white font-black py-4 rounded-2xl shadow-lg shadow-blue-200 hover:bg-blue-700 transition active:scale-95 uppercase tracking-widest">
                        Simpan Perubahan
                    </button>
                    <a href="dashboard_admin.php" class="flex-1 bg-slate-100 text-slate-500 font-black py-4 rounded-2xl text-center flex items-center justify-center hover:bg-slate-200 transition uppercase tracking-widest">
                        Batal
                    </a>
                </div>
            </form>
        </div>
        </div>
    </main>

    <script>
        const tkdnInput = document.getElementById('tkdnInput');
        const hpVal = parseFloat(document.getElementById('hpVal').getAttribute('data-hp')) || 0;
        const heaVal = document.getElementById('heaVal');
        const kpVal = document.getElementById('kpVal');

        const formatRp = (angka) => 'Rp ' + Math.round(angka).toLocaleString('id-ID');

        tkdnInput.addEventListener('input', function() {
            let tkdn = parseFloat(this.value) || 0;
            if(tkdn > 100) tkdn = 100;
            if(tkdn < 0) tkdn = 0;

            const kp = (tkdn / 100) * 0.25;
            const hea = (1 - kp) * hpVal;

            kpVal.innerText = `KP: ${(kp * 100).toFixed(1)}%`;
            heaVal.innerText = formatRp(hea);
        });
    </script>
</body>
</html>
