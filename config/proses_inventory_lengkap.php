<?php
session_start();
include 'database.php';
include 'csrf_helper.php';

if (isset($_POST['simpan_inventory'])) {
    require_csrf_token();
    
    $id         = !empty($_POST['item_id']) ? (int)$_POST['item_id'] : null;
    $name       = $_POST['item_name'];
    $cat        = $_POST['category'];
    $stock      = (int)$_POST['stock'];
    $satuan     = $_POST['satuan'];
    $min        = (int)$_POST['min_stock'];
    $price      = (float)$_POST['price'];

    if (!empty($id)) {
        // Mode Update
        $stmt = mysqli_prepare($conn, "UPDATE inventory SET item_name = ?, category = ?, stock = ?, satuan = ?, min_stock = ?, price_reference = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssisidi", $name, $cat, $stock, $satuan, $min, $price, $id);
    } else {
        // Mode Tambah Baru
        $stmt = mysqli_prepare($conn, "INSERT INTO inventory (item_name, category, stock, satuan, min_stock, price_reference) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssisid", $name, $cat, $stock, $satuan, $min, $price);
    }

    if (mysqli_stmt_execute($stmt)) {
        // LOGIKA SINKRONISASI KE MASTER TEMPLATE
        $stmt_check = mysqli_prepare($conn, "SELECT id FROM procurement_templates WHERE product_name = ?");
        mysqli_stmt_bind_param($stmt_check, "s", $name);
        mysqli_stmt_execute($stmt_check);
        $check_temp = mysqli_stmt_get_result($stmt_check);
        
        if (mysqli_num_rows($check_temp) > 0) {
            $stmt_update = mysqli_prepare($conn, "UPDATE procurement_templates SET base_price = ?, category = ? WHERE product_name = ?");
            mysqli_stmt_bind_param($stmt_update, "dss", $price, $cat, $name);
            mysqli_stmt_execute($stmt_update);
        } else {
            $spec = "Restock dari Inventory: $name";
            $stmt_insert = mysqli_prepare($conn, "INSERT INTO procurement_templates (category, product_name, specification, base_price) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_insert, "sssd", $cat, $name, $spec, $price);
            mysqli_stmt_execute($stmt_insert);
        }

        header("Location: ../admin/inventory.php?status=success");
        exit();
    } else {
        die("Error 500: Terjadi kesalahan sistem saat menyimpan stok, silakan lapor pada Admin.");
    }
}
?>