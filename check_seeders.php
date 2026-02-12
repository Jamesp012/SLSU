<?php
require_once 'c:/xampp/htdocs/SLSU/config/connection.php';
// In Supabase/PostgREST, we can query the information_schema via RPC if enabled,
// but usually it's not. 
// However, we can try to query a system table if allowed.
// Another way is to check the helpers for any other seeders.

$helpersDir = 'c:/xampp/htdocs/SLSU/helpers/';
$files = scandir($helpersDir);
foreach($files as $file) {
    if (strpos($file, 'seed') !== false) {
        echo "Found seeder: $file\n";
        echo file_get_contents($helpersDir . $file);
        echo "\n-------------------\n";
    }
}
