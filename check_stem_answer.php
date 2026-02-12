<?php
require_once 'c:/xampp/htdocs/SLSU/config/connection.php';
global $php_fetch;
$res = $php_fetch('stem_questions', '*', [], null, true);
if (!empty($res) && !isset($res['error'])) {
    if (isset($res[0]['correct_answer'])) {
        echo "Correct answer exists!\n";
        print_r($res[0]);
    } else {
        echo "Correct answer does NOT exist.\n";
    }
}
