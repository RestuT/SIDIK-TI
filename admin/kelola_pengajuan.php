<?php
session_start();
include '../config/database.php'; 

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_admin.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Ambil data pengajuan, nama pemohon, dan sisa anggaran
// Pastikan kolom 'estimasi' diambil untuk keperluan update budget
$query = "SELECT s.*, u.full_name, b.total_limit, b.used_amount, (b.total_limit - b.used_amount) as sisa_pagu 
          FROM submissions s 
          JOIN users u ON s.user_id = u.id 
          LEFT JOIN budget_config b ON b.fiscal_year = 2026
          WHERE s.id = $id";

$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Data tidak ditemukan.");
}

// Ambil daftar teknisi
$technicians = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role = 'technician' OR role = 'admin'");

if (isset($_POST['update'])) {
    $status = $_POST['status'];
    $pic = !empty($_POST['pic_id']) ? "'" . mysqli_real_escape_string($conn, $_POST['pic_id']) . "'" : "NULL";
    $reason = mysqli_real_escape_string($conn, $_POST['reasoning']);

    // --- LOGIKA OTOMATIS UPDATE BUDGET ---
    // Hanya berjalan jika status berubah menjadi 'Selesai' dan tipe-nya 'Pengadaan'
    // Dan pastikan sebelumnya statusnya BUKAN 'Selesai' (agar tidak double subtract jika di-save 2x)
    if ($status === 'Selesai' && $data['type'] === 'Pengadaan' && $data['status'] !== 'Selesai') {
        $biaya_pengadaan = $data['estimasi'];
        $tahun_fiskal = 2026; // Bisa menggunakan date('Y', strtotime($data['created_at']))

        // Jalankan query update ke budget_config
        $update_budget = "UPDATE budget_config 
                          SET used_amount = used_amount + $biaya_pengadaan 
                          WHERE fiscal_year = $tahun_fiskal";
        mysqli_query($conn, $update_budget);
    }
    // --------------------------------------

    $update = "UPDATE submissions SET status = '$status', pic_id = $pic, admin_reasoning = '$reason' WHERE id = $id";
    
    if (mysqli_query($conn, $update)) {
        header("Location: dashboard_admin.php?status=updated");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Validasi Pengadaan - #<?php echo $data['ticket_number']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-50 p-6 md:p-10">
    <div class="max-w-4xl mx-auto bg-white rounded-[35px] shadow-xl overflow-hidden border border-slate-100">
        
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
                        <p class="text-[10px] font-black text-blue-400 uppercase mb-1">Sisa Pagu 2026</p>
                        <p class="font-bold text-blue-700 text-sm">Rp <?php echo number_format($data['sisa_pagu'], 0, ',', '.'); ?></p>
                    </div>
                </div>

                <?php if($data['attachment_path']): ?>
                <a href="<?php echo $data['attachment_path']; ?>" target="_blank" class="block text-center p-4 bg-slate-800 text-white rounded-2xl font-bold hover:bg-blue-600 transition">
                    <i class="fa-solid fa-file-pdf mr-2"></i> Lihat Dokumen KAK / Justifikasi
                </a>
                <?php endif; ?>
            </div>
            <?php if($data['is_appealed'] == 1): ?>
    <div class="bg-red-50 p-6 rounded-3xl border border-red-100 mb-6">
        <h4 class="text-xs font-black text-red-600 uppercase mb-2 tracking-widest italic">
            <i class="fa-solid fa-circle-exclamation"></i> Pengajuan ini adalah Aju Banding
        </h4>
        <p class="text-sm text-gray-700 font-medium">"<?php echo $data['appeal_reason']; ?>"</p>
    </div>
<?php endif; ?>

            <form action="" method="POST" class="space-y-6 border-l border-slate-100 pl-0 md:pl-8">
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
                        <?php while($t = mysqli_fetch_assoc($technicians)): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo $data['pic_id'] == $t['id'] ? 'selected' : ''; ?>>
                                <?php echo $t['full_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Catatan Verifikasi (Reasoning)</label>
                    <textarea name="reasoning" rows="4" placeholder="Berikan alasan atau instruksi tambahan..." class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl font-medium outline-none focus:ring-4 focus:ring-blue-100"><?php echo $data['admin_reasoning']; ?></textarea>
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
</body>
</html>