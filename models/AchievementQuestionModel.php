<?php
// models/AchievementQuestionModel.php
require_once __DIR__ . '/../config/connection.php';

class AchievementQuestionModel {
    private $table = 'achievement_questions';

    public function getAllQuestions() {
        global $php_fetch;
        return $php_fetch($this->table, '*', [], 'question_number.asc', true);
    }

    public function getQuestionsByCategory($category) {
        global $php_fetch;
        return $php_fetch($this->table, '*', ['category' => $category], 'question_number.asc', true);
    }

    public function addQuestion($data) {
        global $php_insert;
        return $php_insert($this->table, $data, true);
    }

    public function getQuestionById($id) {
        global $php_fetch;
        $result = $php_fetch($this->table, '*', ['id' => $id], null, true);
        return !empty($result) && !isset($result['error']) ? $result[0] : null;
    }

    public function updateQuestion($id, $data) {
        global $php_update;
        return $php_update($this->table, $data, ['id' => $id], true);
    }

    public function deleteQuestion($id) {
        global $php_delete;
        return $php_delete($this->table, ['id' => $id], true);
    }
}
