<?php
require 'api/config/database.php';
try {
    $storage = $factory->createStorage();
    $bucket = $storage->getBucket();
    $object = $bucket->upload('test file content', [
        'name' => 'uploads/test.txt'
    ]);
    $url = $object->signedUrl(new \DateTime('+10 years'));
    echo "URL_SUCCESS: " . $url;
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
