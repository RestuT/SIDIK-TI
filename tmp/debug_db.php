<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$conn = mysqli_connect("localhost", "root", "", "sidik_ti");
if (!$conn) {
    die("CONNECTION ERROR: " . mysqli_connect_error());
}
$res = mysqli_query($conn, "SHOW TABLES");
if (!$res) {
    die("QUERY ERROR: " . mysqli_error($conn));
}
echo "TABLES FOUND:\n";
while($row = mysqli_fetch_array($res)) {
    echo "- " . $row[0] . "\n";
}
?>
