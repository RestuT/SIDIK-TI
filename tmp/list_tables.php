<?php
include 'config/database.php';
$res = mysqli_query($conn, "SHOW TABLES");
if($res) {
    echo "TABLES:\n";
    while($row = mysqli_fetch_array($res)) {
        echo "- " . $row[0] . "\n";
    }
} else {
    echo "NO TABLES OR ERROR: " . mysqli_error($conn);
}
?>
