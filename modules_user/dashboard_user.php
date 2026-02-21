<?php
session_start();
include '../config/database.php';

// Proteksi halaman
if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['user'];

// Ambil data audit terbaru dari database
$query = "SELECT s.*, u.full_name as pic_name 
          FROM submissions s 
          LEFT JOIN users u ON s.pic_id = u.id 
          WHERE s.user_id = '$user_id' 
          ORDER BY s.created_at DESC LIMIT 5";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - IT Helpdesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-50 font-sans">

<navbar class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="../modules_user/dashboard_user.php" class="flex items-center text-blue-600 font-bold text-xl">
                <i></i> Dashboard User
            </a> 
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-500">User: <strong class="text-gray-800"><?php echo ucfirst($username); ?></strong></span>
                <i class="fa-solid fa-circle-check text-green-500"></i>
            </div>

             <a href="../auth/logout.php" 
               onclick="return confirm('Apakah Anda yakin ingin keluar?')"
               class="flex items-center gap-2 bg-red-50 text-red-600 px-4 py-2 rounded-xl text-sm font-bold hover:bg-red-600 hover:text-white transition-all duration-300">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Keluar</span>
            </a>
        </div>
        <hr>
    </navbar>

    <main class="pt-24 pb-12">
        <div class="max-w-7xl mx-auto px-4">
            
            <header class="mb-10">
                <h1 class="text-3xl font-bold text-gray-800">Halo, <?php echo ucfirst($username); ?>! 👋</h1>
                <p class="text-gray-500">Pantau pengajuan Anda atau buat permintaan layanan baru di bawah ini.</p>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="space-y-6">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-plus-circle text-blue-600"></i> Buat Pengajuan
                    </h2>
                    
                    <a href="form_maintenance.php" class="block p-6 bg-white rounded-3xl shadow-sm border border-gray-100 hover:shadow-md transition group">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center group-hover:bg-emerald-600 group-hover:text-white transition">
            <i class="fa-solid fa-screwdriver-wrench text-xl"></i>
        </div>
        <div>
            <h3 class="font-bold text-gray-800">Maintenance & Perbaikan</h3>
            <p class="text-xs text-gray-500">Ajukan servis atau pengecekan alat</p>
        </div>
    </div>
</a>
                    <a href="form_pengadaan.php" class="block p-6 bg-white rounded-3xl shadow-sm border border-gray-100 hover:shadow-md transition group">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-2xl flex items-center justify-center group-hover:bg-orange-600 group-hover:text-white transition">
                                <i class="fa-solid fa-cart-shopping text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800">Pengadaan Barang</h3>
                                <p class="text-xs text-gray-500">Minta hardware/software baru</p>
                            </div>
                        </div>
</a>            
                </div>

                <div class="lg:col-span-2 space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fa-solid fa-list-check text-green-600"></i> Status Pengajuan Terbaru
                        </h2>
                        <a href="dashboard_audit.php" class="text-sm text-blue-600 font-semibold hover:underline">Lihat Semua</a>
                    </div>

                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-400 text-[10px] uppercase tracking-widest border-b">
                                    <th class="px-6 py-4">Tiket</th>
                                    <th class="px-6 py-4">Layanan</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4">PIC</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <?php if(mysqli_num_rows($result) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="px-6 py-4 text-sm font-bold text-gray-700">#<?php echo $row['ticket_number']; ?></td>
                                        <td class="px-6 py-4">
                                            <p class="text-sm font-medium text-gray-800"><?php echo $row['title']; ?></p>
                                            <span class="text-[10px] text-gray-400"><?php echo $row['type']; ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-3 py-1 rounded-full text-[10px] font-bold 
                                                <?php 
                                                    if($row['status'] == 'Selesai') echo 'bg-green-100 text-green-700';
                                                    elseif($row['status'] == 'Proses') echo 'bg-blue-100 text-blue-700';
                                                    elseif($row['status'] == 'Ditolak') echo 'bg-red-100 text-red-700';
                                                    else echo 'bg-yellow-100 text-yellow-700';
                                                ?>">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600 italic">
                                            <?php echo $row['pic_name'] ? $row['pic_name'] : 'Belum ditentukan'; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-10 text-center text-gray-400 text-sm">Belum ada riwayat pengajuan.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>


    <?php include '../includes/footer.php'; ?>
</body>
</html>