<?php
session_start();
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

    $inventoryRef = $db->collection('inventory');
    $data = [
        'item_name' => $name,
        'category' => $cat,
        'stock' => $stock,
        'satuan' => $satuan,
        'min_stock' => $min,
        'price_reference' => $price,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if (!empty($id)) {
        // Mode Update
        $inventoryRef->document($id)->set($data, ['merge' => true]);
    } else {
        // Mode Tambah Baru
        $data['created_at'] = date('Y-m-d H:i:s');
        $inventoryRef->add($data);
    }

    // LOGIKA SINKRONISASI KE MASTER TEMPLATE
    $templateQuery = $db->collection('procurement_templates')
        ->where('product_name', '=', $name)
        ->limit(1)
        ->documents();
    
    if (!$templateQuery->isEmpty()) {
        foreach ($templateQuery as $doc) {
            $doc->reference()->update([
                ['path' => 'base_price', 'value' => $price],
                ['path' => 'category', 'value' => $cat]
            ]);
        }
    } else {
        $spec = "Restock dari Inventory: $name";
        $db->collection('procurement_templates')->add([
            'category' => $cat,
            'product_name' => $name,
            'specification' => $spec,
            'base_price' => $price,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    header("Location: ../admin/inventory.php?status=success");
    exit();
}
?>