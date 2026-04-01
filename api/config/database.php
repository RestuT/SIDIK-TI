<?php
/**
 * SIDIK-TI Global Database Configuration
 * Migrated to Firebase Cloud Firestore for Vercel Deployment.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Google\Cloud\Firestore\FirestoreClient;
use Dotenv\Dotenv;

// Initialize Dotenv for local development
if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
}

// Firebase Configuration
// Option 1: Use individual environment variables (Best for Vercel Dashboard)
$projectId = getenv('FIREBASE_PROJECT_ID');
$privateKey = str_replace('\\n', "\n", getenv('FIREBASE_PRIVATE_KEY') ?: '');
$clientEmail = getenv('FIREBASE_CLIENT_EMAIL');

// Option 2: Use the full JSON string (Legacy fallback)
$serviceAccountJson = getenv('FIREBASE_SERVICE_ACCOUNT_JSON');

try {
    $factory = (new Factory);
    
    if ($projectId && $privateKey && $clientEmail) {
        $factory = $factory->withServiceAccount([
            'project_id' => $projectId,
            'private_key' => $privateKey,
            'client_email' => $clientEmail,
        ]);
    } else if ($serviceAccountJson) {
        // Decode JSON from Env Var
        $serviceAccountData = json_decode($serviceAccountJson, true);
        $factory = $factory->withServiceAccount($serviceAccountData);
    } else if (file_exists(__DIR__ . '/../../firebase-auth.json')) {
        // Fallback to local file for development
        $factory = $factory->withServiceAccount(__DIR__ . '/../../firebase-auth.json');
    }

    $firestoreProject = $projectId ?: 'sidik-ti-demo';
    
    // Create Firestore Client
    $firestore = $factory->createFirestore();
    $db = $firestore->database();

    // Compatibility Layer
    // We set $conn to null to prevent errors in legacy mysqli code while we refactor.
    $conn = null;

} catch (\Exception $e) {
    // In production, we might want to log this instead of dying
    if (getenv('VERCEL') === '1') {
        error_log("Firebase Connection Error: " . $e->getMessage());
    } else {
        die("Firebase Connection Error: " . $e->getMessage());
    }
}
?>