<?php
// models/UserModel.php
require_once __DIR__ . '/../config/connection.php';

class UserModel {
    private $table = 'profiles';

    public function login($email, $password, $useServiceRole = false) {
        global $php_fetch;
        $result = $php_fetch($this->table, '*', ['email' => "ilike.$email"], null, $useServiceRole);
        
        if (!empty($result) && !isset($result['error'])) {
            $user = $result[0];
            
            // Extract hashed password from metadata since it's not a direct column
            $metadata = is_string($user['metadata']) ? json_decode($user['metadata'], true) : ($user['metadata'] ?? []);
            $hashed_password = $metadata['password'] ?? null;

            if ($hashed_password && password_verify($password, $hashed_password)) {
                return $user;
            }
        }
        return null;
    }

    public function getUserByEmail($email, $useServiceRole = true) {
        global $php_fetch;
        $result = $php_fetch($this->table, '*', ['email' => "ilike.$email"], null, $useServiceRole);
        return !empty($result) && !isset($result['error']) ? $result[0] : null;
    }

    public function addUser($data, $useServiceRole = true) {
        global $php_insert;
        return $php_insert($this->table, $data, $useServiceRole);
    }

    public function updateUser($id, $data, $useServiceRole = true) {
        global $php_update;
        return $php_update($this->table, $data, ['id' => $id], $useServiceRole);
    }
}
