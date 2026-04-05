<?php
ob_start();
/**
 * SIDIK-TI Dummy Data Seeder (Firestore Version)
 * Use this script to populate your Cloud Firestore database with realistic testing data.
 * Usage: Run once via browser or CLI.
 */

require_once __DIR__ . '/config/database.php';

echo "<h1>SIDIK-TI Seeder (Firestore)</h1>";
echo "<p>Starting data population to Cloud Firestore...</p>";

// --- 1. SEED DEPARTMENTS ---
$depts = ['IT Department', 'Human Resource', 'Finance & Accounting', 'Marketing & Sales', 'General Affairs', 'Sekretariat', 'Bidang IKP', 'Bidang Aptika', 'Bidang Statistik'];
foreach ($depts as $dept) {
    try {
        $check = $db->collection('departments')->where('nama_dept', '=', $dept)->limit(1)->documents();
        if ($check->isEmpty()) {
            $db->collection('departments')->add(['nama_dept' => $dept, 'created_at' => date('Y-m-d H:i:s')]);
        }
    } catch (Exception $e) {}
}
echo "<p>✅ Departments seeded.</p>";

// --- 2. SEED BUDGET CONFIG (Fiscal Year 2026) ---
$fiscal_year = 2026;
$budget_data = [
    ['IT Department', 500000000],
    ['Human Resource', 150000000],
    ['Finance & Accounting', 100000000],
    ['Marketing & Sales', 250000000],
    ['General Affairs', 200000000],
    ['Sekretariat', 300000000],
    ['Bidang Aptika', 450000000]
];
foreach ($budget_data as $b) {
    try {
        $check = $db->collection('budget_config')
            ->where('fiscal_year', '=', (int)$fiscal_year)
            ->where('department', '=', $b[0])
            ->limit(1)
            ->documents();
            
        if ($check->isEmpty()) {
            $db->collection('budget_config')->add([
                'fiscal_year' => $fiscal_year,
                'department' => $b[0],
                'total_limit' => (float)$b[1],
                'used_amount' => 0
            ]);
        } else {
            foreach ($check as $doc) {
                $doc->reference()->update([
                    ['path' => 'total_limit', 'value' => (float)$b[1]]
                ]);
            }
        }
    } catch (Exception $e) {}
}
echo "<p>✅ Budget Config 2026 seeded.</p>";

// --- 3. SEED PROCUREMENT TEMPLATES ---
$templates = [
    ['Hardware', 'Dell Latitude 5420', 'Intel Core i5-1135G7, 16GB RAM, 512GB SSD, 14" FHD', 14500000],
    ['Hardware', 'MacBook Air M2', 'Apple M2 Chip, 8GB RAM, 256GB SSD, 13.6" Liquid Retina', 17500000],
    ['Hardware', 'Lenovo ThinkPad X1 Carbon', 'Intel Core i7-1260P, 32GB RAM, 1TB SSD, 14" 4K', 28500000],
    ['Hardware', 'Printer Epson L3210 EcoTank', 'Print, Scan, Copy, Heat-Free Technology', 2450000],
    ['Hardware', 'HP LaserJet Pro M404dn', 'Mono, Double-Sided Printing, Network Ready', 5800000],
    ['Hardware', 'Cisco ISR 4331 Router', 'Modular Router, 100Mbps-300Mbps System Throughput', 35000000],
    ['Software', 'Microsoft Office 2021', 'Lifetime License for 1 PC', 4500000],
    ['Software', 'Adobe Creative Cloud', 'Annual Subscription - All Apps', 12000000]
];
foreach ($templates as $t) {
    try {
        $check = $db->collection('procurement_templates')
            ->where('product_name', '=', $t[1])
            ->limit(1)
            ->documents();
            
        if ($check->isEmpty()) {
            $db->collection('procurement_templates')->add([
                'category' => $t[0],
                'product_name' => $t[1],
                'specification' => $t[2],
                'base_price' => (float)$t[3],
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    } catch (Exception $e) {}
}
echo "<p>✅ Procurement Templates seeded.</p>";

// --- 4. SEED INVENTORY ---
$inventory = [
    ['Dell Latitude 5420', 'Hardware', 15, 5, 'Unit', 14500000],
    ['MacBook Air M2', 'Hardware', 8, 2, 'Unit', 17500000],
    ['Printer Epson L3210 EcoTank', 'Hardware', 10, 3, 'Unit', 2450000]
];
foreach ($inventory as $i) {
    try {
        $check = $db->collection('inventory')->where('item_name', '=', $i[0])->limit(1)->documents();
        if ($check->isEmpty()) {
            $db->collection('inventory')->add([
                'item_name' => $i[0],
                'category' => $i[1],
                'stock' => (int)$i[2],
                'min_stock' => (int)$i[3],
                'satuan' => $i[4],
                'price_reference' => (float)$i[5],
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            foreach ($check as $doc) {
                $doc->reference()->update([
                    ['path' => 'stock', 'value' => \Google\Cloud\Firestore\FieldValue::increment((int)$i[2])]
                ]);
            }
        }
    } catch (Exception $e) {}
}
echo "<p>✅ Inventory seeded.</p>";

echo "<h2>🎉 All dummy data successfully integrated to Firestore!</h2>";
echo "<p><a href='index.php'>Go to Dashboard</a></p>";
?>
