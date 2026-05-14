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
    if ($db) {
        $settingsRef = $db->collection('system_settings')->document('app_name');
        $settingsSnap = $settingsRef->snapshot();
        if ($settingsSnap->exists()) {
            $brand_name = $settingsSnap->get('setting_value') ?? 'SIDIK-TI';
        }
    }
} catch (Exception $e) {
    // Fallback if collection doesn't exist yet
}
?>

<!-- Universal Headless Navbar for User Modules -->
<header class="flex justify-between items-center px-6 lg:px-10 py-5 w-full sticky top-0 bg-surface/70 dark:bg-slate-900/80 backdrop-blur-xl z-50 border-b border-outline/5 dark:border-white/5 transition-all">
    <div class="flex items-center gap-6">
        <a href="dashboard_user.php" class="flex items-center gap-3 group">
            <img src="<?php echo $base_url ?? '../'; ?>assets/img/logo.png" alt="Logo" class="h-12 md:h-14 w-auto group-hover:scale-105 transition-transform drop-shadow-md">
            <span class="text-xl md:text-2xl font-extrabold tracking-tight text-primary dark:text-indigo-400 group-hover:scale-105 transition-transform"><?php echo htmlspecialchars($brand_name); ?></span>
        </a>
    </div>

    <!-- Desktop Navigation -->
    <div class="hidden md:flex items-center gap-8">
        <nav class="flex gap-8 relative">
            <?php 
                $current_page = basename($_SERVER['PHP_SELF']); 
            ?>
            <a class="<?php echo $current_page == 'dashboard_user.php' ? 'text-primary dark:text-indigo-400 font-bold' : 'text-on-surface-variant dark:text-slate-400 hover:text-primary dark:hover:text-white font-medium'; ?> transition-all text-xs uppercase tracking-[0.15em]" href="dashboard_user.php">Beranda</a>
            <a class="<?php echo $current_page == 'knowledge_base.php' ? 'text-primary dark:text-indigo-400 font-bold' : 'text-on-surface-variant dark:text-slate-400 hover:text-primary dark:hover:text-white font-medium'; ?> transition-all text-xs uppercase tracking-[0.15em]" href="knowledge_base.php">Panduan</a>
            <a class="<?php echo $current_page == 'dashboard_audit.php' ? 'text-primary dark:text-indigo-400 font-bold' : 'text-on-surface-variant dark:text-slate-400 hover:text-primary dark:hover:text-white font-medium'; ?> transition-all text-xs uppercase tracking-[0.15em]" href="dashboard_audit.php">Requests</a>
            <a class="<?php echo $current_page == 'assets_user.php' ? 'text-primary dark:text-indigo-400 font-bold' : 'text-on-surface-variant dark:text-slate-400 hover:text-primary dark:hover:text-white font-medium'; ?> transition-all text-xs uppercase tracking-[0.15em]" href="assets_user.php">Assets</a>
            
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'user'): ?>
                <?php 
                    $target = 'dashboard_user.php';
                    if ($_SESSION['role'] === 'admin') $target = '../admin/dashboard_admin.php';
                    elseif ($_SESSION['role'] === 'technician') $target = '../modules_technician/dashboard_technician.php';
                    elseif ($_SESSION['role'] === 'head') $target = '../modules_head/dashboard_head.php';
                ?>
                <a href="<?php echo $target; ?>" class="flex items-center gap-2 px-3 py-1 bg-primary text-white rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-primary-container transition-all shadow-md shadow-primary/20">
                    <span class="material-symbols-outlined text-sm">shield_person</span>
                    Management
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <!-- User Profile -->
    <div class="flex items-center gap-4">
        <div class="hidden md:flex flex-col items-end mr-2">
            <span class="text-[9px] font-black uppercase tracking-[0.2em] text-on-surface-variant dark:text-slate-400 leading-tight">Pegawai</span>
            <span class="text-xs font-headline font-bold text-on-surface dark:text-white leading-tight max-w-[150px] truncate" title="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ($display_name ?? 'User')); ?>">
                <?php echo htmlspecialchars($_SESSION['full_name'] ?? ($display_name ?? 'User')); ?>
            </span>
        </div>
        
        <!-- Toggle Theme -->
        <button onclick="toggleGlobalTheme()" class="w-10 h-10 rounded-xl bg-surface-low dark:bg-slate-800 border border-outline/10 dark:border-white/10 flex items-center justify-center text-on-surface-variant dark:text-slate-400 transition-all hover:bg-surface-high dark:hover:bg-slate-700 dark:hover:text-white mr-1" title="Ganti Tema">
            <span class="material-symbols-outlined theme-icon-g text-lg">light_mode</span>
        </button>

        <div class="relative" id="profileDropdownContainer">
            <button onclick="toggleProfileDropdown()" class="w-10 h-10 rounded-xl bg-primary/10 dark:bg-indigo-500/20 border border-primary/20 dark:border-indigo-500/30 flex items-center justify-center text-primary dark:text-indigo-400 transition-all hover:bg-primary/20 dark:hover:bg-indigo-500/30 hover:scale-105">
                <span class="material-symbols-outlined text-lg">account_circle</span>
            </button>
            <!-- Dropdown -->
            <div id="profileDropdownMenu" class="absolute right-0 mt-3 w-48 bg-surface dark:bg-slate-900 rounded-2xl shadow-xl border border-outline/10 dark:border-white/10 p-2 opacity-0 translate-y-2 pointer-events-none transition-all duration-300 z-50">
                <a href="profile_user.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-surface-low dark:hover:bg-slate-800 text-on-surface dark:text-slate-200 transition-all font-bold text-[10px] uppercase tracking-[0.15em] border-b border-outline/5 dark:border-white/5 mb-1">
                    <span class="material-symbols-outlined text-base">edit_note</span>
                    Edit Profil
                </a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'user'): ?>
                    <?php 
                        $target = 'dashboard_user.php';
                        if ($_SESSION['role'] === 'admin') $target = '../admin/dashboard_admin.php';
                        elseif ($_SESSION['role'] === 'technician') $target = '../modules_technician/dashboard_technician.php';
                        elseif ($_SESSION['role'] === 'head') $target = '../modules_head/dashboard_head.php';
                    ?>
                    <a href="<?php echo $target; ?>" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-primary/10 dark:hover:bg-indigo-500/20 text-primary dark:text-indigo-400 transition-all font-bold text-[10px] uppercase tracking-[0.15em] border-b border-outline/5 dark:border-white/5 mb-1">
                        <span class="material-symbols-outlined text-base">admin_panel_settings</span>
                        Management
                    </a>
                <?php endif; ?>
                <a href="../auth/logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-rose-500/10 dark:hover:bg-rose-500/20 text-rose-500 dark:text-rose-400 transition-all font-bold text-[10px] uppercase tracking-[0.15em]">
                    <span class="material-symbols-outlined text-base">logout</span>
                    Keluar
                </a>
            </div>
        </div>
    </div>
</header>

<script>
function toggleProfileDropdown() {
    const menu = document.getElementById('profileDropdownMenu');
    if (menu.classList.contains('opacity-0')) {
        menu.classList.remove('opacity-0', 'translate-y-2', 'pointer-events-none');
        menu.classList.add('opacity-100', 'translate-y-0', 'pointer-events-auto');
    } else {
        menu.classList.add('opacity-0', 'translate-y-2', 'pointer-events-none');
        menu.classList.remove('opacity-100', 'translate-y-0', 'pointer-events-auto');
    }
}

document.addEventListener('click', function(event) {
    const container = document.getElementById('profileDropdownContainer');
    const menu = document.getElementById('profileDropdownMenu');
    if (container && !container.contains(event.target)) {
        if (!menu.classList.contains('opacity-0')) {
            menu.classList.add('opacity-0', 'translate-y-2', 'pointer-events-none');
            menu.classList.remove('opacity-100', 'translate-y-0', 'pointer-events-auto');
        }
    }
});
</script>
