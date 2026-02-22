<?php
session_start();
include '../config/database.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Query mengambil data seluruh pengajuan milik user yang sedang login
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
            <div class="bg-white px-6 py-3 rounded-2xl shadow-sm border border-gray-100 text-center">
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
                                    <?php if($row['is_appealed'] == 1): ?>
                                        <span class="block text-[9px] font-bold text-red-500 italic mt-1 uppercase tracking-tighter">Menunggu Banding...</span>
                                    <?php endif; ?>
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
                                <td class="px-8 py-5 text-right flex justify-end gap-2">
                                    <?php if($row['status'] == 'Ditolak' && $row['is_appealed'] == 0): ?>
                                        <button onclick='openAppealModal(<?php echo json_encode($row); ?>)' 
                                                class="inline-flex items-center gap-2 bg-red-100 text-red-600 px-4 py-2 rounded-xl text-[10px] font-black hover:bg-red-600 hover:text-white transition-all duration-300 uppercase">
                                            <i class="fa-solid fa-scale-unbalanced-flip"></i> Aju Banding
                                        </button>
                                    <?php endif; ?>

                                    <a href="cetak_tiket_<?php echo strtolower($row['type']); ?>.php?ticket=<?php echo $row['ticket_number']; ?>" 
                                       class="inline-flex items-center gap-2 bg-slate-100 text-slate-600 px-4 py-2 rounded-xl text-[10px] font-black hover:bg-slate-800 hover:text-white transition-all duration-300 uppercase">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
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

    <div id="modalAppeal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white rounded-[40px] max-w-lg w-full p-10 shadow-2xl transition-all" id="modalContent">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-black text-gray-800 italic uppercase">Form <span class="text-red-600">Aju Banding</span></h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-red-500 transition"><i class="fa-solid fa-xmark text-2xl"></i></button>
            </div>
            
            <div class="bg-red-50 p-4 rounded-2xl mb-6 border border-red-100">
                <p class="text-[10px] font-black text-red-400 uppercase mb-1 tracking-widest">Alasan Penolakan Admin:</p>
                <p id="reject_reason" class="text-sm text-red-700 italic font-medium"></p>
            </div>

            <form action="../config/proses_banding.php" method="POST" class="space-y-4">
                <input type="hidden" name="submission_id" id="appeal_id">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-1">Pembelaan / Alasan Banding</label>
                    <textarea name="appeal_reason" required rows="4" 
                        class="w-full p-4 bg-gray-50 border border-gray-100 rounded-2xl outline-none focus:ring-4 focus:ring-red-100 transition text-sm" 
                        placeholder="Jelaskan alasan kenapa pengajuan ini harus ditinjau kembali..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-100 text-gray-500 font-bold py-4 rounded-2xl uppercase text-xs">Batal</button>
                    <button type="submit" name="kirim_banding" class="flex-[2] bg-red-600 text-white font-black py-4 rounded-2xl shadow-lg hover:bg-red-700 transition uppercase tracking-widest text-xs">Kirim Banding</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="text-center py-10">
        <p class="text-[10px] font-bold text-gray-300 uppercase tracking-[0.3em]">SIDIK-TI Audit & Monitoring System &copy; 2026</p>
    </footer>

    <script>
    function openAppealModal(data) {
        document.getElementById('appeal_id').value = data.id;
        document.getElementById('reject_reason').innerText = data.admin_reasoning || "Tidak ada alasan spesifik.";
        
        const modal = document.getElementById('modalAppeal');
        modal.classList.replace('hidden', 'flex');
    }

    function closeModal() {
        const modal = document.getElementById('modalAppeal');
        modal.classList.replace('flex', 'hidden');
    }
    </script>
</body>
</html>