<?php
require_once 'config/connection.php';
$res = supabaseRequest('GET', 'stem_pathways', [], true);
echo json_encode($res, JSON_PRETTY_PRINT);
?>
