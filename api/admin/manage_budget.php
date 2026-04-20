<?php
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_admin.php");
    exit();
}

// Proses Tambah/Update Anggaran Departemen (UPSERT)
if (isset($_POST['save_budget'])) {
    require_csrf_token();
    
    $year = (int)$_POST['fiscal_year'];
    $dept = $_POST['department'];
    $limit = (float)$_POST['total_limit'];
    
    if ($db) {
        try {
            $budgetRef = $db->collection('budget_config')
                ->where('fiscal_year', '=', $year)
                ->where('department', '=', $dept)
                ->limit(1)
                ->documents();
                
            if ($budgetRef->isEmpty()) {
                $db->collection('budget_config')->add([
                    'fiscal_year' => $year, 'department' => $dept, 'total_limit' => $limit, 'used_amount' => 0
                ]);
            } else {
                foreach ($budgetRef as $doc) {
                    $doc->reference()->update([['path' => 'total_limit', 'value' => $limit]]);
                }
            }
        } catch (Exception $e) { $db = null; }
    }
    
    if (!$db && $conn) {
        $dept_esc = mysqli_real_escape_string($conn, $dept);
        $check = mysqli_query($conn, "SELECT id FROM budget_config WHERE fiscal_year = $year AND department = '$dept_esc'");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "UPDATE budget_config SET total_limit = $limit WHERE fiscal_year = $year AND department = '$dept_esc'");
        } else {
            mysqli_query($conn, "INSERT INTO budget_config (fiscal_year, department, total_limit, used_amount) VALUES ($year, '$dept_esc', $limit, 0)");
        }
    }
}

// Ambil data budget per departemen secara dinamis
$current_year = (int)date('Y');
$budgets_data = [];
$depts_data = [];

if ($db) {
    try {
        $budgets = $db->collection('budget_config')->where('fiscal_year', '=', $current_year)->documents();
        foreach ($budgets as $doc) {
            $data = $doc->data(); $data['id'] = $doc->id(); $budgets_data[] = $data;
        }
        $q_dept = $db->collection('departments')->orderBy('nama_dept', 'ASC')->documents();
        foreach ($q_dept as $doc) { $depts_data[] = $doc->data(); }
    } catch (Exception $e) { $db = null; }
}

if (!$db && $conn) {
    $res_b = mysqli_query($conn, "SELECT * FROM budget_config WHERE fiscal_year = $current_year");
    if ($res_b) { while ($row = mysqli_fetch_assoc($res_b)) { $budgets_data[] = $row; } }
    $res_d = mysqli_query($conn, "SELECT * FROM departments ORDER BY nama_dept ASC");
    if ($res_d) { while ($row = mysqli_fetch_assoc($res_d)) { $depts_data[] = $row; } }
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <?php 
        $pageTitle = 'SIDIK-TI | Financial Allocation Control';
        $base_url = '../';
        include __DIR__ . '/../includes/head_meta.php'; 
    ?>
</head>
<body class="bg-surface font-body text-on-surface antialiased overflow-x-hidden min-h-screen">
    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>
    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen flex flex-col">
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 md:px-8 py-4 md:py-5 border-b border-outline-variant/10 sticky top-0 bg-surface/80 backdrop-blur-xl z-20">
            <div>
                <h1 class="font-headline text-xl md:text-2xl font-extrabold text-on-surface tracking-tight leading-none italic uppercase">Fiscal <span class="text-primary italic">Control</span></h1>
                <p class="text-[10px] text-outline font-black uppercase tracking-widest mt-1">Manajemen Alokasi &amp; Pagu Anggaran Departemen</p>
            </div>
            <div class="px-4 py-2.5 bg-white border border-outline-variant/20 rounded-2xl shadow-sm flex items-center gap-3">
                <span class="material-symbols-outlined text-primary text-xl">calendar_today</span>
                <span class="text-xs font-black text-on-surface uppercase tracking-widest">Fiscal <?php echo $current_year; ?></span>
            </div>
        </header>

        <div class="p-4 md:p-8 space-y-6 md:space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                <div class="lg:col-span-4 h-fit">
                    <section class="bg-white p-8 rounded-[2.5rem] border border-outline-variant/10 shadow-2xl relative overflow-hidden group">
                        <h2 class="flex items-center gap-3 font-headline text-lg font-black text-on-surface uppercase tracking-tight mb-8">
                            <span class="material-symbols-outlined text-primary fill-1">payments</span> Set Alokasi Pagu
                        </h2>
                        <form action="" method="POST" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="fiscal_year" value="<?php echo $current_year; ?>">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2">Departemen</label>
                                <select name="department" required class="block w-full px-5 py-4 bg-surface-container-low border-0 rounded-2xl font-bold text-on-surface">
                                    <option value="">-- Pilih Unit --</option>
                                    <?php foreach ($depts_data as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept['nama_dept']); ?>"><?php echo htmlspecialchars($dept['nama_dept']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2">Total Limit (Rp)</label>
                                <input type="number" name="total_limit" required class="block w-full px-5 py-4 bg-surface-container-low border-0 rounded-2xl font-black text-primary">
                            </div>
                            <button type="submit" name="save_budget" class="w-full py-5 bg-primary text-white font-headline font-black rounded-2xl shadow-xl uppercase tracking-widest text-xs">Simpan</button>
                        </form>
                    </section>
                </div>

                <div class="lg:col-span-8 space-y-6">
                    <section class="grid grid-cols-1 gap-4">
                        <?php if(!empty($budgets_data)): foreach($budgets_data as $b): 
                            $sisa = $b['total_limit'] - $b['used_amount'];
                            $persen = ($b['total_limit'] > 0) ? ($b['used_amount'] / $b['total_limit']) * 100 : 0;
                            $bar_color = ($persen > 80) ? (($persen > 95) ? "bg-error" : "bg-orange-500") : "bg-primary";
                        ?>
                        <div class="bg-white p-6 rounded-[2rem] border border-outline-variant/10 shadow-sm hover:shadow-xl transition-all group flex items-center gap-6">
                            <div class="w-16 h-16 rounded-2xl bg-surface-container-high text-primary font-headline font-black text-base flex items-center justify-center uppercase italic shrink-0"><?php echo substr($b['department'], 0, 2); ?></div>
                            <div class="flex-1 space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-headline font-extrabold text-on-surface uppercase italic tracking-tight"><?php echo $b['department']; ?></h4>
                                    <div class="text-right">
                                        <span class="block text-[9px] font-black text-outline uppercase tracking-widest">Sisa</span>
                                        <span class="block text-sm font-black text-on-surface">Rp <?php echo number_format($sisa, 0, ',', '.'); ?></span>
                                    </div>
                                </div>
                                <div class="w-full bg-surface-container h-2 rounded-full overflow-hidden">
                                    <div class="<?php echo $bar_color; ?> h-full rounded-full transition-all duration-1000" style="width: <?php echo $persen; ?>%"></div>
                                </div>
                            </div>
                            <div class="text-right pl-6 border-l border-outline-variant/10 shrink-0">
                                <p class="text-[9px] font-black text-outline uppercase tracking-widest">Pagu Total</p>
                                <p class="text-base font-headline font-black text-primary">Rp <?php echo number_format($b['total_limit'], 0, ',', '.'); ?></p>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                        <div class="py-20 text-center bg-white rounded-[2rem] border border-outline-variant/10 border-dashed">
                            <p class="font-headline font-black text-outline uppercase tracking-[0.2em] text-xs">No Fiscal Data for <?php echo $current_year; ?></p>
                        </div>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
