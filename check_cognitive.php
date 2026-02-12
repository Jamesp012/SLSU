<?php
require_once 'c:/xampp/htdocs/SLSU/config/connection.php';
global $php_fetch;
$res = $php_fetch('cognitive_questions', '*', [], null, true);
if (isset($res['error'])) {
    echo "Error: " . $res['error'] . "\n";
} else {
    echo "Count: " . count($res) . "\n";
    if (count($res) > 0) print_r($res[0]);
}
