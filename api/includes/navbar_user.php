<!-- Dark Mode: Toggle via class on <html>, persisted in localStorage -->
<script>
    (function() {
        // Jalankan sebelum paint untuk mencegah flash
        var saved = localStorage.getItem('theme');
        var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (saved === 'dark' || (!saved && prefersDark)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    })();

    function toggleGlobalTheme() {
        var isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        document.querySelectorAll('.theme-icon-g').forEach(function(el) {
            el.textContent = isDark ? 'dark_mode' : 'light_mode';
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        var isDark = document.documentElement.classList.contains('dark');
        document.querySelectorAll('.theme-icon-g').forEach(function(el) {
            el.textContent = isDark ? 'dark_mode' : 'light_mode';
        });
    });
</script>

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
<header class="flex justify-between items-center px-6 lg:px-10 py-5 w-full sticky top-0 bg-surface/70 backdrop-blur-xl z-50 border-b border-outline/5 transition-all">
    <div class="flex items-center gap-6">
        <a href="dashboard_user.php" class="flex items-center gap-2 group">
            <span class="text-xl font-extrabold tracking-tight text-primary group-hover:scale-105 transition-transform"><?php echo htmlspecialchars($brand_name); ?></span>
        </a>
    </div>

    <!-- Desktop Navigation -->
    <div class="hidden md:flex items-center gap-8">
        <nav class="flex gap-8 relative">
            <?php 
                $current_page = basename($_SERVER['PHP_SELF']); 
            ?>
            <a class="<?php echo $current_page == 'dashboard_user.php' ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary font-medium'; ?> transition-all text-xs uppercase tracking-[0.15em]" href="dashboard_user.php">Beranda</a>
            <a class="<?php echo $current_page == 'knowledge_base.php' ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary font-medium'; ?> transition-all text-xs uppercase tracking-[0.15em]" href="knowledge_base.php">Panduan</a>
            <a class="<?php echo $current_page == 'dashboard_audit.php' ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary font-medium'; ?> transition-all text-xs uppercase tracking-[0.15em]" href="dashboard_audit.php">Requests</a>
            <a class="<?php echo $current_page == 'assets_user.php' ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary font-medium'; ?> transition-all text-xs uppercase tracking-[0.15em]" href="assets_user.php">Assets</a>
        </nav>
    </div>

    <!-- User Profile -->
    <div class="flex items-center gap-4">
        <div class="hidden md:flex flex-col items-end mr-2">
            <span class="text-[9px] font-black uppercase tracking-[0.2em] text-on-surface-variant leading-tight">Pegawai</span>
            <span class="text-xs font-headline font-bold text-on-surface leading-tight max-w-[150px] truncate" title="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ($display_name ?? 'User')); ?>">
                <?php echo htmlspecialchars($_SESSION['full_name'] ?? ($display_name ?? 'User')); ?>
            </span>
        </div>
        
        <!-- Toggle Theme -->
        <button onclick="toggleGlobalTheme()" class="w-10 h-10 rounded-xl bg-surface-low border border-outline/10 flex items-center justify-center text-on-surface-variant transition-all hover:bg-surface-high mr-1" title="Ganti Tema">
            <span class="material-symbols-outlined theme-icon-g text-lg">light_mode</span>
        </button>

        <div class="relative group">
            <button class="w-10 h-10 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary transition-all hover:bg-primary/20 hover:scale-105">
                <span class="material-symbols-outlined text-lg">account_circle</span>
            </button>
            <!-- Dropdown -->
            <div class="absolute right-0 mt-3 w-48 bg-surface rounded-2xl shadow-xl border border-outline/10 p-2 opacity-0 group-hover:opacity-100 translate-y-2 group-hover:translate-y-0 pointer-events-none group-hover:pointer-events-auto transition-all duration-300 z-50">
                <a href="profile_user.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-surface-low text-primary transition-all font-bold text-[10px] uppercase tracking-[0.15em] border-b border-outline/5 mb-1">
                    <span class="material-symbols-outlined text-base">admin_panel_settings</span>
                    Edit Profil
                </a>
                <a href="../auth/logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-rose-500/10 text-rose-500 transition-all font-bold text-[10px] uppercase tracking-[0.15em]">
                    <span class="material-symbols-outlined text-base">logout</span>
                    Keluar
                </a>
            </div>
        </div>
    </div>
</header>
