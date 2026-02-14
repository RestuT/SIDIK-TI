<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasar Maintenance - IT Helpdesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-50 font-sans">

    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="index.php" class="flex items-center text-blue-600 font-bold text-xl hover:text-blue-800 transition">
                <i class="fa-solid fa-arrow-left mr-2 text-sm"></i> Kembali ke Dashboard
            </a>
            <span class="text-gray-400 font-medium">Panduan Maintenance IT</span>
        </div>
    </nav>

    <header class="bg-white border-b py-12">
        <div class="max-w-5xl mx-auto px-4 text-center">
            <h1 class="text-4xl font-extrabold text-gray-900 mb-4">Panduan Dasar Pemeliharaan Alat TI</h1>
            <p class="text-gray-600 text-lg">Pelajari cara merawat perangkat kerja Anda agar tetap awet dan menunjang produktivitas.</p>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
                <div class="p-6 bg-blue-600 text-white flex items-center gap-4">
                    <i class="fa-solid fa-wifi text-3xl"></i>
                    <h2 class="text-2xl font-bold">Router WiFi</h2>
                </div>
                <div class="p-8">
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <span class="bg-blue-100 text-blue-600 rounded-full h-6 w-6 flex items-center justify-center text-xs font-bold mt-1 mr-3 shrink-0">1</span>
                            <p class="text-gray-700"><strong>Penempatan:</strong> Letakkan di area terbuka, hindari menumpuknya dengan perangkat panas lainnya.</p>
                        </li>
                        <li class="flex items-start">
                            <span class="bg-blue-100 text-blue-600 rounded-full h-6 w-6 flex items-center justify-center text-xs font-bold mt-1 mr-3 shrink-0">2</span>
                            <p class="text-gray-700"><strong>Reboot Berkala:</strong> Matikan selama 30 detik seminggu sekali untuk menyegarkan cache memori.</p>
                        </li>
                        <li class="flex items-start">
                            <span class="bg-blue-100 text-blue-600 rounded-full h-6 w-6 flex items-center justify-center text-xs font-bold mt-1 mr-3 shrink-0">3</span>
                            <p class="text-gray-700"><strong>Update Firmware:</strong> Cek pembaruan melalui dashboard admin router secara rutin.</p>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
                <div class="p-6 bg-emerald-600 text-white flex items-center gap-4">
                    <i class="fa-solid fa-print text-3xl"></i>
                    <h2 class="text-2xl font-bold">Printer</h2>
                </div>
                <div class="p-8">
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <span class="bg-emerald-100 text-emerald-600 rounded-full h-6 w-6 flex items-center justify-center text-xs font-bold mt-1 mr-3 shrink-0">1</span>
                            <p class="text-gray-700"><strong>Pemanasan:</strong> Nyalakan printer minimal 1-2 kali seminggu agar tinta tidak mengering di head.</p>
                        </li>
                        <li class="flex items-start">
                            <span class="bg-emerald-100 text-emerald-600 rounded-full h-6 w-6 flex items-center justify-center text-xs font-bold mt-1 mr-3 shrink-0">2</span>
                            <p class="text-gray-700"><strong>Kebersihan:</strong> Pastikan tidak ada debu masuk ke dalam tray kertas untuk mencegah <em>paper jam</em>.</p>
                        </li>
                        <li class="flex items-start">
                            <span class="bg-emerald-100 text-emerald-600 rounded-full h-6 w-6 flex items-center justify-center text-xs font-bold mt-1 mr-3 shrink-0">3</span>
                            <p class="text-gray-700"><strong>Tinta Original:</strong> Selalu gunakan tinta resmi untuk menjaga umur print head.</p>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
                <div class="p-6 bg-slate-800 text-white flex items-center gap-4">
                    <i class="fa-solid fa-computer text-3xl"></i>
                    <h2 class="text-2xl font-bold">Komputer / PC</h2>
                </div>
                <div class="p-8">
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <span class="bg-slate-200 text-slate-800 rounded-full h-6 w-6 flex items-center justify-center text-xs font-bold mt-1 mr-3 shrink-0">1</span>
                            <p class="text-gray-700"><strong>Bersihkan Debu:</strong> Gunakan udara bertekanan (compressed air) untuk membersihkan kipas PSU dan CPU.</p>
                        </li>
                        <li class="flex items-start">
                            <span class="bg-slate-200 text-slate-800 rounded-full h-6 w-6 flex items-center justify-center text-xs font-bold mt-1 mr-3 shrink-0">2</span>
                            <p class="text-gray-700"><strong>Manajemen Kabel:</strong> Pastikan kabel tidak menutupi lubang ventilasi udara panas.</p>
                        </li>
                        <li class="flex items-start">
                            <span class="bg-slate-200 text-slate-800 rounded-full h-6 w-6 flex items-center justify-center text-xs font-bold mt-1 mr-3 shrink-0">3</span>
                            <p class="text-gray-700"><strong>Software:</strong> Lakukan Disk Cleanup dan Defragment secara rutin jika menggunakan HDD.</p>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
                <div class="p-6 bg-indigo-600 text-white flex items-center gap-4">
                    <i class="fa-solid fa-laptop text-3xl"></i>
                    <h2 class="text-2xl font-bold">Laptop</h2>
                </div>
                <div class="p-8">
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <span class="bg-indigo-100 text-indigo-600 rounded-full h-6 w-6 flex items-center justify-center text-xs font-bold mt-1 mr-3 shrink-0">1</span>
                            <p class="text-gray-700"><strong>Battery Health:</strong> Hindari membiarkan baterai 0% atau terus di-charge 100% dalam waktu yang sangat lama.</p>
                        </li>
                        <li class="flex items-start">
                            <span class="bg-indigo-100 text-indigo-600 rounded-full h-6 w-6 flex items-center justify-center text-xs font-bold mt-1 mr-3 shrink-0">2</span>
                            <p class="text-gray-700"><strong>Engsel:</strong> Buka laptop dari bagian tengah frame, bukan dari pinggir untuk mencegah engsel patah.</p>
                        </li>
                        <li class="flex items-start">
                            <span class="bg-indigo-100 text-indigo-600 rounded-full h-6 w-6 flex items-center justify-center text-xs font-bold mt-1 mr-3 shrink-0">3</span>
                            <p class="text-gray-700"><strong>Suhu:</strong> Jangan gunakan laptop di atas permukaan empuk (kasur/bantal) yang menutup ventilasi.</p>
                        </li>
                    </ul>
                </div>
            </div>

        </div>

        <div class="mt-12 bg-amber-50 border-l-4 border-amber-400 p-6 rounded-r-xl">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fa-solid fa-lightbulb text-amber-500 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-bold text-amber-800">Tips Profesional:</h3>
                    <p class="text-amber-700">Lakukan inventarisasi nomor seri perangkat Anda. Jika terjadi masalah berat, segera lakukan pengajuan perbaikan atau pengadaan baru melalui menu yang tersedia di dashboard.</p>
                </div>
            </div>
        </div>
    </main>

    <footer class="text-center py-10 text-gray-400 border-t">
        IT Helpdesk Support &copy; 2026
    </footer>

</body>
</html>