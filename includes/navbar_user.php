<nav class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
        <a href="../modules_user/dashboard_user.php" class="flex items-center text-blue-600 font-bold text-xl">
            <i class="fa-solid fa-arrow-left mr-2 text-sm"></i> Dashboard
        </a>
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500">User: <strong class="text-gray-800"><?php echo $_SESSION['user']; ?></strong></span>
                <i class="fa-solid fa-circle-check text-green-500 text-xs"></i>
            </div>
            
            <a href="../auth/logout.php" 
               onclick="return confirm('Apakah Anda yakin ingin keluar?')"
               class="flex items-center gap-2 bg-red-50 text-red-600 px-4 py-2 rounded-xl text-sm font-bold hover:bg-red-600 hover:text-white transition-all duration-300">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Keluar</span>
            </a>
        </div>
    </div>
</nav>