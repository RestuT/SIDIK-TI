<?php
/**
 * SIDIK-TI Global Database Configuration
 * Supports Dual Storage: MySQL (Local) and Firebase Firestore (Cloud/Vercel).
 */

// Output buffering: safety net jika auto_prepend_file / .user.ini belum aktif
// ob_start() aman dipanggil berkali-kali (stackable), tidak merusak apapun
ob_start();

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
// CLOUD STORAGE (FIREBASE FIRESTORE)
$projectId = trim(getenv('FIREBASE_PROJECT_ID') ?: '');
$privateKeyRaw = getenv('FIREBASE_PRIVATE_KEY') ?: '';
$privateKey = str_replace('\\n', "\n", trim($privateKeyRaw));
$clientEmail = trim(getenv('FIREBASE_CLIENT_EMAIL') ?: '');
$serviceAccountJson = trim(getenv('FIREBASE_SERVICE_ACCOUNT_JSON') ?: '');
$databaseId = trim(getenv('FIREBASE_DATABASE_ID') ?: '(default)');

try {
    $factory = (new Factory);
    $firebaseAvailable = false;

    if ($serviceAccountJson) {
        $serviceAccountData = json_decode($serviceAccountJson, true);
        if ($serviceAccountData === null) {
             throw new Exception("FIREBASE_SERVICE_ACCOUNT_JSON is not a valid JSON string.");
        }
        $factory = $factory->withServiceAccount($serviceAccountData);
        $firebaseAvailable = true;
    } else if ($projectId && $privateKey && $clientEmail) {
        $factory = $factory->withServiceAccount([
            'type' => 'service_account',
            'project_id' => $projectId,
            'private_key' => $privateKey,
            'client_email' => $clientEmail,
        ]);
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
    // Silent failure for production, log to backend
    error_log("Firebase Initialization Error: " . $e->getMessage());
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

/**
 * 3. CUSTOM SESSION MANAGEMENT
 * Uses Firestore for sessions to ensure persistence on Vercel.
 */
if ($db) {
    require_once __DIR__ . '/session_handler.php';
    $handler = new FirestoreSessionHandler($db);
    session_set_save_handler($handler, true);
}

// Start session centrally if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}