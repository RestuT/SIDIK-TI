<?php
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

// Proteksi akses untuk role admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_admin.php");
    exit();
}

// Mengambil daftar departemen
$departments = [];
if ($db) {
    try {
        $query = $db->collection('departments')->orderBy('nama_dept', 'ASC')->documents();
        foreach ($query as $doc) {
            $data = $doc->data();
            $data['id'] = $doc->id();
            $departments[] = $data;
        }
    } catch (Exception $e) { $db = null; }
}
if (!$db && $conn) {
    $res = mysqli_query($conn, "SELECT * FROM departments ORDER BY nama_dept ASC");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $departments[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <?php 
        $pageTitle = 'SIDIK-TI | Institutional Hierarchy';
        $base_url = '../';
        include __DIR__ . '/../includes/head_meta.php'; 
    ?>
</head>
<body class="bg-surface font-body text-on-surface antialiased overflow-x-hidden min-h-screen">

    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>

    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen flex flex-col">
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 md:px-8 py-4 md:py-5 border-b border-outline-variant/10 sticky top-0 bg-surface/80 backdrop-blur-xl z-30">
            <div>
                <h1 class="font-headline text-xl md:text-2xl font-extrabold text-on-surface tracking-tight leading-none italic uppercase">Master <span class="text-primary italic">Department</span></h1>
                <p class="text-[10px] text-outline font-black uppercase tracking-widest mt-1">Struktur Organisasi &amp; Hierarki Institusi</p>
            </div>
        </header>

        <div class="p-4 md:p-8 space-y-6 md:space-y-8">
            <?php if(isset($_GET['status'])): ?>
                <?php if($_GET['status'] == 'added'): ?>
                    <div class="bg-emerald-100 text-emerald-700 p-4 rounded-2xl flex items-center gap-3 border border-emerald-200 font-bold uppercase tracking-widest text-xs">
                        <span class="material-symbols-outlined fill-1">check_circle</span> Departemen berhasil didaftarkan.
                    </div>
                <?php elseif($_GET['status'] == 'deleted'): ?>
                    <div class="bg-primary-fixed/10 text-primary p-4 rounded-2xl flex items-center gap-3 border border-primary-fixed/30 font-bold uppercase tracking-widest text-xs">
                        <span class="material-symbols-outlined fill-1">info</span> Departemen telah dihapus.
                    </div>
                <?php elseif($_GET['status'] == 'error'): ?>
                    <div class="bg-error-container text-on-error-container p-4 rounded-2xl flex items-center gap-3 border border-error/20 font-bold uppercase tracking-widest text-xs">
                        <span class="material-symbols-outlined fill-1">report</span> Terjadi kesalahan operasional.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                <div class="lg:col-span-4 h-fit">
                    <section class="bg-white p-8 rounded-[2.5rem] border border-outline-variant/10 shadow-2xl relative overflow-hidden group">
                        <h2 class="flex items-center gap-3 font-headline text-lg font-black text-on-surface uppercase tracking-tight mb-8">
                            <span class="material-symbols-outlined text-primary fill-1">add_business</span> Registrasi Unit
                        </h2>
                        <form action="<?php echo htmlspecialchars(dirname($_SERVER['PHP_SELF']) . '/../config/proses_department.php'); ?>" method="POST" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2">Identitas Departemen</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-primary text-lg">domain</span>
                                    <input type="text" name="nama_dept" required placeholder="Cth: IT / HR" class="block w-full pl-12 pr-4 py-4 bg-surface-container-low border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-2 focus:ring-primary/20 transition-all text-sm">
                                </div>
                            </div>
                            <button type="submit" name="add_dept" class="w-full py-5 bg-primary text-white font-headline font-black rounded-2xl shadow-xl uppercase tracking-widest text-xs">Simpan</button>
                        </form>
                    </section>
                </div>

                <div class="lg:col-span-8">
                    <section class="bg-white rounded-[2.5rem] border border-outline-variant/10 shadow-sm overflow-hidden min-h-[400px]">
                        <div class="p-8 border-b border-outline-variant/5">
                            <h2 class="font-headline text-xl font-bold text-on-surface italic uppercase tracking-tighter">Current Structure</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-on-surface-variant text-[10px] font-black uppercase tracking-[0.2em] bg-surface-container-low/30 border-b border-outline-variant/10">
                                        <th class="px-8 py-5 w-20 text-center">No</th>
                                        <th class="px-8 py-5">Unit / Departemen</th>
                                        <th class="px-8 py-5 text-center">Status</th>
                                        <th class="px-8 py-5 text-right w-32">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/5">
                                    <?php if(!empty($departments)): $no = 1; ?>
                                        <?php foreach($departments as $row): ?>
                                        <tr class="group hover:bg-surface-variant/5 transition-all">
                                            <td class="px-8 py-6 text-center">
                                                <span class="text-xs font-black text-outline italic">#<?php echo sprintf("%02d", $no++); ?></span>
                                            </td>
                                            <td class="px-8 py-6">
                                                <div class="flex items-center gap-3">
                                                    <span class="font-headline font-bold text-on-surface uppercase italic"><?php echo htmlspecialchars($row['nama_dept']); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-8 py-6 text-center">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-50 text-[9px] font-black text-emerald-700 border border-emerald-100 uppercase tracking-widest italic">Verified</span>
                                            </td>
                                            <td class="px-8 py-6 text-right">
                                                <a href="../config/hapus_department.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Hapus?');" class="w-10 h-10 inline-flex items-center justify-center bg-error/5 text-error rounded-xl hover:bg-error hover:text-white transition-all">
                                                    <span class="material-symbols-outlined text-xl">delete_sweep</span>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; else: ?>
                                        <tr>
                                            <td colspan="4" class="px-8 py-20 text-center">
                                                <p class="font-headline font-black text-on-surface uppercase tracking-widest text-xs opacity-40">No Structure Data</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
