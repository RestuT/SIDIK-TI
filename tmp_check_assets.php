<?php
require_once __DIR__ . '/api/config/database.php';

$docs = $db->collection('asset_assignments')->documents();
$count = 0;
foreach ($docs as $doc) {
    echo "ID: " . $doc->id() . "\n";
    print_r($doc->data());
    $count++;
}
echo "Total asset_assignments: " . $count . "\n";
?>
