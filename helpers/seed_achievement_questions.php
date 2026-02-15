<?php
// helpers/seed_achievement_questions.php
require_once __DIR__ . '/../config/connection.php';

/*
SQL for creating the tables (UPDATED):

DROP TABLE IF EXISTS achievement_questions CASCADE;

CREATE TABLE achievement_questions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    question_number INT NOT NULL,
    category TEXT NOT NULL,
    question_text TEXT NOT NULL,
    choice_a TEXT NOT NULL,
    choice_b TEXT NOT NULL,
    choice_c TEXT NOT NULL,
    choice_d TEXT NOT NULL,
    correct_answer TEXT NOT NULL, -- Changed from CHAR(1) to TEXT
    competency TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE achievement_scores (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    student_id UUID REFERENCES profiles(id) ON DELETE CASCADE,
    score INT NOT NULL,
    total_questions INT NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    is_passed BOOLEAN NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
*/

// RE-PARSING LOGIC DIRECTLY IN SEEDER FOR RELIABILITY
if (!file_exists(__DIR__ . '/../achievement_temp')) {
    mkdir(__DIR__ . '/../achievement_temp');
    exec('tar -xf ' . escapeshellarg(__DIR__ . '/../achievement-test.docx') . ' -C ' . escapeshellarg(__DIR__ . '/../achievement_temp'));
}

$xml_path = __DIR__ . '/../achievement_temp/word/document.xml';
if (!file_exists($xml_path)) {
    die("Failed to extract docx\n");
}

$xml = file_get_contents($xml_path);
preg_match_all('/<w:t>(.*?)<\/w:t>/', $xml, $matches);
$lines = $matches[1];

$questions = [];
$current_category = "Scientific Ability";

for ($i = 0; $i < count($lines); $i++) {
    $line = trim($lines[$i]);
    
    if ($line === "Scientific Ability" || $line === "Verbal Comprehension" || $line === "Mathematical Ability") {
        $current_category = $line;
        continue;
    }

    if (preg_match('/^(\d+)\.\s+(.*)/', $line, $m)) {
        $q_num = $m[1];
        $q_text = trim($m[2]);
        
        // Append context to specific questions
        if (in_array((int)$q_num, [21, 22, 23])) {
            $q_text .= "\n\n\"While social media connects people globally, studies suggest that excessive use leads to a decline in face-to-face social skills. Users often prioritize digital validation over genuine connection, creating a 'filter bubble' that limits exposure to diverse viewpoints.\"";
        }
        
        $choice_a = $lines[++$i] ?? '';
        $choice_b = $lines[++$i] ?? '';
        $choice_c = $lines[++$i] ?? '';
        $choice_d = $lines[++$i] ?? '';
        $answer = $lines[++$i] ?? '';
        $competency = $lines[++$i] ?? '';

        $choice_a = preg_replace('/^[a-d]\)\s*/', '', $choice_a);
        $choice_b = preg_replace('/^[a-d]\)\s*/', '', $choice_b);
        $choice_c = preg_replace('/^[a-d]\)\s*/', '', $choice_c);
        $choice_d = preg_replace('/^[a-d]\)\s*/', '', $choice_d);
        
        // Ensure answer is just a single character if possible, otherwise keep as is
        $clean_answer = trim(strtolower($answer));
        if (preg_match('/^([a-d])\)/', $clean_answer, $am)) {
            $clean_answer = $am[1];
        } elseif (strlen($clean_answer) > 1) {
            // Take first char if it's a, b, c, or d
            $first = substr($clean_answer, 0, 1);
            if (in_array($first, ['a', 'b', 'c', 'd'])) {
                $clean_answer = $first;
            }
        }

        $questions[] = [
            'question_number' => (int)$q_num,
            'category' => $current_category,
            'question_text' => trim($q_text),
            'choice_a' => trim($choice_a),
            'choice_b' => trim($choice_b),
            'choice_c' => trim($choice_c),
            'choice_d' => trim($choice_d),
            'correct_answer' => $clean_answer,
            'competency' => trim($competency)
        ];
    }
}

global $php_insert, $php_fetch, $php_delete;

echo "Seeding " . count($questions) . " questions...\n";

// Clear existing to avoid duplicates if re-running
$php_delete('achievement_questions', ['question_number' => 'gt.0'], true);

foreach ($questions as $q) {
    $php_insert('achievement_questions', $q, true);
}

echo "Seeding completed successfully.\n";

// Cleanup
exec('rmdir /S /Q ' . escapeshellarg(__DIR__ . '/../achievement_temp'));
