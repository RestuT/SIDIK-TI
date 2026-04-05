<?php

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
        $is_active = isset($_POST['is_active']) ? true : false;
        
        try {
            $db->collection('announcements')->add([
                'title' => $title,
                'content' => $content,
                'urgency' => $urgency,
                'is_active' => $is_active,
                'created_at' => date('Y-m-d H:i:s'),
                'author' => $_SESSION['user'] ?? 'Admin'
            ]);
            header("Location: kelola_pengumuman.php?status=added");
            exit();
        } catch (Exception $e) {
            header("Location: kelola_pengumuman.php?status=error");
            exit();
        }
    }
    
    // 2. TOGGLE STATUS
    if (isset($_POST['toggle_status'])) {
        $doc_id = $_POST['id'];
        $current_status = $_POST['current_status'] === '1' ? true : false;
        
        try {
            $db->collection('announcements')->document($doc_id)->update([
                ['path' => 'is_active', 'value' => !$current_status]
            ]);
            header("Location: kelola_pengumuman.php?status=updated");
            exit();
        } catch (Exception $e) {
            header("Location: kelola_pengumuman.php?status=error");
            exit();
        }
    }
    
    // 3. DELETE PENGUMUMAN
    if (isset($_POST['delete_pengumuman'])) {
        $doc_id = $_POST['id'];
        try {
            $db->collection('announcements')->document($doc_id)->delete();
            header("Location: kelola_pengumuman.php?status=deleted");
            exit();
        } catch (Exception $e) {
            header("Location: kelola_pengumuman.php?status=error");
            exit();
        }
    }
}

// Fetch Announcements
$query = $db->collection('announcements')->orderBy('created_at', 'DESC')->documents();
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Manajemen Pengumuman</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
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
                        "surface-variant": "#e0e3e5",
                        "primary-fixed": "#e2dfff",
                        "tertiary": "#005338",
                        "on-surface": "#191c1e",
                        "background": "#f7f9fb",
                        "on-tertiary": "#ffffff",
                        "surface-container-low": "#f2f4f6",
                        "on-surface-variant": "#464555",
                        "on-secondary": "#ffffff",
                        "surface": "#f7f9fb",
                        "error-container": "#ffdad6",
                        "error": "#ba1a1a",
                        "surface-container-high": "#e6e8ea",
                        "on-primary": "#ffffff",
                        "primary-container": "#4f46e5",
                        "outline-variant": "#c7c4d8",
                        "primary": "#3525cd",
                        "surface-container-lowest": "#ffffff",
                        "outline": "#777587"
                    },
                    fontFamily: { "headline": ["Plus Jakarta Sans"], "body": ["Inter"] },
                    borderRadius: {"DEFAULT": "1rem", "lg": "2rem", "xl": "3rem", "full": "9999px"},
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
        .fill-1 { font-variation-settings: 'FILL' 1; }
    </style>
</head>
<body class="bg-surface font-body text-on-surface antialiased overflow-x-hidden min-h-screen">

    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>

    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen flex flex-col">
        <!-- Header Bar -->
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 md:px-8 py-4 md:py-5 border-b border-outline-variant/10 sticky top-0 bg-surface/80 backdrop-blur-xl z-20">
            <div>
                <h1 class="font-headline text-xl md:text-2xl font-extrabold text-on-surface tracking-tight leading-none italic uppercase">Broadcast <span class="text-primary italic">Center</span></h1>
                <p class="text-[10px] text-outline font-black uppercase tracking-widest mt-1">Sistem Pengumuman IT Terpusat</p>
            </div>
            <div class="flex items-center gap-4">
                <button class="w-10 h-10 rounded-full hover:bg-surface-container flex items-center justify-center text-outline transition-all">
                    <span class="material-symbols-outlined">campaign</span>
                </button>
            </div>
        </header>

        <div class="p-4 md:p-8 space-y-6 md:space-y-8">
            <!-- Messages -->
            <?php if(isset($_GET['status'])): ?>
                <?php if($_GET['status'] == 'added'): ?>
                    <div class="bg-emerald-100 text-emerald-700 p-4 rounded-2xl flex items-center gap-3 border border-emerald-200">
                        <span class="material-symbols-outlined fill-1">check_circle</span>
                        <strong>Sukses!</strong> Pengumuman berhasil dipublikasikan ke sisi user.
                    </div>
                <?php elseif($_GET['status'] == 'updated'): ?>
                    <div class="bg-blue-100 text-blue-700 p-4 rounded-2xl flex items-center gap-3 border border-blue-200">
                        <span class="material-symbols-outlined fill-1">update</span>
                        Status pengumuman ditegel dengan sukses.
                    </div>
                <?php elseif($_GET['status'] == 'deleted'): ?>
                    <div class="bg-slate-200 text-slate-700 p-4 rounded-2xl flex items-center gap-3 border border-slate-300">
                        <span class="material-symbols-outlined fill-1">delete</span>
                        Pengumuman telah ditarik dan dihapus dari server.
                    </div>
                <?php elseif($_GET['status'] == 'error'): ?>
                    <div class="bg-red-100 text-red-700 p-4 rounded-2xl flex items-center gap-3 border border-red-200">
                        <span class="material-symbols-outlined fill-1">error</span>
                        Terjadi kesalahan saat memproses data. Coba lagi.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-10">
                
                <!-- Registration Form Panel -->
                <div class="xl:col-span-4 h-fit">
                    <section class="bg-white p-8 rounded-[2.5rem] border border-outline-variant/10 shadow-2xl shadow-indigo-900/5 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-8 opacity-5 text-primary rotate-12 pointer-events-none">
                            <span class="material-symbols-outlined text-[80px]">campaign</span>
                        </div>
                        
                        <h2 class="flex items-center gap-3 font-headline text-lg font-black text-on-surface uppercase tracking-tight mb-8">
                            <span class="material-symbols-outlined text-primary fill-1">add_alert</span>
                            Buat Pengumuman Baru
                        </h2>

                        <form action="kelola_pengumuman.php" method="POST" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2">Judul Peringatan</label>
                                <input type="text" name="title" required placeholder="Cth: Server Maintenance Weekend" 
                                       class="block w-full px-5 py-4 bg-surface-container-low border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-2 focus:ring-primary/20 transition-all text-sm">
                            </div>

                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2">Tingkat Urgensi</label>
                                <select name="urgency" required class="block w-full px-5 py-4 bg-surface-container-low border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-2 focus:ring-primary/20 transition-all text-sm">
                                    <option value="Normal">Normal (Info Biru)</option>
                                    <option value="Tinggi">Tinggi (Peringatan Merah)</option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2">Isi Pesan / Instruksi</label>
                                <textarea name="content" required rows="4" placeholder="Detail lengkap pengumuman..." 
                                          class="block w-full px-5 py-4 bg-surface-container-low border-0 rounded-2xl text-on-surface font-medium outline-none focus:ring-2 focus:ring-primary/20 transition-all text-sm"></textarea>
                            </div>

                            <div class="flex items-center gap-3 ml-2 pt-2">
                                <input type="checkbox" name="is_active" id="is_active" value="1" checked class="w-5 h-5 text-primary bg-surface-container border-outline rounded focus:ring-primary focus:ring-2 outline-none cursor-pointer">
                                <label for="is_active" class="text-sm font-bold text-on-surface-variant cursor-pointer select-none">Langsung Aktifkan & Broadcast</label>
                            </div>
                            
                            <button type="submit" name="add_pengumuman" class="w-full mt-4 py-5 bg-gradient-to-br from-indigo-600 to-indigo-500 text-white font-headline font-black rounded-2xl shadow-xl shadow-indigo-900/10 hover:shadow-indigo-900/30 hover:-translate-y-1 transition-all active:scale-[0.98] uppercase tracking-widest text-xs flex items-center justify-center gap-3 group/btn">
                                <span class="material-symbols-outlined text-lg fill-1 group-hover/btn:scale-110 transition-transform">send</span>
                                Publikasikan Sekarang
                            </button>
                        </form>
                    </section>
                </div>

                <!-- Database List Panel -->
                <div class="xl:col-span-8">
                    <section class="bg-white rounded-[2.5rem] border border-outline-variant/10 shadow-sm overflow-hidden min-h-[400px]">
                        <div class="p-8 border-b border-outline-variant/5">
                            <h2 class="font-headline text-xl font-bold text-on-surface italic uppercase tracking-tighter">Timeline <span class="text-primary italic">Distribusi</span></h2>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-on-surface-variant text-[10px] font-black uppercase tracking-[0.2em] bg-surface-container-low/30 border-b border-outline-variant/10 whitespace-nowrap">
                                        <th class="px-6 py-5">Status</th>
                                        <th class="px-6 py-5">Judul & Detail</th>
                                        <th class="px-6 py-5 text-center">Urgensi</th>
                                        <th class="px-6 py-5 text-right w-32">Operasi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/5">
                                    <?php if(!$query->isEmpty()): ?>
                                        <?php foreach($query as $doc): 
                                            $row = $doc->data();
                                            $row['id'] = $doc->id();
                                            $isActive = $row['is_active'] ?? false;
                                        ?>
                                        <tr class="group hover:bg-surface-variant/5 transition-all">
                                            <td class="px-6 py-6 w-24 text-center align-top">
                                                <form action="kelola_pengumuman.php" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="current_status" value="<?php echo $isActive ? '1' : '0'; ?>">
                                                    <?php if($isActive): ?>
                                                        <button type="submit" name="toggle_status" title="Nonaktifkan" class="inline-flex items-center justify-center p-2 rounded-xl bg-emerald-100 text-emerald-700 hover:bg-slate-200 transition-all font-bold">
                                                            <span class="material-symbols-outlined fill-1">toggle_on</span>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" name="toggle_status" title="Aktifkan" class="inline-flex items-center justify-center p-2 rounded-xl bg-slate-200 text-slate-500 hover:bg-emerald-100 hover:text-emerald-700 transition-all font-bold">
                                                            <span class="material-symbols-outlined">toggle_off</span>
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                            </td>
                                            <td class="px-6 py-6 align-top">
                                                <div class="flex flex-col gap-1">
                                                    <span class="font-headline font-bold text-on-surface text-base uppercase"><?php echo htmlspecialchars($row['title']); ?></span>
                                                    <span class="text-xs text-on-surface-variant italic"><?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?> • by <?php echo htmlspecialchars($row['author'] ?? 'System'); ?></span>
                                                    <p class="text-sm text-slate-600 mt-2 max-w-lg leading-relaxed"><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
                                                </div>
                                            </td>
                                            <td class="px-6 py-6 text-center align-top">
                                                <?php if($row['urgency'] === 'Tinggi'): ?>
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-red-50 text-[10px] font-black text-red-700 border border-red-100 uppercase tracking-widest">
                                                        Tinggi
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-blue-50 text-[10px] font-black text-blue-700 border border-blue-100 uppercase tracking-widest">
                                                        Normal
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-6 text-right align-top">
                                                <form action="kelola_pengumuman.php" method="POST" onsubmit="return confirm('Hapus permanen pengumuman ini?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" name="delete_pengumuman" class="w-10 h-10 inline-flex items-center justify-center bg-error/5 text-error rounded-xl hover:bg-error hover:text-white transition-all shadow-sm border border-error/10 hover:shadow-error/20 active:scale-90 group/del">
                                                        <span class="material-symbols-outlined text-xl group-hover/del:fill-1">delete</span>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-20 text-center">
                                                <div class="flex flex-col items-center justify-center opacity-40 grayscale group hover:grayscale-0 transition-all">
                                                    <span class="material-symbols-outlined text-[64px] mb-4 text-outline group-hover:animate-bounce">notifications_off</span>
                                                    <p class="font-headline font-black text-on-surface uppercase tracking-widest text-xs">Belum ada Pengumuman</p>
                                                </div>
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
