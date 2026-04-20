<?php
ob_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'head' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../auth/login_user.php");
    exit();
}

$dept = $_SESSION['department'] ?? 'General';
$search_q = $_GET['q'] ?? '';
$staff_list = [];

if ($db) {
    try {
        $docs = $db->collection('users')->where('department', '=', $dept)->documents();
        foreach ($docs as $doc) {
            $u = $doc->data();
            if (!empty($search_q) && stripos($u['full_name'] ?? '', $search_q) === false) continue;
            $staff_list[] = $u;
        }
    } catch (Exception $e) { $db = null; }
}

if (!$db && $conn) {
    $dept_sql = mysqli_real_escape_string($conn, $dept);
    $sql = "SELECT * FROM users WHERE department = '$dept_sql'";
    if (!empty($search_q)) {
        $q = mysqli_real_escape_string($conn, $search_q);
        $sql .= " AND full_name LIKE '%$q%'";
    }
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) { $staff_list[] = $row; }
    }
}
?>

<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Staff Directory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
</head>
<body class="bg-slate-50 font-body text-slate-800 antialiased head-layout">

    <?php include __DIR__ . '/../includes/navbar_head.php'; ?>

    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen">
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-3 px-8 py-6 border-b border-slate-200 sticky top-0 bg-white/80 backdrop-blur-xl z-30">
            <div>
                <h1 class="font-headline text-2xl font-black text-slate-900 tracking-tight italic uppercase">
                    Direktori <span class="text-emerald-600 italic">Internal Staff</span>
                </h1>
                <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest mt-1">Personalia Departemen <?php echo htmlspecialchars($dept); ?></p>
            </div>
            <form action="" method="GET" class="relative group w-full md:w-64">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
                <input name="q" value="<?php echo htmlspecialchars($search_q); ?>" type="text" placeholder="Cari nama staff..." 
                    class="pl-10 pr-4 py-2 bg-slate-100 border-0 rounded-xl text-xs font-bold text-slate-800 outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all w-full">
            </form>
        </header>

        <div class="p-8">
            <section class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[10px] font-black uppercase tracking-[0.2em] bg-slate-50 border-b border-slate-100">
                            <th class="px-8 py-5">Nama Lengkap</th>
                            <th class="px-8 py-5">Jabatan</th>
                            <th class="px-8 py-5 text-center">Role</th>
                            <th class="px-8 py-5 text-right">Kontak</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (count($staff_list) > 0): ?>
                            <?php foreach ($staff_list as $row): ?>
                            <tr class="group hover:bg-emerald-50/30 transition-all">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-9 h-9 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-700 font-black text-xs">
                                            <?php echo substr($row['full_name'], 0, 1); ?>
                                        </div>
                                        <span class="font-bold text-sm text-slate-900"><?php echo htmlspecialchars($row['full_name']); ?></span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="text-xs font-medium text-slate-500 italic"><?php echo htmlspecialchars($row['jabatan'] ?? '-'); ?></span>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <span class="px-3 py-1.5 rounded-full text-[9px] font-black uppercase border 
                                        <?php echo $row['role'] == 'head' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-500 border-slate-200'; ?>">
                                        <?php echo strtoupper($row['role']); ?>
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <span class="text-xs font-mono text-slate-400"><?php echo htmlspecialchars($row['username']); ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-8 py-20 text-center opacity-30 grayscale items-center flex flex-col justify-center">
                                    <span class="material-symbols-outlined text-4xl mb-2">person_off</span>
                                    <p class="text-[10px] font-black uppercase tracking-widest">Tidak ada staff ditemukan</p>
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
