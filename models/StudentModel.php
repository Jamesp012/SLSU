<?php
// models/StudentModel.php
require_once __DIR__ . '/../config/connection.php';

class StudentModel {
    private $table = 'profiles';
    
    public function getAllStudents($useServiceRole = false) {
        global $php_fetch;
        return $php_fetch($this->table, '*', ['role' => 'student'], null, $useServiceRole);
    }

    public function getStudentById($id, $useServiceRole = false) {
        global $php_fetch;
        $result = $php_fetch($this->table, '*', ['id' => $id], null, $useServiceRole);
        return !empty($result) && !isset($result['error']) ? $result[0] : null;
    }

    public function getStudentByEmail($email, $useServiceRole = false) {
        global $php_fetch;
        $result = $php_fetch($this->table, '*', ['email' => $email], null, $useServiceRole);
        return !empty($result) && !isset($result['error']) ? $result[0] : null;
    }

    public function addStudent($data, $useServiceRole = true) {
        global $php_insert;
        // $data should now include: first_name, middle_name, last_name, email, lrn, recent_school, preferred_track
        return $php_insert('profiles', $data, $useServiceRole);
    }

    public function updateStudent($id, $data, $useServiceRole = true) {
        global $php_update;
        return $php_update($this->table, $data, ['id' => $id], $useServiceRole);
    }

    public function deleteStudent($id, $useServiceRole = true) {
        global $php_delete;
        return $php_delete($this->table, ['id' => $id], $useServiceRole);
    }
}
