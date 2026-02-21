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

// Ambil data pengajuan pengadaan, nama user, jabatan, dan departemen
$query = "SELECT s.*, u.full_name, u.jabatan, u.department 
          FROM submissions s 
          JOIN users u ON s.user_id = u.id 
          WHERE s.ticket_number = '$ticket_no' AND s.user_id = '$user_id' AND s.type = 'Pengadaan'";

$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Data tiket pengadaan tidak ditemukan.");
}

// Logika pemisahan rincian biaya dari deskripsi (jika menggunakan template)
$deskripsi_clean = $data['description'];
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

    <div class="max-w-2xl mx-auto bg-white p-10 rounded-[40px] shadow-2xl border border-gray-100 print-card">
        <div class="text-center border-b-2 border-dashed border-gray-200 pb-8 mb-8">
            <div class="bg-orange-600 w-20 h-20 rounded-3xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-orange-200">
                <i class="fa-solid fa-file-invoice-dollar text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-black text-gray-800 tracking-tighter italic uppercase">Bukti Pengajuan <span class="text-orange-600">Barang</span></h1>
            <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-2">Nomor Kontrol: <?php echo $data['ticket_number']; ?></p>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-8 bg-slate-50 p-6 rounded-3xl border border-slate-100">
            <div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Nama Pemohon</p>
                <p class="font-bold text-gray-800"><?php echo $data['full_name']; ?></p>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Jabatan / Dept</p>
                <p class="font-bold text-gray-800"><?php echo ($data['jabatan'] ?? 'Staff'); ?> - <?php echo $data['department']; ?></p>
            </div>
        </div>

        <div class="space-y-4 mb-10">
            <div class="flex justify-between items-start py-3 border-b border-gray-50">
                <span class="text-gray-500 text-sm font-medium">Item & Spek</span>
                <span class="font-bold text-gray-800 text-right max-w-[250px]"><?php echo $data['title']; ?></span>
            </div>
            
            <div class="flex justify-between items-center py-3 border-b border-gray-50">
                <span class="text-gray-500 text-sm font-medium">Urgensi Pengadaan</span>
                <span class="px-4 py-1 rounded-full text-[10px] font-black uppercase <?php echo $data['urgency'] == 'Penting' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600'; ?>">
                    <?php echo $data['urgency']; ?>
                </span>
            </div>
<div class="bg-blue-50/50 p-6 rounded-3xl space-y-4">
    <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest text-center">Estimasi Pembiayaan Akhir</p>
    
    <div class="space-y-2 border-b border-blue-100 pb-4">
        <pre class="text-[11px] text-gray-600 font-sans leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($data['description'] ?? ''); ?></pre>
    </div>

    <div class="flex justify-between items-center">
        <span class="text-gray-500 text-sm italic font-medium">Total Anggaran Diajukan</span>
       <span class="font-black text-blue-700 text-xl">
    Rp <?php 
        $total_estimasi = (float) ($data['estimasi'] ?? 0); 
        echo number_format($total_estimasi, 0, ',', '.'); 
    ?>
</span>
    </div>
    <p class="text-[9px] text-gray-400 text-center leading-tight italic">
        *Kalkulasi otomatis mencakup PPN 10% dan Market Elevation 5% sesuai SOP Dinas.
    </p>
</div>

        <div class="border-t border-gray-100 pt-8">
            <div class="flex items-center justify-between mb-8">
                <div class="text-center flex-1">
                    <p class="text-[9px] font-bold text-gray-400 uppercase mb-4">Scan Verifikasi</p>
                    <div class="w-20 h-20 bg-gray-100 mx-auto rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-qrcode text-gray-300 text-4xl"></i>
                    </div>
                </div>
                <div class="flex-1 text-center border-l border-gray-100">
                    <p class="text-[9px] font-bold text-gray-400 uppercase mb-1">Status Validasi</p>
                    <h3 class="text-xl font-black <?php echo $data['status'] == 'Selesai' ? 'text-green-600' : 'text-orange-600'; ?> uppercase italic">
                        <?php echo $data['status']; ?>
                    </h3>
                </div>
            </div>

            <div class="flex flex-col md:flex-row gap-4 no-print">
                <button onclick="window.print()" class="flex-1 bg-slate-900 text-white py-4 rounded-2xl font-black hover:bg-blue-600 transition active:scale-95 flex items-center justify-center gap-2 uppercase tracking-widest text-xs">
                    <i class="fa-solid fa-print"></i> Cetak Dokumen
                </button>
                <a href="dashboard_user.php" class="flex-1 bg-gray-100 text-gray-500 py-4 rounded-2xl font-black text-center hover:bg-gray-200 transition uppercase tracking-widest text-xs">
                    Dashboard
                </a>
            </div>
        </div>
    </div>

    <p class="text-center text-gray-400 text-[10px] mt-10 uppercase tracking-[0.2em] font-bold no-print">
        Generated by SIDIK-TI - Digital Procurement Module &copy; 2026
    </p>

</body>
</html>