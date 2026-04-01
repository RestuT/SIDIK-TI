<?php
session_start();
include '../config/database.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_user.php");
    exit();
}

// --- LOGIKA PENGHAPUSAN ---
if (isset($_GET['delete_id'])) {
    $id_to_delete = $_GET['delete_id'];
    
    $subRef = $db->collection('submissions')->document($id_to_delete);
    $snapshot = $subRef->snapshot();

    if ($snapshot->exists() && $snapshot->get('status') === 'Selesai') {
        $subRef->delete();
        header("Location: dashboard_admin.php?msg=deleted");
        exit();
    } else {
        header("Location: dashboard_admin.php?msg=error_status");
        exit();
    }
}

// Ambil Statistik
$submissionsRef = $db->collection('submissions');
$stat_total = $submissionsRef->count();
$stat_pending = $submissionsRef->where('status', '=', 'Menunggu')->count();
$stat_process = $submissionsRef->where('status', '=', 'Proses')->count();
$stat_done = $submissionsRef->where('status', '=', 'Selesai')->count();

// --- LOGIKA FILTERING ---
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$search_q = isset($_GET['q']) ? $_GET['q'] : '';

$query = $submissionsRef;

if (!empty($start_date)) {
    $query = $query->where('created_at', '>=', $start_date . ' 00:00:00');
}
if (!empty($end_date)) {
    $query = $query->where('created_at', '<=', $end_date . ' 23:59:59');
}
// Note: Firestore doesn't support built-in LIKE. For search_q, we'll fetch then filter or use index strategy.
// For now, we'll fetch and do client-side filter for simplicity in migration.

$all_submissions = $query->orderBy('created_at', 'DESC')->documents();

// Ambil Ringkasan Anggaran untuk Dashboard
$current_year = date('Y');
$budget_summary = $db->collection('budget_config')
    ->where('fiscal_year', '=', (int)$current_year)
    ->documents();
// Note: Sorting and limiting in Firestore requires indices.
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Dashboard Admin</title>
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
            vertical-align: middle;
        }
        .fill-1 { font-variation-settings: 'FILL' 1; }
    </style>
</head>
<body class="bg-surface font-body text-on-surface antialiased overflow-x-hidden flex min-h-screen">
    
    <?php include '../includes/navbar_admin.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col min-w-0">
        <!-- Top Search Bar -->
        <header class="flex items-center justify-between px-8 py-5 border-b border-outline-variant/10 sticky top-0 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl z-40">
            <form action="" method="GET" class="flex-1 max-w-xl">
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-outline group-focus-within:text-primary transition-colors">
                        <span class="material-symbols-outlined">search</span>
                    </div>
                    <input name="q" value="<?php echo htmlspecialchars($search_q); ?>" 
                        class="block w-full pl-12 pr-12 py-3 bg-surface-container-low dark:bg-slate-800 border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 transition-all outline-none font-medium placeholder:text-outline text-on-surface dark:text-white" 
                        placeholder="Cari tiket, user, atau perangkat..." type="text"/>
                    <?php if(!empty($search_q)): ?>
                        <a href="dashboard_admin.php" class="absolute inset-y-0 right-0 pr-4 flex items-center text-outline hover:text-rose-500 transition-colors">
                            <span class="material-symbols-outlined text-sm">close</span>
                        </a>
                    <?php endif; ?>
                </div>
                <!-- Preserve existing date filters -->
                <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
            </form>
            <div class="flex items-center gap-6 ml-8">
                <!-- Notifikasi Dihapus -->
            </div>
        </header>

        <!-- Dynamic Content -->
        <div class="p-8 space-y-8">
            <!-- Messages -->
            <?php if(isset($_GET['msg'])): ?>
                <?php if($_GET['msg'] == 'deleted'): ?>
                    <div class="bg-emerald-100 text-emerald-700 p-4 rounded-2xl flex items-center gap-3 animate-pulse">
                        <span class="material-symbols-outlined">check_circle</span>
                        <p class="text-sm font-bold uppercase tracking-widest">Riwayat Berhasil Dihapus!</p>
                    </div>
                <?php elseif($_GET['msg'] == 'error_status'): ?>
                    <div class="bg-error-container text-on-error-container p-4 rounded-2xl flex items-center gap-3">
                        <span class="material-symbols-outlined">error</span>
                        <p class="text-sm font-bold uppercase tracking-widest">Gagal! Hanya tiket 'Selesai' yang bisa dihapus.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Breadcrumbs & Actions -->
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="font-headline text-3xl font-extrabold text-on-surface tracking-tight">Overview Panel</h1>
                    <div class="flex items-center gap-2 text-sm text-on-surface-variant font-medium">
                        <span class="text-primary italic">Admin Dashboard</span>
                        <span class="material-symbols-outlined text-xs">chevron_right</span>
                        <span>Management Area</span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <a href="export_excel.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                       class="px-5 py-3 rounded-2xl bg-indigo-50 border border-indigo-100 text-primary font-bold text-sm hover:bg-indigo-600 hover:text-white transition-all active:scale-95 flex items-center gap-2 shadow-sm">
                        <span class="material-symbols-outlined text-lg">download</span>
                        Export Excel
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="relative overflow-hidden bg-gradient-to-br from-primary to-primary-container p-8 rounded-[2.5rem] shadow-2xl shadow-primary/20 text-white col-span-1 lg:col-span-2 flex flex-col justify-between">
                    <div class="absolute top-0 right-0 p-10 opacity-10">
                        <span class="material-symbols-outlined text-[160px] leading-none">confirmation_number</span>
                    </div>
                    <div class="relative z-10">
                        <p class="font-headline font-bold text-indigo-100 uppercase tracking-widest text-xs mb-2">Total Pengajuan Masuk</p>
                        <h3 class="text-6xl font-black tracking-tight italic"><?php echo sprintf("%03d", $stat_total); ?></h3>
                    </div>
                    <div class="relative z-10 flex items-center gap-3 mt-8">
                        <span class="material-symbols-outlined text-emerald-300">trending_up</span>
                        <p class="text-sm font-medium text-emerald-100">+12% dari minggu lalu</p>
                    </div>
                </div>

                <div class="space-y-6 lg:col-span-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white p-6 rounded-3xl shadow-sm border border-outline-variant/5">
                            <div class="flex justify-between items-start mb-4">
                                <div class="w-12 h-12 bg-orange-50 text-orange-600 rounded-2xl flex items-center justify-center">
                                    <span class="material-symbols-outlined">pending_actions</span>
                                </div>
                                <span class="bg-orange-50 text-orange-600 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest">Active</span>
                            </div>
                            <h4 class="text-3xl font-black text-on-surface"><?php echo $stat_pending; ?></h4>
                            <p class="text-on-surface-variant text-xs font-bold mt-1">Menunggu Validasi</p>
                        </div>
                        <div class="bg-white p-6 rounded-3xl shadow-sm border border-outline-variant/5">
                            <div class="flex justify-between items-start mb-4">
                                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center">
                                    <span class="material-symbols-outlined">sync</span>
                                </div>
                                <span class="bg-indigo-50 text-indigo-600 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest">Process</span>
                            </div>
                            <h4 class="text-3xl font-black text-on-surface"><?php echo $stat_process; ?></h4>
                            <p class="text-on-surface-variant text-xs font-bold mt-1">Sedang Dikerjakan</p>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-outline-variant/20 p-6 rounded-3xl flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center border-2 border-emerald-100">
                                <span class="material-symbols-outlined">done_all</span>
                            </div>
                            <div>
                                <h4 class="font-bold text-on-surface">Penyelesaian Berhasil</h4>
                                <p class="text-xs text-on-surface-variant"><?php echo $stat_done; ?> tiket telah selesai.</p>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-outline">arrow_right_alt</span>
                    </div>
                </div>
            </section>
            
            <!-- Budget Overview Section -->
            <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="col-span-1 lg:col-span-3">
                    <div class="flex items-center justify-between mb-2 px-2">
                        <h2 class="font-headline text-xl font-black text-on-surface italic uppercase tracking-tighter">Budget <span class="text-primary italic">Utilization</span></h2>
                        <a href="manage_budget.php" class="text-xs font-bold text-primary hover:underline">View All Allocation</a>
                    </div>
                </div>
                
                <?php 
                $budget_count = 0;
                foreach($budget_summary as $doc): 
                    $b = $doc->data();
                    $persen = ($b['total_limit'] > 0) ? ($b['used_amount'] / $b['total_limit']) * 100 : 0;
                    $color = "bg-primary";
                    if($persen > 80) $color = "bg-orange-500";
                    if($persen > 95) $color = "bg-error";
                    $budget_count++;
                ?>
                <div class="bg-white p-6 rounded-3xl border border-outline-variant/10 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex justify-between items-start mb-4">
                        <div class="space-y-1">
                            <h4 class="font-bold text-on-surface text-sm truncate w-32 md:w-full"><?php echo $b['department']; ?></h4>
                            <p class="text-[10px] text-outline font-black uppercase tracking-widest leading-none">Fiscal Year <?php echo $current_year; ?></p>
                        </div>
                        <span class="text-xs font-black <?php echo $persen > 80 ? 'text-orange-600' : 'text-primary'; ?>"><?php echo round($persen, 1); ?>%</span>
                    </div>
                    <div class="w-full bg-surface-container-high h-2 rounded-full overflow-hidden mb-4 border border-outline-variant/5">
                        <div class="<?php echo $color; ?> h-full rounded-full transition-all duration-1000" style="width: <?php echo $persen; ?>%"></div>
                    </div>
                    <div class="flex justify-between items-center text-[10px] font-bold">
                        <span class="text-on-surface-variant">Used: Rp <?php echo number_format($b['used_amount'], 0, ',', '.'); ?></span>
                        <span class="text-outline">Limit: Rp <?php echo number_format($b['total_limit'], 0, ',', '.'); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if($budget_count == 0): ?>
                    <div class="col-span-1 lg:col-span-3 bg-surface-container-low border border-dashed border-outline-variant/30 p-10 rounded-[2rem] text-center">
                        <span class="material-symbols-outlined text-outline/30 text-5xl mb-2">account_balance_wallet</span>
                        <p class="text-xs font-bold text-outline uppercase tracking-widest">No Budget Data Allocated for <?php echo $current_year; ?></p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Table Section -->
            <section class="bg-white rounded-[2.5rem] border border-outline-variant/10 shadow-2xl shadow-indigo-900/5 overflow-hidden">
                <div class="p-8 border-b border-outline-variant/5 bg-surface-container-low/20 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <h2 class="font-headline text-xl font-black text-on-surface italic uppercase tracking-tighter">Antrean Tiket <span class="text-primary italic">Terbaru</span></h2>
                    
                    <!-- Date Filter Form -->
                    <form action="" method="GET" class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2 px-4 py-2 bg-white rounded-xl border border-outline-variant/20 shadow-sm">
                            <span class="material-symbols-outlined text-xs text-outline font-black">calendar_today</span>
                            <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                                   class="border-0 p-0 text-[10px] font-black uppercase tracking-widest text-on-surface outline-none focus:ring-0 bg-transparent">
                            <span class="text-xs font-black text-outline mx-1">/</span>
                            <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                                   class="border-0 p-0 text-[10px] font-black uppercase tracking-widest text-on-surface outline-none focus:ring-0 bg-transparent">
                        </div>
                        <button type="submit" class="p-2 bg-primary text-white rounded-xl hover:bg-indigo-700 transition-all flex items-center justify-center group">
                            <span class="material-symbols-outlined text-base group-hover:scale-110 transition-transform">filter_list</span>
                        </button>
                        <?php if(!empty($start_date) || !empty($end_date)): ?>
                            <a href="dashboard_admin.php" class="p-2 bg-rose-50 text-rose-500 rounded-xl hover:bg-rose-500 hover:text-white transition-all">
                                <span class="material-symbols-outlined text-base">close</span>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-on-surface-variant text-[10px] font-black uppercase tracking-[0.2em] bg-surface-container-low/50 border-b border-outline-variant/10">
                                <th class="px-8 py-5">Identitas Tiket</th>
                                <th class="px-8 py-5">User & Dept</th>
                                <th class="px-8 py-5">Jenis & Judul</th>
                                <th class="px-8 py-5 text-center">Status</th>
                                <th class="px-8 py-5 text-right">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            <?php 
                            $count = 0;
                            foreach($all_submissions as $doc): 
                                $row = $doc->data();
                                $row['id'] = $doc->id();
                                
                                // Client-side search filtering
                                if (!empty($search_q)) {
                                    $match = stripos($row['ticket_number'], $search_q) !== false || 
                                             stripos($row['user_name'], $search_q) !== false || 
                                             stripos($row['title'], $search_q) !== false;
                                    if (!$match) continue;
                                }
                                
                                $count++;
                            ?>
                                <tr class="group hover:bg-surface-variant/10 transition-all">
                                    <td class="px-8 py-6">
                                        <span class="font-headline font-extrabold text-primary italic">#<?php echo $row['ticket_number']; ?></span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-on-surface"><?php echo htmlspecialchars($row['user_name'] ?? 'Unknown'); ?></span>
                                            <span class="text-[10px] text-on-surface-variant uppercase font-bold"><?php echo htmlspecialchars($row['department'] ?? '-'); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-on-surface-variant"><?php echo htmlspecialchars($row['title']); ?></span>
                                            <span class="text-[10px] font-black text-primary uppercase italic"><?php echo htmlspecialchars($row['type']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-black border
                                            <?php 
                                                if($row['status'] == 'Selesai') echo 'bg-emerald-50 text-emerald-700 border-emerald-100';
                                                elseif($row['status'] == 'Proses') echo 'bg-indigo-50 text-indigo-700 border-indigo-100';
                                                elseif($row['status'] == 'Ditolak') echo 'bg-red-50 text-red-700 border-red-100';
                                                else echo 'bg-orange-50 text-orange-700 border-orange-100';
                                            ?>">
                                            <?php echo strtoupper($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="<?php echo ($row['type'] == 'Maintenance' ? 'kelola_maintenance.php' : 'kelola_pengajuan.php'); ?>?id=<?php echo $row['id']; ?>" 
                                               class="bg-blue-50 text-blue-600 px-4 py-2 rounded-xl text-[10px] font-black hover:bg-blue-600 hover:text-white transition uppercase">
                                               Kelola
                                            </a>
                                            <?php if($row['status'] == 'Selesai'): ?>
                                                <a href="dashboard_admin.php?delete_id=<?php echo $row['id']; ?>" 
                                                   onclick="return confirm('Hapus riwayat ini?')"
                                                   class="w-8 h-8 rounded-lg bg-red-50 text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white transition">
                                                    <span class="material-symbols-outlined text-sm">delete</span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if($count == 0): ?>
                                <tr>
                                    <td colspan="5" class="px-8 py-16 text-center text-on-surface-variant italic">Belum ada antrean tiket.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</body>
</html>