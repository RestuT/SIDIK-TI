<?php
session_start();
include '../config/database.php';

// Proses Simpan Template Baru
if (isset($_POST['save_template'])) {
    $cat = $_POST['category'];
    $name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $spec = mysqli_real_escape_string($conn, $_POST['specification']);
    $price = $_POST['base_price'];

    mysqli_query($conn, "INSERT INTO procurement_templates (category, product_name, specification, base_price) VALUES ('$cat', '$name', '$spec', '$price')");
}

$templates = mysqli_query($conn, "SELECT * FROM procurement_templates ORDER BY category ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Inventory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head><body class="bg-slate-100 flex">

    <?php include '../includes/navbar_admin.php'; ?>

<main class="flex-1 p-10 bg-slate-100 min-h-screen">
    <div class="max-w-5xl mx-auto">
        <h2 class="text-3xl font-black text-gray-800 mb-8 uppercase italic">Master <span class="text-blue-600">Template Produk</span></h2>
        
        <div class="bg-white p-8 rounded-[40px] shadow-sm mb-10 border border-blue-100">
            <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Kategori</label>
                    <select name="category" class="w-full p-3 bg-slate-50 border rounded-2xl outline-none">
                        <option value="hardware">Hardware</option>
                        <option value="software">Software</option>
                        <option value="jaringan">Jaringan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Nama Produk</label>
                    <input type="text" name="product_name" required class="w-full p-3 bg-slate-50 border rounded-2xl outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Harga Dasar (Tanpa Pajak)</label>
                    <input type="number" name="base_price" required class="w-full p-3 bg-slate-50 border rounded-2xl outline-none">
                </div>
                <button type="submit" name="save_template" class="bg-blue-600 text-white p-4 rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-blue-700">Simpan Template</button>
                <div class="md:col-span-4">
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Spesifikasi Lengkap</label>
                    <textarea name="specification" rows="2" class="w-full p-3 bg-slate-50 border rounded-2xl outline-none"></textarea>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-[40px] shadow-sm overflow-hidden border border-gray-100">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b">
                    <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="px-8 py-5">Produk & Spek</th>
                        <th class="px-8 py-5">Harga Dasar</th>
                        <th class="px-8 py-5 text-blue-600">+PPN (10%)</th>
                        <th class="px-8 py-5 text-orange-600">+Elevasi (5%)</th>
                        <th class="px-8 py-5 font-black text-gray-800">Final (User)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php while($t = mysqli_fetch_assoc($templates)): 
                        $ppn = $t['base_price'] * 0.10;
                        $elev = $t['base_price'] * 0.05;
                        $final = $t['base_price'] + $ppn + $elev;
                    ?>
                    <tr class="text-sm">
                        <td class="px-8 py-5">
                            <span class="font-bold block"><?php echo $t['product_name']; ?></span>
                            <span class="text-[10px] text-gray-400"><?php echo $t['specification']; ?></span>
                        </td>
                        <td class="px-8 py-5">Rp <?php echo number_format($t['base_price'], 0, ',', '.'); ?></td>
                        <td class="px-8 py-5 text-blue-500 italic">+<?php echo number_format($ppn, 0, ',', '.'); ?></td>
                        <td class="px-8 py-5 text-orange-500 italic">+<?php echo number_format($elev, 0, ',', '.'); ?></td>
                        <td class="px-8 py-5 font-black text-gray-800">Rp <?php echo number_format($final, 0, ',', '.'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>