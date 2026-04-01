<?php
session_start();
require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../config/csrf_helper.php';

// Proteksi halaman: Memastikan hanya user yang sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php"); 
    exit();
}

// Ambil data user untuk identitas pengusul dari Firestore
$user_id = $_SESSION['user_id'];
try {
    $userSnap = $db->collection('users')->document($user_id)->snapshot();
    $user_data = $userSnap->exists() ? $userSnap->data() : [];
} catch (Exception $e) {
    $user_data = [];
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Maintenance Reporting</title>
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
<body class="bg-surface font-body text-on-surface antialiased overflow-x-hidden min-h-screen">
    
    <?php include __DIR__ . '/../includes/navbar_user.php'; ?>

    <!-- Main Content -->
    <main class="max-w-[1240px] mx-auto px-6 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            
            <!-- Left Panel: Information & Instructions -->
            <div class="lg:col-span-5 space-y-8">
                <div>
                    <h2 class="font-headline text-4xl font-extrabold text-on-surface tracking-tight leading-tight uppercase italic underline decoration-primary/30 underline-offset-8">Maintenance <span class="text-primary italic">Request</span></h2>
                    <p class="text-on-surface-variant font-medium mt-6 leading-relaxed">Laporkan setiap kendala perangkat TI Anda secara mendetail untuk mempermudah tim teknis melakukan diagnosis awal.</p>
                </div>

                <!-- Info Card -->
                <div class="bg-white p-8 rounded-[2.5rem] border border-outline-variant/5 shadow-2xl shadow-indigo-900/5 space-y-6 relative overflow-hidden group hover:shadow-primary/5 transition-all">
                    <div class="absolute top-0 right-0 p-8 opacity-5 text-primary">
                        <span class="material-symbols-outlined text-[120px]">help</span>
                    </div>
                    
                    <h3 class="font-headline font-bold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">tips_and_updates</span>
                        Panduan Pelaporan
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="flex gap-4 p-4 rounded-2xl hover:bg-surface-container transition-colors">
                            <span class="w-8 h-8 rounded-full bg-primary-fixed flex items-center justify-center text-primary font-black text-xs shrink-0">1</span>
                            <div>
                                <p class="text-sm font-bold text-on-surface">Pilih Kategori Perangkat</p>
                                <p class="text-[11px] text-on-surface-variant leading-tight">Pastikan jenis perangkat sesuai dengan master kategori kami.</p>
                            </div>
                        </div>
                        <div class="flex gap-4 p-4 rounded-2xl hover:bg-surface-container transition-colors">
                            <span class="w-8 h-8 rounded-full bg-primary-fixed flex items-center justify-center text-primary font-black text-xs shrink-0">2</span>
                            <div>
                                <p class="text-sm font-bold text-on-surface">Detail Gejala</p>
                                <p class="text-[11px] text-on-surface-variant leading-tight">Sebutkan sejak kapan kendala muncul dan pesan error yang terlihat.</p>
                            </div>
                        </div>
                        <div class="flex gap-4 p-4 rounded-2xl hover:bg-surface-container transition-colors">
                            <span class="w-8 h-8 rounded-full bg-primary-fixed flex items-center justify-center text-primary font-black text-xs shrink-0">3</span>
                            <div>
                                <p class="text-sm font-bold text-on-surface">Lampirkan Foto</p>
                                <p class="text-[11px] text-on-surface-variant leading-tight">Foto fisik kerusakan sangat membantu kami melakukan estimasi pengerjaan.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Support Info -->
                <div class="bg-surface-container p-6 rounded-3xl flex items-center justify-between group cursor-pointer hover:bg-white transition-all shadow-sm border border-outline-variant/10">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-primary shadow-sm border border-outline-variant/20">
                            <span class="material-symbols-outlined">headset_mic</span>
                        </div>
                        <div>
                            <p class="text-xs font-black text-outline uppercase tracking-widest">Emergency Support</p>
                            <p class="text-sm font-bold text-on-surface">Telepon Ekstensi: 1234 (Helpdesk)</p>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-outline group-hover:text-primary transition-all group-hover:translate-x-1">arrow_forward</span>
                </div>
            </div>

            <!-- Right Panel: The Form -->
            <div class="lg:col-span-7">
                <form action="../config/proses_maintenance.php" method="POST" enctype="multipart/form-data" class="bg-white p-10 rounded-[3rem] border border-outline-variant/5 shadow-2xl shadow-indigo-900/5 space-y-8 relative overflow-hidden">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <!-- Decorative Element -->
                    <div class="absolute top-0 left-0 w-2 h-full bg-gradient-to-b from-primary to-primary-container"></div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2 leading-none">Identitas Pemohon</label>
                            <div class="relative group">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors text-lg">person</span>
                                <input class="block w-full pl-12 pr-4 py-4 bg-surface-container-low border-0 rounded-2xl font-bold text-on-surface-variant italic cursor-not-allowed text-sm" 
                                    value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" disabled type="text"/>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2 leading-none">Divisi / Dept</label>
                            <div class="relative group">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors text-lg">apartment</span>
                                <input class="block w-full pl-12 pr-4 py-4 bg-surface-container-low border-0 rounded-2xl font-bold text-on-surface-variant italic cursor-not-allowed text-sm" 
                                    value="<?php echo htmlspecialchars($user_data['department'] ?? ''); ?>" disabled type="text"/>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2 leading-none">Kategori Kerusakan</label>
                            <div class="relative group">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-primary transition-colors text-lg">category</span>
                                <select name="layanan" required class="block w-full pl-12 pr-10 py-4 bg-surface-container-low border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-2 focus:ring-primary/20 appearance-none transition-all text-sm">
                                    <option value="">-- Pilih Jenis --</option>
                                    <option value="Laptop/PC">Laptop / Komputer</option>
                                    <option value="Printer/Scanner">Printer / Scanner</option>
                                    <option value="Jaringan/WiFi">Perangkat Jaringan (WiFi/Switch)</option>
                                    <option value="Server/Aplikasi">Server / Software Aplikasi</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2 leading-none">Identitas Barang (Merk/Tipe)</label>
                            <div class="relative group">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-primary transition-colors text-lg">label</span>
                                <input name="judul" required placeholder="Contoh: Dell Latitude 5420" class="block w-full pl-12 pr-4 py-4 bg-surface-container-low border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-2 focus:ring-primary/20 transition-all text-sm" type="text"/>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2 leading-none">Deskripsi Kendala</label>
                        <div class="relative group">
                            <span class="material-symbols-outlined absolute left-4 top-6 text-primary transition-colors text-lg">description</span>
                            <textarea name="deskripsi" required rows="4" placeholder="Jelaskan secara detail, misal: Blue screen saat membuka Chrome..." class="block w-full pl-12 pr-4 py-4 bg-surface-container-low border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-2 focus:ring-primary/20 transition-all text-sm min-h-[140px]"></textarea>
                        </div>
                    </div>

                    <!-- Modern File Upload Area -->
                    <div class="space-y-4">
                        <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2 leading-none">Lampiran Foto (Wajib)</label>
                        <div class="relative group">
                            <input type="file" name="lampiran" id="file-upload" accept=".jpg, .jpeg, .png, .pdf" required 
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" onchange="updateFileName()">
                            <div id="dropzone" class="border-2 border-dashed border-outline-variant/30 rounded-3xl p-10 text-center bg-surface-container-low/30 group-hover:border-primary/50 group-hover:bg-primary-fixed/5 transition-all duration-300 flex flex-col items-center justify-center gap-3">
                                <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center text-primary shadow-sm border border-outline-variant/20 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">add_photo_alternate</span>
                                </div>
                                <div>
                                    <p id="file-label" class="text-sm font-bold text-on-surface leading-tight">Klik atau Seret Lampiran</p>
                                    <p class="text-[10px] text-outline font-medium mt-1 uppercase tracking-tighter">JPG, PNG atau PDF (Maks 2MB)</p>
                                </div>
                                <div id="file-name-info" class="hidden animate-in fade-in slide-in-from-top-2 duration-300 mt-2">
                                    <div class="px-4 py-2 bg-primary/10 rounded-full border border-primary/20 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-sm text-primary">check_circle</span>
                                        <span id="file-name-display" class="text-[10px] font-bold text-primary truncate max-w-[200px]">document_kerusakan.jpg</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full bg-gradient-to-br from-primary to-primary-container text-white font-headline font-black py-5 rounded-2xl shadow-xl shadow-primary/20 hover:shadow-primary/40 hover:-translate-y-1 transition-all active:scale-[0.98] uppercase tracking-[0.2em] text-xs flex items-center justify-center gap-3">
                            <span class="material-symbols-outlined text-lg fill-1">send</span>
                            Kirim Laporan Maintenance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function updateFileName() {
            const input = document.getElementById('file-upload');
            const displayInfo = document.getElementById('file-name-info');
            const displayText = document.getElementById('file-name-display');
            const label = document.getElementById('file-label');
            const dropzone = document.getElementById('dropzone');

            if (input.files.length > 0) {
                displayText.innerText = input.files[0].name;
                displayInfo.classList.remove('hidden');
                label.innerText = "File Berhasil Dipilih";
                dropzone.classList.add('border-primary/50', 'bg-primary-fixed/5');
                dropzone.classList.remove('border-outline-variant/30', 'bg-surface-container-low/30');
            }
        }
    </script>
</body>
</html>