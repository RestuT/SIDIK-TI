<?php
ob_start();
/**
 * SIDIK-TI Data Migration: MySQL -> Cloud Firestore
 * Run this script locally on your XAMPP server to move your data to the cloud.
 */

define('MIGRATION_MODE', true);
require_once __DIR__ . '/config/database.php';

echo "<h1>🚀 SIDIK-TI Firestore Migration Tool</h1>";

if (!$db) {
    die("<p style='color:red'>❌ Firebase not initialized. Check your credentials.</p>");
}

// 1. Reconnect to MySQL for one last time to pull data
$mysql = mysqli_connect("localhost", "root", "", "sidik_ti"); 
if (!$mysql) die("❌ Local MySQL connection failed.");

$tables_to_migrate = [
    'departments' => 'departments',
    'budget_config' => 'budget_config',
    'inventory' => 'inventory',
    'users' => 'users',
    'submissions' => 'submissions',
    'asset_assignments' => 'asset_assignments',
    'procurement_templates' => 'procurement_templates'
];

foreach ($tables_to_migrate as $mysql_table => $firestore_collection) {
    echo "<h3>Migrating $mysql_table...</h3>";
    
    $result = mysqli_query($mysql, "SELECT * FROM $mysql_table");
    $count = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Use the ID as the document name for consistency
        $id = isset($row['id']) ? (string)$row['id'] : null;
        
        $docRef = $db->collection($firestore_collection);
        
        if ($id) {
            $docRef->document($id)->set($row);
        } else {
            $docRef->add($row);
        }
        $count++;
    }
    
    echo "<p>✅ Moved $count records to Firestore collection '$firestore_collection'.</p>";
}

echo "<h2>🎉 Migration Complete!</h2>";
echo "<p>Your data is now in Cloud Firestore. You can now deploy to Vercel.</p>";
?>
