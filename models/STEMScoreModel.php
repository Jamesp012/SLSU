<?php
// models/STEMScoreModel.php
require_once __DIR__ . '/../config/connection.php';

class STEMScoreModel {
    private $table = 'stem_scores';

    public function getScoreByStudentId($studentId) {
        global $php_fetch;
        $result = $php_fetch($this->table, '*', ['student_id' => $studentId], 'created_at.desc', true);
        
        if (isset($result['error']) || empty($result) || !isset($result[0])) {
            return null;
        }
        
        return $result[0];
    }

    public function addScore($data) {
        global $php_insert;
        return $php_insert($this->table, $data, true);
    }

    public function hasTakenTest($studentId) {
        $score = $this->getScoreByStudentId($studentId);
        return $score !== null;
    }

    public function bulkAddPathwayStanines($data) {
        global $php_insert;
        return $php_insert('pathway_stanines', $data, true);
    }

    public function deletePathwayStanines($studentId) {
        global $php_delete;
        return $php_delete('pathway_stanines', ['student_id' => $studentId], true);
    }

    public function deleteScore($studentId) {
        global $php_delete;
        return $php_delete($this->table, ['student_id' => $studentId], true);
    }
}
