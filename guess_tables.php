<?php
require_once 'c:/xampp/htdocs/SLSU/config/connection.php';
// We can try to list all tables in public schema via RPC or just guess.
$guesses = ['cognitive_aptitude_questions', 'aptitude_questions', 'cognitive_test_questions', 'cognitive_questions', 'exam_questions'];
foreach($guesses as $table) {
    $res = supabaseRequest('GET', "$table?select=count", null, true);
    if (!isset($res['error'])) {
        echo "Found table: $table\n";
    }
}
