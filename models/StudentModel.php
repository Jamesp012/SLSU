<?php
// models/StudentModel.php
require_once __DIR__ . '/../config/connection.php';

class StudentModel {
    private $table = 'profiles';
    
    public function getAllStudents() {
        global $php_fetch;
        return $php_fetch($this->table, '*', ['role' => 'student']);
    }

    public function getStudentById($id) {
        global $php_fetch;
        $result = $php_fetch($this->table, '*', ['id' => $id]);
        return !empty($result) ? $result[0] : null;
    }

    public function addStudent($data) {
        global $php_insert;
        // $data should now include: first_name, middle_name, last_name, email, lrn, recent_school, preferred_track
        return $php_insert('profiles', $data);
    }

    public function updateStudent($id, $data) {
        global $php_update;
        return $php_update($this->table, $data, ['id' => $id]);
    }

    public function deleteStudent($id) {
        global $php_delete;
        return $php_delete($this->table, ['id' => $id]);
    }
}
