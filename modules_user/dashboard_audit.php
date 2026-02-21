<?php
session_start();
include '../config/database.php'; 

// Proteksi: Pastikan user login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Mengambil data seluruh pengajuan milik user yang sedang login
$query = "SELECT s.*, u.full_name as pic_name 
          FROM submissions s 
          LEFT JOIN users u ON s.pic_id = u.id 
          WHERE s.user_id = '$user_id' 
          ORDER BY s.created_at DESC";

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
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-black text-gray-800 tracking-tighter uppercase italic">Riwayat <span class="text-blue-600">Audit</span></h2>
                <p class="text-sm text-gray-500 font-medium">Pantau status pemeliharaan dan pengadaan aset Anda secara transparan.</p>
            </div>
            <div class="bg-white px-6 py-3 rounded-2xl shadow-sm border border-gray-100">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Pengajuan</p>
                <p class="text-xl font-black text-blue-600"><?php echo mysqli_num_rows($result); ?></p>
            </div>
        </div>

        <div class="bg-white rounded-[40px] shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/50 text-gray-400 text-[10px] font-black uppercase tracking-widest border-b">
                            <th class="px-8 py-5">ID & Tanggal</th>
                            <th class="px-8 py-5">Jenis & Item</th>
                            <th class="px-8 py-5">Status Progress</th>
                            <th class="px-8 py-5 text-center">Petugas (PIC)</th>
                            <th class="px-8 py-5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if(mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr class="hover:bg-slate-50 transition-all duration-300">
                                <td class="px-8 py-5">
                                    <span class="block font-black text-blue-600 text-sm italic">#<?php echo $row['ticket_number']; ?></span>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase"><?php echo date('d M Y', strtotime($row['created_at'])); ?></span>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="inline-block px-3 py-1 rounded-lg text-[9px] font-black mb-1 uppercase tracking-tighter
                                        <?php echo $row['type'] == 'Maintenance' ? 'bg-emerald-100 text-emerald-700' : 'bg-orange-100 text-orange-700'; ?>">
                                        <i class="fa-solid <?php echo $row['type'] == 'Maintenance' ? 'fa-screwdriver-wrench' : 'fa-cart-shopping'; ?> mr-1"></i>
                                        <?php echo $row['type']; ?>
                                    </span>
                                    <p class="text-sm text-gray-800 font-bold tracking-tight"><?php echo $row['title']; ?></p>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="flex items-center gap-2 text-xs font-black uppercase
                                        <?php 
                                            if($row['status'] == 'Selesai') echo 'text-green-600';
                                            elseif($row['status'] == 'Proses') echo 'text-blue-600';
                                            elseif($row['status'] == 'Ditolak') echo 'text-red-600';
                                            else echo 'text-yellow-600';
                                        ?>">
                                        <i class="fa-solid fa-circle text-[6px]"></i>
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-center">
                                    <?php if($row['pic_name']): ?>
                                        <div class="flex items-center justify-center gap-2">
                                            <div class="w-7 h-7 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                                                <i class="fa-solid fa-user-gear text-[10px]"></i>
                                            </div>
                                            <span class="text-xs font-bold text-gray-700"><?php echo $row['pic_name']; ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-[10px] font-bold text-gray-300 uppercase tracking-widest italic">Belum Ada PIC</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <?php if($row['type'] == 'Maintenance'): ?>
                                        <a href="cetak_tiket_maintenance.php?ticket=<?php echo $row['ticket_number']; ?>" 
                                           class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-600 px-4 py-2 rounded-xl text-[10px] font-black hover:bg-emerald-600 hover:text-white transition-all duration-300 uppercase">
                                            <i class="fa-solid fa-print"></i> Cetak Bukti
                                        </a>
                                    <?php else: ?>
                                        <a href="cetak_tiket_pengadaan.php?ticket=<?php echo $row['ticket_number']; ?>" 
                                           class="inline-flex items-center gap-2 bg-orange-50 text-orange-600 px-4 py-2 rounded-xl text-[10px] font-black hover:bg-orange-600 hover:text-white transition-all duration-300 uppercase">
                                            <i class="fa-solid fa-print"></i> Cetak Bukti
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-8 py-20 text-center">
                                    <div class="flex flex-col items-center opacity-20">
                                        <i class="fa-solid fa-folder-open text-6xl mb-4"></i>
                                        <p class="text-sm font-black uppercase tracking-widest">Belum ada riwayat pengajuan ditemukan</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="text-center py-10">
        <p class="text-[10px] font-bold text-gray-300 uppercase tracking-[0.3em]">SIDIK-TI Audit & Monitoring System &copy; 2026</p>
    </footer>

</body>
</html>