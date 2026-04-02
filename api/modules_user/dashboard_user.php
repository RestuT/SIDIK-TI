<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Ambil Statistik Dashboard User dari Firestore
$submissionsRef = $db->collection('submissions')->where('user_id', '=', $user_id);

$stat_pending = $submissionsRef->where('status', '=', 'Menunggu')->count();
$stat_process = $submissionsRef->where('status', '=', 'Proses')->count();
$stat_done = $submissionsRef->where('status', '=', 'Selesai')->count();

// 2. Ambil data pengajuan terbaru (Limit 5)
$all_submissions = $submissionsRef->orderBy('created_at', 'DESC')->limit(5)->documents();

// 3. User Detail
$userRef = $db->collection('users')->document($user_id);
$userSnap = $userRef->snapshot();
if ($userSnap->exists()) {
    $user_meta = $userSnap->data();
    $display_name = $user_meta['full_name'] ?? 'User';
} else {
    $display_name = 'User';
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Dashboard User</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
          tailwind.config = {
            darkMode: "class",
            theme: {
              extend: {
                colors: {
                  "primary-fixed-dim": "#c3c0ff",
                  "on-error": "#ffffff",
                  "on-error-container": "#93000a",
                  "on-secondary-container": "#fefcff",
                  "on-tertiary-container": "#67f4b7",
                  "inverse-surface": "#2d3133",
                  "surface-variant": "#e0e3e5",
                  "primary-fixed": "#e2dfff",
                  "tertiary": "#005338",
                  "secondary": "#0051d5",
                  "on-surface": "#191c1e",
                  "background": "#f7f9fb",
                  "on-primary-container": "#dad7ff",
                  "tertiary-fixed-dim": "#4edea3",
                  "surface-tint": "#4d44e3",
                  "on-tertiary": "#ffffff",
                  "secondary-fixed-dim": "#b4c5ff",
                  "secondary-fixed": "#dbe1ff",
                  "surface-container-low": "#f2f4f6",
                  "on-surface-variant": "#464555",
                  "on-secondary": "#ffffff",
                  "surface": "#f7f9fb",
                  "error-container": "#ffdad6",
                  "error": "#ba1a1a",
                  "surface-container-high": "#e6e8ea",
                  "on-tertiary-fixed-variant": "#005236",
                  "surface-container-highest": "#e0e3e5",
                  "on-primary-fixed-variant": "#3323cc",
                  "on-primary": "#ffffff",
                  "primary-container": "#4f46e5",
                  "outline-variant": "#c7c4d8",
                  "on-primary-fixed": "#0f0069",
                  "inverse-on-surface": "#eff1f3",
                  "tertiary-fixed": "#6ffbbe",
                  "on-secondary-fixed-variant": "#003ea8",
                  "primary": "#3525cd",
                  "surface-bright": "#f7f9fb",
                  "secondary-container": "#316bf3",
                  "on-background": "#191c1e",
                  "surface-container-lowest": "#ffffff",
                  "tertiary-container": "#006e4b",
                  "surface-dim": "#d8dadc",
                  "on-secondary-fixed": "#00174b",
                  "surface-container": "#eceef0",
                  "inverse-primary": "#c3c0ff",
                  "outline": "#777587",
                  "on-tertiary-fixed": "#002113"
                },
                fontFamily: {
                  "headline": ["Plus Jakarta Sans"],
                  "body": ["Inter"],
                  "label": ["Inter"]
                },
                borderRadius: {"DEFAULT": "1rem", "lg": "2rem", "xl": "3rem", "full": "9999px"},
              },
            },
          }
        </script>
    <style>
            .material-symbols-outlined {
                font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            }
            body { font-family: 'Inter', sans-serif; }
            h1, h2, h3, .font-headline { font-family: 'Plus Jakarta Sans', sans-serif; }
            body {
              min-height: max(884px, 100dvh);
            }
        </style>
</head>
<body class="bg-background text-on-surface min-h-screen selection:bg-primary/20">
    <!-- TopAppBar -->
    <header class="flex justify-between items-center px-8 py-4 w-full sticky top-0 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl z-40 shadow-sm shadow-indigo-500/5">
        <div class="flex items-center gap-2">
            <span class="text-xl font-extrabold tracking-tight text-indigo-700 dark:text-indigo-300">SIDIK-TI</span>
        </div>
        <div class="hidden md:flex items-center gap-8">
            <nav class="flex gap-6">
                <a class="text-indigo-700 dark:text-indigo-300 font-semibold transition-all duration-300" href="dashboard_user.php">Dashboard</a>
                <a class="text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-300 transition-all duration-300" href="dashboard_audit.php">Requests</a>
                <a class="text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-300 transition-all duration-300" href="assets_user.php">Assets</a>
            </nav>
        </div>
        <div class="flex items-center gap-4">
            <!-- Notifikasi Dihapus -->
            <div class="h-10 w-10 rounded-full overflow-hidden border-2 border-indigo-100 shadow-sm flex items-center justify-center bg-indigo-50">
                <span class="material-symbols-outlined text-indigo-600">person</span>
            </div>
            <a href="../auth/logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')" class="p-2 text-rose-500 hover:bg-rose-50 rounded-full transition-colors">
                <span class="material-symbols-outlined">logout</span>
            </a>
        </div>
    </header>

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
            <!-- Card 1 -->
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
            <!-- Card 2 -->
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
            <!-- Card 3 -->
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
                        foreach($all_submissions as $doc): 
                            $row = $doc->data();
                            $row['id'] = $doc->id();
                            $count++;
                        ?>
                            <tr class="group hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-6">
                                    <span class="font-bold text-indigo-700">#<?php echo htmlspecialchars($row['ticket_number']); ?></span>
                                </td>
                                <td class="px-6 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg <?php echo $row['type'] == 'Maintenance' ? 'bg-amber-50 text-amber-600' : 'bg-indigo-50 text-indigo-600'; ?> flex items-center justify-center">
                                            <span class="material-symbols-outlined text-lg"><?php echo $row['type'] == 'Maintenance' ? 'build' : 'shopping_cart'; ?></span>
                                        </div>
                                        <span class="font-medium text-on-surface"><?php echo htmlspecialchars($row['title']); ?></span>
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
                                    <a href="<?php echo $row['type'] == 'Maintenance' ? 'cetak_tiket_maintenance.php' : 'cetak_tiket_pengadaan.php'; ?>?id=<?php echo $row['id']; ?>" target="_blank" class="text-slate-400 hover:text-primary transition-colors">
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
                    <button class="w-fit px-6 py-3 rounded-xl bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 font-bold transition-all duration-300">Buka Panduan</button>
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
                    <li class="flex gap-4 items-start">
                        <div class="w-2 h-2 rounded-full bg-primary mt-2 flex-shrink-0"></div>
                        <div class="space-y-1">
                            <p class="font-bold text-on-surface text-sm">Maintenance Server Email</p>
                            <p class="text-on-surface-variant text-xs">Sabtu, 14 Okt | 22.00 - 04.00 WIB</p>
                        </div>
                    </li>
                    <li class="flex gap-4 items-start">
                        <div class="w-2 h-2 rounded-full bg-slate-300 mt-2 flex-shrink-0"></div>
                        <div class="space-y-1">
                            <p class="font-bold text-on-surface text-sm">Update Kebijakan Password</p>
                            <p class="text-on-surface-variant text-xs">Mulai berlaku per 1 Nov 2023</p>
                        </div>
                    </li>
                </ul>
            </div>
        </section>
    </main>

    <!-- BottomNavBar for Mobile -->
    <nav class="md:hidden fixed bottom-0 w-full z-50 flex justify-around items-center px-6 py-3 pb-safe bg-white/80 dark:bg-slate-900/80 backdrop-blur-lg shadow-xl rounded-t-3xl">
        <a class="flex flex-col items-center justify-center bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-200 rounded-2xl px-5 py-2 transition-all active:scale-90 duration-200" href="dashboard_user.php">
            <span class="material-symbols-outlined">grid_view</span>
            <span class="font-plus-jakarta-sans text-[10px] font-bold uppercase tracking-wider mt-1">Home</span>
        </a>
        <a class="flex flex-col items-center justify-center text-slate-400 dark:text-slate-500 px-5 py-2 hover:text-indigo-500 transition-colors" href="dashboard_audit.php">
            <span class="material-symbols-outlined">handyman</span>
            <span class="font-plus-jakarta-sans text-[10px] font-bold uppercase tracking-wider mt-1">Requests</span>
        </a>
        <a class="flex flex-col items-center justify-center text-slate-400 dark:text-slate-500 px-5 py-2 hover:text-indigo-500 transition-colors" href="assets_user.php">
            <span class="material-symbols-outlined">list_alt</span>
            <span class="font-plus-jakarta-sans text-[10px] font-bold uppercase tracking-wider mt-1">Assets</span>
        </a>
        <a class="flex flex-col items-center justify-center text-slate-400 dark:text-slate-500 px-5 py-2 hover:text-indigo-500 transition-colors" href="#">
            <span class="material-symbols-outlined">person</span>
            <span class="font-plus-jakarta-sans text-[10px] font-bold uppercase tracking-wider mt-1">Profile</span>
        </a>
    </nav>
</body>
</html>
