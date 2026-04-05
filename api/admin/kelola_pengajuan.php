<?php

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

    // --- LOGIKA OTOMATIS REFUND BUDGET JIKA DITOLAK ---
    if ($status === 'Ditolak' && $data['type'] === 'Pengadaan' && $data['status'] !== 'Ditolak') {
        $biaya = $data['estimasi'];
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
                'user_name' => $data['full_name'], // Denormalization for easier listing
                'department' => $data['department'],
                'price_reference' => (float)($data['estimasi'] ?? 0)
            ]);
        } else {
            foreach ($checkAsset as $existingDoc) {
                $existingDoc->reference()->update([
                    ['path' => 'price_reference', 'value' => (float)($data['estimasi'] ?? 0)]
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
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
        .fill-1 { font-variation-settings: 'FILL' 1; }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased overflow-x-hidden min-h-screen">

    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>

    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen">
        <div class="max-w-5xl mx-auto p-6 md:p-10">
        
        <div class="bg-slate-900 p-8 text-white flex justify-between items-center">
            <div>
                <p class="text-blue-400 text-xs font-black uppercase tracking-widest mb-1">Detail Pengajuan Barang</p>
                <h2 class="text-3xl font-black italic"><?php echo $data['ticket_number']; ?></h2>
            </div>
            <div class="text-right">
                <span class="px-4 py-2 rounded-xl bg-blue-600 text-xs font-bold uppercase"><?php echo $data['status']; ?></span>
            </div>
        </div>

        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
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

                <?php if(!empty($data['attachment_path'])): ?>
                <a href="<?php echo $data['attachment_path']; ?>" target="_blank" class="block text-center p-4 bg-slate-800 text-white rounded-2xl font-bold hover:bg-blue-600 transition">
                    <i class="fa-solid fa-file-pdf mr-2"></i> Lihat Dokumen KAK / Justifikasi
                </a>
                <?php endif; ?>
            </div>
            <?php if(($data['is_appealed'] ?? 0) == 1): ?>
    <div class="bg-red-50 p-6 rounded-3xl border border-red-100 mb-6">
        <h4 class="text-xs font-black text-red-600 uppercase mb-2 tracking-widest italic">
            <i class="fa-solid fa-circle-exclamation"></i> Pengajuan ini adalah Aju Banding
        </h4>
        <p class="text-sm text-gray-700 font-medium">"<?php echo htmlspecialchars($data['appeal_reason'] ?? ''); ?>"</p>
    </div>
<?php endif; ?>

            <form action="" method="POST" class="space-y-6 border-l border-slate-100 pl-0 md:pl-8">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
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
                    <a href="dashboard_admin.php" class="flex-1 bg-slate-100 text-slate-500 font-black py-4 rounded-2xl text-center hover:bg-slate-200 transition uppercase tracking-widest">
                        Batal
                    </a>
                </div>
            </form>
        </div>
        </div>
    </main>

</body>
</html>
