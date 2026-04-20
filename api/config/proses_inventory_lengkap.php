<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/csrf_helper.php';

if (isset($_POST['simpan_inventory'])) {
    require_csrf_token();
    
    $id         = !empty($_POST['item_id']) ? $_POST['item_id'] : null;
    $name       = $_POST['item_name'];
    $cat        = $_POST['category'];
    $stock      = (int)$_POST['stock'];
    $satuan     = $_POST['satuan'];
    $min        = (int)$_POST['min_stock'];
    $price      = (float)$_POST['price'];
    $now        = date('Y-m-d H:i:s');

    $data = [
        'item_name' => $name, 'category' => $cat, 'stock' => $stock, 'satuan' => $satuan,
        'min_stock' => $min, 'price_reference' => $price, 'updated_at' => $now
    ];

    if ($db) {
        try {
            $inventoryRef = $db->collection('inventory');
            if (!empty($id) && !is_numeric($id)) {
                $inventoryRef->document($id)->set($data, ['merge' => true]);
            } else {
                $data['created_at'] = $now;
                $inventoryRef->add($data);
            }
            // SINKRONISASI KE MASTER TEMPLATE
            $templateQuery = $db->collection('procurement_templates')->where('product_name', '=', $name)->limit(1)->documents();
            if (!$templateQuery->isEmpty()) {
                foreach ($templateQuery as $doc) {
                    $doc->reference()->update([['path' => 'base_price', 'value' => $price], ['path' => 'category', 'value' => $cat]]);
                }
            } else {
                $db->collection('procurement_templates')->add([
                    'category' => $cat, 'product_name' => $name, 'specification' => "Restock dari Inventory: $name",
                    'base_price' => $price, 'created_at' => $now
                ]);
            }
        } catch (Exception $e) { $db = null; }
    }

    if (!$db && $conn) {
        $n_e = mysqli_real_escape_string($conn, $name);
        $c_e = mysqli_real_escape_string($conn, $cat);
        $s_e = mysqli_real_escape_string($conn, $satuan);
        
        if (!empty($id) && is_numeric($id)) {
            $sql = "UPDATE inventory SET item_name='$n_e', category='$c_e', stock=$stock, satuan='$s_e', min_stock=$min, price_reference=$price, updated_at='$now' WHERE id=".intval($id);
            mysqli_query($conn, $sql);
        } else {
            $sql = "INSERT INTO inventory (item_name, category, stock, satuan, min_stock, price_reference, created_at, updated_at) 
                    VALUES ('$n_e', '$c_e', $stock, '$s_e', $min, $price, '$now', '$now')";
            mysqli_query($conn, $sql);
        }
        // SINKRONISASI KE MASTER TEMPLATE
        $checkT = mysqli_query($conn, "SELECT id FROM procurement_templates WHERE product_name = '$n_e' LIMIT 1");
        if (mysqli_num_rows($checkT) > 0) {
            mysqli_query($conn, "UPDATE procurement_templates SET base_price=$price, category='$c_e' WHERE product_name='$n_e'");
        } else {
            mysqli_query($conn, "INSERT INTO procurement_templates (category, product_name, specification, base_price, created_at) 
                                 VALUES ('$c_e', '$n_e', 'Restock dari Inventory: $name', $price, '$now')");
        }
    }

    header("Location: ../admin/inventory.php?status=success");
    exit();
}
