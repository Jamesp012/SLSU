<?php
// helpers/seed_test_users.php
/**
 * Use these credentials for testing the login:
 * 
 * ADMIN:
 * Email: admin@slsu.edu.ph
 * Password: admin123
 * 
 * STUDENT:
 * Email: student@example.com
 * Password: student123
 */

require_once __DIR__ . '/../config/connection.php';

function seedTestUsers() {
    global $php_insert;

    // This is for demonstration. In a real Supabase setup, 
    // these would be created via Auth API and then linked to profiles.
    
    echo "Seed info: Use the following for local testing:\n";
    echo "Admin: admin@slsu.edu.ph / admin123\n";
    echo "Student: student@example.com / student123\n";
}
