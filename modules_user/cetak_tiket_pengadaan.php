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

// Ambil data pengajuan pengadaan dan nama user
$query = "SELECT s.*, u.full_name 
          FROM submissions s 
          JOIN users u ON s.user_id = u.id 
          WHERE s.ticket_number = '$ticket_no' AND s.user_id = '$user_id' AND s.type = 'Pengadaan'";

$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Data tiket pengadaan tidak ditemukan.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Pengadaan - <?php echo $data['ticket_number']; ?></title>
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

    <div class="max-w-2xl mx-auto bg-white p-8 rounded-3xl shadow-xl border border-gray-100 print-card">
        <div class="text-center border-b-2 border-dashed border-gray-200 pb-6 mb-8">
            <div class="bg-orange-100 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-cart-shopping text-orange-600 text-2xl"></i>
            </div>
            <h1 class="text-2xl font-extrabold text-gray-800">BUKTI PENGADAAN BARANG</h1>
            <p class="text-gray-500 text-sm">ID Tiket: <span class="font-mono font-bold text-orange-600"><?php echo $data['ticket_number']; ?></span></p>
        </div>

        <div class="space-y-4 mb-8">
            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                <span class="text-gray-500 text-sm">Nama Pemohon</span>
                <span class="font-bold text-gray-800"><?php echo $data['full_name']; ?></span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                <span class="text-gray-500 text-sm">Barang / Spesifikasi</span>
                <span class="font-bold text-gray-800"><?php echo $data['title']; ?></span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                <span class="text-gray-500 text-sm">Tingkat Urgensi</span>
                <span class="px-3 py-1 rounded-lg text-xs font-bold <?php echo $data['urgency'] == 'Penting' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600'; ?>">
                    <?php echo $data['urgency']; ?>
                </span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                <span class="text-gray-500 text-sm">Tanggal Pengajuan</span>
                <span class="text-gray-700"><?php echo date('d/m/Y H:i', strtotime($data['created_at'])); ?></span>
            </div>
        </div>

        <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 text-center">
            <p class="text-xs text-gray-400 uppercase font-bold tracking-widest mb-1">Status Saat Ini</p>
            <h3 class="text-lg font-bold text-blue-600 uppercase"><?php echo $data['status']; ?></h3>
        </div>

        <div class="mt-10 flex flex-col md:flex-row gap-4 no-print">
            <button onclick="window.print()" class="flex-1 bg-gray-800 text-white py-3 rounded-xl font-bold hover:bg-black transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-print"></i> Cetak Bukti PDF
            </button>
            <a href="dashboard_user.php" class="flex-1 bg-white border border-gray-200 text-gray-600 py-3 rounded-xl font-bold text-center hover:bg-gray-50 transition">
                Kembali
            </a>
        </div>
    </div>

    <p class="text-center text-gray-400 text-[10px] mt-8 uppercase tracking-widest no-print">
        Dokumen ini dihasilkan secara otomatis oleh Sistem SIDIK-TI
    </p>

</body>
</html>