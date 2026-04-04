<!-- Theme Loader Script -->
<script>
    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
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

<!-- Mobile Top Navbar -->
<div class="lg:hidden w-full bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border-b border-outline-variant/10 px-4 py-3 flex items-center justify-between sticky top-0 z-40">
    <div class="flex items-center gap-3">
        <button id="mobile-menu-btn" class="p-2 bg-surface-container-low text-primary rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <span class="text-lg font-black text-indigo-600 tracking-tight"><?php echo htmlspecialchars($brand_name); ?></span>
    </div>
</div>

<!-- Sidebar Overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 opacity-0 pointer-events-none transition-opacity duration-300 lg:hidden"></div>

<!-- Sidebar (NavigationDrawer) -->
<aside id="admin-sidebar" class="fixed left-0 top-0 h-full z-50 p-6 flex flex-col gap-4 bg-white/90 dark:bg-slate-900/90 backdrop-blur-2xl w-72 rounded-r-3xl shadow-2xl shadow-indigo-900/5 transition-transform duration-300 border-r border-outline-variant/10 -translate-x-full lg:translate-x-0">
    <div class="lg:hidden absolute top-4 right-4">
        <button id="close-sidebar-btn" class="p-2 bg-rose-50 text-rose-500 rounded-xl hover:bg-rose-500 hover:text-white transition-all flex items-center justify-center">
            <span class="material-symbols-outlined text-sm">close</span>
        </button>
    </div>
    <!-- Brand Header -->
    <div class="flex items-center gap-3 px-4 py-6">
        <div class="w-10 h-10 bg-gradient-to-br from-primary to-primary-container rounded-xl flex items-center justify-center shadow-lg shadow-primary/20">
            <span class="material-symbols-outlined text-white">terminal</span>
        </div>
        <span class="text-2xl font-black text-indigo-600 tracking-tight"><?php echo htmlspecialchars($brand_name); ?></span>
    </div>

    <!-- Admin Profile Section -->
    <div class="flex items-center gap-4 px-4 py-6 bg-surface-container-low rounded-2xl mb-4">
        <div class="relative">
            <div class="w-12 h-12 rounded-full bg-primary-fixed flex items-center justify-center overflow-hidden border-2 border-white shadow-sm">
                <span class="material-symbols-outlined text-primary text-2xl">account_circle</span>
            </div>
            <div class="absolute bottom-0 right-0 w-3 h-3 bg-tertiary-fixed rounded-full border-2 border-white"></div>
        </div>
        <div class="overflow-hidden">
            <p class="font-headline font-bold text-sm text-on-surface leading-none truncate"><?php echo isset($_SESSION['user']) ? ucfirst($_SESSION['user']) : 'System Admin'; ?></p>
            <p class="text-[10px] text-on-surface-variant mt-1 font-bold uppercase tracking-wider">IT Department</p>
        </div>
    </div>

    <!-- Navigation Items -->
    <nav class="flex flex-col gap-2 flex-1">
        <?php 
            $current_page = basename($_SERVER['PHP_SELF']); 
            function get_nav_style($page, $current) {
                return $page == $current 
                    ? 'bg-gradient-to-br from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-200' 
                    : 'text-slate-600 dark:text-slate-400 hover:text-indigo-600 hover:translate-x-1';
            }
        ?>
        
        <a class="group flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 active:scale-98 <?php echo get_nav_style('dashboard_admin.php', $current_page); ?>" href="dashboard_admin.php">
            <span class="material-symbols-outlined <?php if($current_page == 'dashboard_admin.php') echo 'fill-1'; ?>">dashboard</span>
            <span class="font-headline text-sm font-medium">Dashboard</span>
        </a>
        
        <a class="group flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 active:scale-98 <?php echo get_nav_style('manage_users.php', $current_page); ?>" href="manage_users.php">
            <span class="material-symbols-outlined <?php if($current_page == 'manage_users.php') echo 'fill-1'; ?>">group</span>
            <span class="font-headline text-sm font-medium">Users</span>
        </a>

        <a class="group flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 active:scale-98 <?php echo get_nav_style('inventory.php', $current_page); ?>" href="inventory.php">
            <span class="material-symbols-outlined <?php if($current_page == 'inventory.php') echo 'fill-1'; ?>">inventory_2</span>
            <span class="font-headline text-sm font-medium">Inventory</span>
        </a>

        <a class="group flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 active:scale-98 <?php echo get_nav_style('analytics.php', $current_page); ?>" href="analytics.php">
            <span class="material-symbols-outlined <?php if($current_page == 'analytics.php') echo 'fill-1'; ?>">analytics</span>
            <span class="font-headline text-sm font-medium">Analytics</span>
        </a>

        <a class="group flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 active:scale-98 <?php echo get_nav_style('kelola_pengumuman.php', $current_page); ?>" href="kelola_pengumuman.php">
            <span class="material-symbols-outlined <?php if($current_page == 'kelola_pengumuman.php') echo 'fill-1'; ?>">campaign</span>
            <span class="font-headline text-sm font-medium">Pengumuman</span>
        </a>

        <div class="mt-4 pt-4 border-t border-outline-variant/20">
            <p class="px-4 text-[10px] font-black uppercase tracking-widest text-indigo-300 mb-2">Master Configuration</p>
            <a class="group flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 <?php echo get_nav_style('manage_departments.php', $current_page); ?>" href="manage_departments.php">
                <span class="material-symbols-outlined <?php if($current_page == 'manage_departments.php') echo 'fill-1'; ?>">corporate_fare</span>
                <span class="font-headline text-sm font-medium">Departments</span>
            </a>
            <a class="group flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 <?php echo get_nav_style('manage_budget.php', $current_page); ?>" href="manage_budget.php">
                <span class="material-symbols-outlined <?php if($current_page == 'manage_budget.php') echo 'fill-1'; ?>">payments</span>
                <span class="font-headline text-sm font-medium">Budget Control</span>
            </a>
            <a class="group flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 <?php echo get_nav_style('manage_templates.php', $current_page); ?>" href="manage_templates.php">
                <span class="material-symbols-outlined">settings_suggest</span>
                <span class="font-headline text-sm font-medium">Templates</span>
            </a>
        </div>
    </nav>

    <!-- Bottom Actions -->
    <div class="mt-auto pt-6 border-t border-outline-variant/20">
        <a class="group flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 active:scale-98 <?php echo get_nav_style('settings.php', $current_page); ?>" href="settings.php">
            <span class="material-symbols-outlined <?php if($current_page == 'settings.php') echo 'fill-1'; ?>">settings</span>
            <span class="font-headline text-sm font-medium">Settings</span>
        </a>
        <a class="group flex items-center gap-3 px-4 py-3 text-error hover:bg-error-container/20 rounded-2xl transition-all duration-300" href="../auth/logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
            <span class="material-symbols-outlined">logout</span>
            <span class="font-headline text-sm font-medium">Sign Out</span>
        </a>
    </div>
</aside>

<!-- Spacer for fixed sidebar -->
<div class="hidden lg:block w-72 shrink-0"></div>

<script>
    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const menuBtn = document.getElementById('mobile-menu-btn');
    const closeBtn = document.getElementById('close-sidebar-btn');

    function toggleSidebar() {
        sidebar.classList.toggle('-translate-x-full');
        if (sidebar.classList.contains('-translate-x-full')) {
            overlay.classList.add('opacity-0', 'pointer-events-none');
        } else {
            overlay.classList.remove('opacity-0', 'pointer-events-none');
        }
    }

    if (menuBtn) menuBtn.addEventListener('click', toggleSidebar);
    if (closeBtn) closeBtn.addEventListener('click', toggleSidebar);
    if (overlay) overlay.addEventListener('click', toggleSidebar);
</script>

<style>
    .fill-1 { font-variation-settings: 'FILL' 1; }
</style>
