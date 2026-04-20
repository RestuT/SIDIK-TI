<!-- Google Fonts & Material Symbols -->
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

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
</script>

<style>
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    display: inline-block;
    line-height: 1;
    text-transform: none;
    letter-spacing: normal;
    word-wrap: normal;
    white-space: nowrap;
    direction: ltr;
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
    -moz-osx-font-smoothing: grayscale;
    font-feature-settings: 'liga';
}
.fill-1 { font-variation-settings: 'FILL' 1; }

html.dark body { background-color: #020617 !important; color: #f8fafc !important; }
html.dark .bg-white, html.dark .bg-slate-50 { background-color: #0f172a !important; border-color: rgba(255,255,255,0.08) !important; color: #e2e8f0 !important; }
html.dark .text-slate-800, html.dark .text-slate-900 { color: #f8fafc !important; }
html.dark .text-slate-500, html.dark .text-slate-600 { color: #94a3b8 !important; }
</style>

<?php
$brand_name = 'SIDIK-TI';
$dept_name = $_SESSION['department'] ?? 'Department';
?>

<!-- MOBILE TOP APP BAR -->
<div class="lg:hidden fixed top-0 left-0 right-0 z-50 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border-b border-slate-200/50 dark:border-slate-700/50 px-4 h-14 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <button id="mobile-menu-btn" class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center">
            <span class="material-symbols-outlined text-xl">menu</span>
        </button>
        <span class="font-headline text-base font-black text-emerald-600 dark:text-emerald-400">Head View</span>
    </div>
    <div class="flex items-center gap-2">
        <div class="bg-emerald-50 dark:bg-emerald-900/30 px-3 py-1.5 rounded-full">
            <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase"><?php echo htmlspecialchars($dept_name); ?></span>
        </div>
    </div>
</div>

<!-- SIDEBAR NAVIGATION -->
<aside id="head-sidebar" class="fixed top-0 left-0 h-full z-50 w-72 flex flex-col bg-white dark:bg-slate-900 border-r border-slate-100 dark:border-slate-800 transition-transform duration-300 -translate-x-full lg:translate-x-0">
    <!-- Brand -->
    <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-100 dark:border-slate-800 shrink-0">
        <div class="w-10 h-10 bg-gradient-to-br from-emerald-600 to-emerald-500 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-200">
            <span class="material-symbols-outlined text-white">leaderboard</span>
        </div>
        <div class="flex flex-col min-w-0">
            <span class="text-xs font-black text-emerald-600 uppercase tracking-widest leading-none">Management</span>
            <span class="text-xl font-black text-slate-800 dark:text-white tracking-tight truncate"><?php echo htmlspecialchars($brand_name); ?></span>
        </div>
    </div>

    <!-- Profile -->
    <div class="mx-4 mt-4 mb-2 flex items-center gap-3 px-4 py-3.5 bg-emerald-50/50 dark:bg-emerald-900/20 rounded-2xl border border-emerald-100 dark:border-emerald-800/30">
        <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center border-2 border-white dark:border-slate-700">
            <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-xl fill-1">account_circle</span>
        </div>
        <div class="overflow-hidden flex-1">
            <p class="font-bold text-sm text-slate-800 dark:text-white truncate"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Head'); ?></p>
            <p class="text-[10px] text-emerald-600 font-bold uppercase tracking-wider"><?php echo htmlspecialchars($dept_name); ?></p>
        </div>
    </div>

    <!-- Nav -->
    <nav class="flex flex-col gap-0.5 flex-1 overflow-y-auto px-3 py-2">
        <?php $current = basename($_SERVER['PHP_SELF']); ?>
        <p class="px-3 pt-2 pb-1.5 text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">Department Overview</p>
        
        <a href="dashboard_head.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all <?php echo $current == 'dashboard_head.php' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-200' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
            <span class="material-symbols-outlined text-xl">dashboard</span>
            <span class="text-sm font-semibold">Dashboard</span>
        </a>

        <a href="staff_directory.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all <?php echo $current == 'staff_directory.php' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-200' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
            <span class="material-symbols-outlined text-xl">groups</span>
            <span class="text-sm font-semibold">Direktori Staff</span>
        </a>

        <a href="department_assets.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all <?php echo $current == 'department_assets.php' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-200' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
            <span class="material-symbols-outlined text-xl">inventory_2</span>
            <span class="text-sm font-semibold">Penggunaan Aset</span>
        </a>

        <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
            <p class="px-3 pb-1.5 text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">Standard Access</p>
            <a href="../modules_user/dashboard_user.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                <span class="material-symbols-outlined text-xl">confirmation_number</span>
                <span class="text-sm font-semibold">My Tickets</span>
            </a>
        </div>
    </nav>

    <!-- Bottom -->
    <div class="p-3 border-t border-slate-100 dark:border-slate-800 space-y-0.5">
        <button onclick="toggleGlobalTheme()" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
            <span class="material-symbols-outlined text-xl theme-icon-g">light_mode</span>
            <span class="text-sm font-semibold flex-1 text-left">Ganti Tema</span>
        </button>
        <a href="../auth/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20" onclick="return confirm('Yakin ingin keluar?')">
            <span class="material-symbols-outlined text-xl">logout</span>
            <span class="text-sm font-semibold">Sign Out</span>
        </a>
    </div>
</aside>

<div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 opacity-0 pointer-events-none transition-opacity duration-300 lg:hidden"></div>

<script>
(function() {
    const sidebar = document.getElementById('head-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const menuBtn = document.getElementById('mobile-menu-btn');
    const closeBtn = document.getElementById('close-sidebar-btn');

    function openSidebar() {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('opacity-0', 'pointer-events-none');
    }

    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('opacity-0', 'pointer-events-none');
    }

    if (menuBtn)  menuBtn.addEventListener('click', openSidebar);
    if (overlay)  overlay.addEventListener('click', closeSidebar);
})();
</script>

<style>
    @media (min-width: 1024px) {
        body.head-layout > main { margin-left: 18rem; }
    }
</style>
