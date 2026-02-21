<?php
session_start();
include '../config/database.php';

// Proteksi: Pastikan user login dan ada parameter tiket
if (!isset($_SESSION['user_id']) || !isset($_GET['ticket'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$ticket_no = mysqli_real_escape_string($conn, $_GET['ticket']);
$user_id = $_SESSION['user_id'];

// Ambil data pengajuan maintenance, nama user, jabatan, dan departemen
$query = "SELECT s.*, u.full_name, u.jabatan, u.department 
          FROM submissions s 
          JOIN users u ON s.user_id = u.id 
          WHERE s.ticket_number = '$ticket_no' AND s.user_id = '$user_id'";

$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Data tiket maintenance tidak ditemukan.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Maintenance - <?php echo $data['ticket_number']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            body { background: white; padding: 0; }
            .print-card { border: none; shadow: none; width: 100%; max-width: 100%; }
        }
    </style>
</head>
<body class="bg-slate-100 p-4 md:p-10 font-sans">

    <div class="max-w-2xl mx-auto bg-white p-10 rounded-[40px] shadow-2xl border border-gray-100 print-card">
        <div class="text-center border-b-2 border-dashed border-gray-200 pb-8 mb-8">
            <div class="bg-emerald-600 w-20 h-20 rounded-3xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-emerald-200">
                <i class="fa-solid fa-screwdriver-wrench text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-black text-gray-800 tracking-tighter italic uppercase">Bukti Laporan <span class="text-emerald-600">Maintenance</span></h1>
            <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-2">Nomor Tiket: <?php echo $data['ticket_number']; ?></p>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-8 bg-slate-50 p-6 rounded-3xl border border-slate-100">
            <div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Pelapor</p>
                <p class="font-bold text-gray-800"><?php echo $data['full_name']; ?></p>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Jabatan / Dept</p>
                <p class="font-bold text-gray-800"><?php echo ($data['jabatan'] ?? 'Staff'); ?> - <?php echo $data['department']; ?></p>
            </div>
        </div>

        <div class="space-y-6 mb-10">
            <div class="flex justify-between items-start py-3 border-b border-gray-50">
                <span class="text-gray-500 text-sm font-medium">Perangkat</span>
                <span class="font-bold text-gray-800 text-right"><?php echo $data['title']; ?></span>
            </div>
            
            <div>
                <span class="text-gray-500 text-sm font-medium block mb-2">Detail Keluhan / Kerusakan:</span>
                <div class="bg-slate-50 p-4 rounded-2xl text-sm text-gray-700 leading-relaxed border border-slate-100">
                    <?php echo nl2br(htmlspecialchars($data['description'])); ?>
                </div>
            </div>

            <?php if (!empty($data['attachment_path'])): ?>
            <div>
                <span class="text-gray-500 text-sm font-medium block mb-2">Dokumentasi Barang:</span>
                <div class="rounded-2xl overflow-hidden border border-gray-200">
                    <img src="<?php echo $data['attachment_path']; ?>" alt="Foto Kerusakan" class="w-full h-auto object-cover max-h-64">
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="border-t border-gray-100 pt-8">
            <div class="flex items-center justify-between mb-8">
                <div class="text-center flex-1">
                    <p class="text-[9px] font-bold text-gray-400 uppercase mb-4">Verifikasi Sistem</p>
                    <div class="w-20 h-20 bg-gray-50 mx-auto rounded-xl flex items-center justify-center border border-gray-100">
                        <i class="fa-solid fa-check-double text-emerald-500 text-3xl"></i>
                    </div>
                </div>
                <div class="flex-1 text-center border-l border-gray-100">
                    <p class="text-[9px] font-bold text-gray-400 uppercase mb-1">Status Penanganan</p>
                    <h3 class="text-xl font-black text-emerald-600 uppercase italic">
                        <?php echo $data['status']; ?>
                    </h3>
                </div>
            </div>

            <div class="flex flex-col md:flex-row gap-4 no-print">
                <button onclick="window.print()" class="flex-1 bg-slate-900 text-white py-4 rounded-2xl font-black hover:bg-emerald-600 transition active:scale-95 flex items-center justify-center gap-2 uppercase tracking-widest text-xs">
                    <i class="fa-solid fa-print"></i> Cetak Bukti
                </button>
                <a href="dashboard_user.php" class="flex-1 bg-gray-100 text-gray-500 py-4 rounded-2xl font-black text-center hover:bg-gray-200 transition uppercase tracking-widest text-xs">
                    Kembali
                </a>
            </div>
        </div>
    </div>

    <p class="text-center text-gray-400 text-[10px] mt-10 uppercase tracking-[0.2em] font-bold no-print">
        SIDIK-TI Maintenance System &copy; 2026
    </p>

</body>
</html>