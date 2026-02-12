<?php
require_once 'c:/xampp/htdocs/SLSU/config/connection.php';
// Since connection.php uses Supabase (PostgREST), we can't use SHOW TABLES.
// But we can check the public schema tables if there's a way.
// Usually with Supabase we know the table names from models.
// Let's check the files in models/ again.
$modelsDir = 'c:/xampp/htdocs/SLSU/models/';
$files = scandir($modelsDir);
foreach($files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "File: $file\n";
        $content = file_get_contents($modelsDir . $file);
        if (preg_match('/private \$[a-zA-Z0-9_]*Table = \'(.*?)\'/', $content, $m)) {
            echo "  Table: " . $m[1] . "\n";
        } elseif (preg_match('/private \$table = \'(.*?)\'/', $content, $m)) {
            echo "  Table: " . $m[1] . "\n";
        }
    }
}
