<?php
$conn = mysqli_connect("localhost", "root", "", "sidik_ti");
$res = mysqli_query($conn, "SHOW TABLES");
while($row = mysqli_fetch_array($res)) {
    echo $row[0] . "\n";
}
?>
