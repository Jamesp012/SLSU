<?php
require_once 'c:/xampp/htdocs/SLSU/config/connection.php';
global $php_fetch;
$res = $php_fetch('stem_questions', '*', [], null, true);
$hasAnswer = 0;
foreach($res as $q) {
    if (isset($q['correct_answer']) && !empty($q['correct_answer'])) {
        $hasAnswer++;
    }
}
echo "Questions with correct_answer: $hasAnswer\n";
