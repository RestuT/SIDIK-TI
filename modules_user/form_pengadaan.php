<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_query = mysqli_query($conn, "SELECT full_name, jabatan, department FROM users WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($user_query);

// --- LOGIKA INTEGRASI INVENTORY ---
$pre_category = "";
$pre_item_name = "";

if (isset($_GET['from_inv'])) {
    $inv_id = mysqli_real_escape_string($conn, $_GET['from_inv']);
    $get_inv = mysqli_query($conn, "SELECT * FROM inventory WHERE id = '$inv_id'");
    $inv_data = mysqli_fetch_assoc($get_inv);
    
    if ($inv_data) {
        $pre_category = strtolower($inv_data['category']); 
        $pre_item_name = $inv_data['item_name'];
    }
}

$budget_query = mysqli_query($conn, "SELECT * FROM budget_config WHERE fiscal_year = 2026");
$budget_data = mysqli_fetch_assoc($budget_query);
$sisa_anggaran = ($budget_data['total_limit'] ?? 0) - ($budget_data['used_amount'] ?? 0);
// Ambil sisa budget khusus departemen user yang sedang login
$my_dept = $user_data['department'];
$check_budget = mysqli_query($conn, "SELECT total_limit - used_amount as sisa FROM budget_config WHERE department = '$my_dept' AND fiscal_year = 2026");
$budget_dept = mysqli_fetch_assoc($check_budget);
$sisa_dept = $budget_dept['sisa'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengadaan Barang IT - SIDIK-TI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-50 font-sans">

<?php include '../includes/navbar_user.php'; ?>

    <main class="max-w-6xl mx-auto px-4 py-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-wallet text-orange-500"></i> Sisa Anggaran 2026
                    </h3>
                    <p class="text-xl font-bold text-gray-800">Rp <?php echo number_format($sisa_anggaran, 0, ',', '.'); ?></p>
                    <p class="text-[10px] text-gray-400 mt-2 italic">*Berdasarkan DPA Dinas Terkini</p>
                </div>

                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-user-tie text-blue-500"></i> Profil Pemohon
                    </h3>
                    <div class="space-y-2 text-sm">
                        <p class="text-gray-500 italic">Nama: <span class="text-gray-800 font-bold"><?php echo $user_data['full_name']; ?></span></p>
                        <p class="text-gray-500 italic">Jabatan: <span class="text-gray-800 font-bold"><?php echo $user_data['jabatan']; ?></span></p>
                        <p class="text-gray-500 italic">Dept: <span class="text-gray-800 font-bold"><?php echo $user_data['department']; ?></span></p>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <i class="fa-solid fa-cart-plus text-orange-600"></i> 
                        <?php echo isset($_GET['from_inv']) ? "Restock Barang Inventory" : "Form Pengajuan Baru"; ?>
                    </h2>
                    
                    <form action="../config/proses_pengadaan.php" method="POST" enctype="multipart/form-data" class="space-y-5">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wider">Kategori Barang</label>
                                <select id="kategoriBarang" name="kategori" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none font-bold">
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="hardware">Hardware (Perangkat Keras)</option>
                                    <option value="software">Software & Lisensi</option>
                                    <option value="jaringan">Infrastruktur Jaringan</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wider">Tipe Produk (Template)</label>
                                <select id="productTemplate" disabled class="w-full p-3 bg-blue-50 border border-blue-200 rounded-xl outline-none font-bold text-blue-700">
                                    <option value="">-- Pilih Kategori Dahulu --</option>
                                    <?php
                                    $get_all_temp = mysqli_query($conn, "SELECT * FROM procurement_templates");
                                    while($row_t = mysqli_fetch_assoc($get_all_temp)):
                                    ?>
                                        <optgroup label="<?php echo ucfirst($row_t['category']); ?>" data-category="<?php echo $row_t['category']; ?>">
                                            <option value="<?php echo $row_t['id']; ?>" 
                                                    data-spec="<?php echo $row_t['specification']; ?>" 
                                                    data-price="<?php echo $row_t['base_price']; ?>">
                                                <?php echo $row_t['product_name']; ?>
                                            </option>
                                        </optgroup>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="bg-blue-50 p-5 rounded-2xl border border-blue-100">
                            <label class="block text-xs font-bold text-blue-500 uppercase mb-2">Deskripsi Produk & Rincian Biaya</label>
                            <textarea id="deskripsiOtomatis" name="deskripsi" rows="5" readonly required 
                                class="w-full bg-transparent border-none text-sm text-gray-700 font-medium focus:ring-0 resize-none" 
                                placeholder="Detail biaya akan muncul otomatis..."></textarea>
                            <input type="hidden" name="judul" id="judulHidden">
                            <input type="hidden" name="estimasi" id="estimasiHidden">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wider">Dokumen KAK/Nota</label>
                                <input type="file" name="lampiran" accept=".pdf, .jpg, .png" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wider">Urgensi</label>
                                <select name="urgensi" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none">
                                    <option value="Biasa">Biasa</option>
                                    <option value="Penting" <?php echo isset($_GET['from_inv']) ? 'selected' : ''; ?>>Penting (Restock)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wider">Justifikasi Kebutuhan</label>
                            <textarea name="justifikasi" rows="3" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none"><?php echo isset($_GET['from_inv']) ? "Restock otomatis untuk item: " . $pre_item_name . " karena sisa stok gudang menipis." : ""; ?></textarea>
                        </div>

                        <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-black py-4 rounded-xl shadow-lg transition transform active:scale-95 flex items-center justify-center gap-2 uppercase tracking-widest">
                            <i class="fa-solid fa-paper-plane"></i> Kirim Pengajuan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const kat = document.getElementById('kategoriBarang');
        const temp = document.getElementById('productTemplate');
        const desk = document.getElementById('deskripsiOtomatis');
        const judulHidden = document.getElementById('judulHidden');
        const estimasiHidden = document.getElementById('estimasiHidden');
        const masterGroups = Array.from(temp.getElementsByTagName('optgroup'));

        kat.addEventListener('change', function() {
            temp.innerHTML = '<option value="">-- Pilih Tipe Produk --</option>';
            desk.value = "";
            if (this.value !== "") {
                temp.disabled = false;
                masterGroups.forEach(group => {
                    if (group.getAttribute('data-category') === this.value) {
                        temp.appendChild(group.cloneNode(true));
                    }
                });
            } else {
                temp.disabled = true;
            }
        });

        temp.addEventListener('change', function() {
            const sel = this.options[this.selectedIndex];
            if (sel && sel.value !== "") {
                const base = parseFloat(sel.getAttribute('data-price'));
                const spec = sel.getAttribute('data-spec');
                const tax = base * 0.10;
                const elevation = base * 0.05;
                const total = base + tax + elevation;

                judulHidden.value = sel.text.trim();
                estimasiHidden.value = total;

                desk.value = `NAMA BARANG: ${sel.text.trim()}\n` +
                             `SPEK: ${spec}\n` +
                             `-------------------------------------------\n` +
                             `Harga Dasar: Rp ${base.toLocaleString('id-ID')}\n` +
                             `Pajak PPN (10%): Rp ${tax.toLocaleString('id-ID')}\n` +
                             `Elevasi Pasar (5%): Rp ${elevation.toLocaleString('id-ID')}\n` +
                             `TOTAL ESTIMASI: Rp ${total.toLocaleString('id-ID')}`;
            }
        });

        // --- AUTO-FILL DARI INVENTORY ---
        const preCat = "<?php echo $pre_category; ?>";
        const preItem = "<?php echo $pre_item_name; ?>";

        if (preCat !== "") {
            kat.value = preCat;
            kat.dispatchEvent(new Event('change'));

            setTimeout(() => {
                for (let i = 0; i < temp.options.length; i++) {
                    if (temp.options[i].text.toLowerCase().includes(preItem.toLowerCase())) {
                        temp.selectedIndex = i;
                        temp.dispatchEvent(new Event('change'));
                        break;
                    }
                }
            }, 150);
        }
    });
    </script>
</body>
</html>