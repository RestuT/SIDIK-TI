<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>IT Procurement | Strategic Infrastructure Sourcing</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700&amp;display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
          tailwind.config = {
            darkMode: "class",
            theme: {
              extend: {
                colors: {
                  "primary": "#3525cd",
                  "primary-container": "#4f46e5",
                  "secondary": "#0051d5",
                  "secondary-container": "#316bf3",
                  "surface": "#f7f9fb",
                  "on-surface": "#191c1e",
                  "on-surface-variant": "#464555",
                  "surface-container-low": "#f2f4f6",
                  "surface-container-lowest": "#ffffff",
                  "outline-variant": "#c7c4d8",
                  "amber-600": "#d97706",
                },
                fontFamily: {
                  "headline": ["Plus Jakarta Sans"],
                  "body": ["Inter"],
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
            body { font-family: 'Inter', sans-serif; min-height: 100dvh; }
            h1, h2, h3, .font-headline { font-family: 'Plus Jakarta Sans', sans-serif; }
        </style>
    <?php include_once __DIR__ . '/includes/firebase_js.php'; ?>
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
<body class="bg-surface text-on-surface selection:bg-primary-container/30">
    <!-- TopAppBar -->
    <header class="fixed top-0 w-full flex justify-between items-center px-4 md:px-6 h-16 bg-white/80 backdrop-blur-xl z-50 shadow-sm border-b border-indigo-50/50">
        <div class="flex items-center gap-2 md:gap-4">
            <a href="index.php" class="material-symbols-outlined text-slate-500 p-2 hover:bg-slate-50 transition-all rounded-xl md:rounded-full">arrow_back</a>
            <div class="flex items-center gap-2">
                <img src="assets/img/logo.png" alt="Logo" class="h-8 md:h-10 w-auto">
                <span class="text-lg md:text-xl font-extrabold bg-gradient-to-br from-indigo-600 to-indigo-400 bg-clip-text text-transparent">SIDIK-TI</span>
            </div>
        </div>
        <div class="hidden md:flex items-center space-x-8">
            <span class="text-slate-400 font-bold font-plus-jakarta text-xs uppercase tracking-widest">Resource Acquisition Guidelines</span>
        </div>
        <div class="flex items-center">
            <span class="text-secondary font-black text-[9px] md:text-[10px] uppercase tracking-widest bg-blue-50 px-2 md:px-3 py-1 rounded-full border border-blue-100">Procurement Module</span>
        </div>
    </header>

    <main class="pt-32 pb-32 px-4 md:px-10 max-w-7xl mx-auto space-y-24">
        <!-- Header Section -->
        <div class="max-w-4xl space-y-4 md:space-y-6 text-center md:text-left">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-50 text-secondary font-semibold text-[10px] md:text-xs uppercase tracking-[0.2em] mx-auto md:mx-0">
                <span class="w-1.5 h-1.5 rounded-full bg-secondary animate-pulse"></span>
                Strategic Investment
            </div>
            <h1 class="text-3xl md:text-6xl font-extrabold tracking-tight text-on-surface leading-tight">
                Panduan Strategis <span class="bg-gradient-to-br from-secondary to-primary bg-clip-text text-transparent italic tracking-tighter">Pengadaan Aset TI</span>
            </h1>
            <p class="text-on-surface-variant text-base md:text-xl font-medium max-w-3xl leading-relaxed mx-auto md:mx-0">
                Pengadaan bukan sekadar membeli barang baru, melainkan tentang memilih alat yang tepat untuk mengakselerasi produktivitas dengan efisiensi biaya yang terukur.
            </p>
        </div>

        <!-- Grid Content -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
            <!-- Standards -->
            <div class="space-y-8">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-1 bg-secondary rounded-full"></div>
                    <span class="text-xs font-black uppercase tracking-[0.4em] text-secondary">Hardware Standards</span>
                </div>
                
                <div class="group p-6 md:p-8 rounded-2xl md:rounded-3xl bg-white border border-outline-variant/10 shadow-sm hover:shadow-2xl hover:shadow-blue-500/5 transition-all duration-500">
                    <div class="flex items-center gap-4 mb-6 md:mb-8">
                        <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl md:rounded-2xl bg-blue-50 text-secondary flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-2xl md:text-3xl">fact_check</span>
                        </div>
                        <h3 class="text-xl md:text-2xl font-bold tracking-tight">Kriteria Pemilihan</h3>
                    </div>
                    <ul class="space-y-6">
                        <li class="flex gap-4">
                            <span class="material-symbols-outlined text-amber-500 shrink-0">grade</span>
                            <div>
                                <p class="font-bold text-sm mb-1">Business-Grade Reliability</p>
                                <p class="text-on-surface-variant text-xs leading-relaxed">Utamakan perangkat lini bisnis (misal: Latitude, ThinkPad, EliteBook) karena memiliki durabilitas lebih tinggi dan dukungan suku cadang jangka panjang.</p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="material-symbols-outlined text-amber-500 shrink-0">grade</span>
                            <div>
                                <p class="font-bold text-sm mb-1">Performance Benchmarking</p>
                                <p class="text-on-surface-variant text-xs leading-relaxed">Pastikan spesifikasi teknis mendukung beban kerja 3-5 tahun ke depan untuk menghindari *obsolescence* (keusangan) dini.</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="group p-6 md:p-8 rounded-2xl md:rounded-3xl bg-secondary text-white border border-white/10 shadow-2xl transition-all duration-500">
                    <div class="flex items-center gap-4 mb-6 md:mb-8">
                        <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl md:rounded-2xl bg-white/10 text-white flex items-center justify-center">
                            <span class="material-symbols-outlined text-2xl md:text-3xl">balance</span>
                        </div>
                        <h3 class="text-xl md:text-2xl font-bold tracking-tight">Repair vs Replace</h3>
                    </div>
                    <p class="text-xs md:text-sm opacity-80 leading-relaxed mb-6">
                        Gunakan metode 50-50: Jika biaya perbaikan melebihi 50% dari harga unit baru, atau unit sudah berusia lebih dari 4 tahun, maka pengadaan unit baru adalah pilihan yang lebih ekonomis secara jangka panjang.
                    </p>
                    <div class="flex items-center gap-2 text-[10px] md:text-xs font-bold uppercase tracking-widest">
                        <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                        Executive Decision Matrix
                    </div>
                </div>
            </div>

            <!-- Workflow -->
            <div class="space-y-8">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-1 bg-primary rounded-full"></div>
                    <span class="text-xs font-black uppercase tracking-[0.4em] text-primary">Procurement Workflow</span>
                </div>

                <div class="group p-6 md:p-8 rounded-2xl md:rounded-3xl bg-white border border-outline-variant/10 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/5 transition-all duration-500">
                    <div class="flex items-center gap-4 mb-6 md:mb-8">
                        <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl md:rounded-2xl bg-indigo-50 text-primary flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-2xl md:text-3xl">account_tree</span>
                        </div>
                        <h3 class="text-xl md:text-2xl font-bold tracking-tight">Proses Pengajuan</h3>
                    </div>
                    <div class="space-y-8 relative before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[2px] before:bg-indigo-50">
                        <div class="relative pl-10">
                            <div class="absolute left-0 top-1 w-6 h-6 rounded-full bg-indigo-600 flex items-center justify-center text-[10px] text-white font-bold">1</div>
                            <p class="font-bold text-sm">Justifikasi Kebutuhan</p>
                            <p class="text-on-surface-variant text-xs">Jelaskan mengapa unit dibutuhkan (misal: penambahan staff baru atau unit lama rusak total).</p>
                        </div>
                        <div class="relative pl-10">
                            <div class="absolute left-0 top-1 w-6 h-6 rounded-full bg-indigo-600 flex items-center justify-center text-[10px] text-white font-bold">2</div>
                            <p class="font-bold text-sm">Estimasi Anggaran</p>
                            <p class="text-on-surface-variant text-xs">Lampirkan spesifikasi teknis dan estimasi harga pasaran untuk validasi budget.</p>
                        </div>
                        <div class="relative pl-10">
                            <div class="absolute left-0 top-1 w-6 h-6 rounded-full bg-indigo-600 flex items-center justify-center text-[10px] text-white font-bold">3</div>
                            <p class="font-bold text-sm">Pemeriksaan Internal</p>
                            <p class="text-on-surface-variant text-xs">Tim IT akan memverifikasi ketersediaan stok atau melakukan approval pengadaan.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-surface-container-low rounded-2xl md:rounded-[2rem] p-6 md:p-8 space-y-4 border border-outline-variant/10">
                    <span class="material-symbols-outlined text-amber-600 text-3xl md:text-4xl">warning</span>
                    <h4 class="font-bold text-sm md:text-base">Penting:</h4>
                    <p class="text-[11px] md:text-xs text-on-surface-variant leading-relaxed">
                        Segala bentuk pengadaan aset TI wajib melalui sistem **SIDIK-TI** untuk pendataan nomor aset, masa garansi, dan sinkronisasi dengan jadwal pemeliharaan rutin di masa mendatang.
                    </p>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="relative overflow-hidden rounded-2xl md:rounded-[3rem] bg-indigo-900 p-8 md:p-16 text-white text-center italic border border-white/10 shadow-2xl">
            <div class="absolute inset-0 opacity-10 pointer-events-none">
                <div class="absolute top-0 left-0 w-full h-full bg-[radial-gradient(circle_at_center,_white_1px,_transparent_1px)] bg-[size:30px_30px] md:bg-[size:40px_40px]"></div>
            </div>
            <div class="relative z-10 max-w-2xl mx-auto space-y-6 md:space-y-8">
                <h3 class="text-2xl md:text-5xl font-extrabold tracking-tighter leading-tight">Sudah Menentukan Kebutuhan Anda?</h3>
                <p class="text-sm md:text-base opacity-80 font-medium">Beralih ke dashboard untuk memulai proses pengadaan formal dengan tim logistik dan IT kami.</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center pt-2">
                    <a href="index.php" class="px-10 py-5 bg-white text-indigo-900 rounded-2xl font-black text-[9px] md:text-[10px] uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-xl shadow-white/10">
                        Ajukan Sekarang
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer class="py-12 text-center text-[10px] font-black uppercase tracking-[0.4em] text-slate-400 border-t border-indigo-50">
        © 2026 Sidik-TI Resource Center • Strategic Acquisition.
    </footer>
</body>
</html>
