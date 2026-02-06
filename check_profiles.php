<?php
require 'c:/xampp/htdocs/SLSU/config/connection.php';
global $php_fetch;
$res = $php_fetch('profiles', '*', [], null, true);
echo json_encode($res);
