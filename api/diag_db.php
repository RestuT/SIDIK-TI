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
    $factory = (new \Kreait\Firebase\Factory);
    if (getenv('FIREBASE_SERVICE_ACCOUNT_JSON')) {
        $data = json_decode(getenv('FIREBASE_SERVICE_ACCOUNT_JSON'), true);
        if (!$data) $diagnostics['error_logs'][] = "JSON Decode failed for FIREBASE_SERVICE_ACCOUNT_JSON";
        else $factory->withServiceAccount($data);
    } else if (file_exists(__DIR__ . '/../firebase-auth.json')) {
        $factory->withServiceAccount(__DIR__ . '/../firebase-auth.json');
    }
    $factory->createFirestore();
} catch (\Exception $e) {
    $diagnostics['error_logs'][] = "Firebase Error: " . $e->getMessage();
}

echo json_encode($diagnostics, JSON_PRETTY_PRINT);
