<?php
/**
 * SIDIK-TI Global Database Configuration
 * Supports Dual Storage: MySQL (Local) and Firebase Firestore (Cloud/Vercel).
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Dotenv\Dotenv;

// Initialize variables
$db = null;   // Firestore instance
$conn = null; // MySQL connection

// Detect Environment
$isVercel = getenv('VERCEL') === '1';

// Initialize Dotenv for local development
if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
}

/**
 * 1. CLOUD STORAGE (FIREBASE FIRESTORE)
 * Used when running on Vercel or if Firebase credentials are provided.
 */
$projectId = getenv('FIREBASE_PROJECT_ID');
$privateKey = str_replace('\\n', "\n", getenv('FIREBASE_PRIVATE_KEY') ?: '');
$clientEmail = getenv('FIREBASE_CLIENT_EMAIL');
$serviceAccountJson = getenv('FIREBASE_SERVICE_ACCOUNT_JSON');

try {
    $factory = (new Factory);
    $firebaseAvailable = false;

    if ($projectId && $privateKey && $clientEmail) {
        $factory = $factory->withServiceAccount([
            'project_id' => $projectId,
            'private_key' => $privateKey,
            'client_email' => $clientEmail,
        ]);
        $firebaseAvailable = true;
    } else if ($serviceAccountJson) {
        $serviceAccountData = json_decode($serviceAccountJson, true);
        $factory = $factory->withServiceAccount($serviceAccountData);
        $firebaseAvailable = true;
    } else if (file_exists(__DIR__ . '/../../firebase-auth.json')) {
        $factory = $factory->withServiceAccount(__DIR__ . '/../../firebase-auth.json');
        $firebaseAvailable = true;
    }

    if ($firebaseAvailable) {
        $firestore = $factory->createFirestore();
        $db = $firestore->database();
    }
} catch (\Exception $e) {
    error_log("Firebase Initialization Warning: " . $e->getMessage());
}

/**
 * 2. LOCAL STORAGE (MYSQL)
 * Used when running locally on XAMPP.
 */
if (!$isVercel) {
    $db_host = getenv('DB_HOST') ?: 'localhost';
    $db_user = getenv('DB_USER') ?: 'root';
    $db_pass = getenv('DB_PASS') ?: '';
    $db_name = getenv('DB_NAME') ?: 'sidik_ti';

    try {
        $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
        if (!$conn) {
            error_log("MySQL Connection Failed: " . mysqli_connect_error());
        }
    } catch (\Exception $e) {
        error_log("MySQL Connection Error: " . $e->getMessage());
    }
}

// Global helper to check which storage is active
function get_storage_type() {
    global $isVercel;
    return $isVercel ? 'cloud' : 'local';
}
?>