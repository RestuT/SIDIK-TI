<?php
ob_start();
require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../config/csrf_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php"); 
    exit();
}

$user_id = $_SESSION['user_id'];
$user_data = [];

if ($db) {
    try {
        $userSnap = $db->collection('users')->document($user_id)->snapshot();
        $user_data = $userSnap->exists() ? $userSnap->data() : [];
    } catch (Exception $e) { $db = null; }
}

if (!$db && $conn) {
    $uid_e = mysqli_real_escape_string($conn, $user_id);
    $res_user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$uid_e' LIMIT 1");
    if ($row = mysqli_fetch_assoc($res_user)) $user_data = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <?php 
        $pageTitle = 'SIDIK-TI | Form Maintenance';
        $base_url = '../';
        include __DIR__ . '/../includes/head_meta.php'; 
    ?>
</head>
<body class="bg-surface dark:bg-slate-950 font-body text-on-surface dark:text-slate-100 antialiased overflow-x-hidden min-h-screen pb-24 md:pb-0 transition-colors duration-300">
    <?php include __DIR__ . '/../includes/navbar_user.php'; ?>
    <main class="max-w-[1240px] mx-auto px-6 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            <div class="lg:col-span-5 space-y-8">
                <div>
                    <h2 class="font-headline text-4xl font-extrabold text-on-surface dark:text-white tracking-tight leading-tight uppercase italic underline decoration-primary/30 dark:decoration-indigo-500/30 underline-offset-8">Maintenance <span class="text-primary dark:text-indigo-400 italic">Request</span></h2>
                    <p class="text-on-surface-variant dark:text-slate-400 font-medium mt-6 leading-relaxed">Laporkan setiap kendala perangkat TI Anda secara mendetail untuk mempermudah tim teknis melakukan diagnosis awal.</p>
                </div>
                <div class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-outline-variant/5 dark:border-slate-800 shadow-2xl shadow-indigo-900/5 dark:shadow-none space-y-6 relative overflow-hidden group hover:shadow-primary/5 transition-all">
                    <div class="absolute top-0 right-0 p-8 opacity-5 dark:opacity-10 text-primary dark:text-indigo-400"><span class="material-symbols-outlined text-[120px]">help</span></div>
                    <h3 class="font-headline font-bold text-on-surface dark:text-white flex items-center gap-2"><span class="material-symbols-outlined text-primary dark:text-indigo-400">tips_and_updates</span>Panduan Pelaporan</h3>
                    <div class="space-y-4">
                        <div class="flex gap-4 p-4 rounded-2xl hover:bg-surface-container dark:hover:bg-slate-800 transition-colors"><span class="w-8 h-8 rounded-full bg-primary-fixed dark:bg-indigo-500/20 flex items-center justify-center text-primary dark:text-indigo-400 font-black text-xs shrink-0">1</span><div><p class="text-sm font-bold text-on-surface dark:text-slate-200">Pilih Kategori Perangkat</p></div></div>
                        <div class="flex gap-4 p-4 rounded-2xl hover:bg-surface-container dark:hover:bg-slate-800 transition-colors"><span class="w-8 h-8 rounded-full bg-primary-fixed dark:bg-indigo-500/20 flex items-center justify-center text-primary dark:text-indigo-400 font-black text-xs shrink-0">2</span><div><p class="text-sm font-bold text-on-surface dark:text-slate-200">Detail Gejala</p></div></div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-7">
                <form action="<?php echo htmlspecialchars(dirname($_SERVER['PHP_SELF']) . '/../config/proses_maintenance.php'); ?>" method="POST" enctype="multipart/form-data" class="bg-white dark:bg-slate-900 p-10 rounded-[3rem] border border-outline-variant/5 dark:border-slate-800 shadow-2xl shadow-indigo-900/5 dark:shadow-none space-y-8 relative overflow-hidden">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <div class="absolute top-0 left-0 w-2 h-full bg-gradient-to-b from-primary to-primary-container"></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-outline dark:text-slate-500 uppercase tracking-[0.2em] ml-2">Identitas Pemohon</label>
                            <input class="block w-full px-6 py-4 bg-surface-container-low dark:bg-slate-800 border-0 rounded-2xl font-bold text-on-surface-variant dark:text-slate-400 italic cursor-not-allowed text-sm" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" disabled type="text"/>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-outline dark:text-slate-500 uppercase tracking-[0.2em] ml-2">Divisi / Dept</label>
                            <input class="block w-full px-6 py-4 bg-surface-container-low dark:bg-slate-800 border-0 rounded-2xl font-bold text-on-surface-variant dark:text-slate-400 italic cursor-not-allowed text-sm" value="<?php echo htmlspecialchars($user_data['department'] ?? ''); ?>" disabled type="text"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-outline dark:text-slate-500 uppercase tracking-[0.2em] ml-2">Kategori Kerusakan</label>
                            <select name="layanan" required class="block w-full px-6 py-4 bg-surface-container-low dark:bg-slate-800 border-0 rounded-2xl font-bold text-on-surface dark:text-white outline-none focus:ring-2 focus:ring-primary/20 dark:focus:ring-indigo-500/30 appearance-none transition-all text-sm">
                                <option value="">-- Pilih Jenis --</option><option value="Laptop/PC">Laptop / Komputer</option><option value="Printer/Scanner">Printer / Scanner</option><option value="Jaringan/WiFi">Perangkat Jaringan (WiFi/Switch)</option><option value="Server/Aplikasi">Server / Software Aplikasi</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-outline dark:text-slate-500 uppercase tracking-[0.2em] ml-2">Identitas Barang (Merk/Tipe)</label>
                            <input name="judul" required placeholder="Contoh: Dell Latitude 5420" class="block w-full px-6 py-4 bg-surface-container-low dark:bg-slate-800 border-0 rounded-2xl font-bold text-on-surface dark:text-white outline-none focus:ring-2 focus:ring-primary/20 dark:focus:ring-indigo-500/30 transition-all text-sm placeholder:opacity-40" type="text"/>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-outline dark:text-slate-500 uppercase tracking-[0.2em] ml-2">Deskripsi Kendala</label>
                        <textarea name="deskripsi" required rows="4" placeholder="Jelaskan secara detail, misal: Blue screen saat membuka Chrome..." class="block w-full px-6 py-4 bg-surface-container-low dark:bg-slate-800 border-0 rounded-2xl font-bold text-on-surface dark:text-white outline-none focus:ring-2 focus:ring-primary/20 dark:focus:ring-indigo-500/30 transition-all text-sm min-h-[140px] placeholder:opacity-40"></textarea>
                    </div>
                    <div class="space-y-4 p-6 border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-3xl bg-surface-container-low/50 dark:bg-slate-800/50 hover:bg-surface-container-low dark:hover:bg-slate-800 transition-colors">
                        <label class="block text-[10px] font-black text-outline dark:text-slate-500 uppercase tracking-[0.2em] ml-2">Lampiran Foto (Wajib)</label>
                        <input type="file" name="lampiran" id="file-upload" accept=".jpg, .jpeg, .png, .pdf" required class="block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 dark:file:bg-indigo-500/20 file:text-primary dark:file:text-indigo-400 hover:file:bg-primary/20 dark:hover:file:bg-indigo-500/30 cursor-pointer">
                    </div>
                    <button type="submit" class="w-full bg-gradient-to-br from-primary to-primary-container text-white font-headline font-black py-5 rounded-2xl shadow-xl shadow-primary/20 hover:shadow-primary/40 hover:-translate-y-1 transition-all active:scale-[0.98] uppercase tracking-[0.2em] text-xs flex items-center justify-center gap-3">Kirim Laporan Maintenance</button>
                </form>
            </div>
        </div>
    </main>

    <!-- Client-Side Image Compression Script for Vercel/Firestore Optimizations -->
    <script>
        const fileInput = document.getElementById('file-upload');
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                // Hanya kompres jika format gambar
                if (file.type.match(/image.*/)) {
                    const MAX_SIZE_KB = 350; // Target aman di bawah 500KB untuk Firestore 1MB Limit
                    if (file.size / 1024 > MAX_SIZE_KB) {
                        const reader = new FileReader();
                        reader.readAsDataURL(file);
                        reader.onload = function(event) {
                            const img = new Image();
                            img.src = event.target.result;
                            img.onload = function() {
                                const canvas = document.createElement('canvas');
                                const ctx = canvas.getContext('2d');
                                
                                const MAX_WIDTH = 1200;
                                const MAX_HEIGHT = 1200;
                                let width = img.width;
                                let height = img.height;

                                if (width > height && width > MAX_WIDTH) {
                                    height *= MAX_WIDTH / width;
                                    width = MAX_WIDTH;
                                } else if (height > MAX_HEIGHT) {
                                    width *= MAX_HEIGHT / height;
                                    height = MAX_HEIGHT;
                                }

                                canvas.width = width;
                                canvas.height = height;
                                ctx.drawImage(img, 0, 0, width, height);

                                let quality = 0.7;
                                let dataUrl = canvas.toDataURL('image/jpeg', quality);
                                
                                let iter = 0;
                                while(dataUrl.length > 500000 && iter < 3) {
                                    quality -= 0.15;
                                    dataUrl = canvas.toDataURL('image/jpeg', quality);
                                    iter++;
                                }

                                fetch(dataUrl)
                                    .then(res => res.blob())
                                    .then(blob => {
                                        const compressedFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", {
                                            type: 'image/jpeg',
                                            lastModified: Date.now()
                                        });

                                        const dataTransfer = new DataTransfer();
                                        dataTransfer.items.add(compressedFile);
                                        fileInput.files = dataTransfer.files;
                                        
                                        // Update UI name jika ada helper JS (opsional)
                                        console.log("SIDIK-TI: File berhasil dikompres ke " + (compressedFile.size/1024).toFixed(0) + "KB untuk menghindari Limit Payload Vercel.");
                                    });
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
