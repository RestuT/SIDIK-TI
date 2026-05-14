<?php
$current_page = basename($_SERVER['PHP_SELF']); 
function get_mobile_nav_style($page, $current) {
    return $page == $current 
        ? 'flex flex-col items-center justify-center bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-400 rounded-2xl px-3 py-2 transition-all drop-shadow-sm' 
        : 'flex flex-col items-center justify-center text-slate-400 px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-2xl transition-all';
}
function get_mobile_icon_style($page, $current) {
    return $page == $current ? 'fill-1' : '';
}
?>
<!-- Universal BottomNavBar for Mobile -->
<nav class="md:hidden fixed bottom-0 left-0 w-full z-50 flex justify-around items-center px-4 py-3 pb-safe bg-white/90 dark:bg-slate-900/90 backdrop-blur-lg shadow-[0_-4px_20px_rgba(0,0,0,0.05)] border-t border-slate-100 dark:border-white/10 rounded-t-3xl">
    
    <a class="<?php echo get_mobile_nav_style('dashboard_user.php', $current_page); ?>" href="dashboard_user.php">
        <span class="material-symbols-outlined <?php echo get_mobile_icon_style('dashboard_user.php', $current_page); ?>">grid_view</span>
        <span class="font-headline text-[9px] font-bold uppercase tracking-wider mt-1">Home</span>
    </a>
    
    <a class="<?php echo get_mobile_nav_style('knowledge_base.php', $current_page); ?>" href="knowledge_base.php">
        <span class="material-symbols-outlined <?php echo get_mobile_icon_style('knowledge_base.php', $current_page); ?>">menu_book</span>
        <span class="font-headline text-[9px] font-bold uppercase tracking-wider mt-1">Panduan</span>
    </a>

    <a class="<?php echo get_mobile_nav_style('dashboard_audit.php', $current_page); ?>" href="dashboard_audit.php">
        <span class="material-symbols-outlined <?php echo get_mobile_icon_style('dashboard_audit.php', $current_page); ?>">handyman</span>
        <span class="font-headline text-[9px] font-bold uppercase tracking-wider mt-1">Requests</span>
    </a>

    <a class="<?php echo get_mobile_nav_style('assets_user.php', $current_page); ?>" href="assets_user.php">
        <span class="material-symbols-outlined <?php echo get_mobile_icon_style('assets_user.php', $current_page); ?>">list_alt</span>
        <span class="font-headline text-[9px] font-bold uppercase tracking-wider mt-1">Assets</span>
    </a>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'user'): ?>
    <?php 
        $target = 'dashboard_user.php';
        if ($_SESSION['role'] === 'admin') $target = '../admin/dashboard_admin.php';
        elseif ($_SESSION['role'] === 'technician') $target = '../modules_technician/dashboard_technician.php';
        elseif ($_SESSION['role'] === 'head') $target = '../modules_head/dashboard_head.php';
    ?>
    <a class="flex flex-col items-center justify-center text-primary dark:text-indigo-400 px-3 py-2 bg-primary/5 dark:bg-indigo-500/10 hover:bg-primary/10 dark:hover:bg-indigo-500/20 rounded-2xl transition-all" href="<?php echo $target; ?>">
        <span class="material-symbols-outlined">shield_person</span>
        <span class="font-headline text-[9px] font-bold uppercase tracking-wider mt-1">Manage</span>
    </a>
    <?php endif; ?>

</nav>
<style>
    .fill-1 { font-variation-settings: 'FILL' 1; }
</style>
