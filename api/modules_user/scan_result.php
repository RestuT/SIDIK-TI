<?php
require_once __DIR__ . '/../config/database.php';

// Halaman ini bisa diakses publik (tanpa login) untuk keperluan scan verifikasi
// namun hanya membaca data, tidak memodifikasi apapun.

$ticket_id = $_GET['id'] ?? '';
$type = $_GET['type'] ?? ''; // 'pengadaan' atau 'maintenance'

if (empty($ticket_id)) {
    http_response_code(400);
    die("ID Tiket tidak valid.");
}

$data = [];
$user_data = [];
$error = null;

try {
    $submissionRef = $db->collection('submissions')->document($ticket_id);
    $submissionSnap = $submissionRef->snapshot();

    if (!$submissionSnap->exists()) {
        $error = "Tiket tidak ditemukan dalam sistem.";
    } else {
        $data = $submissionSnap->data();
        $data['id'] = $submissionSnap->id();

        // Ambil profil pengguna berdasarkan user_id dalam tiket
        if (!empty($data['user_id'])) {
            $userSnap = $db->collection('users')->document($data['user_id'])->snapshot();
            if ($userSnap->exists()) {
                $user_data = $userSnap->data();
            }
        }
    }
} catch (Exception $e) {
    $error = "Gagal memuat data tiket: " . $e->getMessage();
}

$is_maintenance = ($data['type'] ?? '') === 'Maintenance';
$is_pengadaan   = ($data['type'] ?? '') === 'Pengadaan';

// Warna tema berdasarkan jenis tiket
$accent = $is_maintenance ? 'emerald' : 'orange';
$accent_hex = $is_maintenance ? '#10b981' : '#f97316';
$accent_bg_class = $is_maintenance ? 'bg-emerald-50 border-emerald-100 text-emerald-700' : 'bg-orange-50 border-orange-100 text-orange-700';
$accent_text = $is_maintenance ? 'text-emerald-600' : 'text-orange-600';
$accent_icon_bg = $is_maintenance ? 'bg-emerald-100 text-emerald-600' : 'bg-orange-100 text-orange-600';
$accent_icon = $is_maintenance ? 'build' : 'receipt_long';
$accent_label = $is_maintenance ? 'Maintenance Statement' : 'Procurement Evidence';

// Status warna
$status = $data['status'] ?? '';
$status_class = match(true) {
    $status === 'Selesai' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
    $status === 'Diproses' => 'bg-blue-100 text-blue-700 border-blue-200',
    $status === 'Menunggu' => 'bg-amber-100 text-amber-700 border-amber-200',
    $status === 'Ditolak' => 'bg-red-100 text-red-700 border-red-200',
    default => 'bg-slate-100 text-slate-700 border-slate-200',
};

$status_icon = match(true) {
    $status === 'Selesai' => 'task_alt',
    $status === 'Diproses' => 'pending',
    $status === 'Menunggu' => 'hourglass_empty',
    $status === 'Ditolak' => 'cancel',
    default => 'help',
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIDIK-TI | Scan Result: <?php echo htmlspecialchars($data['ticket_number'] ?? 'Unknown'); ?></title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#3525cd",
                        "surface": "#f7f9fb",
                        "surface-container-low": "#f2f4f6",
                        "outline-variant": "#c7c4d8",
                        "on-surface": "#191c1e",
                        "on-surface-variant": "#464555",
                        "on-primary": "#ffffff",
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

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: 0.4; }
            50% { transform: scale(1.05); opacity: 0.2; }
            100% { transform: scale(1); opacity: 0.4; }
        }
        .animate-fade-in { animation: fadeInUp 0.6s ease-out forwards; }
        .animate-delay-1 { animation-delay: 0.1s; opacity: 0; }
        .animate-delay-2 { animation-delay: 0.2s; opacity: 0; }
        .animate-delay-3 { animation-delay: 0.3s; opacity: 0; }
        .animate-delay-4 { animation-delay: 0.4s; opacity: 0; }
        .animate-delay-5 { animation-delay: 0.5s; opacity: 0; }
        .scan-glow { animation: pulse-ring 2s ease-in-out infinite; }
    </style>
</head>
<body class="bg-surface font-body text-on-surface antialiased min-h-screen">

<?php if ($error): ?>
    <!-- Error State -->
    <div class="min-h-screen flex items-center justify-center px-6">
        <div class="max-w-md w-full text-center">
            <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <span class="material-symbols-outlined text-5xl text-red-500 fill-1">qr_code_scanner</span>
            </div>
            <h1 class="font-headline text-2xl font-black text-on-surface mb-2 uppercase italic">Scan Gagal</h1>
            <p class="text-sm text-on-surface-variant"><?php echo htmlspecialchars($error); ?></p>
            <a href="../modules_user/dashboard_user.php" class="mt-8 inline-block px-6 py-3 bg-primary text-white rounded-2xl font-bold text-sm">
                Kembali ke Dashboard
            </a>
        </div>
    </div>

<?php else: ?>

    <!-- Hero Header -->
    <header class="relative overflow-hidden bg-primary pt-12 pb-20 px-6">
        <!-- Decorative circles -->
        <div class="absolute -top-16 -right-16 w-64 h-64 bg-white/5 rounded-full scan-glow"></div>
        <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-white/5 rounded-full scan-glow" style="animation-delay: 1s;"></div>
        
        <div class="max-w-xl mx-auto relative z-10 text-center text-white animate-fade-in">
            <!-- QR Scan Badge -->
            <div class="inline-flex items-center gap-2 bg-white/15 backdrop-blur px-4 py-2 rounded-full mb-6">
                <span class="material-symbols-outlined text-sm fill-1">qr_code_scanner</span>
                <span class="text-[10px] font-black uppercase tracking-widest">QR Scan Result</span>
            </div>

            <!-- Icon -->
            <div class="w-20 h-20 mx-auto rounded-3xl bg-white/15 backdrop-blur flex items-center justify-center mb-5">
                <span class="material-symbols-outlined text-4xl fill-1"><?php echo $accent_icon; ?></span>
            </div>

            <!-- Title -->
            <h1 class="font-headline text-3xl font-black tracking-tight uppercase italic mb-2">
                <?php echo htmlspecialchars($accent_label); ?>
            </h1>
            <p class="text-white/60 text-xs font-bold uppercase tracking-widest">
                Sistem Informasi & Distribusi Inventaris Komputer – TI
            </p>

            <!-- Ticket Number -->
            <div class="mt-6 bg-white/10 backdrop-blur px-5 py-3 rounded-2xl inline-block">
                <p class="text-[10px] font-black uppercase tracking-widest text-white/60 mb-1">No. Tiket</p>
                <p class="font-headline font-black text-lg tracking-tight">
                    #<?php echo htmlspecialchars($data['ticket_number'] ?? 'N/A'); ?>
                </p>
            </div>
        </div>
    </header>

    <!-- Content Cards -->
    <div class="max-w-xl mx-auto px-4 -mt-10 pb-12 space-y-4">

        <!-- Status Card (Floating over header) -->
        <div class="bg-white rounded-3xl shadow-xl shadow-primary/10 p-5 flex items-center gap-4 border border-outline-variant/10 animate-fade-in animate-delay-1">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center <?php echo $accent_icon_bg; ?> shrink-0">
                <span class="material-symbols-outlined text-2xl fill-1"><?php echo $status_icon; ?></span>
            </div>
            <div class="flex-1">
                <p class="text-[10px] font-black text-on-surface-variant uppercase tracking-widest">Status Tiket</p>
                <p class="font-headline font-black text-2xl uppercase italic tracking-tight"><?php echo htmlspecialchars($status); ?></p>
            </div>
            <div class="shrink-0">
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-[10px] font-black border uppercase tracking-widest <?php echo $status_class; ?>">
                    <?php echo htmlspecialchars($status); ?>
                </span>
            </div>
        </div>

        <!-- Pemohon Info -->
        <div class="bg-white rounded-3xl shadow-sm border border-outline-variant/10 overflow-hidden animate-fade-in animate-delay-2">
            <div class="px-5 py-4 border-b border-outline-variant/5 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-lg fill-1">person</span>
                <h2 class="font-headline font-black text-sm uppercase tracking-widest text-on-surface">Informasi Pemohon</h2>
            </div>
            <div class="p-5 space-y-4">
                <!-- Name -->
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-surface-container-low flex items-center justify-center text-primary font-black text-sm shrink-0">
                        <?php echo strtoupper(substr($user_data['full_name'] ?? $data['full_name'] ?? 'U', 0, 1)); ?>
                    </div>
                    <div>
                        <p class="font-bold text-on-surface text-base leading-tight">
                            <?php echo htmlspecialchars($user_data['full_name'] ?? $data['full_name'] ?? 'Tidak Diketahui'); ?>
                        </p>
                        <p class="text-xs text-on-surface-variant mt-0.5">
                            <?php echo htmlspecialchars($user_data['jabatan'] ?? $data['jabatan'] ?? '—'); ?>
                        </p>
                    </div>
                </div>
                <!-- Divider -->
                <hr class="border-outline-variant/10">
                <!-- Department -->
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-on-surface-variant">apartment</span>
                    <div>
                        <p class="text-[9px] font-black text-on-surface-variant uppercase tracking-widest">Unit Kerja / Departemen</p>
                        <p class="font-bold text-sm text-on-surface mt-0.5">
                            <?php echo htmlspecialchars($user_data['department'] ?? $data['department'] ?? '—'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Tiket -->
        <div class="bg-white rounded-3xl shadow-sm border border-outline-variant/10 overflow-hidden animate-fade-in animate-delay-3">
            <div class="px-5 py-4 border-b border-outline-variant/5 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-lg fill-1"><?php echo $accent_icon; ?></span>
                <h2 class="font-headline font-black text-sm uppercase tracking-widest text-on-surface">
                    Detail <?php echo $is_maintenance ? 'Maintenance' : 'Pengadaan'; ?>
                </h2>
            </div>
            <div class="p-5 space-y-4">
                <!-- Title -->
                <div>
                    <p class="text-[9px] font-black text-on-surface-variant uppercase tracking-widest mb-1">
                        <?php echo $is_maintenance ? 'Objek Perawatan' : 'Nama Item / Perangkat'; ?>
                    </p>
                    <p class="font-headline font-black text-lg text-on-surface leading-tight">
                        <?php echo htmlspecialchars($data['title'] ?? '—'); ?>
                    </p>
                </div>

                <?php if ($is_pengadaan && !empty($data['urgency'])): ?>
                <div>
                    <p class="text-[9px] font-black text-on-surface-variant uppercase tracking-widest mb-2">Level Urgensi</p>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl <?php echo $accent_bg_class; ?> text-xs font-black uppercase tracking-widest border">
                        <span class="material-symbols-outlined text-sm">bolt</span>
                        <?php echo htmlspecialchars($data['urgency']); ?>
                    </span>
                </div>
                <?php endif; ?>

                <!-- Description -->
                <div>
                    <p class="text-[9px] font-black text-on-surface-variant uppercase tracking-widest mb-2">
                        <?php echo $is_maintenance ? 'Deskripsi Malfungsi' : 'Keterangan Pengajuan'; ?>
                    </p>
                    <div class="bg-surface-container-low p-4 rounded-2xl text-sm text-on-surface-variant leading-relaxed italic border border-outline-variant/10">
                        "<?php echo nl2br(htmlspecialchars($data['description'] ?? '—')); ?>"
                    </div>
                </div>

                <?php if ($is_pengadaan && !empty($data['estimasi'])): ?>
                <!-- Budget -->
                <div class="bg-orange-50 rounded-2xl p-4 border border-orange-100 flex items-center justify-between">
                    <div>
                        <p class="text-[9px] font-black text-orange-500 uppercase tracking-widest mb-0.5">Total Anggaran Diajukan</p>
                        <p class="text-[9px] text-orange-400 italic">*Incl. PPN + Biaya Administrasi</p>
                    </div>
                    <p class="font-headline font-black text-2xl text-orange-600 tracking-tight">
                        Rp <?php echo number_format((float)$data['estimasi'], 0, ',', '.'); ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Timestamp & Meta -->
        <div class="bg-white rounded-3xl shadow-sm border border-outline-variant/10 overflow-hidden animate-fade-in animate-delay-4">
            <div class="px-5 py-4 border-b border-outline-variant/5 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-lg fill-1">schedule</span>
                <h2 class="font-headline font-black text-sm uppercase tracking-widest text-on-surface">Timestamp & Verifikasi</h2>
            </div>
            <div class="p-5 grid grid-cols-2 gap-4">
                <!-- Tanggal Pengajuan -->
                <div>
                    <p class="text-[9px] font-black text-on-surface-variant uppercase tracking-widest mb-1">Tanggal Pengajuan</p>
                    <p class="font-bold text-sm text-on-surface">
                        <?php echo !empty($data['created_at']) ? date('d M Y', strtotime($data['created_at'])) : '—'; ?>
                    </p>
                    <p class="text-[10px] text-on-surface-variant font-medium">
                        <?php echo !empty($data['created_at']) ? date('H:i:s', strtotime($data['created_at'])) : ''; ?>
                    </p>
                </div>
                <!-- Tipe Tiket -->
                <div>
                    <p class="text-[9px] font-black text-on-surface-variant uppercase tracking-widest mb-1">Tipe Layanan</p>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl <?php echo $accent_bg_class; ?> text-xs font-black border uppercase">
                        <?php echo htmlspecialchars($data['type'] ?? '—'); ?>
                    </span>
                </div>
                <!-- Ticket ID -->
                <div class="col-span-2 bg-surface-container-low rounded-2xl p-3 border border-outline-variant/10">
                    <p class="text-[9px] font-black text-on-surface-variant uppercase tracking-widest mb-1">Document ID</p>
                    <p class="font-mono text-xs text-on-surface-variant break-all"><?php echo htmlspecialchars($data['id'] ?? '—'); ?></p>
                </div>
            </div>
        </div>

        <?php if (!empty($data['attachment_path'])): ?>
        <!-- Attachment Photo -->
        <div class="bg-white rounded-3xl shadow-sm border border-outline-variant/10 overflow-hidden animate-fade-in animate-delay-5">
            <div class="px-5 py-4 border-b border-outline-variant/5 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-lg fill-1">photo_camera</span>
                <h2 class="font-headline font-black text-sm uppercase tracking-widest text-on-surface">Dokumentasi Visual</h2>
            </div>
            <div class="p-3">
                <img src="<?php echo htmlspecialchars($data['attachment_path']); ?>"
                     alt="Foto Kerusakan"
                     class="w-full rounded-2xl object-cover max-h-72">
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer Branding -->
        <div class="animate-fade-in animate-delay-5 text-center pt-4 pb-6">
            <div class="inline-flex items-center gap-2 bg-white border border-outline-variant/10 px-5 py-3 rounded-full shadow-sm">
                <span class="material-symbols-outlined text-primary text-sm fill-1">verified_user</span>
                <p class="text-[10px] font-black text-on-surface-variant uppercase tracking-widest">
                    SIDIK-TI Official Document Verified
                </p>
            </div>
            <p class="text-[9px] text-on-surface-variant mt-3 opacity-50">
                Dipindai pada <?php echo date('d M Y, H:i:s'); ?> WIB
            </p>
        </div>

    </div>

<?php endif; ?>

</body>
</html>
