<?php
session_start();
include 'database.php';

if (isset($_POST['simpan_inventory'])) {
    $id         = $_POST['item_id'];
    $name       = mysqli_real_escape_string($conn, $_POST['item_name']);
    $cat        = $_POST['category'];
    $stock      = $_POST['stock'];
    $satuan     = $_POST['satuan'];
    $min        = $_POST['min_stock'];
    $price      = $_POST['price'];

    if (!empty($id)) {
        // Mode Update
        $query = "UPDATE inventory SET 
                  item_name = '$name', category = '$cat', stock = '$stock', 
                  satuan = '$satuan', min_stock = '$min', price_reference = '$price' 
                  WHERE id = '$id'";
    } else {
        // Mode Tambah Baru
        $query = "INSERT INTO inventory (item_name, category, stock, satuan, min_stock, price_reference) 
                  VALUES ('$name', '$cat', '$stock', '$satuan', '$min', '$price')";
    }

    if (mysqli_query($conn, $query)) {
        // LOGIKA SINKRONISASI KE MASTER TEMPLATE
        // Cek apakah item sudah ada di template pengadaan
        $check_temp = mysqli_query($conn, "SELECT id FROM procurement_templates WHERE product_name = '$name'");
        
        if (mysqli_num_rows($check_temp) > 0) {
            // Update harga dasar di template jika nama barang sama
            mysqli_query($conn, "UPDATE procurement_templates SET base_price = '$price', category = '$cat' WHERE product_name = '$name'");
        } else {
            // Jika belum ada di master template, buat otomatis agar user bisa memilihnya di form pengadaan
            mysqli_query($conn, "INSERT INTO procurement_templates (category, product_name, specification, base_price) 
                                 VALUES ('$cat', '$name', 'Restock dari Inventory: $name', '$price')");
        }

        header("Location: ../admin/inventory.php?status=success");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>