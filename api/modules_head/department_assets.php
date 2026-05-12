<?php
ob_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'head' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../auth/login_user.php");
    exit();
}

$dept = $_SESSION['department'] ?? 'General';
$assets = [];

if ($db) {
    try {
        $docs = $db->collection('asset_assignments')->where('department', '=', $dept)->documents();
        foreach ($docs as $doc) {
            $assets[] = $doc->data();
        }
    } catch (Exception $e) { $db = null; }
}

if (!$db && $conn) {
    $dept_sql = mysqli_real_escape_string($conn, $dept);
    $sql = "SELECT aa.*, u.full_name as user_name 
            FROM asset_assignments aa 
            JOIN users u ON aa.user_id = u.id 
            WHERE u.department = '$dept_sql' 
            ORDER BY aa.assigned_at DESC";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) { $assets[] = $row; }
    }
}
?>

<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Department Assets</title>
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
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-3 px-8 py-6 border-b border-slate-200 sticky top-0 bg-white/80 backdrop-blur-xl z-30">
            <div>
                <h1 class="font-headline text-2xl font-black text-slate-900 tracking-tight italic uppercase">
                    Monitoring <span class="text-emerald-600 italic">Penggunaan Aset</span>
                </h1>
                <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest mt-1">Daftar Inventaris Departemen <?php echo htmlspecialchars($dept); ?></p>
            </div>
            <div class="bg-emerald-50 px-4 py-2 rounded-2xl border border-emerald-100 flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600 text-sm">inventory</span>
                <span class="text-[10px] font-black text-emerald-700 uppercase tracking-widest"><?php echo count($assets); ?> Aset Terdaftar</span>
            </div>
        </header>

        <div class="p-8">
            <section class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[10px] font-black uppercase tracking-[0.2em] bg-slate-50 border-b border-slate-100">
                            <th class="px-8 py-5">Nama Barang / S/N</th>
                            <th class="px-8 py-5">Kategori</th>
                            <th class="px-8 py-5">Pemegang Aset</th>
                            <th class="px-8 py-5 text-center">Status</th>
                            <th class="px-8 py-5 text-right">Tgl Penyerahan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (count($assets) > 0): ?>
                            <?php foreach ($assets as $row): ?>
                            <tr class="group hover:bg-emerald-50/30 transition-all">
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-sm text-slate-900 leading-none mb-1"><?php echo htmlspecialchars($row['item_name']); ?></span>
                                        <span class="text-[10px] font-mono text-slate-400 font-bold"><?php echo htmlspecialchars($row['serial_number'] ?? 'SN-PENDING'); ?></span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-sm text-slate-300">category</span>
                                        <span class="text-xs font-semibold text-slate-600 uppercase tracking-tight"><?php echo htmlspecialchars($row['category'] ?? '-'); ?></span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-slate-700 leading-none"><?php echo htmlspecialchars($row['user_name'] ?? '-'); ?></span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <span class="px-3 py-1.5 rounded-full text-[9px] font-black uppercase 
                                        <?php echo ($row['status'] == 'Active') ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'; ?>">
                                        <?php echo strtoupper($row['status'] ?? 'Active'); ?>
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <span class="text-xs font-bold text-slate-500 uppercase"><?php echo date('d M Y', strtotime($row['assigned_at'] ?? 'now')); ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-8 py-20 text-center opacity-30 grayscale items-center flex flex-col justify-center">
                                    <span class="material-symbols-outlined text-4xl mb-2">inventory_2</span>
                                    <p class="text-[10px] font-black uppercase tracking-widest">Belum ada aset terdaftar di departemen ini</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </main>
</body>
</html>

