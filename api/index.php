<?php
session_start();
$correct_code = '061806'; // Default 6 digit code for testing
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auth_code'])) {
    if ($_POST['auth_code'] === $correct_code) {
        $_SESSION['authenticated_testing'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error_msg = 'Kode authenticator salah. Silakan coba lagi.';
    }
}

if (!isset($_SESSION['authenticated_testing']) || $_SESSION['authenticated_testing'] !== true) {
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Authenticator Wall | SIDIK-TI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-4 font-['Inter']">
    <div class="bg-white p-8 rounded-3xl shadow-xl max-w-md w-full text-center border border-slate-100">
        <div class="w-16 h-16 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-slate-800 mb-2 font-['Plus_Jakarta_Sans']">Akses Terbatas</h2>
        <p class="text-slate-500 mb-8 text-sm leading-relaxed">
            Ingin mengakses aplikasi ini untuk melakukan testing? coba masukkan 6 code authenticator terlebih dahulu.
        </p>
        
        <form method="POST" class="space-y-4">
            <div>
                <input type="text" name="auth_code" maxlength="6" class="w-full text-center text-2xl tracking-widest font-bold py-3 border-2 border-slate-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 outline-none transition-all" placeholder="••••••" required autofocus>
                <?php if ($error_msg): ?>
                    <p class="text-red-500 text-xs mt-2 font-medium"><?php echo $error_msg; ?></p>
                <?php endif; ?>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 rounded-xl transition-colors shadow-lg shadow-indigo-600/30">
                Verifikasi Kode
            </button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}
?>
<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Smart IT Infrastructure Maintenance</title>
    <link rel="icon" type="image/png" href="assets/img/logo.png" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700&amp;display=swap" rel="stylesheet"/>
        <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              "on-tertiary": "#ffffff",
              "tertiary-fixed": "#6ffbbe",
              "surface-container-lowest": "#ffffff",
              "surface-container": "#eceef0",
              "on-error-container": "#93000a",
              "secondary-fixed-dim": "#b4c5ff",
              "outline-variant": "#c7c4d8",
              "on-primary": "#ffffff",
              "on-tertiary-fixed-variant": "#005236",
              "tertiary-fixed-dim": "#4edea3",
              "secondary-fixed": "#dbe1ff",
              "on-secondary-fixed-variant": "#003ea8",
              "inverse-primary": "#c3c0ff",
              "surface-container-highest": "#e0e3e5",
              "primary-fixed": "#e2dfff",
              "tertiary": "#005338",
              "on-primary-fixed-variant": "#3323cc",
              "primary-container": "#4f46e5",
              "on-tertiary-fixed": "#002113",
              "secondary-container": "#316bf3",
              "secondary": "#0051d5",
              "surface-bright": "#f7f9fb",
              "inverse-surface": "#2d3133",
              "surface": "#f7f9fb",
              "on-primary-container": "#dad7ff",
              "on-error": "#ffffff",
              "surface-variant": "#e0e3e5",
              "on-tertiary-container": "#67f4b7",
              "on-secondary-fixed": "#00174b",
              "on-primary-fixed": "#0f0069",
              "primary-fixed-dim": "#c3c0ff",
              "on-surface": "#191c1e",
              "on-secondary": "#ffffff",
              "surface-tint": "#4d44e3",
              "error": "#ba1a1a",
              "background": "#f7f9fb",
              "inverse-on-surface": "#eff1f3",
              "surface-dim": "#d8dadc",
              "on-background": "#191c1e",
              "primary": "#3525cd",
              "surface-container-high": "#e6e8ea",
              "surface-container-low": "#f2f4f6",
              "outline": "#777587",
              "on-surface-variant": "#464555",
              "on-secondary-container": "#fefcff",
              "error-container": "#ffdad6",
              "tertiary-container": "#006e4b"
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
        }
        body { font-family: 'Inter', sans-serif; min-height: max(884px, 100dvh); }
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
<header class="fixed top-0 w-full flex justify-between items-center px-6 h-16 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl z-50 shadow-sm shadow-indigo-500/5">
    <div class="flex items-center gap-3">
        <img src="assets/img/logo.png" alt="Logo" class="h-8 md:h-10 w-auto">
        <span class="text-xl font-extrabold bg-gradient-to-br from-indigo-600 to-indigo-400 bg-clip-text text-transparent">SIDIK-TI</span>
    </div>
    <div class="hidden md:flex items-center space-x-8">
        <a class="text-indigo-600 dark:text-indigo-400 font-bold font-plus-jakarta text-lg tracking-tight" href="index.php">Home</a>
        <a class="text-slate-500 dark:text-slate-400 font-plus-jakarta text-lg tracking-tight hover:text-indigo-500 transition-colors" href="materi_maintenance.php">Maintenance</a>
        <a class="text-slate-500 dark:text-slate-400 font-plus-jakarta text-lg tracking-tight hover:text-indigo-500 transition-colors" href="materi_procurement.php">Procurement</a>
    </div>
    <div class="flex items-center gap-2">
        <a href="auth/login_admin.php" class="material-symbols-outlined text-indigo-600 dark:text-indigo-400 p-2 hover:bg-indigo-50/50 transition-all duration-300 rounded-full">admin_panel_settings</a>
    </div>
</header>

    <main class="pt-24 pb-32 px-4 md:px-10 max-w-7xl mx-auto space-y-12 md:space-y-20">
<!-- Hero Section -->
<section class="relative overflow-hidden rounded-2xl md:rounded-3xl bg-surface-container-low min-h-[400px] md:min-h-[500px] flex items-center p-6 md:p-16">
<div class="absolute inset-0 z-0">
<div class="absolute top-[-10%] right-[-10%] w-96 h-96 bg-primary/10 blur-[100px] rounded-full"></div>
<div class="absolute bottom-[-10%] left-[-10%] w-96 h-96 bg-secondary/10 blur-[100px] rounded-full"></div>
<img alt="IT Infrastructure" class="w-full h-full object-cover opacity-10 mix-blend-overlay" data-alt="Modern server room with glowing blue lights and organized cables, professional technology infrastructure aesthetic" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBAfjNyMhEcIsLn1-8iTJemzXmgVux7Jg-lqBwfBxhoZzBgwPwis8a7uSjaJ9QFn38LOPy9TcI-P8C3PKMXO2z6S4D4vgqLfhuiHZIy8e8KJi3GBso4V0U68lXRDdEdfTBFF_woBikL0y0fe-FGX1_UXrbRSyvI-hiCuMJAIsJteL5HXfFxzTZSqSDLt-uhjooQlDpIsku-5tM5idApWqugPTeCQOgUuSnIxoCR9xWenWdhCvk6EjuMti1hk6Wf5jyxANS80ZNHCtE"/>
</div>
<div class="relative z-10 max-w-3xl space-y-8">
<div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-indigo-50 text-indigo-700 font-semibold text-sm">
<span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                    Smart IT Maintenance System
                </div>
<h1 class="text-3xl md:text-6xl font-extrabold tracking-tight text-on-surface leading-[1.1]">
                    Sistem Pemeliharaan Adalah <span class="bg-gradient-to-br from-primary to-primary-container bg-clip-text text-transparent">Investasi</span>, Bukan Beban
                </h1>
<p class="text-on-surface-variant text-lg md:text-xl leading-relaxed max-w-2xl font-body">
                    Optimalkan kinerja infrastruktur TI Anda dengan pemeliharaan terukur dan pengadaan perangkat berkualitas tinggi bersama SIDIK-TI.
                </p>
<div class="flex flex-col sm:flex-row gap-4 pt-4">
<a href="auth/login_user.php" class="px-8 py-4 bg-gradient-to-r from-primary to-primary-container text-white rounded-2xl font-bold shadow-lg shadow-indigo-500/20 hover:scale-[1.02] active:scale-95 transition-all duration-300 text-center">
                        Mulai Pengajuan
                    </a>
<a href="materi_maintenance.php" class="px-8 py-4 bg-white text-primary rounded-2xl font-bold border-2 border-primary/10 hover:bg-indigo-50 hover:border-primary/20 transition-all duration-300 text-center">
                        Pelajari Maintenance
                    </a>
</div>
</div>
</section>

        <!-- Layanan Kami Section -->
<section class="space-y-10">
<div class="text-center space-y-3">
<h2 class="text-3xl md:text-4xl font-bold text-on-surface tracking-tight">Layanan Kami</h2>
<div class="h-1.5 w-20 bg-primary mx-auto rounded-full"></div>
</div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
<!-- Preventive -->
<div class="group p-8 rounded-3xl bg-surface-container-lowest border border-outline-variant/10 hover:shadow-xl transition-all duration-500">
<div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined text-3xl">verified_user</span>
</div>
<h3 class="text-xl font-bold mb-3 text-on-surface">Preventive Maintenance</h3>
<p class="text-on-surface-variant leading-relaxed text-sm mb-6">
                        Tindakan pencegahan rutin untuk menjaga perangkat tetap dalam kondisi optimal sebelum terjadi kegagalan sistem.
                    </p>
<div class="flex items-center text-emerald-600 font-semibold text-xs uppercase tracking-widest gap-2">
<span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Active Protection
                    </div>
</div>
<!-- Corrective -->
<div class="group p-8 rounded-3xl bg-surface-container-lowest border border-outline-variant/10 hover:shadow-xl transition-all duration-500">
<div class="w-14 h-14 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined text-3xl">construction</span>
</div>
<h3 class="text-xl font-bold mb-3 text-on-surface">Corrective Maintenance</h3>
<p class="text-on-surface-variant leading-relaxed text-sm mb-6">
                        Perbaikan cepat dan tepat saat terjadi kerusakan pada hardware maupun software guna meminimalkan downtime operasional.
                    </p>
<div class="flex items-center text-orange-600 font-semibold text-xs uppercase tracking-widest gap-2">
<span class="w-2 h-2 rounded-full bg-orange-500"></span>
                        Rapid Response
                    </div>
</div>
<!-- Predictive -->
<div class="group p-8 rounded-3xl bg-surface-container-lowest border border-outline-variant/10 hover:shadow-xl transition-all duration-500">
<div class="w-14 h-14 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined text-3xl">insights</span>
</div>
<h3 class="text-xl font-bold mb-3 text-on-surface">Predictive Maintenance</h3>
<p class="text-on-surface-variant leading-relaxed text-sm mb-6">
                        Analisis data performa untuk memprediksi potensi kerusakan di masa depan dan melakukan intervensi sebelum masalah muncul.
                    </p>
<div class="flex items-center text-purple-600 font-semibold text-xs uppercase tracking-widest gap-2">
<span class="w-2 h-2 rounded-full bg-purple-500"></span>
                        Data Driven
                    </div>
</div>
</div>
</section>

        <!-- Pilih Jenis Layanan Section -->
<section class="space-y-10">
<div class="flex flex-col md:flex-row justify-between items-end gap-4">
<div class="space-y-3">
<h2 class="text-3xl md:text-4xl font-bold text-on-surface tracking-tight">Pilih Jenis Layanan</h2>
<p class="text-on-surface-variant max-w-xl">Tentukan kebutuhan operasional Anda untuk mendapatkan solusi TI yang paling relevan.</p>
</div>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
<!-- Maintenance Perangkat TI -->
<div class="relative group rounded-3xl overflow-hidden p-8 md:p-12 bg-surface-container-low border border-outline-variant/20 hover:border-primary/30 transition-all duration-500">
    <div class="absolute top-0 right-0 p-12 opacity-[0.03] group-hover:opacity-[0.05] transition-opacity">
        <span class="material-symbols-outlined text-[12rem]">engineering</span>
    </div>
    <div class="relative z-10 space-y-6">
        <div class="w-16 h-16 rounded-2xl bg-primary/10 text-primary flex items-center justify-center group-hover:scale-110 transition-transform">
            <span class="material-symbols-outlined text-4xl">engineering</span>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-on-surface mb-2">Maintenance Perangkat TI</h3>
            <p class="text-on-surface-variant text-sm max-w-xs">Perawatan berkala dan perbaikan hardware operasional kantor secara profesional.</p>
        </div>
        <div class="flex flex-wrap gap-4">
            <a href="auth/login_user.php" class="inline-flex items-center gap-2 px-8 py-3 bg-primary text-white rounded-xl font-bold text-sm hover:shadow-lg hover:shadow-primary/30 transition-all">
                Pilih Layanan
                <span class="material-symbols-outlined text-xl">arrow_forward</span>
            </a>
            <a href="materi_maintenance.php" class="inline-flex items-center gap-2 px-4 py-3 text-primary font-bold text-sm hover:bg-primary/5 rounded-xl transition-all">
                Materi
                <span class="material-symbols-outlined text-lg">menu_book</span>
            </a>
        </div>
    </div>
</div>
<!-- Pengadaan Barang IT -->
<div class="relative group rounded-3xl overflow-hidden p-8 md:p-12 bg-surface-container-low border border-outline-variant/20 hover:border-secondary/30 transition-all duration-500">
    <div class="absolute top-0 right-0 p-12 opacity-[0.03] group-hover:opacity-[0.05] transition-opacity">
        <span class="material-symbols-outlined text-[12rem]">shopping_cart</span>
    </div>
    <div class="relative z-10 space-y-6">
        <div class="w-16 h-16 rounded-2xl bg-secondary/10 text-secondary flex items-center justify-center group-hover:scale-110 transition-transform">
            <span class="material-symbols-outlined text-4xl">shopping_cart</span>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-on-surface mb-2">Pengadaan Barang IT</h3>
            <p class="text-on-surface-variant text-sm max-w-xs">Pengadaan unit baru berkualitas untuk mendukung produktivitas perusahaan.</p>
        </div>
        <div class="flex flex-wrap gap-4">
            <a href="auth/login_user.php" class="inline-flex items-center gap-2 px-8 py-3 bg-secondary text-white rounded-xl font-bold text-sm hover:shadow-lg hover:shadow-secondary/30 transition-all">
                Pilih Layanan
                <span class="material-symbols-outlined text-xl">arrow_forward</span>
            </a>
            <a href="materi_procurement.php" class="inline-flex items-center gap-2 px-4 py-3 text-secondary font-bold text-sm hover:bg-secondary/5 rounded-xl transition-all">
                Materi
                <span class="material-symbols-outlined text-lg">menu_book</span>
            </a>
        </div>
    </div>
</div>
</div>
        <!-- Strategic Insight Section -->
        <section class="bg-primary rounded-3xl md:rounded-[3rem] p-1 shadow-2xl">
            <div class="bg-primary rounded-[1.8rem] md:rounded-[2.8rem] p-6 md:p-16 text-white grid grid-cols-1 md:grid-cols-2 gap-10 md:gap-12 items-center relative overflow-hidden group">
                <div class="absolute -top-10 -right-10 w-64 h-64 bg-white/5 rounded-full group-hover:scale-110 transition-transform duration-700"></div>
                <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-white/5 rounded-full group-hover:scale-125 transition-transform duration-700"></div>
                
                <div class="relative z-10 space-y-8">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 text-white font-bold text-[10px] uppercase tracking-widest italic border border-white/10">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-300 animate-pulse"></span>
                        New Intelligence Module
                    </div>
                    <div>
                        <h2 class="text-2xl md:text-5xl font-extrabold tracking-tight italic leading-tight uppercase text-center md:text-left">Economic <span class="bg-gradient-to-br from-indigo-200 to-white bg-clip-text text-transparent">Valuation</span> Insight</h2>
                        <p class="text-indigo-100/70 text-base md:text-lg font-medium mt-4 md:mt-6 leading-relaxed italic">Pelajari bagaimana aset TI Anda terdepresiasi dan temukan strategi terbaik untuk pengadaan barang di masa depan.</p>
                    </div>
                    <div class="pt-2">
                        <a href="modules_user/asset_market_analysis.php" class="inline-flex items-center gap-4 px-10 py-5 bg-white text-primary rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:shadow-2xl transition-all hover:-translate-y-1 active:scale-95">
                            Lihat Analisis Pasar
                            <span class="material-symbols-outlined text-lg font-black italic">analytics</span>
                        </a>
                    </div>
                </div>

                <div class="relative z-10 grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4">
                    <div class="p-4 md:p-6 bg-white/10 backdrop-blur-md rounded-2xl md:rounded-3xl border border-white/10 space-y-2 md:space-y-3 hover:bg-white/15 transition-colors">
                        <span class="material-symbols-outlined text-2xl md:text-3xl opacity-60">trending_down</span>
                        <p class="text-[9px] md:text-[10px] font-black uppercase tracking-widest opacity-60">Avg. Laptop Drop</p>
                        <p class="text-2xl md:text-3xl font-black italic">30%<span class="text-xs opacity-40 ml-1">/yr</span></p>
                    </div>
                    <div class="p-4 md:p-6 bg-white/10 backdrop-blur-md rounded-2xl md:rounded-3xl border border-white/10 space-y-2 md:space-y-3 hover:bg-white/15 transition-colors">
                        <span class="material-symbols-outlined text-2xl md:text-3xl opacity-60">update</span>
                        <p class="text-[9px] md:text-[10px] font-black uppercase tracking-widest opacity-60">Replacement Cycle</p>
                        <p class="text-2xl md:text-3xl font-black italic">3-5<span class="text-xs opacity-40 ml-1">y</span></p>
                    </div>
                    <div class="p-4 md:p-6 bg-white/10 backdrop-blur-md rounded-2xl md:rounded-3xl border border-white/10 space-y-2 md:space-y-3 hover:bg-white/15 transition-colors">
                        <span class="material-symbols-outlined text-2xl md:text-3xl opacity-60">payments</span>
                        <p class="text-[9px] md:text-[10px] font-black uppercase tracking-widest opacity-60">Budget Efficiency</p>
                        <p class="text-2xl md:text-3xl font-black italic">15%<span class="text-xs opacity-40 ml-1">up</span></p>
                    </div>
                    <div class="p-4 md:p-6 bg-white/10 backdrop-blur-md rounded-2xl md:rounded-3xl border border-white/10 space-y-2 md:space-y-3 hover:bg-white/15 transition-colors">
                        <span class="material-symbols-outlined text-2xl md:text-3xl opacity-60">savings</span>
                        <p class="text-[9px] md:text-[10px] font-black uppercase tracking-widest opacity-60">Resale Strategy</p>
                        <p class="text-2xl md:text-3xl font-black italic text-nowrap">Optimal</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
<footer class="bg-surface-container-low py-12 px-6">
<div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-8">
<div class="space-y-4 text-center md:text-left">
    <div class="flex items-center justify-center md:justify-start gap-3">
        <img src="assets/img/logo.png" alt="Logo" class="h-10 w-auto">
        <span class="text-2xl font-extrabold bg-gradient-to-br from-indigo-600 to-indigo-400 bg-clip-text text-transparent">SIDIK-TI</span>
    </div>
    <p class="text-on-surface-variant max-w-sm text-sm">Memberikan standar tertinggi dalam pemeliharaan infrastruktur digital Anda untuk keberlanjutan bisnis yang lebih baik.</p>
</div>
<div class="text-on-surface-variant font-medium text-sm text-center md:text-right">
                © 2026 IT Helpdesk System - Focused on Reliability.
            </div>
</div>
</footer>
<!-- BottomNavBar (Mobile Only) -->
<nav class="fixed bottom-0 left-0 w-full z-50 flex justify-around items-center px-4 pb-8 pt-3 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl md:hidden shadow-[0_-4px_24px_rgba(79,70,229,0.06)] rounded-t-[2.5rem] border-t border-indigo-50/50">
<a class="flex flex-col items-center justify-center bg-primary text-white rounded-2xl px-5 py-2 transition-all duration-300" href="index.php">
<span class="material-symbols-outlined text-2xl">home</span>
<span class="font-plus-jakarta text-[11px] font-semibold uppercase tracking-wider mt-1">Home</span>
</a>
<a class="flex flex-col items-center justify-center text-slate-400 dark:text-slate-500 px-5 py-2 hover:text-indigo-500 transition-all duration-200 active:scale-90" href="materi_maintenance.php">
<span class="material-symbols-outlined text-2xl">build</span>
<span class="font-plus-jakarta text-[10px] font-semibold uppercase tracking-wider mt-1">Maint.</span>
</a>
<a class="flex flex-col items-center justify-center text-slate-400 dark:text-slate-500 px-5 py-2 hover:text-indigo-500 transition-all duration-200 active:scale-90" href="auth/login_user.php">
<span class="material-symbols-outlined text-2xl">shopping_cart</span>
<span class="font-plus-jakarta text-[10px] font-semibold uppercase tracking-wider mt-1">Procure.</span>
</a>
<a class="flex flex-col items-center justify-center text-slate-400 dark:text-slate-500 px-5 py-2 hover:text-indigo-500 transition-all duration-200 active:scale-90" href="auth/login_user.php">
<span class="material-symbols-outlined text-2xl">person</span>
<span class="font-plus-jakarta text-[10px] font-semibold uppercase tracking-wider mt-1">Profile</span>
</a>
</nav>
</body>
</html>
