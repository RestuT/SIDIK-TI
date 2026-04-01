<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_user.php");
    exit();
}

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
    $trend_data = [];
    $trend_labels = [];
    for ($i = 5; $i >= 0; $i--) {
        $month_label = date('M Y', strtotime("-$i months"));
        $month_start = date('Y-m-d 00:00:00', strtotime("first day of -$i months"));
        $month_end = date('Y-m-d 23:59:59', strtotime("last day of -$i months"));
        
        // Firestore query for count in range
        // Note: created_at is stored as string in some places, or timestamp. 
        // Based on previous migrations, it's string.
        $count = 0;
        foreach ($submissions_docs as $doc) {
            $created_at = $doc->data()['created_at'] ?? '';
            if ($created_at >= $month_start && $created_at <= $month_end) {
                $count++;
            }
        }
        
        $trend_labels[] = $month_label;
        $trend_data[] = $count;
    }

    // --- 4. RINGKASAN STATISTIK ---
    $stat_total_assets = count($assets_docs->rows());
    
    $stat_avg_completion = 0;
    $stat_pending = 0;
    foreach ($submissions_docs as $doc) {
        $status = $doc->data()['status'] ?? '';
        if ($status === 'Selesai') $stat_avg_completion++;
        if ($status === 'Menunggu') $stat_pending++;
    }

} catch (Exception $e) {
    // Handle Error
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Systems Analytics</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#3525cd",
                        "primary-container": "#4f46e5",
                        "on-surface": "#191c1e",
                        "on-surface-variant": "#464555",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-low": "#f2f4f6",
                        "surface-container-high": "#e6e8ea",
                        "outline-variant": "#c7c4d8",
                    },
                    fontFamily: {
                        "headline": ["Plus Jakarta Sans"],
                        "body": ["Inter"],
                    },
                    borderRadius: {"DEFAULT": "1rem", "lg": "2rem", "xl": "3rem", "full": "9999px"},
                },
            },
        }
    </script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        .fill-1 { font-variation-settings: 'FILL' 1; }
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body class="bg-surface-container-low font-body text-on-surface antialiased flex min-h-screen">
    
    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col min-w-0">
        <!-- Header Bar -->
        <header class="flex items-center justify-between px-8 py-5 border-b border-outline-variant/10 sticky top-0 bg-white/80 backdrop-blur-xl z-20">
            <div>
                <h1 class="font-headline text-2xl font-extrabold text-on-surface tracking-tight italic uppercase leading-none">System <span class="text-primary italic text-3xl">Analytics</span></h1>
                <p class="text-[10px] text-on-surface-variant font-black uppercase tracking-[0.2em] mt-1">Data Insights & Performance Metrics</p>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="window.print()" class="px-5 py-2.5 bg-white border border-outline-variant/30 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">print</span> Print Report
                </button>
            </div>
        </header>

        <div class="p-8 space-y-10">
            <!-- Stats Row -->
            <section class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Card 1 -->
                <div class="bg-primary p-8 rounded-[2.5rem] text-white shadow-2xl shadow-primary/20 relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 opacity-10 rotate-12 transition-transform group-hover:rotate-0 duration-700">
                        <span class="material-symbols-outlined text-[120px]">inventory_2</span>
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-widest opacity-60 mb-2">Total Aset</p>
                    <h3 class="text-5xl font-black italic tracking-tighter"><?php echo $stat_total_assets; ?></h3>
                </div>

                <!-- Card 2 -->
                <div class="bg-white p-8 rounded-[2.5rem] border border-outline-variant/10 shadow-sm relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 opacity-5 rotate-12">
                        <span class="material-symbols-outlined text-[100px] text-emerald-600">task_alt</span>
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-2">Tiket Selesai</p>
                    <h3 class="text-5xl font-black italic tracking-tighter text-emerald-600"><?php echo $stat_avg_completion; ?></h3>
                </div>

                <!-- Card 3 -->
                <div class="bg-white p-8 rounded-[2.5rem] border border-outline-variant/10 shadow-sm relative overflow-hidden group">
                     <div class="absolute -right-4 -top-4 opacity-5 rotate-12">
                        <span class="material-symbols-outlined text-[100px] text-orange-600">hourglass_empty</span>
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-2">Menunggu Proses</p>
                    <h3 class="text-5xl font-black italic tracking-tighter text-orange-600"><?php echo $stat_pending; ?></h3>
                </div>

                <!-- Card 4: Date Info -->
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
                <!-- Trend Chart -->
                <div class="bg-white p-10 rounded-[3rem] border border-outline-variant/5 shadow-sm space-y-8">
                    <div>
                        <h4 class="font-headline text-xl font-black italic uppercase tracking-tighter">Volume <span class="text-primary italic">Request Trend</span></h4>
                        <p class="text-[10px] text-on-surface-variant font-black uppercase tracking-widest">Total pengajuan masuk per bulan</p>
                    </div>
                    <div class="h-64">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <!-- Distribution Chart -->
                <div class="bg-white p-10 rounded-[3rem] border border-outline-variant/5 shadow-sm space-y-8">
                    <div>
                        <h4 class="font-headline text-xl font-black italic uppercase tracking-tighter">Service <span class="text-primary italic">Distribution</span></h4>
                        <p class="text-[10px] text-on-surface-variant font-black uppercase tracking-widest">Perbandingan Maintenance vs Pengadaan</p>
                    </div>
                    <div class="h-64 flex justify-center">
                        <canvas id="serviceChart"></canvas>
                    </div>
                </div>

                <!-- Assets Bar Chart -->
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
        // Chart Defaults
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.font.size = 11;
        Chart.defaults.color = '#464555';

        // 1. Trend Line Chart
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

        // 2. Service Pie Chart
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

        // 3. Asset Bar Chart
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
