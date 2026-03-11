<?php
session_start();
include '../config/database.php';
include '../config/csrf_helper.php';

// Proteksi akses untuk role admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_admin.php");
    exit();
}

// Mengambil daftar departemen
$query = mysqli_query($conn, "SELECT * FROM departments ORDER BY nama_dept ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Departemen - SIDIK-TI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    
    <?php include '../includes/navbar_admin.php'; ?>
    
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="bg-white shadow-sm px-8 py-5 flex justify-between items-center z-10 shrink-0 border-b border-gray-100">
            <div>
                <h1 class="text-2xl font-black text-gray-800 tracking-tight">Master <span class="text-blue-600">Departemen</span></h1>
                <p class="text-sm text-gray-500 font-medium mt-1">Kelola data referensi departemen institusi Anda</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold border-2 border-white shadow-md">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                
                <?php if(isset($_GET['status']) && $_GET['status'] == 'added'): ?>
                <div class="bg-emerald-50 text-emerald-600 p-4 rounded-xl font-bold flex items-center gap-3 border border-emerald-200">
                    <i class="fa-solid fa-check-circle text-xl"></i> Departemen baru berhasil ditambahkan!
                </div>
                <?php elseif(isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
                <div class="bg-blue-50 text-blue-600 p-4 rounded-xl font-bold flex items-center gap-3 border border-blue-200">
                    <i class="fa-solid fa-info-circle text-xl"></i> Departemen berhasil dihapus.
                </div>
                <?php elseif(isset($_GET['status']) && $_GET['status'] == 'error'): ?>
                <div class="bg-red-50 text-red-600 p-4 rounded-xl font-bold flex items-center gap-3 border border-red-200">
                    <i class="fa-solid fa-triangle-exclamation text-xl"></i> Gagal. Mungkin nama departemen sudah ada.
                </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Form Tambah Departemen -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 sticky top-0">
                            <h3 class="text-lg font-black text-gray-800 mb-6 flex items-center gap-2">
                                <i class="fa-regular fa-building text-blue-500"></i> Tambah Departemen
                            </h3>
                            
                            <form action="../config/proses_department.php" method="POST" class="space-y-4">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Nama Departemen Instansi</label>
                                    <input type="text" name="nama_dept" required placeholder="Contoh: IT / Kepegawaian" 
                                           class="w-full p-4 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-4 focus:ring-blue-100 outline-none transition font-medium text-gray-700">
                                </div>
                                <button type="submit" name="add_dept" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-4 rounded-2xl shadow-lg shadow-blue-200 transition active:scale-95 flex items-center justify-center gap-2 uppercase tracking-widest text-sm">
                                    <i class="fa-solid fa-plus"></i> Simpan Departemen
                                </button>
                            </form>
                            <div class="mt-6 p-4 bg-orange-50 rounded-2xl border border-orange-100">
                                <p class="text-xs text-orange-700 text-justify leading-relaxed font-medium">
                                    <i class="fa-solid fa-circle-info mr-1"></i> Data departemen digunakan sebagai acuan validasi pagu anggaran dan saat michelin mendaftar sebagai _user_. Hapus data usang secara berkala.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Daftar Departemen -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-3xl p-8 shadow-sm border border-gray-100">
                            <h3 class="text-lg font-black text-gray-800 mb-6 border-b border-gray-100 pb-4">
                                Daftar Master Departemen Saat Ini
                            </h3>
                            
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-widest border-b border-gray-200">
                                            <th class="p-4 font-black w-16 text-center rounded-tl-2xl">No</th>
                                            <th class="p-4 font-black">Identitas Departemen</th>
                                            <th class="p-4 font-black w-32 text-center rounded-tr-2xl">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <?php if(mysqli_num_rows($query) > 0): $no = 1; ?>
                                            <?php while($row = mysqli_fetch_assoc($query)): ?>
                                                <tr class="hover:bg-blue-50/50 transition duration-150">
                                                    <td class="p-4 text-center font-bold text-gray-400"><?php echo $no++; ?></td>
                                                    <td class="p-4">
                                                        <div class="font-bold text-gray-800 text-base"><?php echo htmlspecialchars($row['nama_dept']); ?></div>
                                                    </td>
                                                    <td class="p-4 text-center">
                                                        <a href="../config/hapus_department.php?id=<?php echo $row['id']; ?>" 
                                                           onclick="return confirm('Yakin ingin menghapus departemen ini? Ini dapat menyebabkan inkonsistensi data jika sudah digunakan oleh anggaran/user.');" 
                                                           class="inline-flex h-9 w-9 bg-red-100 text-red-600 rounded-xl hover:bg-red-600 hover:text-white items-center justify-center transition shadow-sm">
                                                            <i class="fa-solid fa-trash-can text-sm"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="p-8 text-center text-gray-400 font-medium">-- Belum ada cabang departemen terdaftar --</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>
</body>
</html>
