<?php
require_once 'c:/xampp/htdocs/SLSU/config/connection.php';
global $php_fetch;
$res = $php_fetch('stem_questions', '*', [], 'question_number.asc', true);
if (!isset($res['error'])) {
    echo "Total: " . count($res) . "\n";
    // Check for different patterns
    foreach ($res as $q) {
        if (!preg_match('/interested|want|fascinated|driven|keen|passionate/i', $q['question_text'])) {
            echo "Different Q: " . $q['question_number'] . ": " . $q['question_text'] . "\n";
            // break; // just show one for now
        }
    }
}
