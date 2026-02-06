<?php
// helpers/seed_pathways.php
require_once __DIR__ . '/../config/connection.php';

$pathways = [
    ['name' => 'Computer Science'],
    ['name' => 'Information Technology'],
    ['name' => 'Civil Engineering'],
    ['name' => 'Mechanical Engineering'],
    ['name' => 'Electrical Engineering'],
    ['name' => 'Nursing'],
    ['name' => 'Medical Technology / Medical Laboratory Science'],
    ['name' => 'Pharmacy'],
    ['name' => 'Biology'],
    ['name' => 'Chemistry'],
    ['name' => 'Mathematics / Statistics'],
    ['name' => 'Education major in Sciences']
];

global $php_insert, $php_fetch;

// Check if pathways already exist
$existing = $php_fetch('stem_pathways', '*', [], null, true);

if (isset($existing['error'])) {
    echo "Error fetching pathways: " . $existing['message'] . "\n";
    echo "Please ensure the 'stem_pathways' table exists in your database.\n";
    exit();
}

if (empty($existing)) {
    foreach ($pathways as $pw) {
        $php_insert('stem_pathways', $pw, true);
    }
    echo "Pathways seeded successfully.\n";
} else {
    echo "Pathways already exist. Skipping seed.\n";
}
