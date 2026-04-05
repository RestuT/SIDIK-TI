<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>IT Maintenance | Digital Infrastructure Excellence</title>
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
                  "emerald-600": "#059669",
                  "orange-600": "#ea580c",
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
        </style>
    <?php include_once __DIR__ . '/includes/firebase_js.php'; ?>
</head>
<body class="bg-surface text-on-surface selection:bg-primary-container/30">
    <!-- TopAppBar -->
    <header class="fixed top-0 w-full flex justify-between items-center px-4 md:px-6 h-16 bg-white/80 backdrop-blur-xl z-50 shadow-sm border-b border-indigo-50/50">
        <div class="flex items-center gap-2 md:gap-4">
            <a href="index.php" class="material-symbols-outlined text-slate-500 p-2 hover:bg-slate-50 transition-all rounded-xl md:rounded-full">arrow_back</a>
            <span class="text-lg md:text-xl font-extrabold bg-gradient-to-br from-indigo-600 to-indigo-400 bg-clip-text text-transparent">SIDIK-TI</span>
        </div>
        <div class="hidden md:flex items-center space-x-8">
            <span class="text-slate-400 font-bold font-plus-jakarta text-xs uppercase tracking-widest">Digital Infrastructure Guidelines</span>
        </div>
        <div class="flex items-center">
            <span class="text-indigo-600 font-black text-[9px] md:text-[10px] uppercase tracking-widest bg-indigo-50 px-2 md:px-3 py-1 rounded-full border border-indigo-100">Maintenance Module</span>
        </div>
    </header>

    <main class="pt-32 pb-32 px-4 md:px-10 max-w-7xl mx-auto space-y-24">
        <!-- Header Section -->
        <div class="max-w-4xl space-y-4 md:space-y-6">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-50 text-emerald-700 font-semibold text-[10px] md:text-xs uppercase tracking-[0.2em]">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Operational Excellence
            </div>
            <h1 class="text-3xl md:text-6xl font-extrabold tracking-tight text-on-surface leading-tight">
                Strategi Pemeliharaan <span class="bg-gradient-to-br from-emerald-600 to-primary bg-clip-text text-transparent italic tracking-tighter">Infrastruktur Digital</span>
            </h1>
            <p class="text-on-surface-variant text-base md:text-xl font-medium max-w-3xl leading-relaxed">
                Pemeliharaan TI bukan sekadar memperbaiki yang rusak, melainkan cara memanjangkan siklus hidup aset dan memastikan keberlangsungan operasional tanpa kendala.
            </p>
        </div>

        <!-- Grid Content -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
            <!-- Hardware Ecosystem -->
            <div class="space-y-8">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-1 bg-primary rounded-full"></div>
                    <span class="text-xs font-black uppercase tracking-[0.4em] text-primary">Hardware Ecosystem</span>
                </div>
                
                <div class="group p-6 md:p-8 rounded-2xl md:rounded-3xl bg-white border border-outline-variant/10 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/5 transition-all duration-500">
                    <div class="flex items-center gap-4 mb-6 md:mb-8">
                        <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl md:rounded-2xl bg-indigo-50 text-primary flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-2xl md:text-3xl">laptop_mac</span>
                        </div>
                        <h3 class="text-xl md:text-2xl font-bold tracking-tight">Workstation & Laptop</h3>
                    </div>
                    <ul class="space-y-6">
                        <li class="flex gap-4">
                            <span class="material-symbols-outlined text-emerald-500 shrink-0">check_circle</span>
                            <div>
                                <p class="font-bold text-sm mb-1">Optimal Thermal Management</p>
                                <p class="text-on-surface-variant text-xs leading-relaxed">Hindari penggunaan laptop di atas permukaan lunak (kasur/sofa) yang menghambat ventilasi udara panas dari CPU/GPU.</p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="material-symbols-outlined text-emerald-500 shrink-0">check_circle</span>
                            <div>
                                <p class="font-bold text-sm mb-1">Battery Health Preservation</p>
                                <p class="text-on-surface-variant text-xs leading-relaxed">Jaga siklus charging di antara 20% - 80% untuk mengurangi degradasi sel ion lithium pada unit mobile workstation Anda.</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="group p-6 md:p-8 rounded-2xl md:rounded-3xl bg-white border border-outline-variant/10 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/5 transition-all duration-500">
                    <div class="flex items-center gap-4 mb-6 md:mb-8">
                        <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl md:rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-2xl md:text-3xl">print</span>
                        </div>
                        <h3 class="text-xl md:text-2xl font-bold tracking-tight">Peripherals & Network</h3>
                    </div>
                    <ul class="space-y-6">
                        <li class="flex gap-4">
                            <span class="material-symbols-outlined text-emerald-500 shrink-0">check_circle</span>
                            <div>
                                <p class="font-bold text-sm mb-1">Head Printer Longevity</p>
                                <p class="text-on-surface-variant text-xs leading-relaxed">Nyalakan dan jalankan dokumen minimal 2 kali seminggu untuk mencegah penyumbatan tinta pada saluran *thermal inkjet*.</p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="material-symbols-outlined text-emerald-500 shrink-0">check_circle</span>
                            <div>
                                <p class="font-bold text-sm mb-1">Router Thermal Cycling</p>
                                <p class="text-on-surface-variant text-xs leading-relaxed">Lakukan *power cycle* seminggu sekali untuk mendinginkan komponen IC dan membersihkan *buffer* memori perangkat jaringan.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Software Integrity -->
            <div class="space-y-8">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-1 bg-secondary rounded-full"></div>
                    <span class="text-xs font-black uppercase tracking-[0.4em] text-secondary">Software Integrity</span>
                </div>

                <div class="group p-6 md:p-8 rounded-2xl md:rounded-3xl bg-secondary-container/5 border border-outline-variant/10 shadow-sm hover:shadow-2xl hover:shadow-blue-500/5 transition-all duration-500">
                    <div class="flex items-center gap-4 mb-6 md:mb-8">
                        <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl md:rounded-2xl bg-blue-50 text-secondary flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-2xl md:text-3xl">terminal</span>
                        </div>
                        <h3 class="text-xl md:text-2xl font-bold tracking-tight">System Operations</h3>
                    </div>
                    <ul class="space-y-6">
                        <li class="flex gap-4">
                            <span class="material-symbols-outlined text-blue-500 shrink-0">security</span>
                            <div>
                                <p class="font-bold text-sm mb-1">Zero-Day Patching</p>
                                <p class="text-on-surface-variant text-xs leading-relaxed">Instal pembaruan sistem operasi dalam waktu 24 jam setelah rilis keamanan dirilis untuk mencegah eksploitasi celah digital.</p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="material-symbols-outlined text-blue-500 shrink-0">cloud_done</span>
                            <div>
                                <p class="font-bold text-sm mb-1">Data Backup Redundancy</p>
                                <p class="text-on-surface-variant text-xs leading-relaxed">Gunakan strategi backup 3-2-1: tiga salinan data, dua media yang berbeda, dan satu salinan tersimpan secara *offsite* atau di cloud.</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="bg-primary rounded-3xl md:rounded-[2.5rem] p-1 shadow-2xl">
                    <div class="bg-primary rounded-[1.4rem] md:rounded-[2.2rem] p-6 md:p-8 text-white space-y-4 md:space-y-6 border border-white/10">
                        <span class="material-symbols-outlined text-4xl md:text-5xl">verified</span>
                        <h3 class="text-xl md:text-2xl font-bold leading-tight">Mencegah Lebih Baik daripada Memperbaiki</h3>
                        <p class="opacity-80 text-xs md:text-sm leading-relaxed">
                            Biaya pemeliharaan preventif rata-rata 3x lebih efisien daripada biaya perbaikan reaktif yang mencakup kerugian waktu dan produktivitas karyawan.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Professional Tips -->
        <div class="bg-surface-container-low rounded-3xl md:rounded-[3rem] p-8 md:p-16 border border-outline-variant/10">
            <div class="max-w-4xl mx-auto flex flex-col md:flex-row items-center gap-8 md:gap-12 text-center md:text-left">
                <div class="w-full md:w-1/3 text-primary flex justify-center md:justify-start">
                    <span class="material-symbols-outlined text-6xl md:text-[8rem] opacity-20">lightbulb</span>
                </div>
                <div class="w-full md:w-2/3 space-y-4 md:space-y-6">
                    <h3 class="text-2xl md:text-3xl font-bold text-on-surface italic">IT Pro Insights</h3>
                    <p class="text-on-surface-variant text-sm md:text-base font-medium leading-relaxed">
                        Lakukan inventarisasi nomor seri perangkat Anda secara mandiri di SIDIK-TI dashboard. Jika terjadi anomali performa secara konstan selama lebih dari 3 hari operasional, segera ajukan pemeriksaan melalui modul **Corrective Maintenance**.
                    </p>
                    <a href="index.php" class="inline-flex items-center gap-3 px-8 py-4 bg-primary text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:shadow-xl hover:shadow-primary/30 transition-all">
                        Kembali ke Dashboard
                        <span class="material-symbols-outlined text-lg">home</span>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer class="py-12 text-center text-[10px] font-black uppercase tracking-[0.4em] text-slate-400 border-t border-indigo-50">
        © 2026 Sidik-TI Academic Center • All Rights Reserved.
    </footer>
</body>
</html>
