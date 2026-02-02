<?php
// models/UserModel.php
require_once __DIR__ . '/../config/connection.php';

class UserModel {
    private $table = 'users';

    public function login($email, $password) {
        global $php_fetch;
        // In a real scenario, we would check the password hash
        // For Supabase, we might use their Auth API, but here we simulate with REST
        $result = $php_fetch($this->table, '*', ['email' => $email]);
        
        if (!empty($result)) {
            $user = $result[0];
            if (password_verify($password, $user['password'])) {
                return $user;
            }
        }
        return null;
    }

    public function getUserByEmail($email) {
        global $php_fetch;
        $result = $php_fetch($this->table, '*', ['email' => $email]);
        return !empty($result) ? $result[0] : null;
    }
}
