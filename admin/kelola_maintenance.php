<?php
session_start();
include '../config/database.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_user.php");
    exit();
}

// Ambil ID dari Parameter URL
if (!isset($_GET['id'])) {
    header("Location: dashboard_admin.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Ambil Data Pengajuan & Detail Pemohon
$query = "SELECT s.*, u.full_name as pemohon, u.department, u.jabatan 
          FROM submissions s 
          JOIN users u ON s.user_id = u.id 
          WHERE s.id = '$id' AND s.type = 'Maintenance'";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Data maintenance tidak ditemukan.");
}

// Ambil Data Teknisi untuk pilihan PIC
$technicians = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role = 'staff' OR role = 'admin'");

// Proses Update Status & PIC
if (isset($_POST['update_maintenance'])) {
    $status = $_POST['status'];
    $pic_id = !empty($_POST['pic_id']) ? "'" . mysqli_real_escape_string($conn, $_POST['pic_id']) . "'" : "NULL";
    $note   = mysqli_real_escape_string($conn, $_POST['admin_note']);

    $update = "UPDATE submissions SET 
               status = '$status', 
               pic_id = $pic_id, 
               admin_reasoning = '$note' 
               WHERE id = '$id'";

    if (mysqli_query($conn, $update)) {
        header("Location: dashboard_admin.php?status=success");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Maintenance - SIDIK-TI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-100 flex font-sans">

    <?php include '../includes/navbar_admin.php'; ?>

    <main class="flex-1 p-10">
        <div class="max-w-4xl mx-auto">
            <a href="dashboard_admin.php" class="inline-flex items-center text-sm font-bold text-gray-500 hover:text-blue-600 mb-6 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke Dashboard
            </a>

            <div class="bg-white rounded-[40px] shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-emerald-600 p-8 text-white flex justify-between items-center">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest opacity-80">Tiket Maintenance</p>
                        <h2 class="text-3xl font-black italic">#<?php echo $data['ticket_number']; ?></h2>
                    </div>
                    <div class="bg-white/20 px-6 py-2 rounded-full backdrop-blur-md">
                        <span class="text-sm font-black uppercase italic"><?php echo $data['status']; ?></span>
                    </div>
                </div>

                <div class="p-10 grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-6">
                        <section>
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Informasi Pelapor</h4>
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                <p class="text-sm font-bold text-gray-800"><?php echo $data['pemohon']; ?></p>
                                <p class="text-xs text-gray-500"><?php echo $data['jabatan']; ?> - <?php echo $data['department']; ?></p>
                            </div>
                        </section>

                        <section>
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Detail Perangkat & Keluhan</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between border-b pb-2">
                                    <span class="text-sm text-gray-500 italic">Nama Barang:</span>
                                    <span class="text-sm font-bold text-gray-800"><?php echo $data['title']; ?></span>
                                </div>
                                <div class="bg-emerald-50 p-4 rounded-2xl border border-emerald-100">
                                    <p class="text-xs font-bold text-emerald-700 mb-2 underline">Deskripsi Kerusakan:</p>
                                    <p class="text-sm text-gray-700 italic"><?php echo nl2br(htmlspecialchars($data['description'])); ?></p>
                                </div>
                            </div>
                        </section>

                        <?php if($data['attachment_path']): ?>
                        <section>
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Dokumentasi Foto</h4>
                            <img src="<?php echo $data['attachment_path']; ?>" class="w-full rounded-3xl border border-gray-200 shadow-sm hover:scale-[1.02] transition duration-500">
                        </section>
                        <?php endif; ?>
                    </div>

                    <div class="bg-slate-50 p-8 rounded-[35px] border border-slate-100 h-fit">
                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-6 text-center">Tindakan Administrator</h4>
                        
                        <form action="" method="POST" class="space-y-6">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Ubah Status</label>
                                <select name="status" class="w-full p-4 bg-white border border-gray-200 rounded-2xl font-black text-sm outline-none focus:ring-4 focus:ring-emerald-100 transition">
                                    <option value="Menunggu" <?php echo ($data['status'] == 'Menunggu') ? 'selected' : ''; ?>>Menunggu</option>
                                    <option value="Proses" <?php echo ($data['status'] == 'Proses') ? 'selected' : ''; ?>>Proses Perbaikan</option>
                                    <option value="Selesai" <?php echo ($data['status'] == 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
                                    <option value="Ditolak" <?php echo ($data['status'] == 'Ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Tugaskan Teknisi (PIC)</label>
                                <select name="pic_id" class="w-full p-4 bg-white border border-gray-200 rounded-2xl font-black text-sm outline-none focus:ring-4 focus:ring-emerald-100 transition">
                                    <option value="">-- Belum Ada PIC --</option>
                                    <?php while($t = mysqli_fetch_assoc($technicians)): ?>
                                        <option value="<?php echo $t['id']; ?>" <?php echo ($data['pic_id'] == $t['id']) ? 'selected' : ''; ?>>
                                            <?php echo $t['full_name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Instruksi / Catatan Admin</label>
                                <textarea name="admin_note" rows="4" placeholder="Masukkan instruksi perbaikan untuk teknisi..." class="w-full p-4 bg-white border border-gray-200 rounded-2xl text-sm outline-none focus:ring-4 focus:ring-emerald-100 transition"><?php echo $data['admin_reasoning']; ?></textarea>
                            </div>

                            <button type="submit" name="update_maintenance" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-4 rounded-2xl shadow-lg shadow-emerald-100 transition transform active:scale-95 uppercase tracking-widest text-xs">
                                <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Perubahan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

</body>
</html>