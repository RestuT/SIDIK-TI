<!-- Theme Loader Script -->
<script>
    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
</script>

<?php
// Fetch Global App Name
$brand_query = mysqli_query($conn, "SELECT setting_value FROM system_settings WHERE setting_key = 'app_name'");
$brand_name = ($brand_query && mysqli_num_rows($brand_query) > 0) ? mysqli_fetch_assoc($brand_query)['setting_value'] : 'SIDIK-TI';
?>

<!-- Sidebar (NavigationDrawer) -->
<aside class="fixed left-0 top-0 h-full z-50 p-6 flex flex-col gap-4 bg-white/80 dark:bg-slate-900/80 backdrop-blur-2xl w-72 rounded-r-3xl shadow-2xl shadow-indigo-900/5 transition-all duration-300 border-r border-outline-variant/10">
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

        <div class="mt-4 pt-4 border-t border-outline-variant/20">
            <p class="px-4 text-[10px] font-black uppercase tracking-widest text-indigo-300 mb-2">Master Configuration</p>
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

<style>
    .fill-1 { font-variation-settings: 'FILL' 1; }
</style>