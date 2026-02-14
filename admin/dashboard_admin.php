<?php
session_start();
include '../config/database.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_user.php");
    exit();
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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
            <div class="bg-white p-6 rounded-3xl shadow-sm border-l-8 border-blue-500">
                <p class="text-gray-500 text-sm font-bold uppercase">Total Pengajuan</p>
                <h3 class="text-4xl font-black text-gray-800"><?php echo $total_sub; ?></h3>
            </div>
            <div class="bg-white p-6 rounded-3xl shadow-sm border-l-8 border-yellow-500">
                <p class="text-gray-500 text-sm font-bold uppercase">Menunggu Validasi</p>
                <h3 class="text-4xl font-black text-gray-800"><?php echo $pending; ?></h3>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Tiket & Pemohon</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Jenis & Judul</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4">
                            <span class="font-bold text-blue-600">#<?php echo $row['ticket_number']; ?></span>
                            <p class="text-xs text-gray-500"><?php echo $row['pemohon']; ?></p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100"><?php echo $row['type']; ?></span>
                            <p class="text-sm font-medium"><?php echo $row['title']; ?></p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-bold <?php echo $row['status'] == 'Selesai' ? 'text-green-600' : 'text-yellow-600'; ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <a href="kelola_pengajuan.php?id=<?php echo $row['id']; ?>" class="bg-blue-100 text-blue-600 px-4 py-2 rounded-lg text-xs font-bold hover:bg-blue-600 hover:text-white transition">Kelola</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>