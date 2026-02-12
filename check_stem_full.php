<?php
require_once 'c:/xampp/htdocs/SLSU/config/connection.php';
global $php_fetch;
$res = $php_fetch('stem_questions', '*', [], null, true);
if (!empty($res) && !isset($res['error'])) {
    print_r($res[0]);
}
