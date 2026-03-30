<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
try {
    $conn = mysqli_connect("localhost", "root", "", "sidik_ti");
    if (!$conn) {
        echo "CONNECTION ERROR: " . mysqli_connect_error() . "\n";
        exit;
    }
    $res = mysqli_query($conn, "SHOW TABLES");
    if (!$res) {
        echo "QUERY ERROR: " . mysqli_error($conn) . "\n";
        exit;
    }
    echo "TABLES:\n";
    while($row = mysqli_fetch_array($res)) {
        echo "- " . $row[0] . "\n";
    }
} catch (Exception $e) {
    echo "EXCEPTiON: " . $e->getMessage() . "\n";
}
?>
