<?php
/**
 * Diagnostic Script for Vercel Database Connection
 */
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json');

$diagnostics = [
    'environment' => [
        'is_vercel' => getenv('VERCEL') === '1',
        'php_version' => PHP_VERSION,
    ],
    'firebase_vars' => [
        'FIREBASE_PROJECT_ID' => getenv('FIREBASE_PROJECT_ID') ? 'Set (Match found)' : 'NOT SET',
        'FIREBASE_CLIENT_EMAIL' => getenv('FIREBASE_CLIENT_EMAIL') ? 'Set (Match found)' : 'NOT SET',
        'FIREBASE_PRIVATE_KEY' => getenv('FIREBASE_PRIVATE_KEY') ? 'Set (Match found)' : 'NOT SET',
        'FIREBASE_SERVICE_ACCOUNT_JSON' => getenv('FIREBASE_SERVICE_ACCOUNT_JSON') ? 'Set (Match found)' : 'NOT SET',
    ],
    'connection_status' => [
        'firestore_active' => ($db !== null),
        'mysql_active' => ($conn !== null),
    ],
    'error_logs' => [],
    'file_checks' => [
        'firebase-auth.json_exists' => file_exists(__DIR__ . '/../firebase-auth.json'),
        'vendor_autoload_exists' => file_exists(__DIR__ . '/../vendor/autoload.php'),
    ]
];

// Re-try initialization to capture error
try {
    // FORCE REST BACKBACK for environments without gRPC (like Vercel)
    putenv('GOOGLE_CLOUD_PHP_FIRESTORE_REST_ONLY=true');
    
    $factory = (new \Kreait\Firebase\Factory);
    
    $pId = trim(getenv('FIREBASE_PROJECT_ID') ?: '');
    $pKeyRaw = getenv('FIREBASE_PRIVATE_KEY') ?: '';
    $pKey = str_replace('\\n', "\n", trim($pKeyRaw));
    $cEmail = trim(getenv('FIREBASE_CLIENT_EMAIL') ?: '');
    $sAccountJson = trim(getenv('FIREBASE_SERVICE_ACCOUNT_JSON') ?: '');

    if ($sAccountJson) {
        $data = json_decode($sAccountJson, true);
        if (!$data) $diagnostics['error_logs'][] = "JSON Decode failed for FIREBASE_SERVICE_ACCOUNT_JSON";
        else $factory = $factory->withServiceAccount($data);
    } else if ($pId && $pKey && $cEmail) {
        $factory = $factory->withServiceAccount([
            'type' => 'service_account',
            'project_id' => $pId,
            'private_key' => $pKey,
            'client_email' => $cEmail,
        ]);
    } else if (file_exists(__DIR__ . '/../firebase-auth.json')) {
        $factory = $factory->withServiceAccount(__DIR__ . '/../firebase-auth.json');
    }
    
    $factory->createFirestore();
} catch (\Exception $e) {
    $diagnostics['error_logs'][] = "Firebase Error Trace: " . $e->getMessage();
}

echo json_encode($diagnostics, JSON_PRETTY_PRINT);
