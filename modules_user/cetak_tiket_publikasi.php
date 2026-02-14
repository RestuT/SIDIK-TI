<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['ticket'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$ticket_no = mysqli_real_escape_string($conn, $_GET['ticket']);
$user_id = $_SESSION['user_id'];

// Ambil data pengajuan dan nama user
$query = "SELECT s.*, u.full_name 
          FROM submissions s 
          JOIN users u ON s.user_id = u.id 
          WHERE s.ticket_number = '$ticket_no' AND s.user_id = '$user_id'";

$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Data tiket tidak ditemukan atau Anda tidak memiliki akses.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Pengajuan - <?php echo $data['ticket_number']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none; }
            body { background: white; }
            .print-card { border: none; shadow: none; }
        }
    </style>
</head>
<body class="bg-gray-100 p-10">

    <div class="max-w-2xl mx-auto bg-white p-8 rounded-xl shadow-lg border border-gray-200 print-card">
        <div class="text-center border-b-2 border-gray-100 pb-6 mb-6">
            <h1 class="text-2xl font-bold text-blue-700 uppercase tracking-widest">Bukti Pengajuan Layanan</h1>
            <p class="text-gray-500 text-sm">IT Helpdesk System - SIDIK-TI</p>
        </div>

        <div class="space-y-4">
            <div class="flex justify-between border-b border-gray-50 pb-2">
                <span class="text-gray-500 font-medium">Nomor Tiket</span>
                <span class="font-bold text-blue-600"><?php echo $data['ticket_number']; ?></span>
            </div>
            <div class="flex justify-between border-b border-gray-50 pb-2">
                <span class="text-gray-500 font-medium">Nama Pemohon</span>
                <span class="font-bold text-gray-800"><?php echo $data['full_name']; ?></span>
            </div>
            <div class="flex justify-between border-b border-gray-50 pb-2">
                <span class="text-gray-500 font-medium">Jenis Layanan</span>
                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-bold">
                    <?php echo strtoupper($data['type']); ?>
                </span>
            </div>
            <div class="flex justify-between border-b border-gray-50 pb-2">
                <span class="text-gray-500 font-medium">Judul Pengajuan</span>
                <span class="text-gray-800 font-semibold"><?php echo $data['title']; ?></span>
            </div>
            <div class="flex justify-between border-b border-gray-50 pb-2">
                <span class="text-gray-500 font-medium">Tanggal Pengajuan</span>
                <span class="text-gray-700"><?php echo date('d F Y, H:i', strtotime($data['created_at'])); ?></span>
            </div>
        </div>

        <div class="mt-10 p-4 bg-yellow-50 border-l-4 border-yellow-400 text-xs text-yellow-700 italic">
            *Simpan file ini sebagai bukti pengajuan yang sah. Anda dapat memantau status pengerjaan secara berkala di Dashboard Audit.
        </div>

        <div class="mt-8 flex gap-4 no-print">
            <button onclick="window.print()" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition">
                Cetak ke PDF / Printer
            </button>
            <a href="dashboard_user.php" class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-bold text-center hover:bg-gray-300 transition">
                Kembali ke Dashboard
            </a>
        </div>
    </div>

</body>
</html>