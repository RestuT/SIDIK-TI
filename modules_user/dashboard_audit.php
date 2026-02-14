<?php
session_start();
// PERBAIKAN: Hubungkan ke database. Gunakan ../ karena file ini ada di dalam folder modules_user/
include '../config/database.php'; 

// Proteksi: Pastikan user login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// PERBAIKAN: Gunakan kueri SQL nyata untuk mengambil data user yang sedang login
$query = "SELECT s.*, u.full_name as pic_name 
          FROM submissions s 
          LEFT JOIN users u ON s.pic_id = u.id 
          WHERE s.user_id = '$user_id' 
          ORDER BY s.created_at DESC";

// Variabel $conn berasal dari file ../config/database.php
$result = mysqli_query($conn, $query); 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Pengajuan - IT Helpdesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-50 font-sans">

    <?php include '../includes/navbar_user.php'; ?>

    <main class="max-w-7xl mx-auto px-4 py-10">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Riwayat Seluruh Pengajuan</h2>

        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 text-gray-400 text-xs uppercase tracking-wider border-b">
                            <th class="px-6 py-4">ID & Tanggal</th>
                            <th class="px-6 py-4">Jenis & Judul</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Petugas (PIC)</th>
                            <th class="px-6 py-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if(mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4">
                                    <span class="block font-bold text-gray-700 text-sm"><?php echo $row['ticket_number']; ?></span>
                                    <span class="text-xs text-gray-400"><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold mb-1 
                                        <?php echo $row['type'] == 'Publikasi' ? 'bg-purple-100 text-purple-700' : 'bg-orange-100 text-orange-700'; ?>">
                                        <?php echo strtoupper($row['type']); ?>
                                    </span>
                                    <p class="text-sm text-gray-800 font-medium"><?php echo $row['title']; ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-bold 
                                        <?php 
                                            if($row['status'] == 'Selesai') echo 'text-green-600';
                                            elseif($row['status'] == 'Proses') echo 'text-blue-600';
                                            elseif($row['status'] == 'Ditolak') echo 'text-red-600';
                                            else echo 'text-yellow-600';
                                        ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?php echo $row['pic_name'] ? $row['pic_name'] : '-'; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if($row['type'] == 'Publikasi'): ?>
                                        <a href="cetak_tiket.php?ticket=<?php echo $row['ticket_number']; ?>" class="text-blue-600 font-bold text-sm hover:underline">Cetak</a>
                                    <?php else: ?>
                                        <a href="cetak_tiket_pengadaan.php?ticket=<?php echo $row['ticket_number']; ?>" class="text-orange-600 font-bold text-sm hover:underline">Cetak</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-400">Belum ada riwayat pengajuan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>