<?php
require_once __DIR__ . '/config/connection.php';
global $php_fetch;
$res = $php_fetch('achievement_scores', '*', [], 'created_at.desc', true, 5);
print_r($res);
