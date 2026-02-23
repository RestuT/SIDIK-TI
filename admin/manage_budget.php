<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_admin.php");
    exit();
}

// Proses Tambah/Update Anggaran Departemen
if (isset($_POST['save_budget'])) {
    $year = $_POST['fiscal_year'];
    $dept = mysqli_real_escape_string($conn, $_POST['department']);
    $limit = $_POST['total_limit'];
    
    $query = "INSERT INTO budget_config (fiscal_year, department, total_limit, used_amount) 
              VALUES ('$year', '$dept', '$limit', 0)
              ON DUPLICATE KEY UPDATE total_limit = '$limit'";
    
    mysqli_query($conn, $query);
}

// Ambil data budget per departemen
$budgets = mysqli_query($conn, "SELECT * FROM budget_config WHERE fiscal_year = 2026 ORDER BY department ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Budget Per Departemen - SIDIK-TI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-100 flex font-sans">
    <?php include '../includes/navbar_admin.php'; ?>

    <main class="flex-1 p-10">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-black text-gray-800 uppercase italic">Alokasi <span class="text-blue-600">Budget Dept</span></h2>
            <div class="bg-white px-6 py-2 rounded-2xl shadow-sm border font-bold text-blue-600">Tahun 2026</div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="bg-white p-8 rounded-[40px] shadow-sm border border-gray-100 h-fit">
                <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-6">Input Pagu Departemen</h4>
                <form action="" method="POST" class="space-y-4">
                    <input type="hidden" name="fiscal_year" value="2026">
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase mb-2">Pilih Departemen</label>
                        <select name="department" required class="w-full p-3 bg-slate-50 border border-gray-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 font-bold">
                            <option value="IT">IT / Kominfo</option>
                            <option value="Keuangan">Bagian Keuangan</option>
                            <option value="Umum">Bagian Umum</option>
                            <option value="Kepegawaian">BKPSDM</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase mb-2">Total Pagu (Rp)</label>
                        <input type="number" name="total_limit" required class="w-full p-3 bg-slate-50 border border-gray-100 rounded-2xl outline-none font-bold">
                    </div>
                    <button type="submit" name="save_budget" class="w-full bg-blue-600 text-white font-black py-4 rounded-2xl uppercase text-[10px] tracking-widest hover:bg-blue-700 transition">
                        Simpan Alokasi
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 space-y-4">
                <?php while($b = mysqli_fetch_assoc($budgets)): 
                    $sisa = $b['total_limit'] - $b['used_amount'];
                    $persen = ($b['total_limit'] > 0) ? ($b['used_amount'] / $b['total_limit']) * 100 : 0;
                ?>
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center gap-6">
                    <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 text-xl font-black italic">
                        <?php echo substr($b['department'], 0, 2); ?>
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between mb-2">
                            <h4 class="font-black text-gray-800 uppercase italic text-sm"><?php echo $b['department']; ?></h4>
                            <span class="text-xs font-bold text-gray-400">Sisa: Rp <?php echo number_format($sisa, 0, ',', '.'); ?></span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-blue-600 h-full" style="width: <?php echo $persen; ?>%"></div>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] font-black text-gray-400 uppercase">Total Pagu</p>
                        <p class="text-sm font-bold text-gray-800">Rp <?php echo number_format($b['total_limit'], 0, ',', '.'); ?></p>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>
</body>
</html>