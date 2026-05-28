<?php
ob_start();
require_once __DIR__ . '/../config/database.php';

// Proteksi akses role Head
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'head' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../auth/login_user.php");
    exit();
}

$dept = $_SESSION['department'] ?? 'General';
$dept_id = $_SESSION['user_id'];

$stat_staff = 0;
$stat_assets = 0;

if ($db) {
    try {
        // Count Staff
        $staffDocs = $db->collection('users')->where('department', '=', $dept)->documents();
        foreach ($staffDocs as $doc) { $stat_staff++; }

        // Count Assets (Join simulation)
        $assetDocs = $db->collection('asset_assignments')->where('department', '=', $dept)->documents();
        foreach ($assetDocs as $doc) { $stat_assets++; }
    } catch (Exception $e) { $db = null; }
}

if (!$db && $conn) {
    $dept_sql = mysqli_real_escape_string($conn, $dept);
    
    // Count Staff
    $res1 = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE department = '$dept_sql'");
    $stat_staff = mysqli_fetch_assoc($res1)['total'];

    // Count Assets
    $res2 = mysqli_query($conn, "SELECT COUNT(*) as total 
                                 FROM asset_assignments aa 
                                 JOIN users u ON aa.user_id = u.id 
                                 WHERE u.department = '$dept_sql'");
    $stat_assets = mysqli_fetch_assoc($res2)['total'];
}
?>

<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Head Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
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
<body class="bg-slate-50 font-body text-slate-800 antialiased head-layout">

    <?php include __DIR__ . '/../includes/navbar_head.php'; ?>

    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen">
        <!-- Header Bar with Premium Dark Background -->
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 px-8 py-7 border-b border-slate-800 sticky top-0 bg-slate-900 text-white z-30 shadow-lg shadow-slate-950/10">
            <div>
                <h1 class="font-headline text-2xl font-extrabold text-white tracking-tight italic uppercase">
                    Department <span class="text-emerald-400 italic">Head Dashboard</span>
                </h1>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest leading-none mt-2">Monitoring & Pengawasan Internal - <?php echo htmlspecialchars($dept); ?></p>
            </div>
            <div class="flex items-center gap-2 bg-emerald-500/10 px-4 py-2.5 rounded-2xl border border-emerald-500/20">
                <span class="material-symbols-outlined text-emerald-400 text-sm fill-1">verified</span>
                <span class="text-[10px] font-black text-emerald-400 uppercase tracking-widest">Authorized Head Access</span>
            </div>
        </header>

        <div class="p-8 space-y-8">
            <!-- Alert -->
            <div class="bg-gradient-to-r from-emerald-600 to-emerald-500 p-8 rounded-[2.5rem] text-white shadow-xl shadow-emerald-900/10 relative overflow-hidden">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="max-w-xl">
                        <h2 class="text-3xl font-black italic mb-2 tracking-tight uppercase">Selamat Bekerja, Pak/Bu <?php echo explode(' ', $_SESSION['full_name'])[0]; ?></h2>
                        <p class="text-emerald-50/80 text-sm font-medium leading-relaxed">
                            Panel ini memungkinkan Anda memantau direktori staff dan inventaris aset khusus di departemen **<?php echo htmlspecialchars($dept); ?>**. 
                            Pastikan pengawasan aset berjalan efisien untuk mendukung produktivitas tim.
                        </p>
                    </div>
                </div>
                <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
            </div>

            <!-- Stats Grid -->
            <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm flex items-center justify-between group hover:border-emerald-500 transition-all">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Personil Departemen</p>
                        <h4 class="text-4xl font-black text-slate-900 group-hover:text-emerald-600 transition-all font-headline italic">
                            <?php echo $stat_staff; ?> <span class="text-lg not-italic font-black text-slate-300">Staff</span>
                        </h4>
                    </div>
                    <div class="w-16 h-16 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 shadow-inner group-hover:bg-emerald-600 group-hover:text-white transition-all">
                        <span class="material-symbols-outlined text-3xl">groups</span>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm flex items-center justify-between group hover:border-emerald-500 transition-all">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Aset Dalam Penggunaan</p>
                        <h4 class="text-4xl font-black text-slate-900 group-hover:text-emerald-600 transition-all font-headline italic">
                            <?php echo $stat_assets; ?> <span class="text-lg not-italic font-black text-slate-300">Items</span>
                        </h4>
                    </div>
                    <div class="w-16 h-16 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 shadow-inner group-hover:bg-emerald-600 group-hover:text-white transition-all">
                        <span class="material-symbols-outlined text-3xl">inventory_2</span>
                    </div>
                </div>
            </section>

            <!-- Quick Actions -->
            <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <a href="staff_directory.php" class="bg-white p-6 rounded-3xl border border-slate-100 flex items-center gap-4 hover:bg-emerald-50 transition-all group">
                    <span class="material-symbols-outlined text-emerald-600 group-hover:scale-110 transition-transform">person_search</span>
                    <span class="text-xs font-black uppercase tracking-widest text-slate-700">Lihat Staff</span>
                </a>
                <a href="department_assets.php" class="bg-white p-6 rounded-3xl border border-slate-100 flex items-center gap-4 hover:bg-emerald-50 transition-all group">
                    <span class="material-symbols-outlined text-emerald-600 group-hover:scale-110 transition-transform">qr_code_2</span>
                    <span class="text-xs font-black uppercase tracking-widest text-slate-700">Pantau Aset</span>
                </a>
                <a href="../modules_user/dashboard_user.php" class="bg-white p-6 rounded-3xl border border-slate-100 flex items-center gap-4 hover:bg-emerald-50 transition-all group">
                    <span class="material-symbols-outlined text-emerald-600 group-hover:scale-110 transition-transform">confirmation_number</span>
                    <span class="text-xs font-black uppercase tracking-widest text-slate-700">Tiket Saya</span>
                </a>
            </section>
        </div>
    </main>
</body>
</html>
