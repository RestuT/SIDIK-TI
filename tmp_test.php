<?php
require 'api/config/database.php';
try {
    $storage = $factory->createStorage();
    $bucket = $storage->getBucket();
    echo "BUCKET=" . $bucket->name();
} catch (Exception $e) {
    echo "ERROR=" . $e->getMessage();
}
?>
