<?php
ob_start();

require_once __DIR__ . '/../config/database.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_user.php");
    exit();
}

$service_labels = [];
$service_data = [];
$asset_labels = [];
$asset_data = [];
$trend_labels = [];
$trend_data = [];
$stat_total_assets = 0;
$stat_avg_completion = 0;
$stat_pending = 0;

if ($db) {
    try {
        // --- 1. DATA UNTUK PIE CHART (Maintenance vs Pengadaan) ---
        $submissions_docs = $db->collection('submissions')->documents();
        $service_counts = [];
        foreach ($submissions_docs as $doc) {
            $type = $doc->data()['type'] ?? 'Unknown';
            $service_counts[$type] = ($service_counts[$type] ?? 0) + 1;
        }
        $service_labels = array_keys($service_counts);
        $service_data = array_values($service_counts);

        // --- 2. DATA UNTUK BAR CHART (Kategori Aset) ---
        $assets_docs = $db->collection('asset_assignments')->documents();
        $category_counts = [];
        foreach ($assets_docs as $doc) {
            $cat = $doc->data()['category'] ?? 'Unknown';
            $category_counts[$cat] = ($category_counts[$cat] ?? 0) + 1;
        }
        $asset_labels = array_keys($category_counts);
        $asset_data = array_values($category_counts);

        // --- 3. DATA UNTUK LINE CHART (Tren 6 Bulan Terakhir) ---
        for ($i = 5; $i >= 0; $i--) {
            $month_label = date('M Y', strtotime("-$i months"));
            $month_start = date('Y-m-d 00:00:00', strtotime("first day of -$i months"));
            $month_end = date('Y-m-d 23:59:59', strtotime("last day of -$i months"));
            
            $count_trend = 0;
            foreach ($submissions_docs as $doc) {
                $created_at = $doc->data()['created_at'] ?? '';
                if ($created_at >= $month_start && $created_at <= $month_end) {
                    $count_trend++;
                }
            }
            $trend_labels[] = $month_label;
            $trend_data[] = $count_trend;
        }

        // --- 4. RINGKASAN STATISTIK ---
        $stat_total_assets = count($assets_docs->rows());
        foreach ($submissions_docs as $doc) {
            $status = $doc->data()['status'] ?? '';
            if ($status === 'Selesai') $stat_avg_completion++;
            if ($status === 'Menunggu') $stat_pending++;
        }
    } catch (Exception $e) {
        $db = null; // Fallback
    }
}

if (!$db && $conn) {
    // 1. Service Distribution (Pie)
    $res_service = mysqli_query($conn, "SELECT type, COUNT(*) as c FROM submissions GROUP BY type");
    if ($res_service) {
        while ($row = mysqli_fetch_assoc($res_service)) {
            $service_labels[] = $row['type'];
            $service_data[] = (int)$row['c'];
        }
    }

    // 2. Asset Composition (Bar)
    $res_asset = mysqli_query($conn, "SELECT category, COUNT(*) as c FROM asset_assignments GROUP BY category");
    if ($res_asset) {
        while ($row = mysqli_fetch_assoc($res_asset)) {
            $asset_labels[] = $row['category'];
            $asset_data[] = (int)$row['c'];
        }
    }

    // 3. Trend Line (6 Months)
    for ($i = 5; $i >= 0; $i--) {
        $month_label = date('M Y', strtotime("-$i months"));
        $month_start = date('Y-m-01 00:00:00', strtotime("-$i months"));
        $month_end = date('Y-m-t 23:59:59', strtotime("-$i months"));
        
        $sql_trend = "SELECT COUNT(*) as c FROM submissions WHERE created_at BETWEEN '$month_start' AND '$month_end'";
        $res_trend = mysqli_query($conn, $sql_trend);
        $row_trend = mysqli_fetch_assoc($res_trend);
        
        $trend_labels[] = $month_label;
        $trend_data[] = (int)($row_trend['c'] ?? 0);
    }

    // 4. Statistics
    $stat_total_assets = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM asset_assignments"))['c'] ?? 0;
    $stat_avg_completion = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM submissions WHERE status = 'Selesai'"))['c'] ?? 0;
    $stat_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM submissions WHERE status = 'Menunggu'"))['c'] ?? 0;
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <?php 
        $pageTitle = 'SIDIK-TI | Systems Analytics';
        $base_url = '../';
        include __DIR__ . '/../includes/head_meta.php'; 
    ?>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-surface-container-low font-body text-on-surface antialiased overflow-x-hidden min-h-screen">
    
    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>

    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen flex flex-col">
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 md:px-8 py-4 md:py-5 border-b border-outline-variant/10 sticky top-0 bg-surface/80 backdrop-blur-xl z-30">
            <div>
                <h1 class="font-headline text-xl md:text-2xl font-extrabold text-on-surface tracking-tight italic uppercase leading-none">System <span class="text-primary italic md:text-3xl">Analytics</span></h1>
                <p class="text-[10px] text-on-surface-variant font-black uppercase tracking-[0.2em] mt-1">Data Insights &amp; Performance Metrics</p>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="window.print()" class="px-4 py-2.5 bg-white border border-outline-variant/30 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">print</span> <span class="hidden sm:inline">Print Report</span>
                </button>
            </div>
        </header>

        <div class="p-4 md:p-8 space-y-8 md:space-y-10">
            <!-- Stats Row -->
            <section class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-primary p-8 rounded-[2.5rem] text-white shadow-2xl shadow-primary/20 relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 opacity-10 rotate-12 transition-transform group-hover:rotate-0 duration-700">
                        <span class="material-symbols-outlined text-[120px]">inventory_2</span>
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-widest opacity-60 mb-2">Total Aset</p>
                    <h3 class="text-5xl font-black italic tracking-tighter"><?php echo $stat_total_assets; ?></h3>
                </div>

                <div class="bg-white p-8 rounded-[2.5rem] border border-outline-variant/10 shadow-sm relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 opacity-5 rotate-12">
                        <span class="material-symbols-outlined text-[100px] text-emerald-600">task_alt</span>
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-2">Tiket Selesai</p>
                    <h3 class="text-5xl font-black italic tracking-tighter text-emerald-600"><?php echo $stat_avg_completion; ?></h3>
                </div>

                <div class="bg-white p-8 rounded-[2.5rem] border border-outline-variant/10 shadow-sm relative overflow-hidden group">
                     <div class="absolute -right-4 -top-4 opacity-5 rotate-12">
                        <span class="material-symbols-outlined text-[100px] text-orange-600">hourglass_empty</span>
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-2">Menunggu Proses</p>
                    <h3 class="text-5xl font-black italic tracking-tighter text-orange-600"><?php echo $stat_pending; ?></h3>
                </div>

                <div class="bg-indigo-50 p-8 rounded-[2.5rem] border border-indigo-100 flex flex-col justify-center">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-2 h-2 bg-indigo-600 rounded-full animate-ping"></span>
                        <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600">Update Terakhir</span>
                    </div>
                    <h4 class="font-headline font-bold text-lg text-indigo-900 leading-tight">Laporan Visual Periode <?php echo date('M Y'); ?></h4>
                </div>
            </section>

            <!-- Charts Grid -->
            <section class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white p-10 rounded-[3rem] border border-outline-variant/5 shadow-sm space-y-8">
                    <div>
                        <h4 class="font-headline text-xl font-black italic uppercase tracking-tighter">Volume <span class="text-primary italic">Request Trend</span></h4>
                        <p class="text-[10px] text-on-surface-variant font-black uppercase tracking-widest">Total pengajuan masuk per bulan</p>
                    </div>
                    <div class="h-64">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <div class="bg-white p-10 rounded-[3rem] border border-outline-variant/5 shadow-sm space-y-8">
                    <div>
                        <h4 class="font-headline text-xl font-black italic uppercase tracking-tighter">Service <span class="text-primary italic">Distribution</span></h4>
                        <p class="text-[10px] text-on-surface-variant font-black uppercase tracking-widest">Perbandingan Maintenance vs Pengadaan</p>
                    </div>
                    <div class="h-64 flex justify-center">
                        <canvas id="serviceChart"></canvas>
                    </div>
                </div>

                <div class="lg:col-span-2 bg-white p-10 rounded-[3rem] border border-outline-variant/5 shadow-sm space-y-8">
                    <div>
                        <h4 class="font-headline text-xl font-black italic uppercase tracking-tighter">Asset <span class="text-primary italic">Composition</span></h4>
                        <p class="text-[10px] text-on-surface-variant font-black uppercase tracking-widest">Jumlah perangkat berdasarkan kategori utama</p>
                    </div>
                    <div class="h-72">
                        <canvas id="assetChart"></canvas>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script>
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.font.size = 11;
        Chart.defaults.color = '#464555';

        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($trend_labels); ?>,
                datasets: [{
                    label: 'Jumlah Pengajuan',
                    data: <?php echo json_encode($trend_data); ?>,
                    borderColor: '#3525cd',
                    backgroundColor: 'rgba(53, 37, 205, 0.1)',
                    borderWidth: 4,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#3525cd',
                    pointBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                    x: { grid: { display: false } }
                }
            }
        });

        new Chart(document.getElementById('serviceChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($service_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($service_data); ?>,
                    backgroundColor: ['#4f46e5', '#f59e0b', '#10b981', '#3b82f6'],
                    borderWidth: 0,
                    hoverOffset: 20
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, font: { weight: 'bold' } } }
                }
            }
        });

        new Chart(document.getElementById('assetChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($asset_labels); ?>,
                datasets: [{
                    label: 'Jumlah Unit',
                    data: <?php echo json_encode($asset_data); ?>,
                    backgroundColor: '#3525cd',
                    borderRadius: 16,
                    maxBarThickness: 50
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>
