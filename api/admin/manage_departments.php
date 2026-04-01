<?php
session_start();
include '../config/database.php';
include '../config/csrf_helper.php';

// Proteksi akses untuk role admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_admin.php");
    exit();
}

// Mengambil daftar departemen
$query = $db->collection('departments')->orderBy('nama_dept', 'ASC')->documents();
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Institutional Hierarchy</title>
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
<body class="bg-surface font-body text-on-surface antialiased flex min-h-screen">

    <?php include '../includes/navbar_admin.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <!-- Header Bar -->
        <header class="flex items-center justify-between px-8 py-5 border-b border-outline-variant/10 sticky top-0 bg-surface/80 backdrop-blur-xl z-20">
            <div>
                <h1 class="font-headline text-2xl font-extrabold text-on-surface tracking-tight leading-none italic uppercase">Master <span class="text-primary italic">Department</span></h1>
                <p class="text-[10px] text-outline font-black uppercase tracking-widest mt-1">Struktur Organisasi & Hierarki Institusi</p>
            </div>
            <div class="flex items-center gap-4">
                <button class="w-10 h-10 rounded-full hover:bg-surface-container flex items-center justify-center text-outline transition-all">
                    <span class="material-symbols-outlined">account_tree</span>
                </button>
            </div>
        </header>

        <div class="p-8 space-y-8">
            <!-- Messages -->
            <?php if(isset($_GET['status']) && $_GET['status'] == 'added'): ?>
                <div class="bg-emerald-100 text-emerald-700 p-4 rounded-2xl flex items-center gap-3 border border-emerald-200 animate-in fade-in slide-in-from-top-2 duration-300 font-bold uppercase tracking-widest text-xs">
                    <span class="material-symbols-outlined fill-1" style="font-variation-settings:'FILL' 1">check_circle</span>
                    Departemen baru berhasil didaftarkan ke sistem.
                </div>
            <?php elseif(isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
                <div class="bg-primary-fixed/10 text-primary p-4 rounded-2xl flex items-center gap-3 border border-primary-fixed/30 animate-in fade-in slide-in-from-top-2 duration-300 font-bold uppercase tracking-widest text-xs">
                    <span class="material-symbols-outlined fill-1" style="font-variation-settings:'FILL' 1">info</span>
                    Identitas departemen telah dieliminasi.
                </div>
            <?php elseif(isset($_GET['status']) && $_GET['status'] == 'error'): ?>
                <div class="bg-error-container text-on-error-container p-4 rounded-2xl flex items-center gap-3 border border-error/20 animate-in fade-in slide-in-from-top-2 duration-300 font-bold uppercase tracking-widest text-xs">
                    <span class="material-symbols-outlined fill-1" style="font-variation-settings:'FILL' 1">report</span>
                    Gagal. Duplikasi atau interferensi basis data terdeteksi.
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                
                <!-- Registration Form Panel -->
                <div class="lg:col-span-4 h-fit">
                    <section class="bg-white p-8 rounded-[2.5rem] border border-outline-variant/10 shadow-2xl shadow-indigo-900/5 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-8 opacity-5 text-primary rotate-12">
                            <span class="material-symbols-outlined text-[80px]">corporate_fare</span>
                        </div>
                        
                        <h2 class="flex items-center gap-3 font-headline text-lg font-black text-on-surface uppercase tracking-tight mb-8">
                            <span class="material-symbols-outlined text-primary fill-1">add_business</span>
                            Registrasi Unit
                        </h2>

                        <form action="../config/proses_department.php" method="POST" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2">Identitas Departemen</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-primary text-lg">domain</span>
                                    <input type="text" name="nama_dept" required placeholder="Cth: IT / Human Resource" 
                                        class="block w-full pl-12 pr-4 py-4 bg-surface-container-low border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-2 focus:ring-primary/20 transition-all text-sm">
                                </div>
                            </div>
                            
                            <button type="submit" name="add_dept" class="w-full py-5 bg-gradient-to-br from-indigo-600 to-indigo-500 text-white font-headline font-black rounded-2xl shadow-xl shadow-indigo-900/10 hover:shadow-indigo-900/30 hover:-translate-y-1 transition-all active:scale-[0.98] uppercase tracking-widest text-xs flex items-center justify-center gap-3 group/btn">
                                <span class="material-symbols-outlined text-lg fill-1 group-hover/btn:scale-110 transition-transform">save</span>
                                Simpan Unit Baru
                            </button>
                        </form>

                        <div class="mt-8 p-6 bg-surface-container-low rounded-3xl border border-outline-variant/10 group-hover:bg-primary-fixed/5 transition-all">
                            <div class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-primary text-base mt-0.5">info</span>
                                <p class="text-[10px] text-on-surface-variant font-bold leading-relaxed uppercase tracking-tight">
                                    Data ini berfungsi sebagai anchor validasi anggaran dan pendaftaran personil. Pastikan sinkron dengan SOTK Institusi.
                                </p>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Database List Panel -->
                <div class="lg:col-span-8">
                    <section class="bg-white rounded-[2.5rem] border border-outline-variant/10 shadow-sm overflow-hidden min-h-[400px]">
                        <div class="p-8 border-b border-outline-variant/5">
                            <h2 class="font-headline text-xl font-bold text-on-surface italic uppercase tracking-tighter">Current <span class="text-primary italic">Structure</span></h2>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-on-surface-variant text-[10px] font-black uppercase tracking-[0.2em] bg-surface-container-low/30 border-b border-outline-variant/10">
                                        <th class="px-8 py-5 w-20 text-center">No</th>
                                        <th class="px-8 py-5">Identitas Unit / Departemen</th>
                                        <th class="px-8 py-5 text-center">Status</th>
                                        <th class="px-8 py-5 text-right w-32">Operation</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/5">
                                    <?php if(!$query->isEmpty()): $no = 1; ?>
                                        <?php foreach($query as $doc): 
                                            $row = $doc->data();
                                            $row['id'] = $doc->id();
                                        ?>
                                        <tr class="group hover:bg-surface-variant/5 transition-all">
                                            <td class="px-8 py-6 text-center">
                                                <span class="text-xs font-black text-outline italic">#<?php echo sprintf("%02d", $no++); ?></span>
                                            </td>
                                            <td class="px-8 py-6">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-2 h-2 rounded-full bg-primary grow-0 shrink-0 group-hover:scale-150 transition-transform"></div>
                                                    <span class="font-headline font-bold text-on-surface group-hover:text-primary transition-colors text-base uppercase italic underline decoration-transparent group-hover:decoration-primary/30 underline-offset-4 decoration-2"><?php echo htmlspecialchars($row['nama_dept']); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-8 py-6 text-center">
                                                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-[9px] font-black text-emerald-700 border border-emerald-100 uppercase tracking-widest">
                                                    <span class="w-1 h-1 rounded-full bg-emerald-500"></span>
                                                    Verified
                                                </div>
                                            </td>
                                            <td class="px-8 py-6 text-right">
                                                <a href="../config/hapus_department.php?id=<?php echo $row['id']; ?>" 
                                                   onclick="return confirm('Yakin ingin menghapus departemen ini? Ini dapat menyebabkan inkonsistensi data jika sudah digunakan oleh anggaran/user.');" 
                                                   class="w-10 h-10 inline-flex items-center justify-center bg-error/5 text-error rounded-xl hover:bg-error hover:text-white transition-all shadow-sm border border-error/10 hover:shadow-error/20 active:scale-90 group/del">
                                                    <span class="material-symbols-outlined text-xl group-hover/del:fill-1">delete_sweep</span>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="px-8 py-20 text-center">
                                                <div class="flex flex-col items-center justify-center opacity-40 grayscale group hover:grayscale-0 transition-all">
                                                    <span class="material-symbols-outlined text-[64px] mb-4 text-outline group-hover:animate-bounce">domain_disabled</span>
                                                    <p class="font-headline font-black text-on-surface uppercase tracking-widest text-xs">Hierarchy Under Construction</p>
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
