<?php
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

// Proteksi akses untuk role admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_admin.php");
    exit();
}

// Handler POST Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    
    // 1. ADD PENGUMUMAN
    if (isset($_POST['add_pengumuman'])) {
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $urgency = $_POST['urgency'] ?? 'Normal';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $author = $_SESSION['user'] ?? 'Admin';
        $created_at = date('Y-m-d H:i:s');
        
        if ($db) {
            try {
                $db->collection('announcements')->add([
                    'title' => $title, 'content' => $content, 'urgency' => $urgency,
                    'is_active' => (bool)$is_active, 'created_at' => $created_at, 'author' => $author
                ]);
                header("Location: kelola_pengumuman.php?status=added"); exit();
            } catch (Exception $e) { $db = null; }
        }
        if (!$db && $conn) {
            $t = mysqli_real_escape_string($conn, $title);
            $c = mysqli_real_escape_string($conn, $content);
            $u = mysqli_real_escape_string($conn, $urgency);
            $a = mysqli_real_escape_string($conn, $author);
            $sql = "INSERT INTO announcements (title, content, urgency, is_active, created_at, author) 
                    VALUES ('$t', '$c', '$u', $is_active, '$created_at', '$a')";
            if (mysqli_query($conn, $sql)) { header("Location: kelola_pengumuman.php?status=added"); exit(); }
        }
        header("Location: kelola_pengumuman.php?status=error"); exit();
    }
    
    // 2. TOGGLE STATUS
    if (isset($_POST['toggle_status'])) {
        $doc_id = $_POST['id'];
        $current_status = (int)$_POST['current_status'];
        $new_status = $current_status ? 0 : 1;
        
        if ($db && !is_numeric($doc_id)) {
            try {
                $db->collection('announcements')->document($doc_id)->update([
                    ['path' => 'is_active', 'value' => (bool)$new_status]
                ]);
                header("Location: kelola_pengumuman.php?status=updated"); exit();
            } catch (Exception $e) { $db = null; }
        }
        if (!$db && $conn) {
            $sql = "UPDATE announcements SET is_active = $new_status WHERE id = " . intval($doc_id);
            if (mysqli_query($conn, $sql)) { header("Location: kelola_pengumuman.php?status=updated"); exit(); }
        }
        header("Location: kelola_pengumuman.php?status=error"); exit();
    }
    
    // 3. DELETE PENGUMUMAN
    if (isset($_POST['delete_pengumuman'])) {
        $doc_id = $_POST['id'];
        if ($db && !is_numeric($doc_id)) {
            try {
                $db->collection('announcements')->document($doc_id)->delete();
                header("Location: kelola_pengumuman.php?status=deleted"); exit();
            } catch (Exception $e) { $db = null; }
        }
        if (!$db && $conn) {
            $sql = "DELETE FROM announcements WHERE id = " . intval($doc_id);
            if (mysqli_query($conn, $sql)) { header("Location: kelola_pengumuman.php?status=deleted"); exit(); }
        }
        header("Location: kelola_pengumuman.php?status=error"); exit();
    }
}

// Fetch Announcements
$announcements = [];
if ($db) {
    try {
        $query = $db->collection('announcements')->orderBy('created_at', 'DESC')->documents();
        foreach ($query as $doc) {
            $data = $doc->data();
            $data['id'] = $doc->id();
            $announcements[] = $data;
        }
    } catch (Exception $e) { $db = null; }
}
if (!$db && $conn) {
    $res = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $announcements[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <?php 
        $pageTitle = 'SIDIK-TI | Manajemen Pengumuman';
        $base_url = '../';
        include __DIR__ . '/../includes/head_meta.php'; 
    ?>
</head>
<body class="bg-surface font-body text-on-surface antialiased overflow-x-hidden min-h-screen">

    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>

    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen flex flex-col">
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 md:px-8 py-4 md:py-5 border-b border-outline-variant/10 sticky top-0 bg-surface/80 backdrop-blur-xl z-30">
            <div>
                <h1 class="font-headline text-xl md:text-2xl font-extrabold text-on-surface tracking-tight leading-none italic uppercase">Broadcast <span class="text-primary italic">Center</span></h1>
                <p class="text-[10px] text-outline font-black uppercase tracking-widest mt-1">Sistem Pengumuman IT Terpusat</p>
            </div>
        </header>

        <div class="p-4 md:p-8 space-y-6 md:space-y-8">
            <?php if(isset($_GET['status'])): ?>
                <?php if($_GET['status'] == 'added'): ?>
                    <div class="bg-emerald-100 text-emerald-700 p-4 rounded-2xl flex items-center gap-3 border border-emerald-200">
                        <span class="material-symbols-outlined fill-1 text-xl">check_circle</span>
                        <strong>Sukses!</strong> Pengumuman berhasil dipublikasikan.
                    </div>
                <?php elseif($_GET['status'] == 'updated'): ?>
                    <div class="bg-blue-100 text-blue-700 p-4 rounded-2xl flex items-center gap-3 border border-blue-200">
                        <span class="material-symbols-outlined fill-1 text-xl">update</span>
                        Status pengumuman berhasil diupdate.
                    </div>
                <?php elseif($_GET['status'] == 'deleted'): ?>
                    <div class="bg-slate-200 text-slate-700 p-4 rounded-2xl flex items-center gap-3 border border-slate-300">
                        <span class="material-symbols-outlined fill-1 text-xl">delete</span>
                        Pengumuman telah dihapus.
                    </div>
                <?php elseif($_GET['status'] == 'error'): ?>
                    <div class="bg-red-100 text-red-700 p-4 rounded-2xl flex items-center gap-3 border border-red-200">
                        <span class="material-symbols-outlined fill-1 text-xl">error</span>
                        Terjadi kesalahan saat memproses data.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-10">
                <div class="xl:col-span-4 h-fit">
                    <section class="bg-white p-8 rounded-[2.5rem] border border-outline-variant/10 shadow-2xl relative overflow-hidden group">
                        <h2 class="flex items-center gap-3 font-headline text-lg font-black text-on-surface uppercase tracking-tight mb-8">
                            <span class="material-symbols-outlined text-primary fill-1">add_alert</span> Buat Baru
                        </h2>
                        <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="POST" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2">Judul</label>
                                <input type="text" name="title" required placeholder="Cth: Maintenance" class="block w-full px-5 py-4 bg-surface-container-low border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-2 focus:ring-primary/20 transition-all text-sm">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2">Urgensi</label>
                                <select name="urgency" required class="block w-full px-5 py-4 bg-surface-container-low border-0 rounded-2xl font-bold text-on-surface">
                                    <option value="Normal">Normal</option>
                                    <option value="Tinggi">Tinggi</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2">Pesan</label>
                                <textarea name="content" required rows="4" class="block w-full px-5 py-4 bg-surface-container-low border-0 rounded-2xl text-on-surface"></textarea>
                            </div>
                            <div class="flex items-center gap-3 ml-2 pt-2">
                                <input type="checkbox" name="is_active" id="is_active" value="1" checked class="w-5 h-5 text-primary rounded">
                                <label for="is_active" class="text-sm font-bold text-on-surface-variant">Langsung Aktifkan</label>
                            </div>
                            <button type="submit" name="add_pengumuman" class="w-full mt-4 py-5 bg-primary text-white font-headline font-black rounded-2xl shadow-xl uppercase tracking-widest text-xs">Kirim</button>
                        </form>
                    </section>
                </div>

                <div class="xl:col-span-8">
                    <section class="bg-white rounded-[2.5rem] border border-outline-variant/10 shadow-sm overflow-hidden min-h-[400px]">
                        <div class="p-8 border-b border-outline-variant/5">
                            <h2 class="font-headline text-xl font-bold text-on-surface italic uppercase tracking-tighter">Timeline</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-on-surface-variant text-[10px] font-black uppercase tracking-[0.2em] bg-surface-container-low/30 border-b border-outline-variant/10">
                                        <th class="px-6 py-5">Status</th>
                                        <th class="px-6 py-5">Detail</th>
                                        <th class="px-6 py-5 text-center">Urgensi</th>
                                        <th class="px-6 py-5 text-right w-32">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/5">
                                    <?php if(!empty($announcements)): foreach($announcements as $row): 
                                        $isActive = (bool)($row['is_active'] ?? false);
                                    ?>
                                        <tr class="group hover:bg-surface-variant/5 transition-all">
                                            <td class="px-6 py-6 w-24 text-center align-top">
                                                <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="current_status" value="<?php echo $isActive ? '1' : '0'; ?>">
                                                    <button type="submit" name="toggle_status" class="inline-flex items-center justify-center p-2 rounded-xl <?php echo $isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500'; ?>">
                                                        <span class="material-symbols-outlined <?php echo $isActive ? 'fill-1' : ''; ?>"><?php echo $isActive ? 'toggle_on' : 'toggle_off'; ?></span>
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="px-6 py-6 align-top">
                                                <div class="flex flex-col gap-1">
                                                    <span class="font-headline font-bold text-on-surface text-base uppercase"><?php echo htmlspecialchars($row['title']); ?></span>
                                                    <span class="text-xs text-on-surface-variant italic"><?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?> • by <?php echo htmlspecialchars($row['author'] ?? 'Admin'); ?></span>
                                                    <p class="text-sm text-slate-600 mt-2 max-w-lg leading-relaxed"><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
                                                </div>
                                            </td>
                                            <td class="px-6 py-6 text-center align-top">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase <?php echo ($row['urgency'] === 'Tinggi') ? 'bg-red-50 text-red-700' : 'bg-blue-50 text-blue-700'; ?>">
                                                    <?php echo $row['urgency']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-6 text-right align-top">
                                                <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="POST" onsubmit="return confirm('Hapus permanen?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" name="delete_pengumuman" class="w-10 h-10 inline-flex items-center justify-center bg-error/5 text-error rounded-xl hover:bg-error hover:text-white transition-all">
                                                        <span class="material-symbols-outlined text-xl">delete</span>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; else: ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-20 text-center">
                                                <p class="font-headline font-black text-on-surface uppercase tracking-widest text-xs opacity-40">Belum ada pengumuman</p>
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
