<?php
require_once __DIR__ . '/config/connection.php';
global $php_fetch;

$tables = ['user', 'accounts', 'user_accounts', 'students', 'auth_users'];

foreach ($tables as $table) {
    echo "Testing table: $table... ";
    $result = $php_fetch($table, '*', ['limit' => 1]);
    if (isset($result['error'])) {
        echo "Error: " . $result['error'] . "\n";
    } else {
        echo "FOUND! Count: " . count($result) . "\n";
    }
}
