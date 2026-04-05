<?php
ob_start();

require_once __DIR__ . '/../config/database.php';

// Proteksi: Pastikan user login dan ada parameter id
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    // Ambil data pengajuan maintenance dari Firestore
    $submissionRef = $db->collection('submissions')->document($id);
    $submissionSnap = $submissionRef->snapshot();

    if (!$submissionSnap->exists()) {
        die("Data tiket maintenance tidak ditemukan.");
    }

    $data = $submissionSnap->data();
    $data['id'] = $submissionSnap->id();

    // Validasi kepemilikan dan tipe
    if (($data['user_id'] ?? '') !== $user_id || ($data['type'] ?? '') !== 'Maintenance') {
        die("Akses ditolak atau tipe tidak sesuai.");
    }

    // Ambil detail pemohon
    $userRef = $db->collection('users')->document($user_id);
    $userSnap = $userRef->snapshot();
    $user_data = $userSnap->exists() ? $userSnap->data() : [];
    
    $data['full_name'] = $user_data['full_name'] ?? 'Unknown';
    $data['jabatan'] = $user_data['jabatan'] ?? 'Personnel';
    $data['department'] = $user_data['department'] ?? 'Unknown';

} catch (Exception $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Ticket - <?php echo htmlspecialchars($data['ticket_number'] ?? ''); ?></title>
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
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#3525cd",
                        "surface": "#f7f9fb",
                        "outline-variant": "#c7c4d8",
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
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .print-card { border: none !important; box-shadow: none !important; width: 100% !important; max-width: 100% !important; border-radius: 0 !important; }
        }
    </style>
</head>
<body class="bg-surface p-6 md:p-12 font-body text-slate-900 antialiased">

    <div class="max-w-3xl mx-auto bg-white p-12 rounded-[3rem] shadow-2xl border border-outline-variant/10 print-card relative overflow-hidden">
        <!-- Watermark/Decorative -->
        <div class="absolute top-0 right-0 p-12 opacity-[0.03] rotate-12 pointer-events-none">
            <span class="material-symbols-outlined text-[200px]">handyman</span>
        </div>

        <div class="flex flex-col items-center border-b-2 border-dashed border-slate-100 pb-10 mb-10 relative z-10">
            <div class="w-16 h-16 bg-primary/10 text-primary rounded-2xl flex items-center justify-center mb-6">
                <span class="material-symbols-outlined text-3xl fill-1">build</span>
            </div>
            <h1 class="font-headline text-3xl font-black tracking-tight text-slate-800 uppercase italic">Maintenance <span class="text-primary italic">Statement</span></h1>
            <div class="mt-4 px-4 py-1.5 bg-slate-50 border border-slate-100 rounded-full">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Official Ticket: <span class="text-primary">#<?php echo htmlspecialchars($data['ticket_number'] ?? ''); ?></span></p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 mb-10">
            <div class="space-y-6">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Informasi Pelapor</p>
                    <p class="text-lg font-bold text-slate-800 leading-tight"><?php echo htmlspecialchars($data['full_name']); ?></p>
                    <p class="text-xs font-medium text-slate-500 mt-1"><?php echo htmlspecialchars($data['jabatan']); ?></p>
                </div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Unit Kerja / Dept</p>
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-primary/5 border border-primary/10 rounded-lg">
                        <span class="material-symbols-outlined text-primary text-sm">apartment</span>
                        <span class="text-xs font-bold text-primary"><?php echo htmlspecialchars($data['department']); ?></span>
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
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">devices</span>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Objek Pemeliharaan</p>
                </div>
                <p class="font-headline font-black text-xl text-slate-800 group-hover:text-primary transition-colors"><?php echo htmlspecialchars($data['title'] ?? ''); ?></p>
            </div>
            
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">description</span>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Deskripsi Malfungsi / Catatan</p>
                </div>
                <div class="bg-surface p-6 rounded-[2rem] border border-slate-100 italic text-sm text-slate-700 leading-relaxed font-medium">
                    "<?php echo nl2br(htmlspecialchars($data['description'] ?? '')); ?>"
                </div>
            </div>

            <?php if (!empty($data['attachment_path'])): ?>
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">photo_library</span>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Dokumentasi Visual</p>
                </div>
                <div class="rounded-3xl overflow-hidden border border-slate-200 shadow-sm transition-transform hover:scale-[1.01]">
                    <img src="<?php echo htmlspecialchars($data['attachment_path']); ?>" alt="Foto Kerusakan" class="w-full h-auto object-cover max-h-80 grayscale-[0.2] hover:grayscale-0 transition-all">
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="border-t-2 border-dashed border-slate-100 pt-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 mb-12">
                <div class="text-center md:text-left space-y-4">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Verification Signature</p>
                    <div class="w-28 h-28 bg-white rounded-2xl flex items-center justify-center border border-slate-100 mx-auto md:mx-0 group overflow-hidden shadow-sm">
                        <?php 
                            // Buat URL yang benar untuk kedua environment (XAMPP & Vercel)
                            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                            $host     = $_SERVER['HTTP_HOST'];
                            $script   = $_SERVER['SCRIPT_NAME']; // misal: /SIDIK-TI/api/modules_user/cetak_tiket_maintenance.php
                            // Ambil base path yang sama dengan lokasi scan_result.php
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
                <button onclick="window.print()" class="flex-[2] py-5 bg-slate-900 text-white font-headline font-black rounded-2xl shadow-xl shadow-slate-900/10 hover:shadow-primary/30 hover:bg-primary transition-all active:scale-[0.98] uppercase tracking-[0.2em] text-[10px] flex items-center justify-center gap-3">
                    <span class="material-symbols-outlined text-xl">print</span>
                    Generate PDF / Cetak Tiket
                </button>
                <a href="dashboard_user.php" class="flex-1 py-5 bg-surface text-slate-400 border border-slate-200 font-headline font-black rounded-2xl text-center hover:bg-slate-100 transition-all uppercase tracking-widest text-[10px]">
                    Dashboard
                </a>
            </div>
        </div>
    </div>

    <footer class="text-center mt-12 no-print">
        <p class="text-[9px] font-black text-slate-300 uppercase tracking-[0.4em] leading-loose">
            SIDIK-TI Maintenance System • Secure Official Document &copy; <?php echo date('Y'); ?>
        </p>
    </footer>

</body>
</html>
