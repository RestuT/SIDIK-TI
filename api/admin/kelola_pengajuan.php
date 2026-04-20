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

// Data storage variables
$data = null;
$technicians = [];

if ($db) {
    try {
        $submissionRef = $db->collection('submissions')->document($id);
        $snap = $submissionRef->snapshot();
        if ($snap->exists()) {
            $data = $snap->data();
            $data['id'] = $snap->id();
            
            // Fetch User
            $uRef = $db->collection('users')->document($data['user_id']);
            $uSnap = $uRef->snapshot();
            $uData = $uSnap->exists() ? $uSnap->data() : [];
            $data['full_name'] = $uData['full_name'] ?? 'Unknown';
            $data['department'] = $uData['department'] ?? 'Unknown';

            // Fetch Budget
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

            // Fetch Techs
            $tech_docs = $db->collection('users')->where('role', 'in', ['technician', 'admin', 'staff'])->documents();
            foreach ($tech_docs as $doc) {
                $t = $doc->data();
                $t['id'] = $doc->id();
                $technicians[] = $t;
            }
        }
    } catch (Exception $e) { $db = null; }
}

if (!$db && $conn) {
    $id_sql = mysqli_real_escape_string($conn, $id);
    $res = mysqli_query($conn, "SELECT s.*, u.full_name, u.department FROM submissions s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = '$id_sql'");
    $data = mysqli_fetch_assoc($res);
    
    if ($data) {
        $dept_sql = mysqli_real_escape_string($conn, $data['department'] ?? '');
        $b_res = mysqli_query($conn, "SELECT (total_limit - used_amount) as sisa FROM budget_config WHERE department = '$dept_sql' AND fiscal_year = $current_year");
        $b_row = mysqli_fetch_assoc($b_res);
        $data['sisa_pagu'] = $b_row['sisa'] ?? 0;

        $t_res = mysqli_query($conn, "SELECT id, full_name, role FROM users WHERE role IN ('technician', 'admin', 'staff')");
        while ($row = mysqli_fetch_assoc($t_res)) {
            $technicians[] = $row;
        }
    }
}

if (!$data) {
    die("Data tidak ditemukan.");
}

if (isset($_POST['update'])) {
    require_csrf_token();
    
    $status = $_POST['status'];
    $pic_id = !empty($_POST['pic_id']) ? $_POST['pic_id'] : null;
    $reason = $_POST['reasoning'];
    
    $tkdn_input = (float)($_POST['tkdn_pct'] ?? 0);
    $kp = ($tkdn_input / 100) * 0.25;
    $estimasi_awal = (float)($data['estimasi'] ?? 0);
    $hea_calculated = (1 - $kp) * $estimasi_awal;
    $capitalized_price = $hea_calculated; 

    if ($db) {
        try {
            if ($status === 'Ditolak' && $data['type'] === 'Pengadaan' && $data['status'] !== 'Ditolak') {
                $budgetDocs = $db->collection('budget_config')
                    ->where('fiscal_year', '=', (int)$current_year)
                    ->where('department', '=', $data['department'])
                    ->limit(1)->documents();
                foreach ($budgetDocs as $doc) {
                    $doc->reference()->update([['path' => 'used_amount', 'value' => \Google\Cloud\Firestore\FieldValue::increment(-$estimasi_awal)]]);
                }
            }
            if ($status === 'Selesai' && $data['type'] === 'Pengadaan') {
                $db->collection('asset_assignments')->add([
                    'user_id' => $data['user_id'],
                    'item_name' => $data['title'],
                    'serial_number' => $_POST['serial_number'] ?? 'SN-PENDING',
                    'category' => $_POST['category'] ?? 'Devices',
                    'assigned_at' => date('Y-m-d H:i:s'),
                    'status' => 'Active',
                    'user_name' => $data['full_name'],
                    'department' => $data['department'],
                    'price_reference' => $capitalized_price,
                    'original_price' => $estimasi_awal,
                    'tkdn_pct' => $tkdn_input,
                    'kode_barang' => 'AST-' . time(),
                    'latest_condition_code' => 1
                ]);
            }
            $db->collection('submissions')->document($id)->update([
                ['path' => 'status', 'value' => $status],
                ['path' => 'pic_id', 'value' => $pic_id],
                ['path' => 'admin_reasoning', 'value' => $reason]
            ]);
        } catch (Exception $e) {}
    } else if ($conn) {
        $status_sql = mysqli_real_escape_string($conn, $status);
        $pic_sql = !empty($pic_id) ? "'" . mysqli_real_escape_string($conn, $pic_id) . "'" : "NULL";
        $reason_sql = mysqli_real_escape_string($conn, $reason);
        $id_sql = mysqli_real_escape_string($conn, $id);

        if ($status === 'Ditolak' && $data['type'] === 'Pengadaan' && $data['status'] !== 'Ditolak') {
            $dept_sql = mysqli_real_escape_string($conn, $data['department']);
            mysqli_query($conn, "UPDATE budget_config SET used_amount = used_amount - $estimasi_awal WHERE department = '$dept_sql' AND fiscal_year = $current_year");
        }
        if ($status === 'Selesai' && $data['type'] === 'Pengadaan') {
            $uid = mysqli_real_escape_string($conn, $data['user_id']);
            $title = mysqli_real_escape_string($conn, $data['title']);
            $sn = mysqli_real_escape_string($conn, $_POST['serial_number'] ?? '');
            $cat = mysqli_real_escape_string($conn, $_POST['category'] ?? '');
            $now = date('Y-m-d');
            mysqli_query($conn, "INSERT INTO asset_assignments (user_id, item_name, serial_number, category, assigned_at, status) VALUES ('$uid', '$title', '$sn', '$cat', '$now', 'Active')");
        }
        mysqli_query($conn, "UPDATE submissions SET status = '$status_sql', pic_id = $pic_sql, admin_reasoning = '$reason_sql' WHERE id = '$id_sql'");
    }
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
                            <div class="flex justify-between items-center bg-indigo-600 p-4 rounded-xl shadow-md text-white">
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
                </div>
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
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Bobot TKDN Vendor (%)</label>
                        <input id="tkdnInput" name="tkdn_pct" type="number" min="0" max="100" value="0" class="w-full pl-5 pr-12 py-4 bg-indigo-50/50 border border-indigo-100 rounded-2xl font-bold text-indigo-900 outline-none focus:ring-4 focus:ring-indigo-100"/>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tugaskan Petugas (PIC)</label>
                        <select name="pic_id" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl font-bold outline-none focus:ring-4 focus:ring-blue-100">
                            <option value="">-- Pilih PIC --</option>
                            <?php foreach($technicians as $t): ?>
                                <option value="<?php echo $t['id']; ?>" <?php echo ($data['pic_id'] ?? '') == $t['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['full_name']); ?> (<?php echo strtoupper($t['role'] ?? ''); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Pilih Kategori Aset</label>
                            <select name="category" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl font-bold outline-none focus:ring-4 focus:ring-blue-100">
                                <option value="Laptop">Laptop / PC</option>
                                <option value="Printer">Printer / Scanner</option>
                                <option value="Monitor">Monitor</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Input S/N</label>
                            <input name="serial_number" placeholder="SN-XXXX" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl font-bold outline-none focus:ring-4 focus:ring-blue-100" type="text"/>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Catatan Verifikasi</label>
                        <textarea name="reasoning" rows="4" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl font-medium outline-none focus:ring-4 focus:ring-blue-100"><?php echo htmlspecialchars($data['admin_reasoning'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" name="update" class="w-full bg-blue-600 text-white font-black py-4 rounded-2xl shadow-lg hover:bg-blue-700 transition uppercase tracking-widest">
                        Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </main>
    <script>
        const tkdnInput = document.getElementById('tkdnInput');
        const hpVal = parseFloat(document.getElementById('hpVal').getAttribute('data-hp')) || 0;
        const heaVal = document.getElementById('heaVal');
        const kpVal = document.getElementById('kpVal');
        const formatRp = (a) => 'Rp ' + Math.round(a).toLocaleString('id-ID');
        tkdnInput.addEventListener('input', function() {
            let tkdn = parseFloat(this.value) || 0;
            const kp = (tkdn / 100) * 0.25;
            const hea = (1 - kp) * hpVal;
            kpVal.innerText = `KP: ${(kp * 100).toFixed(1)}%`;
            heaVal.innerText = formatRp(hea);
        });
    </script>
</body>
</html>
