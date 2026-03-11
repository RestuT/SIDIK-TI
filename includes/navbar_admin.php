<aside class="w-64 bg-slate-900 min-h-screen text-white p-6 shadow-xl">
        <h1 class="text-2xl font-bold mb-10 text-blue-400">SIDIK-TI <span class="text-xs text-white block">Admin Panel</span></h1>
        <nav class="space-y-4">
            <a href="dashboard_admin.php" class="block p-3 hover:bg-slate-800 bg-blue-600 rounded-xl font-bold"><i class="fa-solid fa-gauge mr-2"></i> Dashboard</a>
            <div class="space-y-1 mt-6 border-b border-indigo-700/50 pb-6 mb-6">
                <!-- Data Master Section -->
                <p class="px-4 text-[10px] font-black uppercase tracking-widest text-indigo-300 mb-2">Master Data</p>
                <a href="../admin/manage_templates.php" class="flex items-center space-x-3 text-indigo-100 p-3 rounded-2xl hover:bg-white/10 transition group <?php echo basename($_SERVER['PHP_SELF']) == 'manage_templates.php' ? 'bg-white/20 font-bold' : ''; ?>">
                    <i class="fa-solid fa-layer-group w-5 text-center group-hover:scale-110 transition shrink-0"></i>
                    <span class="truncate">Master KAK/Template</span>
                </a>
                <a href="../admin/manage_departments.php" class="flex items-center space-x-3 text-indigo-100 p-3 rounded-2xl hover:bg-white/10 transition group <?php echo basename($_SERVER['PHP_SELF']) == 'manage_departments.php' ? 'bg-white/20 font-bold' : ''; ?>">
                    <i class="fa-regular fa-building w-5 text-center group-hover:scale-110 transition shrink-0"></i>
                    <span class="truncate">Kelola Departemen</span>
                </a>
                <a href="../admin/manage_users.php" class="flex items-center space-x-3 text-indigo-100 p-3 rounded-2xl hover:bg-white/10 transition group <?php echo basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'bg-white/20 font-bold' : ''; ?>">
                    <i class="fa-solid fa-users-gear w-5 text-center group-hover:scale-110 transition shrink-0"></i>
                    <span class="truncate">Daftar Pengguna</span>
                </a>
                <a href="../admin/manage_budget.php" class="flex items-center space-x-3 text-indigo-100 p-3 rounded-2xl hover:bg-white/10 transition group <?php echo basename($_SERVER['PHP_SELF']) == 'manage_budget.php' ? 'bg-white/20 font-bold' : ''; ?>">
                    <i class="fa-solid fa-wallet w-5 text-center group-hover:scale-110 transition shrink-0"></i>
                    <span class="truncate">Alokasi Anggaran</span>
                </a>
            </div>
            <a href="../auth/logout.php" class="block p-3 text-red-400 hover:bg-red-900/20 rounded-xl transition"><i class="fa-solid fa-power-off mr-2"></i> Logout</a>
        </nav>
    </aside>