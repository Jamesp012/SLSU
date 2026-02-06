<?php
require_once __DIR__ . '/config/connection.php';
global $php_insert;

$fields = ['password', 'lrn', 'recent_school', 'preferred_track', 'onboarding_completed'];

foreach ($fields as $field) {
    echo "Testing column: $field... ";
    $result = $php_insert('profiles', [$field => 'test']);
    if (isset($result['error']) && strpos($result['response'], "Could not find the '$field' column") !== false) {
        echo "NOT FOUND\n";
    } else {
        echo "FOUND (or other error)\n";
        if (isset($result['response'])) echo "   Response: " . $result['response'] . "\n";
    }
}
