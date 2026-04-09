<!-- Theme Loader & Global Override Script -->
<script>
    function updateThemeIconG(theme) {
        const icons = document.querySelectorAll('.theme-icon-g');
        icons.forEach(i => i.textContent = theme === 'dark' ? 'dark_mode' : 'light_mode');
    }
    function toggleGlobalTheme() {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.theme = 'light';
            updateThemeIconG('light');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.theme = 'dark';
            updateThemeIconG('dark');
        }
    }
    
    // Initializer
    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
        document.addEventListener('DOMContentLoaded', () => updateThemeIconG('dark'));
    } else {
        document.documentElement.classList.remove('dark');
        document.addEventListener('DOMContentLoaded', () => updateThemeIconG('light'));
    }
</script>

<style>
/* =========================================================
   GLOBAL DARK MODE OVERRIDE (FORCING RAW CSS)
   Memastikan Panel yang hanya punya class bg-white otomatis
   menjadi dark theme tanpa harus edit ratusan baris file.
   ========================================================= */
html.dark body { background-color: #020617 !important; color: #f8fafc !important; }
html.dark .bg-white, html.dark .bg-surface-container-low, html.dark [class*="bg-surface"], html.dark .bg-slate-50 { 
    background-color: #0f172a !important; 
    border-color: rgba(255,255,255,0.08) !important; 
    color: #e2e8f0 !important; 
}
html.dark .text-on-surface, html.dark .text-slate-800, html.dark .text-slate-900, html.dark h1, html.dark h2, html.dark h3, html.dark h4 { 
    color: #f8fafc !important; 
}
html.dark .text-slate-500, html.dark .text-slate-600, html.dark [class*="text-on-surface-variant"] { 
    color: #94a3b8 !important; 
}
html.dark [class*="border-outline"], html.dark .border-slate-200, html.dark .border-slate-100 { 
    border-color: rgba(255,255,255,0.08) !important; 
}
html.dark input, html.dark select, html.dark textarea { 
    background-color: #1e293b !important; color: #f1f5f9 !important; border-color: rgba(255,255,255,0.15) !important; 
}
html.dark table thead tr, html.dark th { background-color: rgba(255,255,255,0.05) !important; color: #cbd5e1 !important; border-color: rgba(255,255,255,0.1) !important; }
html.dark table td { border-color: rgba(255,255,255,0.05) !important; color: #e2e8f0 !important;}
</style>

<?php
// Fetch Global App Name from Firestore
$brand_name = 'SIDIK-TI';
try {
    $settingsRef = $db->collection('system_settings')->document('app_name');
    $settingsSnap = $settingsRef->snapshot();
    if ($settingsSnap->exists()) {
        $brand_name = $settingsSnap->get('setting_value') ?? 'SIDIK-TI';
    }
} catch (Exception $e) {
    // Fallback if collection doesn't exist yet
}
?>

<!-- Universal Headless Navbar for User Modules -->
<header class="flex justify-between items-center px-8 py-4 w-full sticky top-0 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl z-50 shadow-sm shadow-indigo-500/5 transition-all">
    <div class="flex items-center gap-6">
        <a href="dashboard_user.php" class="flex items-center gap-2 group">
            <span class="text-xl font-extrabold tracking-tight text-indigo-700 dark:text-indigo-300 group-hover:scale-105 transition-transform"><?php echo htmlspecialchars($brand_name); ?></span>
        </a>
    </div>

    <!-- Desktop Navigation -->
    <div class="hidden md:flex items-center gap-8">
        <nav class="flex gap-8 relative">
            <?php 
                $current_page = basename($_SERVER['PHP_SELF']); 
            ?>
            <a class="<?php echo $current_page == 'dashboard_user.php' ? 'text-indigo-700 font-bold' : 'text-slate-500 hover:text-indigo-600 font-medium'; ?> transition-all text-sm uppercase tracking-widest" href="dashboard_user.php">Beranda</a>
            <a class="<?php echo $current_page == 'knowledge_base.php' ? 'text-indigo-700 font-bold' : 'text-slate-500 hover:text-indigo-600 font-medium'; ?> transition-all text-sm uppercase tracking-widest" href="knowledge_base.php">Panduan</a>
            <a class="<?php echo $current_page == 'dashboard_audit.php' ? 'text-indigo-700 font-bold' : 'text-slate-500 hover:text-indigo-600 font-medium'; ?> transition-all text-sm uppercase tracking-widest" href="dashboard_audit.php">Requests</a>
            <a class="<?php echo $current_page == 'assets_user.php' ? 'text-indigo-700 font-bold' : 'text-slate-500 hover:text-indigo-600 font-medium'; ?> transition-all text-sm uppercase tracking-widest" href="assets_user.php">Assets</a>
        </nav>
    </div>

    <!-- User Profile -->
    <div class="flex items-center gap-4">
        <div class="hidden md:flex flex-col items-end mr-2">
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 leading-tight">Pegawai</span>
            <span class="text-xs font-headline font-bold text-on-surface leading-tight max-w-[150px] truncate" title="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ($display_name ?? 'User')); ?>">
                <?php echo htmlspecialchars($_SESSION['full_name'] ?? ($display_name ?? 'User')); ?>
            </span>
        </div>
        
        <!-- Toggle Theme -->
        <button onclick="toggleGlobalTheme()" class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-600 transition-all hover:bg-white hover:shadow-lg mr-1" title="Ganti Tema">
            <span class="material-symbols-outlined theme-icon-g text-xl">light_mode</span>
        </button>
        <div class="relative group">
            <button class="w-10 h-10 rounded-xl bg-slate-50 border border-indigo-100 flex items-center justify-center text-indigo-600 transition-all hover:bg-white hover:shadow-lg">
                <span class="material-symbols-outlined">account_circle</span>
            </button>
            <!-- Dropdown -->
            <div class="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-2xl border border-outline-variant/10 p-2 opacity-0 group-hover:opacity-100 translate-y-2 group-hover:translate-y-0 pointer-events-none group-hover:pointer-events-auto transition-all duration-300 z-50">
                <a href="profile_user.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-indigo-50 text-indigo-700 transition-all font-bold text-[10px] uppercase tracking-widest border-b border-slate-100 mb-1">
                    <span class="material-symbols-outlined text-lg">admin_panel_settings</span>
                    Edit Profil
                </a>
                <a href="../auth/logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-rose-50 text-rose-600 transition-all font-bold text-[10px] uppercase tracking-widest">
                    <span class="material-symbols-outlined text-lg">logout</span>
                    Keluar Sesi
                </a>
            </div>
        </div>
    </div>
</header>
