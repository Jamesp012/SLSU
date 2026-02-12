<?php
// models/AchievementScoreModel.php
require_once __DIR__ . '/../config/connection.php';

class AchievementScoreModel {
    private $table = 'achievement_scores';

    public function getScoreByStudentId($studentId) {
        global $php_fetch;
        $result = $php_fetch($this->table, '*', ['student_id' => $studentId], 'created_at.desc', true);
        return !empty($result) ? $result[0] : null;
    }

    public function addScore($data) {
        global $php_insert;
        return $php_insert($this->table, $data, true);
    }

    public function hasTakenTest($studentId) {
        $score = $this->getScoreByStudentId($studentId);
        return $score !== null;
    }
}
