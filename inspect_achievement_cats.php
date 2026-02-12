<?php
require_once 'c:/xampp/htdocs/SLSU/config/connection.php';
global $php_fetch;
$res = $php_fetch('achievement_questions', '*', [], 'question_number.asc', true);
if (isset($res['error'])) {
    echo "Error: " . $res['error'] . "\n";
} else {
    echo "Total questions: " . count($res) . "\n";
    $cats = [];
    foreach ($res as $row) {
        $cats[$row['category']] = ($cats[$row['category']] ?? 0) + 1;
    }
    print_r($cats);
}
