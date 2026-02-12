<?php
require_once 'c:/xampp/htdocs/SLSU/config/connection.php';
global $php_fetch;
$res = $php_fetch('stem_questions', 'category', [], null, true);
$cats = [];
foreach ($res as $row) {
    $cats[$row['category']] = ($cats[$row['category']] ?? 0) + 1;
}
print_r($cats);
