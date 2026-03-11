<?php
session_start();
include '../config/database.php';
include '../config/csrf_helper.php';

// Proteksi akses untuk role admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_admin.php");
    exit();
}

// Mengambil daftar pengguna biasa (bukan super admin)
$query = mysqli_query($conn, "SELECT * FROM users WHERE role = 'user' ORDER BY full_name ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - SIDIK-TI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    
    <?php include '../includes/navbar_admin.php'; ?>
    
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="bg-white shadow-sm px-8 py-5 flex justify-between items-center z-10 shrink-0 border-b border-gray-100">
            <div>
                <h1 class="text-2xl font-black text-gray-800 tracking-tight">Manajemen <span class="text-blue-600">Pengguna</span></h1>
                <p class="text-sm text-gray-500 font-medium mt-1">Kelola direktori pendaftaran pegawai/klien Anda</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold border-2 border-white shadow-md">
                    <i class="fa-solid fa-users-gear"></i>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-6xl mx-auto space-y-8">
                
                <?php if(isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
                <div class="bg-blue-50 text-blue-600 p-4 rounded-xl font-bold flex items-center gap-3 border border-blue-200">
                    <i class="fa-solid fa-info-circle text-xl"></i> Akun pengguna telah berhasil dihapus dari sistem!
                </div>
                <?php endif; ?>

                <div class="bg-white rounded-3xl p-8 shadow-sm border border-gray-100">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 border-b border-gray-100 pb-4">
                        <h3 class="text-lg font-black text-gray-800">
                            Direktori Pegawai Terdaftar
                        </h3>
                        <div class="bg-blue-50 text-blue-700 font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-2">
                            <i class="fa-solid fa-user-check"></i> 
                            <?php echo mysqli_num_rows($query); ?> Akun Terverifikasi
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-widest border-b border-gray-200">
                                    <th class="p-4 font-black w-16 text-center rounded-tl-2xl">No</th>
                                    <th class="p-4 font-black">Informasi Pegawai</th>
                                    <th class="p-4 font-black">Identitas Jabatan</th>
                                    <th class="p-4 font-black">Kredensial</th>
                                    <th class="p-4 font-black w-32 text-center rounded-tr-2xl">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if(mysqli_num_rows($query) > 0): $no = 1; ?>
                                    <?php while($row = mysqli_fetch_assoc($query)): ?>
                                        <tr class="hover:bg-blue-50/50 transition duration-150 group">
                                            <td class="p-4 text-center font-bold text-gray-400 group-hover:text-blue-500"><?php echo $no++; ?></td>
                                            
                                            <!-- Data Pegawai -->
                                            <td class="p-4 space-y-1">
                                                <div class="font-black text-gray-800 text-sm"><?php echo htmlspecialchars($row['full_name'] ?? $row['fullname'] ?? '-', ENT_QUOTES); ?></div>
                                                <div class="text-[10px] text-gray-500 font-bold uppercase tracking-widest bg-gray-100 w-max px-2 py-0.5 rounded-md">ID: #<?php echo $row['id']; ?></div>
                                            </td>
                                            
                                            <!-- Karir / Departemen -->
                                            <td class="p-4">
                                                <div class="font-bold text-blue-700 text-sm flex items-center gap-1.5">
                                                    <i class="fa-regular fa-building text-blue-400"></i> <?php echo htmlspecialchars($row['department'] ?? '-'); ?>
                                                </div>
                                                <div class="text-[11px] font-bold text-gray-500 mt-0.5 uppercase tracking-wide">
                                                    <?php echo htmlspecialchars($row['jabatan'] ?? '-'); ?>
                                                </div>
                                            </td>

                                            <!-- Login Data -->
                                            <td class="p-4 space-y-1">
                                                <div class="text-xs font-black text-emerald-600 bg-emerald-50 px-2 py-1 rounded w-max border border-emerald-100 flex items-center gap-1">
                                                    <i class="fa-solid fa-fingerprint text-[10px]"></i> <?php echo htmlspecialchars($row['username']); ?>
                                                </div>
                                                <div class="text-[10px] text-gray-400 italic">Posisi: Akses User</div>
                                            </td>
                                            
                                            <!-- Action Delete -->
                                            <td class="p-4 text-center">
                                                <a href="../config/hapus_user.php?id=<?php echo $row['id']; ?>" 
                                                    onclick="return confirm('PERINGATAN!\n\nMenghapus akun <?php echo htmlspecialchars(addslashes($row['username'])); ?> akan mempengaruhi tiket history miliknya (jika ada).\n\nYakin ingin menghapus permanen karyawan ini?');" 
                                                    class="inline-flex h-9 w-9 bg-red-50 text-red-500 rounded-xl hover:bg-red-600 hover:text-white items-center justify-center transition shadow-sm border border-red-100 hover:border-red-600 group-hover:scale-110">
                                                    <i class="fa-solid fa-user-minus text-sm"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="p-12 text-center">
                                            <div class="inline-flex items-center justify-center p-4 bg-gray-50 rounded-full mb-3 text-gray-400">
                                                <i class="fa-solid fa-user-slash text-2xl"></i>
                                            </div>
                                            <p class="text-gray-500 font-bold">Belum Ada Pegawai Yang Mendaftar</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>
