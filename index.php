<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Smart IT Infrastructure Maintenance</title>
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
                  "surface": "#f7f9fb",
                  "on-surface": "#191c1e",
                  "on-surface-variant": "#464555",
                  "surface-container-low": "#f2f4f6",
                  "surface-container-lowest": "#ffffff",
                  "outline-variant": "#c7c4d8",
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
</head>
<body class="bg-surface text-on-surface selection:bg-primary-container/30">
    <!-- TopAppBar -->
    <header class="fixed top-0 w-full flex justify-between items-center px-6 md:px-10 h-16 md:h-20 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl z-50 shadow-sm shadow-indigo-500/5">
        <div class="flex items-center gap-4">
            <span class="text-xl md:text-2xl font-extrabold bg-gradient-to-br from-indigo-600 to-indigo-400 bg-clip-text text-transparent">SIDIK-TI</span>
        </div>
        <div class="hidden md:flex items-center space-x-10">
            <a class="text-indigo-600 font-bold font-plus-jakarta text-sm uppercase tracking-widest" href="index.php">Home</a>
            <a class="text-slate-500 font-bold font-plus-jakarta text-sm uppercase tracking-widest hover:text-indigo-500 transition-colors" href="materi_maintenance.php">Maintenance</a>
            <a class="text-slate-500 font-bold font-plus-jakarta text-sm uppercase tracking-widest hover:text-indigo-500 transition-colors" href="auth/login_user.php">Procurement</a>
        </div>
        <div class="flex items-center gap-3">
            <a href="auth/login_admin.php" class="hidden sm:flex items-center gap-2 px-6 py-2.5 bg-indigo-50 text-indigo-700 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-indigo-100 transition-all">
                <span class="material-symbols-outlined text-lg">admin_panel_settings</span>
                Login Admin
            </a>
            <button class="md:hidden material-symbols-outlined text-slate-500 p-2 rounded-full hover:bg-indigo-50">menu</button>
        </div>
    </header>

    <main class="pt-24 md:pt-32 pb-32 px-4 md:px-10 max-w-7xl mx-auto space-y-20 md:space-y-32">
        <!-- Hero Section -->
        <section class="relative overflow-hidden rounded-[2.5rem] md:rounded-[4rem] bg-surface-container-low min-h-[500px] md:min-h-[600px] flex items-center p-8 md:p-20 border border-white/40 shadow-inner">
            <div class="absolute inset-0 z-0">
                <div class="absolute top-[-10%] right-[-10%] w-[500px] h-[500px] bg-primary/10 blur-[120px] rounded-full"></div>
                <div class="absolute bottom-[-10%] left-[-10%] w-[500px] h-[500px] bg-indigo-200/20 blur-[120px] rounded-full"></div>
                <img alt="IT Infrastructure" class="w-full h-full object-cover opacity-10 mix-blend-overlay" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBAfjNyMhEcIsLn1-8iTJemzXmgVux7Jg-lqBwfBxhoZzBgwPwis8a7uSjaJ9QFn38LOPy9TcI-P8C3PKMXO2z6S4D4vgqLfhuiHZIy8e8KJi3GBso4V0U68lXRDdEdfTBFF_woBikL0y0fe-FGX1_UXrbRSyvI-hiCuMJAIsJteL5HXfFxzTZSqSDLt-uhjooQlDpIsku-5tM5idApWqugPTeCQOgUuSnIxoCR9xWenWdhCvk6EjuMti1hk6Wf5jyxANS80ZNHCtE"/>
            </div>
            <div class="relative z-10 max-w-3xl space-y-10">
                <div class="inline-flex items-center gap-3 px-5 py-2 rounded-full bg-indigo-50 text-indigo-700 font-bold text-xs uppercase tracking-widest border border-indigo-100">
                    <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                    Verified IT Management System
                </div>
                <h1 class="text-4xl md:text-7xl font-extrabold tracking-tight text-on-surface leading-[1.05]">
                    Sistem Pemeliharaan Adalah <span class="bg-gradient-to-r from-primary to-indigo-400 bg-clip-text text-transparent italic">Investasi</span>.
                </h1>
                <p class="text-on-surface-variant text-lg md:text-2xl leading-relaxed max-w-2xl font-body font-medium">
                    Optimalkan kinerja infrastruktur TI Anda dengan pemeliharaan terukur dan pengadaan perangkat berkualitas tinggi melalui platform <span class="text-primary font-bold">SIDIK-TI</span>.
                </p>
                <div class="flex flex-col sm:flex-row gap-5 pt-4">
                    <a href="auth/login_user.php" class="px-10 py-5 bg-primary text-white rounded-2xl font-black shadow-2xl shadow-indigo-500/30 hover:scale-[1.05] hover:shadow-indigo-500/40 active:scale-95 transition-all duration-500 flex items-center justify-center gap-3 uppercase tracking-widest text-xs">
                        Mulai Pengajuan
                        <span class="material-symbols-outlined">rocket_launch</span>
                    </a>
                    <a href="materi_maintenance.php" class="px-10 py-5 bg-white text-on-surface font-black rounded-2xl border border-outline-variant/30 hover:bg-surface-container-low transition-all duration-300 text-center uppercase tracking-widest text-xs">
                        Pelajari Maintenance
                    </a>
                </div>
            </div>
        </section>

        <!-- Layanan Kami Section -->
        <section class="space-y-16">
            <div class="text-center space-y-4">
                <p class="text-[10px] font-black text-primary uppercase tracking-[0.4em]">Our CORE Core Services</p>
                <h2 class="text-4xl md:text-5xl font-extrabold text-on-surface tracking-tight leading-none italic uppercase">Layanan Kami</h2>
                <div class="h-1.5 w-16 bg-primary mx-auto rounded-full"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Preventive -->
                <div class="group p-10 rounded-[2.5rem] bg-surface-container-lowest border border-outline-variant/10 hover:shadow-2xl hover:shadow-indigo-500/5 transition-all duration-500 hover:-translate-y-2">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-10 group-hover:scale-110 transition-transform shadow-sm">
                        <span class="material-symbols-outlined text-3xl">shield</span>
                    </div>
                    <h3 class="text-2xl font-black mb-4 text-on-surface tracking-tight uppercase italic">Preventive</h3>
                    <p class="text-on-surface-variant leading-relaxed text-sm font-medium mb-8">
                        Tindakan pencegahan rutin untuk menjaga perangkat tetap dalam kondisi optimal sebelum terjadi kegagalan sistem.
                    </p>
                    <div class="flex items-center text-emerald-600 font-black text-[10px] uppercase tracking-widest gap-2 bg-emerald-50 w-fit px-3 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Active Protection
                    </div>
                </div>
                <!-- Corrective -->
                <div class="group p-10 rounded-[2.5rem] bg-surface-container-lowest border border-outline-variant/10 hover:shadow-2xl hover:shadow-indigo-500/5 transition-all duration-500 hover:-translate-y-2">
                    <div class="w-14 h-14 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center mb-10 group-hover:scale-110 transition-transform shadow-sm">
                        <span class="material-symbols-outlined text-3xl">construction</span>
                    </div>
                    <h3 class="text-2xl font-black mb-4 text-on-surface tracking-tight uppercase italic">Corrective</h3>
                    <p class="text-on-surface-variant leading-relaxed text-sm font-medium mb-8">
                        Perbaikan cepat dan tepat saat terjadi kerusakan guna meminimalkan downtime operasional instansi.
                    </p>
                    <div class="flex items-center text-orange-600 font-black text-[10px] uppercase tracking-widest gap-2 bg-orange-50 w-fit px-3 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                        Rapid Response
                    </div>
                </div>
                <!-- Predictive -->
                <div class="group p-10 rounded-[2.5rem] bg-surface-container-lowest border border-outline-variant/10 hover:shadow-2xl hover:shadow-indigo-500/5 transition-all duration-500 hover:-translate-y-2">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-10 group-hover:scale-110 transition-transform shadow-sm">
                        <span class="material-symbols-outlined text-3xl">insights</span>
                    </div>
                    <h3 class="text-2xl font-black mb-4 text-on-surface tracking-tight uppercase italic">Predictive</h3>
                    <p class="text-on-surface-variant leading-relaxed text-sm font-medium mb-8">
                        Analisis data performa untuk memprediksi potensi kerusakan di masa depan dan intervensi sebelum masalah muncul.
                    </p>
                    <div class="flex items-center text-indigo-600 font-black text-[10px] uppercase tracking-widest gap-2 bg-indigo-50 w-fit px-3 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                        Data Driven
                    </div>
                </div>
            </div>
        </section>

        <!-- Pilih Layanan Section -->
        <section class="space-y-16" id="modul">
            <div class="flex flex-col md:flex-row justify-between items-end gap-6">
                <div class="space-y-4">
                    <p class="text-[10px] font-black text-primary uppercase tracking-[0.4em]">Get Started</p>
                    <h2 class="text-4xl md:text-5xl font-extrabold text-on-surface tracking-tight uppercase italic">Pilih Jenis <span class="bg-gradient-to-r from-primary to-indigo-400 bg-clip-text text-transparent">Layanan</span></h2>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <!-- Maintenance Perangkat TI -->
                <div class="relative group rounded-[2.5rem] overflow-hidden aspect-[16/9] md:aspect-auto md:h-[400px] shadow-2xl">
                    <img alt="Maintenance Device" class="absolute inset-0 w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBmIo3Ug9ygg4lEfojFxYw53ixDEnF_ugh2V4zNAI_behxuDfqgpow0du_FHNvOroljBW3JMPZVH47scYzA29WXLriQ10npBRLBKW6WQQSBXoi8acZTSvVBlBYGUEp0rHw_kJi-uA2FobL4JcDk5G5FzRgvaTYvHjTDHUZW938cPErD1UfbY8v4KglFhVVdKCWonQhGj76mqB1-1iJH1it3pHAUDJzv7KB6MRv8DksPjIKk3F_3-QEvOqj4nLKKiJtXXfJPwbyY6xU"/>
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/40 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 p-10 w-full">
                        <h3 class="text-3xl font-black text-white mb-2 uppercase italic tracking-tighter">Maintenance Perangkat TI</h3>
                        <p class="text-slate-200 text-sm mb-8 max-w-sm font-medium">Perawatan berkala dan perbaikan hardware operasional kantor.</p>
                        <a href="auth/login_user.php" class="w-fit flex items-center gap-3 px-8 py-4 bg-white text-primary rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary hover:text-white transition-all shadow-xl">
                            Ajukan Maintenance
                            <span class="material-symbols-outlined text-lg">arrow_forward</span>
                        </a>
                    </div>
                </div>
                <!-- Pengadaan Barang IT -->
                <div class="relative group rounded-[2.5rem] overflow-hidden aspect-[16/9] md:aspect-auto md:h-[400px] shadow-2xl">
                    <img alt="Procurement" class="absolute inset-0 w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110" src="https://lh3.googleusercontent.com/aida-public/AB6AXuD77O9rlsEY0ewWv7HXLsFjLIPGksRnwUfgPZy90ZlNDTHJs5JQijSwiVRFJ33fN0be7q0fZHpNV01cV2MaB2cRmd7Unnoq-8KUCovLKWjw1khjJZm2HKxmGArNwpc9IzL7_VFjl_ckuBUpv7DFPtrYd8b5tc3k_mU3YD6TRbhPGRn_e2bFm5C_s9YNvK2FHfqUl3GoOC59NN5gpuLlcIlXaFruZC5WdyrPYHvPa5WJNJxXJCHh1QABuHg6pJH1TCDZy-G6oNPxgoQ"/>
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/40 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 p-10 w-full">
                        <h3 class="text-3xl font-black text-white mb-2 uppercase italic tracking-tighter">Pengadaan Barang IT</h3>
                        <p class="text-slate-200 text-sm mb-8 max-w-sm font-medium">Pengadaan unit baru untuk mendukung produktivitas perusahaan.</p>
                        <a href="auth/login_user.php" class="w-fit flex items-center gap-3 px-8 py-4 bg-white text-primary rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary hover:text-white transition-all shadow-xl">
                            Validasi Diri
                            <span class="material-symbols-outlined text-lg">lock</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-surface-container-low py-20 px-6 border-t border-indigo-100">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 justify-between items-center gap-12">
            <div class="space-y-6 text-center md:text-left">
                <span class="text-3xl font-black bg-gradient-to-br from-indigo-600 to-primary bg-clip-text text-transparent italic tracking-tighter">SIDIK-TI</span>
                <p class="text-on-surface-variant max-w-md text-sm font-medium leading-relaxed">Memberikan standar tertinggi dalam pemeliharaan infrastruktur digital Anda untuk keberlanjutan bisnis yang lebih baik.</p>
            </div>
            <div class="space-y-4 text-center md:text-right">
                <p class="text-on-surface-variant font-black text-xs uppercase tracking-[0.2em]">
                    © 2026 IT Helpdesk System • Focused on Reliability.
                </p>
                <div class="flex justify-center md:justify-end gap-6 text-slate-400">
                    <span class="material-symbols-outlined hover:text-primary transition-colors cursor-pointer">facebook</span>
                    <span class="material-symbols-outlined hover:text-primary transition-colors cursor-pointer">hub</span>
                    <span class="material-symbols-outlined hover:text-primary transition-colors cursor-pointer">domain</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- BottomNavBar (Mobile Only) -->
    <nav class="fixed bottom-0 left-0 w-full z-50 flex justify-around items-center px-4 pb-6 pt-3 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl md:hidden shadow-[0_-8px_32px_rgba(79,70,229,0.12)] rounded-t-[2rem] border-t border-indigo-50">
        <a class="flex flex-col items-center justify-center bg-primary text-white rounded-2xl px-5 py-2 transition-all duration-300" href="index.php">
            <span class="material-symbols-outlined text-2xl">home</span>
            <span class="font-plus-jakarta text-[10px] font-black uppercase tracking-wider mt-1">Home</span>
        </a>
        <a class="flex flex-col items-center justify-center text-slate-400 px-5 py-2 hover:text-primary transition-all duration-200" href="materi_maintenance.php">
            <span class="material-symbols-outlined text-2xl">build</span>
            <span class="font-plus-jakarta text-[10px] font-black uppercase tracking-wider mt-1">Repair</span>
        </a>
        <a class="flex flex-col items-center justify-center text-slate-400 px-5 py-2 hover:text-primary transition-all duration-200" href="auth/login_user.php">
            <span class="material-symbols-outlined text-2xl">shopping_cart</span>
            <span class="font-plus-jakarta text-[10px] font-black uppercase tracking-wider mt-1">Buy</span>
        </a>
        <a class="flex flex-col items-center justify-center text-slate-400 px-5 py-2 hover:text-primary transition-all duration-200" href="auth/login_user.php">
            <span class="material-symbols-outlined text-2xl">person</span>
            <span class="font-plus-jakarta text-[10px] font-black uppercase tracking-wider mt-1">Login</span>
        </a>
    </nav>
</body>
</html>