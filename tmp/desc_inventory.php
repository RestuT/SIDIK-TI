<?php
$conn = mysqli_connect("localhost", "root", "", "sidik_ti");
if (!$conn) die("FAIL");
$res = mysqli_query($conn, "DESC inventory");
echo "INVENTORY TABLE:\n";
while($row = mysqli_fetch_array($res)) {
    echo "- " . $row[0] . " (" . $row[1] . ")\n";
}
?>
