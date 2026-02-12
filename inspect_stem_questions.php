<?php
require_once 'c:/xampp/htdocs/SLSU/config/connection.php';
global $php_fetch;
$res = $php_fetch('stem_questions', '*', [], 'question_number.asc', true);
if (isset($res['error'])) {
    echo "Error: " . $res['error'] . "\n";
} else {
    echo "Total questions: " . count($res) . "\n";
    // Show first 5 and then some from the middle
    echo "First 5:\n";
    for($i=0; $i<5 && $i<count($res); $i++) {
        echo $res[$i]['question_number'] . ": " . $res[$i]['question_text'] . "\n";
    }
    echo "\nMiddle 5:\n";
    $start = floor(count($res)/2);
    for($i=$start; $i<$start+5 && $i<count($res); $i++) {
        echo $res[$i]['question_number'] . ": " . $res[$i]['question_text'] . "\n";
    }
}
