<?php
ob_start();
require_once __DIR__ . '/../config/database.php';

// Proteksi: Hasil scan tiket harus login
if (!isset($_SESSION['user_id'])) {
    $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    header("Location: ../auth/login_user.php?redirect=" . urlencode($current_url));
    exit();
}

// Ambil ID tiket dari URL
$ticket_id = $_GET['id'] ?? '';
$data      = [];
$user_data = [];
$error     = null;

if (empty($ticket_id)) {
    $error = "ID tiket tidak tersedia. Pastikan QR code dipindai dengan benar.";
} else {
    if ($db) {
        try {
            $submissionSnap = $db->collection('submissions')->document($ticket_id)->snapshot();
            if ($submissionSnap->exists()) {
                $data       = $submissionSnap->data();
                $data['id'] = $submissionSnap->id();
                if (!empty($data['user_id'])) {
                    $userSnap = $db->collection('users')->document($data['user_id'])->snapshot();
                    if ($userSnap->exists()) { $user_data = $userSnap->data(); }
                }
            }
        } catch (Exception $e) { $db = null; }
    }

    if (!$db && $conn) {
        $id_e = mysqli_real_escape_string($conn, $ticket_id);
        $res = mysqli_query($conn, "SELECT s.*, u.full_name, u.jabatan, u.department as u_dept FROM submissions s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = '$id_e' LIMIT 1");
        if ($row = mysqli_fetch_assoc($res)) {
            $data = $row;
            $user_data = [
                'full_name' => $row['full_name'],
                'jabatan' => $row['jabatan'],
                'department' => $row['u_dept']
            ];
        }
    }

    if (empty($data)) {
        $error = "Tiket tidak ditemukan dalam sistem. ID: " . htmlspecialchars($ticket_id);
    }
}

// --- Theming ---
$is_maintenance = ($data['type'] ?? '') === 'Maintenance';
$is_pengadaan   = ($data['type'] ?? '') === 'Pengadaan';

$accent_icon_bg  = $is_maintenance ? 'bg-emerald-100 text-emerald-600'   : 'bg-orange-100 text-orange-600';
$accent_badge    = $is_maintenance ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-orange-50 text-orange-700 border-orange-200';
$accent_section  = $is_maintenance ? 'text-emerald-600' : 'text-orange-600';
$accent_box      = $is_maintenance ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-100 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-300' : 'bg-orange-50 dark:bg-orange-500/10 border-orange-100 dark:border-orange-500/20 text-orange-700 dark:text-orange-300';
$accent_icon     = $is_maintenance ? 'build' : 'receipt_long';
$accent_label    = $is_maintenance ? 'Maintenance Statement' : 'Procurement Evidence';
$header_from     = $is_maintenance ? 'from-emerald-700' : 'from-orange-600';
$header_to       = $is_maintenance ? 'to-emerald-500'   : 'to-amber-500';

$status = $data['status'] ?? '';
$status_config = match(true) {
    $status === 'Selesai'   => ['bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20', 'task_alt'],
    $status === 'Diproses'  => ['bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-500/20', 'pending'],
    $status === 'Menunggu'  => ['bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/20', 'hourglass_empty'],
    $status === 'Ditolak'   => ['bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 border-red-200 dark:border-red-500/20', 'cancel'],
    default                 => ['bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700', 'help'],
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIDIK-TI | Scan Result <?php echo htmlspecialchars($data['ticket_number'] ?? ''); ?></title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        headline: ["Plus Jakarta Sans"],
                        body: ["Inter"],
                    },
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

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .card { animation: fadeUp 0.5s ease-out both; }
        .card:nth-child(1) { animation-delay: 0.05s; }
        .card:nth-child(2) { animation-delay: 0.12s; }
        .card:nth-child(3) { animation-delay: 0.19s; }
        .card:nth-child(4) { animation-delay: 0.26s; }
        .card:nth-child(5) { animation-delay: 0.33s; }
        .card:nth-child(6) { animation-delay: 0.40s; }
    </style>

    <script>
        // Inject base tag to preserve relative link resolution
        if (!document.querySelector('base')) {
            var base = document.createElement('base');
            base.href = window.location.href.split('?')[0];
            document.head.appendChild(base);
        }
        // Mask URL to Pretty Path
        if (window.history.replaceState) {
            var path = window.location.pathname;
            var search = window.location.search;
            if (path.includes('/api/')) {
                window.history.replaceState(null, null, path.replace('/api/', '/') + search);
            }
        }
    </script>
</head>
<body class="bg-slate-100 dark:bg-slate-950 font-body text-slate-900 dark:text-slate-100 antialiased min-h-screen transition-colors duration-300">

<?php if ($error): ?>
<!-- ======================== ERROR STATE ======================== -->
<div class="min-h-screen flex items-center justify-center px-6 py-12">
    <div class="max-w-sm w-full text-center bg-white dark:bg-slate-900 p-8 rounded-[2rem] shadow-xl border border-red-100 dark:border-red-900/30">
        <div class="w-24 h-24 bg-red-100 dark:bg-red-500/10 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner border border-red-200 dark:border-red-500/20">
            <span class="material-symbols-outlined text-5xl text-red-400">qr_code_2_add</span>
        </div>
        <h1 class="font-headline text-2xl font-black text-slate-800 dark:text-white mb-3 uppercase italic">Tiket Tidak Ditemukan</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed"><?php echo htmlspecialchars($error); ?></p>
        <div class="mt-8 p-4 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-2xl text-left text-xs text-amber-700 dark:text-amber-400">
            <p class="font-bold mb-1">💡 Pastikan:</p>
            <ul class="list-disc ml-4 space-y-1">
                <li>QR code dipindai secara penuh dan jelas</li>
                <li>QR code berasal dari tiket SIDIK-TI yang valid</li>
                <li>Koneksi internet aktif</li>
            </ul>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ======================== BERHASIL ======================== -->

<!-- HERO HEADER -->
<header class="bg-gradient-to-br <?php echo $header_from . ' ' . $header_to; ?> text-white pt-12 pb-24 px-5 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle at 80% 20%, white 1px, transparent 1px); background-size: 32px 32px;"></div>
    <div class="max-w-lg mx-auto relative z-10">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-sm px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest mb-5">
            <span class="material-symbols-outlined text-sm fill-1">qr_code_scanner</span>
            QR Scan Result • SIDIK-TI
        </div>

        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-3xl fill-1"><?php echo $accent_icon; ?></span>
            </div>
            <div>
                <h1 class="font-headline text-2xl font-black tracking-tight uppercase italic leading-tight">
                    <?php echo htmlspecialchars($accent_label); ?>
                </h1>
                <p class="text-white/70 text-xs font-semibold mt-0.5 uppercase tracking-widest">
                    SIDIK-TI Digital Verification
                </p>
            </div>
        </div>

        <!-- Ticket Number Pill -->
        <div class="mt-6 bg-white/15 backdrop-blur px-4 py-3 rounded-2xl inline-flex items-center gap-3">
            <span class="material-symbols-outlined text-sm fill-1">confirmation_number</span>
            <div>
                <p class="text-[9px] font-black uppercase tracking-widest text-white/60">No. Tiket</p>
                <p class="font-headline font-black text-base tracking-tight leading-none">
                    #<?php echo htmlspecialchars($data['ticket_number'] ?? 'N/A'); ?>
                </p>
            </div>
        </div>
    </div>
</header>

<!-- CONTENT CARDS -->
<div class="max-w-lg mx-auto px-4 -mt-14 pb-12 space-y-3">

    <!-- 1. STATUS CARD -->
    <div class="card bg-white dark:bg-slate-900 rounded-3xl shadow-lg shadow-slate-200 dark:shadow-none p-5 flex items-center gap-4 border border-transparent dark:border-slate-800">
        <div class="w-14 h-14 rounded-2xl <?php echo $accent_icon_bg; ?> flex items-center justify-center shrink-0 shadow-sm">
            <span class="material-symbols-outlined text-2xl fill-1"><?php echo $status_config[1]; ?></span>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Status Tiket</p>
            <p class="font-headline font-black text-xl text-slate-800 dark:text-white uppercase italic tracking-tight leading-tight truncate">
                <?php echo htmlspecialchars($status ?: '—'); ?>
            </p>
        </div>
        <span class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-black border uppercase tracking-wide <?php echo $status_config[0]; ?>">
            <span class="material-symbols-outlined text-sm fill-1"><?php echo $status_config[1]; ?></span>
            <?php echo htmlspecialchars($status ?: '—'); ?>
        </span>
    </div>

    <!-- 2. PEMOHON -->
    <div class="card bg-white dark:bg-slate-900 rounded-3xl shadow-sm overflow-hidden border border-transparent dark:border-slate-800">
        <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-600 dark:text-slate-400 text-lg fill-1">badge</span>
            <h2 class="font-headline font-black text-xs text-slate-700 dark:text-slate-300 uppercase tracking-widest">Informasi Pemohon</h2>
        </div>
        <div class="p-5 space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-headline font-black text-slate-600 dark:text-slate-300 shrink-0">
                    <?php echo strtoupper(substr($user_data['full_name'] ?? $data['full_name'] ?? 'U', 0, 1)); ?>
                </div>
                <div>
                    <p class="font-bold text-slate-800 dark:text-white leading-tight">
                        <?php echo htmlspecialchars($user_data['full_name'] ?? $data['full_name'] ?? '—'); ?>
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        <?php echo htmlspecialchars($user_data['jabatan'] ?? $data['jabatan'] ?? '—'); ?>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 pt-1 border-t border-slate-50 dark:border-slate-800/50">
                <span class="material-symbols-outlined text-slate-400 text-lg">apartment</span>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Departemen / Unit Kerja</p>
                    <p class="font-bold text-sm text-slate-800 dark:text-white">
                        <?php echo htmlspecialchars($user_data['department'] ?? $data['department'] ?? '—'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. DETAIL TIKET -->
    <div class="card bg-white dark:bg-slate-900 rounded-3xl shadow-sm overflow-hidden border border-transparent dark:border-slate-800">
        <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-600 dark:text-slate-400 text-lg fill-1"><?php echo $accent_icon; ?></span>
            <h2 class="font-headline font-black text-xs text-slate-700 dark:text-slate-300 uppercase tracking-widest">
                Detail <?php echo $is_maintenance ? 'Pemeliharaan' : 'Pengadaan'; ?>
            </h2>
        </div>
        <div class="p-5 space-y-4">
            <!-- Nama Item -->
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">
                    <?php echo $is_maintenance ? 'Objek Pemeliharaan' : 'Nama Item / Perangkat'; ?>
                </p>
                <p class="font-headline font-black text-lg text-slate-800 dark:text-white leading-tight">
                    <?php echo htmlspecialchars($data['title'] ?? '—'); ?>
                </p>
            </div>

            <?php if ($is_pengadaan && !empty($data['urgency'])): ?>
            <!-- Urgensi -->
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Level Urgensi</p>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest border <?php echo $accent_badge; ?>">
                    <span class="material-symbols-outlined text-sm fill-1">bolt</span>
                    <?php echo htmlspecialchars($data['urgency']); ?>
                </span>
            </div>
            <?php endif; ?>

            <!-- Deskripsi -->
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">
                    <?php echo $is_maintenance ? 'Deskripsi Kerusakan' : 'Keterangan'; ?>
                </p>
                <div class="<?php echo $accent_box; ?> border rounded-2xl p-4 text-sm leading-relaxed font-medium italic shadow-inner">
                    "<?php echo nl2br(htmlspecialchars($data['description'] ?? '—')); ?>"
                </div>
            </div>

            <?php if ($is_pengadaan && !empty($data['estimasi'])): ?>
            <!-- Total Biaya -->
            <div class="bg-orange-50 dark:bg-orange-500/10 border border-orange-100 dark:border-orange-500/20 rounded-2xl p-4 flex items-center justify-between">
                <div>
                    <p class="text-[9px] font-black text-orange-500 dark:text-orange-400 uppercase tracking-widest">Total Anggaran</p>
                    <p class="text-[9px] text-orange-400 dark:text-orange-500 italic mt-0.5">Termasuk PPN + Administrasi</p>
                </div>
                <p class="font-headline font-black text-2xl text-orange-600 dark:text-orange-400 tracking-tight">
                    Rp <?php echo number_format((float)$data['estimasi'], 0, ',', '.'); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 4. TIMESTAMP -->
    <div class="card bg-white dark:bg-slate-900 rounded-3xl shadow-sm overflow-hidden border border-transparent dark:border-slate-800">
        <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-600 dark:text-slate-400 text-lg fill-1">schedule</span>
            <h2 class="font-headline font-black text-xs text-slate-700 dark:text-slate-300 uppercase tracking-widest">Timestamp & Meta</h2>
        </div>
        <div class="p-5 grid grid-cols-2 gap-4">
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Tanggal Pengajuan</p>
                <p class="font-bold text-sm text-slate-800 dark:text-white">
                    <?php echo !empty($data['created_at']) ? date('d M Y', strtotime($data['created_at'])) : '—'; ?>
                </p>
                <p class="text-xs text-slate-400">
                    <?php echo !empty($data['created_at']) ? date('H:i:s', strtotime($data['created_at'])) : ''; ?>
                </p>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Jenis Layanan</p>
                <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest border <?php echo $accent_badge; ?>">
                    <?php echo htmlspecialchars($data['type'] ?? '—'); ?>
                </span>
            </div>
            <div class="col-span-2">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Document ID</p>
                <p class="font-mono text-[11px] text-slate-500 bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl px-3 py-2 break-all">
                    <?php echo htmlspecialchars($data['id'] ?? '—'); ?>
                </p>
            </div>
        </div>
    </div>

    <?php if (!empty($data['attachment_path'])): 
        $img_src = $data['attachment_path'];
        if (strpos($img_src, '../uploads/') === 0) $img_src = '../' . $img_src;
    ?>
    <!-- 5. FOTO KERUSAKAN -->
    <div class="card bg-white dark:bg-slate-900 rounded-3xl shadow-sm overflow-hidden border border-transparent dark:border-slate-800">
        <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-600 dark:text-slate-400 text-lg fill-1">photo_camera</span>
            <h2 class="font-headline font-black text-xs text-slate-700 dark:text-slate-300 uppercase tracking-widest">Dokumentasi Visual</h2>
        </div>
        <div class="p-3">
            <img src="<?php echo htmlspecialchars($img_src); ?>"
                 alt="Foto Kerusakan"
                 class="w-full rounded-2xl object-cover max-h-64">
        </div>
    </div>
    <?php endif; ?>

    <!-- 6. FOOTER BRANDING -->
    <div class="card text-center py-6">
        <div class="inline-flex items-center gap-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-4 py-2.5 rounded-full shadow-sm mb-3">
            <span class="material-symbols-outlined text-emerald-500 text-sm fill-1">verified_user</span>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">SIDIK-TI Official Document</p>
        </div>
        <p class="text-[9px] text-slate-400">
            Dipindai pada <?php echo date('d M Y, H:i:s'); ?> WIB
        </p>
    </div>

</div>

<?php endif; ?>

</body>
</html>

