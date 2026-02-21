<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard IT Helpdesk - Pemeliharaan Sistem</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-50 font-sans">

<?php include 'includes/navbar.php'; ?>

    <main class="pt-24 pb-12">
        <div class="max-w-screen-xl mx-auto px-4">
            
            <section class="bg-gradient-to-r from-[#14797b] to-[#0f5a5c] rounded-3xl p-8 md:p-16 text-white shadow-xl mb-12">
                <div class="md:w-2/3">
                    <h1 class="text-4xl md:text-5xl font-extrabold mb-4 leading-tight">Sistem Pemeliharaan Adalah Investasi, Bukan Beban.</h1>
                    <p class="text-lg opacity-90 mb-8 leading-relaxed">
                        Selamat datang di SIDIK-TI. Sebelum melanjutkan pengajuan publikasi atau barang, mari pastikan aset Anda dalam kondisi optimal melalui siklus pemeliharaan yang tepat.
                    </p>
                    <div class="flex gap-4">
                        <a href="#modul" class="bg-white text-blue-700 px-6 py-3 rounded-full font-bold hover:bg-gray-100 transition">Mulai Pengajuan</a>
                        <a href="materi_maintenance.php" class="border border-white/50 px-6 py-3 rounded-full font-bold hover:bg-white/10 transition">Pelajari Maintenance</a>
                    </div>
                </div>
            </section>

            <section id="materi" class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-lg flex items-center justify-center mb-6">
                        <i class="fa-solid fa-shield text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Preventive Maintenance</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        Tindakan pencegahan berkala pada hardware dan software untuk meminimalisir risiko kerusakan mendadak pada sistem publikasi Anda.
                    </p>
                </div>

                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="w-12 h-12 bg-red-100 text-red-600 rounded-lg flex items-center justify-center mb-6">
                        <i class="fa-solid fa-screwdriver-wrench text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Corrective Maintenance</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        Perbaikan yang dilakukan setelah ditemukan kesalahan (error) pada data administrasi atau kegagalan fungsi pada publikasi yang sedang berjalan.
                    </p>
                </div>

                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center mb-6">
                        <i class="fa-solid fa-chart-line text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Predictive Maintenance</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        Analisis data audit dari pengadaan barang sebelumnya untuk memprediksi kapan aset IT kantor Anda perlu diperbarui atau diganti.
                    </p>
                </div>
            </section>

        <section id="modul" class="border-t border-gray-200 pt-16">
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">Pilih Jenis Layanan</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 max-w-4xl mx-auto">
        
        <div class="group relative bg-white p-1 rounded-3xl bg-gradient-to-b from-green-400 to-emerald-500 shadow-lg transition hover:scale-105">
    <div class="bg-white rounded-[22px] p-8 h-full flex flex-col justify-between">
        <div>
            <h4 class="text-2xl font-bold text-gray-800 mb-2">Maintenance Perangkat TI</h4>
            <p class="text-gray-500 mb-6 text-sm">Ajukan pemeliharaan rutin atau perbaikan perangkat keras dan lunak Anda secara terjadwal.</p>
        </div>
        <a href="auth/login_user.php" class="inline-flex items-center text-emerald-600 font-bold group-hover:underline text-lg">
            Ajukan Maintenance <i class="fa-solid fa-screwdriver-wrench ml-2"></i>
        </a>
    </div>
</div>

        <div class="group relative bg-white p-1 rounded-3xl bg-gradient-to-b from-orange-400 to-red-500 shadow-lg transition hover:scale-105">
            <div class="bg-white rounded-[22px] p-8 h-full flex flex-col justify-between">
                <div>
                    <h4 class="text-2xl font-bold text-gray-800 mb-2">Pengadaan Barang IT</h4>
                    <p class="text-gray-500 mb-6 text-sm">Ajukan kebutuhan hardware atau software baru dengan fitur budget tracking dan inventory check otomatis.</p>
                </div>

                <a href="auth/login_user.php" class="inline-flex items-center text-orange-600 font-bold group-hover:underline text-lg">
                    Validasi Diri <i class="fa-solid fa-lock ml-2"></i>
                </a>
            </div>
        </div>

    </div>
</section>
        </div>
    </main>

<?php include 'includes/footer.php'; ?>

</body>
</html>