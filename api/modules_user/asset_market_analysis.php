<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Asset Market Analysis | SIDIK-TI Intelligence</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700&amp;display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
          tailwind.config = {
            darkMode: "class",
            theme: {
              extend: {
                colors: {
                  "primary": "#3525cd",
                  "primary-container": "#4f46e5",
                  "secondary": "#0051d5",
                  "surface": "#f7f9fb",
                  "on-surface": "#191c1e",
                  "on-surface-variant": "#464555",
                  "surface-container-low": "#f2f4f6",
                  "surface-container-lowest": "#ffffff",
                  "outline-variant": "#c7c4d8",
                  "amber-600": "#d97706",
                  "rose-600": "#e11d48",
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
    <style>
            .material-symbols-outlined {
                font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            }
            body { font-family: 'Inter', sans-serif; min-height: 100dvh; }
            h1, h2, h3, .font-headline { font-family: 'Plus Jakarta Sans', sans-serif; }
            .animate-float { animation: float 3s ease-in-out infinite; }
            @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        </style>
</head>
<body class="bg-surface text-on-surface selection:bg-primary/20 pb-24 md:pb-0">
    <!-- TopAppBar -->
    <header class="fixed top-0 w-full flex justify-between items-center px-6 h-16 bg-white/80 backdrop-blur-xl z-50 shadow-sm border-b border-indigo-50">
        <div class="flex items-center gap-4">
            <a href="assets_user.php" class="material-symbols-outlined text-slate-500 p-2 hover:bg-slate-50 transition-all rounded-full">arrow_back</a>
            <span class="text-xl font-extrabold bg-gradient-to-br from-indigo-600 to-indigo-400 bg-clip-text text-transparent">SIDIK-TI</span>
        </div>
        <div class="hidden md:flex items-center space-x-8">
            <span class="text-slate-400 font-bold font-plus-jakarta text-xs uppercase tracking-widest italic">Asset Market Insight</span>
        </div>
        <div class="flex items-center">
            <span class="text-indigo-600 font-black text-[10px] uppercase tracking-widest bg-indigo-50 px-3 py-1 rounded-full">Pricing Module</span>
        </div>
    </header>

    <main class="pt-32 pb-32 px-4 md:px-10 max-w-7xl mx-auto space-y-24">
        <!-- Hero Section -->
        <div class="max-w-4xl space-y-6">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-indigo-50 text-indigo-700 font-semibold text-xs uppercase tracking-[0.2em]">
                <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                Market Intelligence
            </div>
            <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight text-on-surface leading-tight">
                Analisis Depresiasi & <span class="bg-gradient-to-r from-primary to-indigo-400 bg-clip-text text-transparent italic tracking-tighter">Valuasi Aset TI</span>
            </h1>
            <p class="text-on-surface-variant text-lg md:text-xl font-medium max-w-2xl leading-relaxed">
                Memahami bagaimana nilai infrastruktur digital Anda berubah seiring waktu untuk perencanaan anggaran yang lebih strategis.
            </p>
        </div>

        <!-- Interactive Calculator -->
        <div class="bg-primary p-1 rounded-[3rem] shadow-2xl">
            <div class="bg-primary rounded-[2.8rem] p-8 md:p-12 text-white grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div class="space-y-6">
                    <h3 class="text-3xl font-bold italic tracking-tight">Depreciation Calculator</h3>
                    <p class="opacity-70 text-sm leading-relaxed">Masukkan perkiraan harga beli awal untuk melihat proyeksi nilai aset Anda setelah satu tahun pemakaian.</p>
                    
                    <div class="space-y-4">
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold opacity-60">Rp</span>
                            <input type="number" id="inputPrice" value="10000000" oninput="calculateDepreciation()" 
                                class="w-full pl-12 pr-6 py-4 bg-white/10 border-2 border-white/20 rounded-2xl outline-none focus:border-white focus:bg-white/20 transition-all font-black text-2xl">
                        </div>
                        <select id="inputType" onchange="calculateDepreciation()" 
                            class="w-full px-6 py-4 bg-white/10 border-2 border-white/20 rounded-2xl outline-none focus:border-white appearance-none font-bold">
                            <option value="laptop" class="text-on-surface">Laptop (Avg. 22.5% Drop)</option>
                            <option value="printer" class="text-on-surface">Printer (Avg. 15% Drop)</option>
                            <option value="router" class="text-on-surface">Network/Router (Avg. 10% Drop)</option>
                        </select>
                    </div>
                </div>

                <div class="bg-white/10 backdrop-blur-md rounded-3xl p-8 border border-white/10 flex flex-col items-center justify-center space-y-2 relative overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/5 rounded-full animate-float"></div>
                    <p class="text-[10px] font-black uppercase tracking-widest opacity-60">Estimasi Nilai Tahun Depan</p>
                    <h4 id="resultValue" class="text-4xl md:text-5xl font-black italic tracking-tighter">Rp 7.750.000</h4>
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-rose-500/20 text-rose-200 rounded-full text-[10px] font-black mt-4">
                        <span class="material-symbols-outlined text-sm">trending_down</span>
                        <span id="resultDrop">TERDEPRESIASI 22.5%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Segments -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Laptop -->
            <div class="group p-8 rounded-[2.5rem] bg-white border border-outline-variant/10 shadow-sm hover:shadow-2xl transition-all duration-500">
                <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-primary flex items-center justify-center mb-8 group-hover:rotate-12 transition-transform">
                    <span class="material-symbols-outlined text-3xl">laptop_mac</span>
                </div>
                <h3 class="text-2xl font-bold mb-4">Laptop</h3>
                <div class="text-rose-600 font-black text-sm mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined">chart_data</span>
                    Penurunan 15% – 30%
                </div>
                <p class="text-on-surface-variant text-sm leading-relaxed mb-6 italic">
                    Perangkat yang paling cepat terdepresiasi karena siklus pembaruan CPU/GPU tahunan yang sangat ketat.
                </p>
                <div class="space-y-4">
                    <div class="p-4 bg-slate-50 rounded-2xl">
                        <p class="text-[10px] font-black text-outline uppercase mb-1">Kondisi Baru</p>
                        <p class="text-xs font-medium">Turun ~15-20% saat model baru rilis (cuci gudang).</p>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-2xl">
                        <p class="text-[10px] font-black text-outline uppercase mb-1">Nilai Bekas</p>
                        <p class="text-xs font-medium">Harganya bisa anjlok hingga 40% tergantung kesehatan baterai.</p>
                    </div>
                </div>
            </div>

            <!-- Printer -->
            <div class="group p-8 rounded-[2.5rem] bg-white border border-outline-variant/10 shadow-sm hover:shadow-2xl transition-all duration-500">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-8 group-hover:rotate-12 transition-transform">
                    <span class="material-symbols-outlined text-3xl">print</span>
                </div>
                <h3 class="text-2xl font-bold mb-4">Printer</h3>
                <div class="text-rose-600 font-black text-sm mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined">chart_data</span>
                    Penurunan 10% – 20%
                </div>
                <p class="text-on-surface-variant text-sm leading-relaxed mb-6 italic">
                    Siklus hidup produk yang lebih lambat. Produsen lebih fokus mengambil untung dari penjualan tinta/consumables.
                </p>
                <div class="space-y-4">
                    <div class="p-4 bg-slate-50 rounded-2xl">
                        <p class="text-[10px] font-black text-outline uppercase mb-1">Harga Unit</p>
                        <p class="text-xs font-medium">Sangat stabil di pasar unit baru. Hanya turun ~10% setahun.</p>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-2xl">
                        <p class="text-[10px] font-black text-outline uppercase mb-1">Risiko Bekas</p>
                        <p class="text-xs font-medium">Kekhawatiran pada kondisi *print head* membuat harga unit bekas turun tajam.</p>
                    </div>
                </div>
            </div>

            <!-- Network -->
            <div class="group p-8 rounded-[2.5rem] bg-white border border-outline-variant/10 shadow-sm hover:shadow-2xl transition-all duration-500">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-8 group-hover:rotate-12 transition-transform">
                    <span class="material-symbols-outlined text-3xl">wifi_tethering</span>
                </div>
                <h3 class="text-2xl font-bold mb-4">Network/Router</h3>
                <div class="text-rose-600 font-black text-sm mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined">chart_data</span>
                    Penurunan 5% – 15%
                </div>
                <p class="text-on-surface-variant text-sm leading-relaxed mb-6 italic">
                    Perangkat "pasang dan lupakan". Teknologinya bertahan lebih lama selama standar WiFi (WiFi 6/7) masih relevan.
                </p>
                <div class="space-y-4">
                    <div class="p-4 bg-slate-50 rounded-2xl">
                        <p class="text-[10px] font-black text-outline uppercase mb-1">Faktor Stabilitas</p>
                        <p class="text-xs font-medium">Harga tidak banyak berubah kecuali ada standar WiFi baru yang rilis massal.</p>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-2xl">
                        <p class="text-[10px] font-black text-outline uppercase mb-1">Teknologi Lama</p>
                        <p class="text-xs font-medium">Stok lama biasanya didiskon besar saat transisi standar teknologi.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Factors & Tips -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <div class="space-y-8">
                <div class="flex items-center gap-4">
                    <div class="w-1 bg-primary h-8 rounded-full"></div>
                    <h3 class="text-2xl font-bold italic tracking-tight">Faktor Utama yang Mempengaruhi Harga</h3>
                </div>
                <div class="space-y-6">
                    <div class="flex gap-6">
                        <div class="shrink-0 w-12 h-12 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined">calendar_month</span>
                        </div>
                        <div>
                            <p class="font-bold text-on-surface">Siklus Rilis Produk</p>
                            <p class="text-sm text-on-surface-variant leading-relaxed">Harga turun drastis 1-2 bulan sebelum model penerusnya diluncurkan.</p>
                        </div>
                    </div>
                    <div class="flex gap-6">
                        <div class="shrink-0 w-12 h-12 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center justify-center text-primary">
                             <span class="material-symbols-outlined">currency_exchange</span>
                        </div>
                        <div>
                            <p class="font-bold text-on-surface">Kurs Mata Uang</p>
                            <p class="text-sm text-on-surface-variant leading-relaxed">Penguatan/pelemahan Rupiah terhadap USD sangat berpengaruh karena sebagian besar aset TI adalah barang impor.</p>
                        </div>
                    </div>
                    <div class="flex gap-6">
                        <div class="shrink-0 w-12 h-12 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center justify-center text-primary">
                             <span class="material-symbols-outlined">stars</span>
                        </div>
                        <div>
                            <p class="font-bold text-on-surface">Ekosistem Merek</p>
                            <p class="text-sm text-on-surface-variant leading-relaxed">Merek dengan ekosistem kuat (seperti Apple) cenderung memiliki nilai jual kembali yang jauh lebih stabil.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-indigo-950 rounded-[3rem] p-10 text-white flex flex-col justify-center space-y-6 relative overflow-hidden">
                <div class="absolute -bottom-10 -right-10 w-64 h-64 bg-primary/20 rounded-full blur-3xl"></div>
                <span class="material-symbols-outlined text-5xl text-primary">verified</span>
                <h3 class="text-3xl font-bold italic">The "Sweet Spot" Strategy</h3>
                <p class="text-indigo-200/80 leading-relaxed font-medium">
                    Membeli perangkat baru di usia **9-12 bulan setelah rilis** adalah pilihan paling cerdas. 
                    Harganya sudah turun cukup jauh (~20-25%), namun teknologinya masih sangat mumpuni untuk digunakan hingga 3-5 tahun ke depan.
                </p>
                <div class="pt-4">
                    <a href="index.php" class="inline-flex items-center gap-3 px-8 py-4 bg-primary text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:shadow-xl hover:shadow-primary/30 transition-all">
                        Kembali ke Dashboard
                        <span class="material-symbols-outlined text-lg">home</span>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer class="py-12 text-center text-[10px] font-black uppercase tracking-[0.4em] text-slate-400 border-t border-indigo-50">
        © 2026 Sidik-TI Market Intelligence • Economic Valuation Dept.
    </footer>

    <script>
        function calculateDepreciation() {
            const price = parseFloat(document.getElementById('inputPrice').value) || 0;
            const type = document.getElementById('inputType').value;
            let dropPercent = 22.5;

            if (type === 'laptop') dropPercent = 22.5;
            else if (type === 'printer') dropPercent = 15;
            else if (type === 'router') dropPercent = 10;

            const remainingValue = price * (1 - (dropPercent / 100));
            
            document.getElementById('resultValue').innerText = "Rp " + Math.round(remainingValue).toLocaleString('id-ID');
            document.getElementById('resultDrop').innerText = "TERDEPRESIASI " + dropPercent + "%";
        }
    </script>
</body>
</html>
