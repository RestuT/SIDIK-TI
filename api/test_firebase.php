<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/config/database.php';

echo "SIDIK-TI Firebase Connection Test\n";
echo "================================\n";
echo "Environment: " . (getenv('VERCEL') ? 'Vercel' : 'Local') . "\n";
echo "Active Storage: " . get_storage_type() . "\n";

// Check Library
echo "Searching for Kreait\\Firebase\\Factory: " . (class_exists('Kreait\Firebase\Factory') ? 'FOUND' : 'MISSING') . "\n";

// Check Env Vars (Silent check)
echo "FIREBASE_SERVICE_ACCOUNT_JSON set: " . (getenv('FIREBASE_SERVICE_ACCOUNT_JSON') ? 'YES' : 'NO') . "\n";
echo "FIREBASE_PROJECT_ID set: " . (getenv('FIREBASE_PROJECT_ID') ? 'YES' : 'NO') . "\n";

if (!$db) {
    echo "ERROR: Firestore instance (\$db) is NULL.\n";
    echo "Check if FIREBASE_PROJECT_ID, FIREBASE_PRIVATE_KEY, and FIREBASE_CLIENT_EMAIL are set in Vercel.\n";
    exit;
}

try {
    echo "Attempting to write test document to 'test_connection' collection...\n";
    $testRef = $db->collection('test_connection')->add([
        'message' => 'Hello from SIDIK-TI!',
        'timestamp' => date('Y-m-d H:i:s'),
        'source' => getenv('VERCEL') ? 'Vercel' : 'Local'
    ]);
    
    echo "SUCCESS! Document ID: " . $testRef->id() . "\n";
    echo "Data has been sent to Firestore.\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}
