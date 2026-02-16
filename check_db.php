<?php
/**
 * Database Schema Checker for SLSU Entrance Exam Portal
 * This script checks for the existence of required tables and columns.
 */

require_once __DIR__ . '/config/connection.php';

echo "<h1>Database Schema Check</h1>";

$required_tables = [
    'profiles',
    'stem_pathways',
    'stem_questions',
    'stem_scores',
    'pathway_stanines',
    'achievement_questions',
    'achievement_scores'
];

$required_columns = [
    'profiles' => ['achievement_stanine', 'cognitive_stanines', 'preferred_track', 'lrn', 'recent_school'],
    'achievement_scores' => ['category_scores']
];

foreach ($required_tables as $table) {
    echo "<h3>Checking table: <code>$table</code></h3>";
    
    // Check table existence by attempting to fetch 1 row
    $result = supabaseRequest('GET', $table, ['limit' => 1], true);
    
    if (isset($result['error'])) {
        echo "<p style='color:red;'>❌ Table <code>$table</code> is missing or inaccessible.</p>";
        echo "<pre>Error: " . json_encode($result) . "</pre>";
    } else {
        echo "<p style='color:green;'>✅ Table <code>$table</code> exists.</p>";
        
        // Check columns for this table if defined
        if (isset($required_columns[$table])) {
            foreach ($required_columns[$table] as $column) {
                // To check if a column exists, we try to select it specifically
                $col_check = supabaseRequest('GET', "$table?select=$column&limit=1", null, true);
                
                if (isset($col_check['error']) && strpos(json_encode($col_check), 'column') !== false) {
                    echo "<p style='color:orange;'>⚠️ Column <code>$column</code> is missing in <code>$table</code>.</p>";
                } else {
                    echo "<p style='color:green;'>&nbsp;&nbsp;&nbsp;✅ Column <code>$column</code> exists.</p>";
                }
            }
        }
    }
    echo "<hr>";
}

echo "<h2>Required SQL to fix issues</h2>";
echo "<p>If any items above are missing, run this SQL in your Supabase SQL Editor:</p>";

$sql = "
-- 1. Ensure profiles table has required columns
ALTER TABLE public.profiles ADD COLUMN IF NOT EXISTS achievement_stanine INT;
ALTER TABLE public.profiles ADD COLUMN IF NOT EXISTS cognitive_stanines JSONB;
ALTER TABLE public.profiles ADD COLUMN IF NOT EXISTS preferred_track TEXT;
ALTER TABLE public.profiles ADD COLUMN IF NOT EXISTS lrn TEXT;
ALTER TABLE public.profiles ADD COLUMN IF NOT EXISTS recent_school TEXT;

-- 2. Create STEM Pathways table
CREATE TABLE IF NOT EXISTS public.stem_pathways (
    id BIGSERIAL PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 3. Create STEM Questions table
CREATE TABLE IF NOT EXISTS public.stem_questions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    pathway_id BIGINT REFERENCES stem_pathways(id) ON DELETE CASCADE,
    question_number INT NOT NULL,
    question_text TEXT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 4. Create STEM Scores table (Interest-Based Assessment Results)
CREATE TABLE IF NOT EXISTS public.stem_scores (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    student_id UUID REFERENCES profiles(id) ON DELETE CASCADE,
    top_pathway TEXT NOT NULL,
    max_score INT NOT NULL,
    all_scores JSONB NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 5. Create Pathway Stanines table (Individual rows for each pathway result)
CREATE TABLE IF NOT EXISTS public.pathway_stanines (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    student_id UUID REFERENCES profiles(id) ON DELETE CASCADE,
    pathway_id BIGINT REFERENCES stem_pathways(id) ON DELETE CASCADE,
    pathway_name TEXT,
    stanine INT NOT NULL,
    raw_score INT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE(student_id, pathway_id)
);

-- 6. Create Scholastic Ability (Achievement) Questions table
CREATE TABLE IF NOT EXISTS public.achievement_questions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    question_number INT NOT NULL,
    category TEXT NOT NULL,
    question_text TEXT NOT NULL,
    choice_a TEXT NOT NULL,
    choice_b TEXT NOT NULL,
    choice_c TEXT NOT NULL,
    choice_d TEXT NOT NULL,
    correct_answer TEXT NOT NULL,
    competency TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 7. Create Scholastic Ability (Achievement) Scores table
CREATE TABLE IF NOT EXISTS public.achievement_scores (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    student_id UUID REFERENCES profiles(id) ON DELETE CASCADE,
    score INT NOT NULL,
    total_questions INT NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    stanine INT,
    category_scores JSONB,
    is_passed BOOLEAN NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Ensure existing table has the new column
ALTER TABLE public.achievement_scores ADD COLUMN IF NOT EXISTS category_scores JSONB;
";

echo "<pre style='background:#f4f4f4; padding:15px; border:1px solid #ddd;'>" . htmlspecialchars($sql) . "</pre>";
?>
