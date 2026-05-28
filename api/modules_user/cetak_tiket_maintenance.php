<?php
ob_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$data = [];

if ($db) {
    try {
        $submissionRef = $db->collection('submissions')->document($id);
        $submissionSnap = $submissionRef->snapshot();
        if ($submissionSnap->exists()) {
            $data = $submissionSnap->data(); $data['id'] = $submissionSnap->id();
            if (($data['user_id'] ?? '') === $user_id && ($data['type'] ?? '') === 'Maintenance') {
                $userSnap = $db->collection('users')->document($user_id)->snapshot();
                if ($userSnap->exists()) {
                    $u = $userSnap->data();
                    $data['full_name'] = $u['full_name'] ?? 'Unknown';
                    $data['jabatan'] = $u['jabatan'] ?? 'Personnel';
                    $data['department'] = $u['department'] ?? 'Unknown';
                }
            } else { die("Akses ditolak."); }
        } else { $db = null; }
    } catch (Exception $e) { $db = null; }
}

if (!$db && $conn) {
    $id_e = mysqli_real_escape_string($conn, $id);
    $uid_e = mysqli_real_escape_string($conn, $user_id);
    $res = mysqli_query($conn, "SELECT s.*, u.full_name, u.jabatan, u.department FROM submissions s JOIN users u ON s.user_id = u.id WHERE s.id = '$id_e' AND s.user_id = '$uid_e' AND s.type = 'Maintenance' LIMIT 1");
    if ($row = mysqli_fetch_assoc($res)) { $data = $row; }
    else { die("Data tidak ditemukan atau akses ditolak."); }
}

if (empty($data)) die("Data tiket tidak ditemukan.");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <?php 
        $pageTitle = 'SIDIK-TI | Cetak Tiket Maintenance';
        $base_url = '../';
        include __DIR__ . '/../includes/head_meta.php'; 
    ?>
</head>
<body class="bg-surface p-6 md:p-12 font-body text-slate-900 antialiased transition-colors duration-300">
    <script>
        // Force light mode on print preview sheet pages for extreme ink efficiency and perfect legibility
        document.documentElement.classList.remove('dark');
    </script>
    <div class="max-w-3xl mx-auto bg-white p-12 rounded-[3rem] shadow-2xl border border-outline-variant/10 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-12 opacity-[0.03] rotate-12 pointer-events-none"><span class="material-symbols-outlined text-[200px]">handyman</span></div>
        <div class="flex flex-col items-center border-b-2 border-dashed border-slate-100 pb-10 mb-10 relative z-10">
            <div class="w-16 h-16 bg-primary/10 text-primary rounded-2xl flex items-center justify-center mb-6"><span class="material-symbols-outlined text-3xl fill-1">build</span></div>
            <h1 class="font-headline text-3xl font-black tracking-tight text-slate-800 uppercase italic">Maintenance <span class="text-primary italic">Statement</span></h1>
            <div class="mt-4 px-4 py-1.5 bg-slate-50 border border-slate-100 rounded-full"><p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Official Ticket: <span class="text-primary">#<?php echo htmlspecialchars($data['ticket_number'] ?? ''); ?></span></p></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 mb-10">
            <div class="space-y-6">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Informasi Pelapor</p>
                    <p class="text-lg font-bold text-slate-800 leading-tight"><?php echo htmlspecialchars($data['full_name'] ?? ''); ?></p>
                    <p class="text-xs font-medium text-slate-500 mt-1"><?php echo htmlspecialchars($data['jabatan'] ?? ''); ?></p>
                </div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Unit Kerja / Dept</p>
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-primary/5 border border-primary/10 rounded-lg">
                        <span class="material-symbols-outlined text-primary text-sm">apartment</span>
                        <span class="text-xs font-bold text-primary"><?php echo htmlspecialchars($data['department'] ?? ''); ?></span>
                    </div>
                </div>
            </div>
            <div class="flex flex-col md:items-end justify-center">
                <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 w-full md:w-fit text-center">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Timestamp Laporan</p>
                    <p class="text-sm font-black text-slate-800 tracking-tight leading-none italic uppercase">
                        <?php echo date('d F Y', !empty($data['created_at']) ? strtotime($data['created_at']) : time()); ?>
                        <span class="block text-[10px] text-slate-400 mt-1 not-italic font-bold tracking-widest"><?php echo date('H:i:s P', !empty($data['created_at']) ? strtotime($data['created_at']) : time()); ?></span>
                    </p>
                </div>
            </div>
        </div>

        <div class="space-y-8 mb-12">
            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-2"><span class="material-symbols-outlined text-primary text-xl">devices</span><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Objek Pemeliharaan</p></div>
                <p class="font-headline font-black text-xl text-slate-800"><?php echo htmlspecialchars($data['title'] ?? ''); ?></p>
            </div>
            <div class="space-y-3">
                <div class="flex items-center gap-2"><span class="material-symbols-outlined text-primary text-xl">description</span><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Deskripsi Malfungsi / Catatan</p></div>
                <div class="bg-surface p-6 rounded-[2rem] border border-slate-100 italic text-sm text-slate-700 leading-relaxed font-medium">"<?php echo nl2br(htmlspecialchars($data['description'] ?? '')); ?>"</div>
            </div>
            <?php if (!empty($data['attachment_path'])): 
                // Use view_attachment.php secure proxy to ensure image loading bypasses server root path differences
                $img_src = '../config/view_attachment.php?id=' . urlencode($data['ticket_number'] ?? '');
            ?>
            <div class="space-y-3">
                <div class="flex items-center gap-2"><span class="material-symbols-outlined text-primary text-xl">photo_library</span><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Dokumentasi Visual</p></div>
                <div class="rounded-3xl overflow-hidden border border-slate-200 shadow-sm"><img src="<?php echo htmlspecialchars($img_src); ?>" alt="Foto Kerusakan" class="w-full h-auto object-cover max-h-80 grayscale-[0.2] hover:grayscale-0 transition-all"></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="border-t-2 border-dashed border-slate-100 pt-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 mb-12">
                <div class="text-center md:text-left space-y-4">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Verification Signature</p>
                    <div class="w-28 h-28 bg-white rounded-2xl flex items-center justify-center border border-slate-100 mx-auto md:mx-0 group overflow-hidden shadow-sm">
                        <?php 
                            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                            $host     = $_SERVER['HTTP_HOST'];
                            $script   = $_SERVER['SCRIPT_NAME']; 
                            $base_path = rtrim(dirname($script), '/\\');
                            $scan_url  = $protocol . '://' . $host . $base_path . '/scan_result.php?id=' . urlencode($data['id']);
                        ?>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=8&data=<?php echo urlencode($scan_url); ?>" 
                             alt="Scan to view ticket details" 
                             class="w-full h-full p-1 group-hover:scale-110 transition-transform">
                    </div>
                    <p class="text-[9px] font-bold text-slate-300 italic uppercase tracking-tighter leading-tight">Scan QR untuk lihat detail tiket</p>
                </div>
                <div class="flex flex-col md:items-end justify-center">
                    <div class="text-center md:text-right bg-primary/5 p-6 rounded-3xl border border-primary/10 w-full md:w-64">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Status Penanganan</p>
                        <h3 class="font-headline text-2xl font-black text-primary uppercase italic tracking-tighter">
                            <?php echo htmlspecialchars($data['status'] ?? ''); ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="flex flex-col md:flex-row gap-4 no-print">
                <button onclick="window.print()" class="flex-[2] py-5 bg-slate-900 text-white font-headline font-black rounded-2xl shadow-xl hover:bg-primary transition-all uppercase tracking-[0.2em] text-[10px] flex items-center justify-center gap-3"><span class="material-symbols-outlined text-xl">print</span>Generate PDF / Cetak Tiket</button>
                <a href="dashboard_user.php" class="flex-1 py-5 bg-surface text-slate-400 border border-slate-200 font-headline font-black rounded-2xl text-center hover:bg-slate-100 transition-all uppercase tracking-widest text-[10px]">Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
