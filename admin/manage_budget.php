<?php
session_start();
include '../config/database.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_user.php");
    exit();
}

// Proses Update Anggaran
if (isset($_POST['update_budget'])) {
    $year = $_POST['fiscal_year'];
    $new_limit = $_POST['total_limit'];
    
    $update = mysqli_query($conn, "UPDATE budget_config SET total_limit = '$new_limit' WHERE fiscal_year = '$year'");
    if ($update) { $msg = "Budget berhasil diperbarui!"; }
}

// Ambil Data Anggaran Terbaru
$budget_query = mysqli_query($conn, "SELECT * FROM budget_config ORDER BY fiscal_year DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Anggaran - SIDIK-TI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-100 flex font-sans">
    <?php include '../includes/navbar_admin.php'; ?>

    <main class="flex-1 p-10">
        <h2 class="text-3xl font-black text-gray-800 uppercase italic mb-8">Manajemen <span class="text-emerald-600">Budget</span></h2>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <?php while($b = mysqli_fetch_assoc($budget_query)): 
                $sisa = $b['total_limit'] - $b['used_amount'];
                $persen_pakai = ($b['used_amount'] / $b['total_limit']) * 100;
            ?>
            <div class="bg-white p-8 rounded-[40px] shadow-sm border border-gray-100 lg:col-span-2">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Tahun Fiskal</p>
                        <h3 class="text-4xl font-black text-gray-800"><?php echo $b['fiscal_year']; ?></h3>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Sisa Dana</p>
                        <h3 class="text-2xl font-black text-emerald-600">Rp <?php echo number_format($sisa, 0, ',', '.'); ?></h3>
                    </div>
                </div>

                <div class="space-y-2 mb-8">
                    <div class="flex justify-between text-xs font-bold uppercase italic">
                        <span class="text-gray-400">Serapan Anggaran</span>
                        <span class="text-blue-600"><?php echo number_format($persen_pakai, 1); ?>%</span>
                    </div>
                    <div class="w-full bg-gray-100 h-4 rounded-full overflow-hidden">
                        <div class="bg-blue-600 h-full transition-all duration-1000" style="width: <?php echo $persen_pakai; ?>%"></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-slate-50 p-4 rounded-2xl">
                        <p class="text-[10px] font-black text-gray-400 uppercase">Pagu Total</p>
                        <p class="font-bold">Rp <?php echo number_format($b['total_limit'], 0, ',', '.'); ?></p>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-2xl">
                        <p class="text-[10px] font-black text-gray-400 uppercase">Terpakai</p>
                        <p class="font-bold text-blue-600">Rp <?php echo number_format($b['used_amount'], 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-8 rounded-[40px] shadow-sm border border-gray-100">
                <h4 class="text-sm font-black text-gray-800 uppercase italic mb-6">Update Pagu</h4>
                <form action="" method="POST" class="space-y-4">
                    <input type="hidden" name="fiscal_year" value="<?php echo $b['fiscal_year']; ?>">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Total Anggaran Baru</label>
                        <input type="number" name="total_limit" value="<?php echo $b['total_limit']; ?>" 
                               class="w-full p-4 bg-slate-50 border border-gray-100 rounded-2xl font-bold outline-none focus:ring-4 focus:ring-emerald-100 transition">
                    </div>
                    <button type="submit" name="update_budget" class="w-full bg-slate-900 text-white font-black py-4 rounded-2xl uppercase text-[10px] tracking-widest hover:bg-emerald-600 transition shadow-lg">
                        Simpan Perubahan
                    </button>
                </form>
            </div>
            <?php endwhile; ?>
        </div>
    </main>
</body>
</html>