<?php
require_once 'api/config/database.php';
if ($db) {
    $docs = $db->collection('asset_assignments')->limit(5)->documents();
    foreach ($docs as $doc) {
        echo "ID: " . $doc->id() . "\n";
        print_r($doc->data());
        echo "\n---\n";
    }
}
