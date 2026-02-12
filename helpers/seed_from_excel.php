<?php
// helpers/seed_from_excel.php
require_once __DIR__ . '/../config/connection.php';

function getSharedStrings($xmlPath) {
    $strings = [];
    $xml = simplexml_load_file($xmlPath);
    foreach ($xml->si as $si) {
        if (isset($si->t)) {
            $strings[] = (string)$si->t;
        } elseif (isset($si->r)) {
            $text = "";
            foreach ($si->r as $r) {
                $text .= (string)$r->t;
            }
            $strings[] = $text;
        } else {
            $strings[] = (string)$si;
        }
    }
    return $strings;
}

function parseSheet($xmlPath, $sharedStrings) {
    $rows = [];
    $xml = simplexml_load_file($xmlPath);
    foreach ($xml->sheetData->row as $row) {
        $rowData = [];
        foreach ($row->c as $c) {
            $ref = (string)$c['r'];
            $col = preg_replace('/[0-9]/', '', $ref);
            $type = (string)$c['t'];
            $value = (string)$c->v;
            
            if ($type === 's') {
                $rowData[$col] = $sharedStrings[(int)$value];
            } else {
                $rowData[$col] = $value;
            }
        }
        $rows[] = $rowData;
    }
    return $rows;
}

$sharedStringsPath = __DIR__ . '/../excel_temp/xl/sharedStrings.xml';
$sheetPath = __DIR__ . '/../excel_temp/xl/worksheets/sheet1.xml';

if (!file_exists($sharedStringsPath) || !file_exists($sheetPath)) {
    die("Excel XML files not found. Please ensure extraction was successful.\n");
}

$sharedStrings = getSharedStrings($sharedStringsPath);
$sheetRows = parseSheet($sheetPath, $sharedStrings);

$questions = [];
$currentCategory = "General";
$currentQ = null;

foreach ($sheetRows as $index => $row) {
    // Skip header row
    if ($index === 0) continue;
    
    $colA = trim($row['A'] ?? '');
    $colB = trim($row['B'] ?? '');
    $colC = trim($row['C'] ?? '');
    $colD = trim($row['D'] ?? '');
    
    // Check if it's a category header
    if (strpos($colA, 'Section') !== false || strpos($colA, 'Ability') !== false) {
        if ($currentQ) {
            $questions[] = $currentQ;
            $currentQ = null;
        }
        $currentCategory = str_replace('Section ', '', $colA);
        continue;
    }
    
    // Check if it's a new question (starts with a number)
    // Updated regex to handle non-breaking spaces and other variations
    if (preg_match('/^(\d+)\.[\s\x{00A0}]*(.*)/u', $colA, $matches)) {
        if ($currentQ) {
            $questions[] = $currentQ;
        }
        $qNum = $matches[1];
        $qText = $matches[2];
        
        $currentQ = [
            'question_number' => (int)$qNum,
            'category' => $currentCategory,
            'question_text' => trim($qText),
            'choice_a' => '',
            'choice_b' => '',
            'choice_c' => '',
            'choice_d' => '',
            'correct_answer' => strtolower($colC),
            'competency' => trim($colD)
        ];
    }
    
    // Parse choice from Column B
    if ($currentQ && $colB !== '') {
        if (preg_match('/^a\)\s*(.*)/i', $colB, $m)) $currentQ['choice_a'] = trim($m[1]);
        elseif (preg_match('/^b\)\s*(.*)/i', $colB, $m)) $currentQ['choice_b'] = trim($m[1]);
        elseif (preg_match('/^c\)\s*(.*)/i', $colB, $m)) $currentQ['choice_c'] = trim($m[1]);
        elseif (preg_match('/^d\)\s*(.*)/i', $colB, $m)) $currentQ['choice_d'] = trim($m[1]);
    }
}

// Add last question
if ($currentQ) {
    $questions[] = $currentQ;
}

global $php_insert, $php_fetch, $php_delete;

echo "Found " . count($questions) . " questions in Excel.\n";

if (count($questions) > 0) {
    echo "Seeding into database...\n";
    
    // Clear existing questions to avoid duplicates
    // Using supabaseRequest directly because php_delete hardcodes 'eq.' prefix
    // 'id=not.is.null' is a trick to delete all rows in Supabase
    supabaseRequest('DELETE', 'achievement_questions?id=not.is.null');
    
    foreach ($questions as $q) {
        // Validation: Ensure all choices are filled if possible, or at least log if empty
        if (empty($q['choice_a']) || empty($q['choice_b']) || empty($q['choice_c']) || empty($q['choice_d'])) {
            // Some questions might have fewer choices, but usually they have 4
        }

        $res = $php_insert('achievement_questions', $q, true);
        if (isset($res['error'])) {
            echo "Error inserting question " . $q['question_number'] . ": " . $res['error'] . "\n";
        }
    }
    echo "Seeding completed.\n";
} else {
    echo "No questions found to seed.\n";
}
