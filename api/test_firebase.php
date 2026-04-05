<?php
ob_start();
header('Content-Type: text/plain');

// DEBUG: Catch any initialization errors from database.php
ob_start();
require_once __DIR__ . '/config/database.php';
$init_output = ob_get_clean();

echo "SIDIK-TI Firebase Connection Test\n";
echo "================================\n";
echo "Environment: " . (getenv('VERCEL') ? 'Vercel' : 'Local') . "\n";
echo "Active Storage: " . get_storage_type() . "\n";

// Check Library
echo "Searching for Kreait\\Firebase\\Factory: " . (class_exists('Kreait\Firebase\Factory') ? 'FOUND' : 'MISSING') . "\n";
echo "Extension GRPC: " . (extension_loaded('grpc') ? 'LOADED' : 'MISSING') . "\n";
echo "Extension Protobuf: " . (extension_loaded('protobuf') ? 'LOADED' : 'MISSING') . "\n";

// Check Env Vars (Silent check)
$jsonStr = getenv('FIREBASE_SERVICE_ACCOUNT_JSON');
echo "FIREBASE_SERVICE_ACCOUNT_JSON set: " . ($jsonStr ? 'YES' : 'NO') . "\n";
if ($jsonStr) {
    $temp = json_decode($jsonStr, true);
    echo "JSON Decode: " . ($temp === null ? 'FAILED: ' . json_last_error_msg() : 'SUCCESS') . "\n";
}

if ($init_output) {
    echo "\nInitialization Output:\n$init_output\n";
}

if (!$db) {
    echo "\nERROR: Firestore instance (\$db) is NULL.\n";
    echo "Please check the 'Initialization Output' above for hints.\n";
    exit;
}

try {
    echo "\nAttempting to write test document...\n";
    $testRef = $db->collection('test_connection')->add([
        'message' => 'Hello from SIDIK-TI!',
        'timestamp' => date('Y-m-d H:i:s'),
        'source' => getenv('VERCEL') ? 'Vercel' : 'Local'
    ]);
    
    echo "SUCCESS! Document ID: " . $testRef->id() . "\n";
} catch (Exception $e) {
    echo "FAILED to write: " . $e->getMessage() . "\n";
}
