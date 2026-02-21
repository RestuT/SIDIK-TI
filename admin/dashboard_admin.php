<?php
session_start();
include '../config/database.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_user.php");
    exit();
}

// --- LOGIKA PENGHAPUSAN (BARU) ---
if (isset($_GET['delete_id'])) {
    $id_to_delete = mysqli_real_escape_string($conn, $_GET['delete_id']);
    
    // Pastikan hanya menghapus yang statusnya 'Selesai' untuk keamanan data
    $check_status = mysqli_query($conn, "SELECT status FROM submissions WHERE id = '$id_to_delete'");
    $status_data = mysqli_fetch_assoc($check_status);

    if ($status_data && $status_data['status'] === 'Selesai') {
        $delete_query = "DELETE FROM submissions WHERE id = '$id_to_delete'";
        if (mysqli_query($conn, $delete_query)) {
            header("Location: dashboard_admin.php?msg=deleted");
            exit();
        }
    } else {
        header("Location: dashboard_admin.php?msg=error_status");
        exit();
    }
}

// Ambil Statistik
$total_sub = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM submissions"))['total'];
$pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM submissions WHERE status = 'Menunggu'"))['total'];

// Ambil Semua Pengajuan
$query = "SELECT s.*, u.full_name as pemohon 
          FROM submissions s 
          JOIN users u ON s.user_id = u.id 
          ORDER BY s.created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - SIDIK-TI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-100 flex">

    <?php include '../includes/navbar_admin.php'; ?>

    <main class="flex-1 p-10">
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 p-4 mb-6 rounded-r-xl text-sm font-bold">
                <i class="fa-solid fa-check-circle mr-2"></i> Riwayat berhasil dihapus secara permanen.
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
            <div class="bg-white p-6 rounded-3xl shadow-sm border-l-8 border-blue-500">
                <p class="text-gray-500 text-sm font-bold uppercase tracking-widest">Total Pengajuan</p>
                <h3 class="text-4xl font-black text-gray-800"><?php echo $total_sub; ?></h3>
            </div>
            <div class="bg-white p-6 rounded-3xl shadow-sm border-l-8 border-yellow-500">
                <p class="text-gray-500 text-sm font-bold uppercase tracking-widest">Menunggu Validasi</p>
                <h3 class="text-4xl font-black text-gray-800"><?php echo $pending; ?></h3>
            </div>
        </div>

        <div class="bg-white rounded-[40px] shadow-sm overflow-hidden border border-gray-100">
            <table class="w-full text-left">
                <thead class="bg-gray-50/50 border-b">
                    <tr>
                        <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Tiket & Pemohon</th>
                        <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Jenis & Judul</th>
                        <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Status</th>
                        <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr class="hover:bg-slate-50 transition duration-300">
                        <td class="px-8 py-5">
                            <span class="font-black text-blue-600 italic">#<?php echo $row['ticket_number']; ?></span>
                            <p class="text-xs font-bold text-gray-400 uppercase mt-1"><?php echo $row['pemohon']; ?></p>
                        </td>
                        <td class="px-8 py-5">
                            <span class="text-[9px] font-black px-2 py-0.5 rounded uppercase tracking-tighter <?php echo $row['type'] == 'Maintenance' ? 'bg-emerald-100 text-emerald-700' : 'bg-orange-100 text-orange-700'; ?>">
                                <?php echo $row['type']; ?>
                            </span>
                            <p class="text-sm font-bold text-gray-800 mt-1"><?php echo $row['title']; ?></p>
                        </td>
                        <td class="px-8 py-5 text-center">
                            <span class="text-xs font-black uppercase <?php 
                                if($row['status'] == 'Selesai') echo 'text-green-600';
                                elseif($row['status'] == 'Ditolak') echo 'text-red-600';
                                else echo 'text-yellow-600'; 
                            ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 flex gap-2">
    <?php 
        // Logika Pengalihan Halaman Kelola
        $target_page = ($row['type'] == 'Maintenance') ? 'kelola_maintenance.php' : 'kelola_pengajuan.php';
    ?>
    
    <a href="<?php echo $target_page; ?>?id=<?php echo $row['id']; ?>" 
       class="bg-blue-100 text-blue-600 px-4 py-2 rounded-xl text-xs font-black hover:bg-blue-600 hover:text-white transition uppercase tracking-tighter">
       <i class="fa-solid fa-gear mr-1"></i> Kelola
    </a>

    <?php if($row['status'] == 'Selesai'): ?>
    <a href="dashboard_admin.php?delete_id=<?php echo $row['id']; ?>" 
       onclick="return confirm('Apakah Anda yakin ingin menghapus riwayat yang telah selesai ini?')"
       class="bg-red-50 text-red-500 px-4 py-2 rounded-xl text-xs font-black hover:bg-red-500 hover:text-white transition uppercase tracking-tighter">
       <i class="fa-solid fa-trash"></i>
    </a>
    <?php endif; ?>
</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>