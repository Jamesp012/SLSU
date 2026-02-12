<?php
require_once 'c:/xampp/htdocs/SLSU/config/connection.php';
global $conn;
$res = $conn->query("DESCRIBE achievement_scores");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " ";
}
echo "\n";
