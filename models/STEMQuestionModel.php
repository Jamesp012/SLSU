<?php
// models/STEMQuestionModel.php
require_once __DIR__ . '/../config/connection.php';

class STEMQuestionModel {
    private $questionsTable = 'stem_questions';
    private $pathwaysTable = 'stem_pathways';

    public function getAllPathways() {
        global $php_fetch;
        return $php_fetch($this->pathwaysTable, '*', [], 'id.asc', true);
    }

    public function getQuestionsByPathway($pathwayId) {
        global $php_fetch;
        return $php_fetch($this->questionsTable, '*', ['pathway_id' => $pathwayId], 'question_number.asc', true);
    }

    public function getAllQuestions() {
        global $php_fetch;
        return $php_fetch($this->questionsTable, '*, stem_pathways(name)', [], 'question_number.asc', true);
    }

    public function addQuestion($data) {
        global $php_insert;
        return $php_insert($this->questionsTable, $data, true);
    }

    public function updateQuestion($id, $data) {
        global $php_update;
        return $php_update($this->questionsTable, $data, ['id' => $id], true);
    }

    public function deleteQuestion($id) {
        global $php_delete;
        return $php_delete($this->questionsTable, ['id' => $id], true);
    }

    public function getQuestionByNumber($number) {
        global $php_fetch;
        $result = $php_fetch($this->questionsTable, '*', ['question_number' => $number], null, true);
        return !empty($result) ? $result[0] : null;
    }
}
