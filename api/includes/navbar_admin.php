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
    $settingsRef  = $db->collection('system_settings')->document('app_name');
    $settingsSnap = $settingsRef->snapshot();
    if ($settingsSnap->exists()) {
        $brand_name = $settingsSnap->get('setting_value') ?? 'SIDIK-TI';
    }
} catch (Exception $e) { /* fallback */ }
?>

<!-- ============================================================
     MOBILE TOP APP BAR (hanya muncul di < lg = < 1024px)
     ============================================================ -->
<div id="mobile-topbar"
     class="lg:hidden fixed top-0 left-0 right-0 z-50
            bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl
            border-b border-slate-200/50 dark:border-slate-700/50
            px-4 h-14 flex items-center justify-between shadow-sm">
    <div class="flex items-center gap-3">
        <button id="mobile-menu-btn"
                class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400
                       flex items-center justify-center active:scale-95 transition-all">
            <span class="material-symbols-outlined text-xl">menu</span>
        </button>
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 bg-gradient-to-br from-indigo-600 to-indigo-500 rounded-lg flex items-center justify-center shadow-sm">
                <span class="material-symbols-outlined text-white text-sm">terminal</span>
            </div>
            <span class="font-headline text-base font-black text-indigo-600 dark:text-indigo-400 tracking-tight">
                <?php echo htmlspecialchars($brand_name); ?>
            </span>
        </div>
    </div>
    <div class="flex items-center gap-2 bg-indigo-50 dark:bg-indigo-900/30 px-3 py-1.5 rounded-full">
        <span class="material-symbols-outlined text-indigo-500 text-sm fill-1">admin_panel_settings</span>
        <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">Admin</span>
    </div>
</div>

<!-- ============================================================
     SIDEBAR OVERLAY (backdrop saat sidebar terbuka di mobile)
     ============================================================ -->
<div id="sidebar-overlay"
     class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40
            opacity-0 pointer-events-none transition-opacity duration-300 lg:hidden">
</div>

<!-- ============================================================
     SIDEBAR NAVIGATION DRAWER

     DESKTOP (lg+):
       - Posisi: fixed, kiri, tinggi penuh, lebar 288px (w-72)
       - Selalu tampil, tidak bisa ditutup
       - Konten utama didorong oleh padding-left via CSS var

     MOBILE (< lg):
       - Posisi: fixed, slide-in dari kiri
       - Default tersembunyi (-translate-x-full)
       - Dibuka via hamburger menu
     ============================================================ -->
<aside id="admin-sidebar"
       class="fixed top-0 left-0 h-full z-50
              w-72 flex flex-col
              bg-white dark:bg-slate-900
              border-r border-slate-100 dark:border-slate-800
              shadow-2xl shadow-indigo-900/10
              transition-transform duration-300 ease-in-out
              -translate-x-full lg:translate-x-0">

    <!-- Close button (mobile only) -->
    <div class="lg:hidden absolute top-3 right-3 z-10">
        <button id="close-sidebar-btn"
                class="w-8 h-8 rounded-xl bg-slate-100 text-slate-500
                       hover:bg-rose-100 hover:text-rose-500
                       flex items-center justify-center transition-all active:scale-90">
            <span class="material-symbols-outlined text-sm">close</span>
        </button>
    </div>

    <!-- Brand Header -->
    <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-100 dark:border-slate-800 shrink-0">
        <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-indigo-500 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-200 dark:shadow-indigo-900/30 shrink-0">
            <span class="material-symbols-outlined text-white">terminal</span>
        </div>
        <span class="text-xl font-black text-indigo-600 dark:text-indigo-400 tracking-tight truncate">
            <?php echo htmlspecialchars($brand_name); ?>
        </span>
    </div>

    <!-- Admin Profile Section -->
    <div class="mx-4 mt-4 mb-2 flex items-center gap-3 px-4 py-3.5
                bg-gradient-to-r from-indigo-50 to-slate-50 dark:from-indigo-900/20 dark:to-slate-800/20
                rounded-2xl border border-indigo-100 dark:border-indigo-800/30 shrink-0">
        <div class="relative shrink-0">
            <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center border-2 border-white dark:border-slate-700 shadow-sm">
                <span class="material-symbols-outlined text-indigo-600 dark:text-indigo-400 text-xl fill-1">account_circle</span>
            </div>
            <div class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-400 rounded-full border-2 border-white dark:border-slate-900"></div>
        </div>
        <div class="overflow-hidden flex-1 min-w-0">
            <p class="font-headline font-bold text-sm text-slate-800 dark:text-white leading-none truncate">
                <?php echo isset($_SESSION['user']) ? ucfirst(htmlspecialchars($_SESSION['user'])) : 'System Admin'; ?>
            </p>
            <p class="text-[10px] text-indigo-500 dark:text-indigo-400 mt-1 font-bold uppercase tracking-wider">IT Department</p>
        </div>
    </div>

    <!-- Navigation Items -->
    <nav class="flex flex-col gap-0.5 flex-1 overflow-y-auto px-3 py-2 min-h-0">
        <?php
            $current_page = basename($_SERVER['PHP_SELF']);
            function get_nav_style($page, $current) {
                return $page == $current
                    ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 dark:shadow-indigo-900/30'
                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400';
            }
        ?>

        <p class="px-3 pt-2 pb-1.5 text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-600">Main Menu</p>

        <a class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 active:scale-[0.98] <?php echo get_nav_style('dashboard_admin.php', $current_page); ?>"
           href="dashboard_admin.php">
            <span class="material-symbols-outlined text-xl shrink-0 <?php if($current_page == 'dashboard_admin.php') echo 'fill-1'; ?>">dashboard</span>
            <span class="font-headline text-sm font-semibold truncate">Dashboard</span>
        </a>

        <a class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 active:scale-[0.98] <?php echo get_nav_style('manage_users.php', $current_page); ?>"
           href="manage_users.php">
            <span class="material-symbols-outlined text-xl shrink-0 <?php if($current_page == 'manage_users.php') echo 'fill-1'; ?>">group</span>
            <span class="font-headline text-sm font-semibold truncate">Users</span>
        </a>

        <a class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 active:scale-[0.98] <?php echo get_nav_style('inventory.php', $current_page); ?>"
           href="inventory.php">
            <span class="material-symbols-outlined text-xl shrink-0 <?php if($current_page == 'inventory.php') echo 'fill-1'; ?>">inventory_2</span>
            <span class="font-headline text-sm font-semibold truncate">Inventory</span>
        </a>

        <a class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 active:scale-[0.98] <?php echo get_nav_style('analytics.php', $current_page); ?>"
           href="analytics.php">
            <span class="material-symbols-outlined text-xl shrink-0 <?php if($current_page == 'analytics.php') echo 'fill-1'; ?>">analytics</span>
            <span class="font-headline text-sm font-semibold truncate">Analytics</span>
        </a>

        <a class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 active:scale-[0.98] <?php echo get_nav_style('kelola_pengumuman.php', $current_page); ?>"
           href="kelola_pengumuman.php">
            <span class="material-symbols-outlined text-xl shrink-0 <?php if($current_page == 'kelola_pengumuman.php') echo 'fill-1'; ?>">campaign</span>
            <span class="font-headline text-sm font-semibold truncate">Pengumuman</span>
        </a>

        <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
            <p class="px-3 pb-1.5 text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-600">Master Config</p>

            <a class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 <?php echo get_nav_style('manage_departments.php', $current_page); ?>"
               href="manage_departments.php">
                <span class="material-symbols-outlined text-xl shrink-0 <?php if($current_page == 'manage_departments.php') echo 'fill-1'; ?>">corporate_fare</span>
                <span class="font-headline text-sm font-semibold truncate">Departments</span>
            </a>

            <a class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 <?php echo get_nav_style('manage_budget.php', $current_page); ?>"
               href="manage_budget.php">
                <span class="material-symbols-outlined text-xl shrink-0 <?php if($current_page == 'manage_budget.php') echo 'fill-1'; ?>">payments</span>
                <span class="font-headline text-sm font-semibold truncate">Budget Control</span>
            </a>

            <a class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 <?php echo get_nav_style('manage_templates.php', $current_page); ?>"
               href="manage_templates.php">
                <span class="material-symbols-outlined text-xl shrink-0 <?php if($current_page == 'manage_templates.php') echo 'fill-1'; ?>">settings_suggest</span>
                <span class="font-headline text-sm font-semibold truncate">Templates</span>
            </a>
        </div>
    </nav>

    <!-- Bottom Actions -->
    <div class="shrink-0 border-t border-slate-100 dark:border-slate-800 p-3 space-y-0.5">
        <a class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 <?php echo get_nav_style('settings.php', $current_page); ?>"
           href="settings.php">
            <span class="material-symbols-outlined text-xl shrink-0 <?php if($current_page == 'settings.php') echo 'fill-1'; ?>">settings</span>
            <span class="font-headline text-sm font-semibold truncate">Settings</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-all duration-200"
           href="../auth/logout.php"
           onclick="return confirm('Yakin ingin keluar?')">
            <span class="material-symbols-outlined text-xl shrink-0">logout</span>
            <span class="font-headline text-sm font-semibold truncate">Sign Out</span>
        </a>
    </div>
</aside>

<script>
(function() {
    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const menuBtn = document.getElementById('mobile-menu-btn');
    const closeBtn = document.getElementById('close-sidebar-btn');

    function openSidebar() {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('opacity-0', 'pointer-events-none');
        document.body.classList.add('overflow-hidden');
    }

    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('opacity-0', 'pointer-events-none');
        document.body.classList.remove('overflow-hidden');
    }

    if (menuBtn)  menuBtn.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (overlay)  overlay.addEventListener('click', closeSidebar);
})();
</script>

<style>
    .fill-1 { font-variation-settings: 'FILL' 1; }

    /*
     * SISTEM LAYOUT SIDEBAR DESKTOP
     * Sidebar lebar 288px (w-72 = 18rem), fixed di kiri.
     * Semua halaman admin harus memiliki:
     *   body:  overflow-x: hidden
     *   main:  margin-left: 18rem  (lg:ml-72)
     * Ini lebih reliable daripada spacer-div karena tidak bergantung pada
     * flex container dan bekerja dengan sticky header di dalam main.
     */
    @media (min-width: 1024px) {
        /* Terapkan margin-left secara global untuk semua elemen main admin */
        body.admin-layout > main,
        body.admin-layout > .admin-content {
            margin-left: 18rem; /* setara w-72 */
        }
    }
</style>
