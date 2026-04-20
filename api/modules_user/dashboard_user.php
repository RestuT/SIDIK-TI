<?php
ob_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stat_pending = 0; $stat_process = 0; $stat_done = 0;
$display_name = $_SESSION['full_name'] ?? 'User';
$all_submissions = [];
$announcements = [];

if ($db) {
    try {
        $submissionsRef = $db->collection('submissions')->where('user_id', '=', $user_id);
        $stat_pending = $submissionsRef->where('status', '=', 'Menunggu')->count();
        $stat_process = $submissionsRef->where('status', '=', 'Proses')->count();
        $stat_done = $submissionsRef->where('status', '=', 'Selesai')->count();
        $all_submissions_docs = $submissionsRef->orderBy('created_at', 'DESC')->limit(5)->documents();
        foreach ($all_submissions_docs as $doc) {
            $d = $doc->data(); $d['id'] = $doc->id();
            $all_submissions[] = $d;
        }
        $userSnap = $db->collection('users')->document($user_id)->snapshot();
        if ($userSnap->exists()) $display_name = $userSnap->get('full_name') ?? 'User';
        $annQuery = $db->collection('announcements')->where('is_active', '=', true)->orderBy('created_at', 'DESC')->limit(3)->documents();
        foreach ($annQuery as $doc) { $announcements[] = $doc->data(); }
    } catch (Exception $e) { $db = null; }
}

if (!$db && $conn) {
    $uid_e = mysqli_real_escape_string($conn, $user_id);
    $res_stats = mysqli_query($conn, "SELECT status, COUNT(*) as c FROM submissions WHERE user_id = '$uid_e' GROUP BY status");
    while ($row = mysqli_fetch_assoc($res_stats)) {
        if ($row['status'] === 'Menunggu') $stat_pending = (int)$row['c'];
        elseif ($row['status'] === 'Proses') $stat_process = (int)$row['c'];
        elseif ($row['status'] === 'Selesai') $stat_done = (int)$row['c'];
    }
    $res_subs = mysqli_query($conn, "SELECT * FROM submissions WHERE user_id = '$uid_e' ORDER BY created_at DESC LIMIT 5");
    while ($row = mysqli_fetch_assoc($res_subs)) { $all_submissions[] = $row; }
    $res_user = mysqli_query($conn, "SELECT full_name FROM users WHERE id = '$uid_e' LIMIT 1");
    if ($res_user && $u_row = mysqli_fetch_assoc($res_user)) $display_name = $u_row['full_name'];
    $res_ann = mysqli_query($conn, "SELECT * FROM announcements WHERE is_active = 1 ORDER BY created_at DESC LIMIT 3");
    while ($row = mysqli_fetch_assoc($res_ann)) { $announcements[] = $row; }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <?php 
        $pageTitle = 'SIDIK-TI | Dashboard User';
        $base_url = '../';
        include __DIR__ . '/../includes/head_meta.php'; 
    ?>
</head>
<body class="bg-background text-on-surface min-h-screen selection:bg-primary/20 pb-24 md:pb-0 transition-colors duration-300">
    <?php include __DIR__ . '/../includes/navbar_user.php'; ?>

    <main class="max-w-7xl mx-auto px-6 md:px-10 py-10 space-y-10">
        <!-- Welcome Header -->
        <section class="flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div class="space-y-1">
                <p class="text-on-surface-variant font-medium tracking-wide body-sm">OVERVIEW</p>
                <h1 class="text-4xl font-extrabold text-on-surface tracking-tight">Halo, <?php echo htmlspecialchars($display_name); ?></h1>
                <p class="text-on-surface-variant max-w-lg">Selamat datang kembali di pusat bantuan IT. Pantau status pengajuan dan inventaris Anda di sini.</p>
            </div>
            <div class="flex gap-3">
                <a href="form_maintenance.php" class="group flex items-center justify-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white px-6 py-4 rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:shadow-xl hover:translate-y-[-2px] active:scale-95 transition-all duration-300">
                    <span class="material-symbols-outlined">build</span>
                    Maintenance
                </a>
                <a href="form_pengadaan.php" class="group flex items-center justify-center gap-2 bg-white text-primary border-2 border-primary/20 px-6 py-4 rounded-2xl font-bold hover:bg-indigo-50 transition-all duration-300">
                    <span class="material-symbols-outlined">shopping_cart</span>
                    Pengadaan
                </a>
            </div>
        </section>

        <!-- Bento Stats Grid -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-surface-container-lowest p-8 rounded-3xl shadow-sm border border-transparent hover:border-primary/10 transition-all duration-500 group">
                <div class="flex justify-between items-start mb-6">
                    <div class="p-4 bg-orange-50 text-orange-600 rounded-2xl group-hover:scale-110 transition-transform duration-300">
                        <span class="material-symbols-outlined text-3xl">pending_actions</span>
                    </div>
                    <span class="text-xs font-bold text-orange-500 bg-orange-50 px-3 py-1 rounded-full uppercase tracking-widest">Menunggu</span>
                </div>
                <div class="space-y-1">
                    <h3 class="text-4xl font-black text-on-surface"><?php echo $stat_pending; ?></h3>
                    <p class="text-on-surface-variant font-medium">Tiket Menunggu</p>
                </div>
            </div>
            <div class="bg-surface-container-lowest p-8 rounded-3xl shadow-sm border border-transparent hover:border-primary/10 transition-all duration-500 group">
                <div class="flex justify-between items-start mb-6">
                    <div class="p-4 bg-indigo-50 text-indigo-600 rounded-2xl group-hover:scale-110 transition-transform duration-300">
                        <span class="material-symbols-outlined text-3xl">sync</span>
                    </div>
                    <span class="text-xs font-bold text-indigo-500 bg-indigo-50 px-3 py-1 rounded-full uppercase tracking-widest">Proses</span>
                </div>
                <div class="space-y-1">
                    <h3 class="text-4xl font-black text-on-surface"><?php echo str_pad($stat_process, 2, '0', STR_PAD_LEFT); ?></h3>
                    <p class="text-on-surface-variant font-medium">Sedang Diproses</p>
                </div>
            </div>
            <div class="bg-surface-container-lowest p-8 rounded-3xl shadow-sm border border-transparent hover:border-primary/10 transition-all duration-500 group">
                <div class="flex justify-between items-start mb-6">
                    <div class="p-4 bg-emerald-50 text-emerald-600 rounded-2xl group-hover:scale-110 transition-transform duration-300">
                        <span class="material-symbols-outlined text-3xl">check_circle</span>
                    </div>
                    <span class="text-xs font-bold text-emerald-500 bg-emerald-50 px-3 py-1 rounded-full uppercase tracking-widest">Selesai</span>
                </div>
                <div class="space-y-1">
                    <h3 class="text-4xl font-black text-on-surface"><?php echo $stat_done; ?></h3>
                    <p class="text-on-surface-variant font-medium">Tiket Selesai</p>
                </div>
            </div>
        </section>

        <!-- Main Content: Recent Activity -->
        <section class="bg-surface-container-lowest rounded-xl md:rounded-3xl p-6 md:p-10 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-on-surface tracking-tight">Riwayat Pengajuan Terakhir</h2>
                    <p class="text-on-surface-variant text-sm mt-1">Daftar 5 aktivitas terakhir yang Anda ajukan.</p>
                </div>
                <a href="dashboard_audit.php" class="text-primary font-bold text-sm hover:underline decoration-2 underline-offset-4">Lihat Semua</a>
            </div>
            <div class="overflow-x-auto -mx-6 md:mx-0">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-on-surface-variant text-xs uppercase tracking-[0.15em] border-b border-slate-100">
                            <th class="px-6 py-5 font-bold">Nomor Tiket</th>
                            <th class="px-6 py-5 font-bold">Jenis Pengajuan</th>
                            <th class="px-6 py-5 font-bold">Tanggal</th>
                            <th class="px-6 py-5 font-bold">Status</th>
                            <th class="px-6 py-5 font-bold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php 
                        $count = 0;
                        foreach($all_submissions as $row): 
                            $count++;
                        ?>
                            <tr class="group hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-6">
                                    <span class="font-bold text-indigo-700">#<?php echo htmlspecialchars($row['ticket_number']); ?></span>
                                </td>
                                <td class="px-6 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg <?php echo ($row['type'] ?? '') == 'Maintenance' ? 'bg-amber-50 text-amber-600' : 'bg-indigo-50 text-indigo-600'; ?> flex items-center justify-center">
                                            <span class="material-symbols-outlined text-lg"><?php echo ($row['type'] ?? '') == 'Maintenance' ? 'build' : 'shopping_cart'; ?></span>
                                        </div>
                                        <span class="font-medium text-on-surface"><?php echo htmlspecialchars($row['title'] ?? ''); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-6 text-on-surface-variant"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <td class="px-6 py-6">
                                    <?php 
                                        $rowStatus = $row['status'] ?? '';
                                        $statusClass = "bg-slate-50 text-slate-700 border-slate-100/50";
                                        $dotClass = "bg-slate-500";
                                        if($rowStatus == 'Menunggu') { $statusClass = "bg-orange-50 text-orange-700 border-orange-100/50"; $dotClass = "bg-orange-500 animate-pulse"; }
                                        elseif($rowStatus == 'Proses') { $statusClass = "bg-blue-50 text-blue-700 border-blue-100/50"; $dotClass = "bg-blue-500"; }
                                        elseif($rowStatus == 'Selesai') { $statusClass = "bg-emerald-50 text-emerald-700 border-emerald-100/50"; $dotClass = "bg-emerald-500"; }
                                        elseif($rowStatus == 'Dibatalkan' || $rowStatus == 'Ditolak') { $statusClass = "bg-rose-50 text-rose-700 border-rose-100/50"; $dotClass = "bg-rose-500"; }
                                    ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full <?php echo $statusClass; ?> text-xs font-bold border">
                                        <span class="w-1.5 h-1.5 rounded-full <?php echo $dotClass; ?>"></span>
                                        <?php echo $rowStatus; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-6 text-right">
                                    <a href="<?php echo ($row['type'] ?? '') == 'Maintenance' ? 'cetak_tiket_maintenance.php' : 'cetak_tiket_pengadaan.php'; ?>?id=<?php echo $row['id']; ?>" target="_blank" class="text-slate-400 hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined">print</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if($count == 0): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-20 text-center text-on-surface-variant">Belum ada riwayat pengajuan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Informational Section (Bento Continued) -->
        <section class="grid grid-cols-1 md:grid-cols-2 gap-8 pb-10">
            <div class="relative overflow-hidden rounded-3xl p-10 bg-indigo-900 text-white min-h-[300px] flex flex-col justify-end">
                <div class="absolute top-0 right-0 p-8 opacity-20">
                    <span class="material-symbols-outlined text-[120px]">tips_and_updates</span>
                </div>
                <div class="relative z-10 space-y-4">
                    <h3 class="text-3xl font-bold leading-tight">Butuh bantuan cepat?<br/>Cek Basis Pengetahuan.</h3>
                    <p class="text-indigo-200/80 max-w-sm">Temukan tutorial mandiri untuk masalah umum seperti printer, VPN, dan instalasi software.</p>
                    <a href="knowledge_base.php" class="inline-block w-fit px-6 py-3 rounded-xl bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 font-bold transition-all duration-300">Buka Panduan</a>
                </div>
            </div>
            <div class="bg-surface-container-high rounded-3xl p-10 space-y-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-white rounded-2xl text-primary shadow-sm">
                        <span class="material-symbols-outlined">campaign</span>
                    </div>
                    <h3 class="text-xl font-bold text-on-surface">Pengumuman IT</h3>
                </div>
                <ul class="space-y-4">
                    <?php if(!empty($announcements)): ?>
                        <?php foreach($announcements as $ann): ?>
                            <li class="flex gap-4 items-start w-full">
                                <div class="w-2 h-2 rounded-full <?php echo isset($ann['urgency']) && $ann['urgency'] === 'Tinggi' ? 'bg-red-500 animate-pulse shadow-[0_0_8px_rgba(239,68,68,0.6)]' : 'bg-primary'; ?> mt-2 flex-shrink-0"></div>
                                <div class="space-y-1 w-full relative">
                                    <p class="font-bold text-on-surface text-sm break-words"><?php echo htmlspecialchars($ann['title']); ?></p>
                                    <p class="text-on-surface-variant text-[11px] leading-snug break-words pr-2"><?php echo htmlspecialchars($ann['content'] ?? ''); ?></p>
                                    <p class="text-slate-400 text-[9px] font-bold uppercase tracking-wider mt-1"><?php echo isset($ann['created_at']) ? date('d M Y | H:i', strtotime($ann['created_at'])) : ''; ?></p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="text-center text-slate-400 text-xs font-bold uppercase tracking-widest py-8 opacity-50">
                            <span class="material-symbols-outlined block text-3xl mb-2 mx-auto">check_circle</span>
                            Belum Ada Pengumuman
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../includes/bottom_nav_user.php'; ?>
</body>
</html>
