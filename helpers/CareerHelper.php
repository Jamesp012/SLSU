<?php
// helpers/CareerHelper.php

class CareerHelper {
    public static function getStanineInfo($stanine) {
        $map = [
            1 => ['range' => '1 – 3', 'interpretation' => 'Very Low'],
            2 => ['range' => '4 – 10', 'interpretation' => 'Low'],
            3 => ['range' => '11 – 22', 'interpretation' => 'Below Average'],
            4 => ['range' => '23 – 39', 'interpretation' => 'Slightly Below Average'],
            5 => ['range' => '40 – 59', 'interpretation' => 'Average'],
            6 => ['range' => '60 – 76', 'interpretation' => 'Slightly Above Average'],
            7 => ['range' => '77 – 88', 'interpretation' => 'Above Average'],
            8 => ['range' => '89 – 95', 'interpretation' => 'High'],
            9 => ['range' => '96 – 99', 'interpretation' => 'Very High']
        ];
        return $map[$stanine] ?? ['range' => 'N/A', 'interpretation' => 'N/A'];
    }

    public static function getRecommendations($track, $pathwayScores = null) {
        if ($pathwayScores && is_array($pathwayScores)) {
            // Sort by stanine, then raw_score descending
            uasort($pathwayScores, function($a, $b) {
                if ($b['stanine'] !== $a['stanine']) {
                    return $b['stanine'] - $a['stanine'];
                }
                return $b['raw_score'] - $a['raw_score'];
            });

            // Get top 3
            $top3 = array_slice($pathwayScores, 0, 3, true);
            $recommendations = [];
            foreach ($top3 as $pId => $data) {
                $pathwayName = $data['name'];
                $details = self::getPathwayDetails($pathwayName);
                $recommendations[] = [
                    'pathway' => $pathwayName,
                    'stanine' => $data['stanine'],
                    'description' => $details['description'],
                    'courses' => $details['courses'],
                    'academic_electives' => $details['academic'],
                    'techpro_electives' => $details['techpro'],
                    'cluster' => $details['cluster']
                ];
            }
            return $recommendations;
        }

        if ($track === 'Science Technology, Engineering and Mathematics') {
            $details = self::getPathwayDetails('General STEM');
            return [
                [
                    'pathway' => 'General STEM', 
                    'description' => $details['description'],
                    'courses' => $details['courses'],
                    'academic_electives' => $details['academic'],
                    'techpro_electives' => $details['techpro'],
                    'cluster' => $details['cluster']
                ]
            ];
        }

        $courses = self::getGeneralCoursesByTrack($track);
        return [
            [
                'pathway' => $track, 
                'description' => 'General academic and vocational track based on student preference.',
                'courses' => $courses,
                'academic_electives' => ['General Education'],
                'techpro_electives' => ['Basic Digital Literacy'],
                'cluster' => 'General Academics'
            ]
        ];
    }

    private static function getPathwayDetails($pathway) {
        $mapping = [
            'Computer Science' => [
                'description' => 'High aptitude in Logical Reasoning and Mathematical Ability with a Strong Inclination for Computational Problem-Solving and Digital Innovation. This suggests a preference for abstract design, algorithmic development, and systems thinking (Investigative & Conventional/Realistic).',
                'cluster' => 'ICT and Engineering',
                'courses' => ['Software Development', 'AI / Data Science', 'Digital Forensics', 'Computer Engineering'],
                'academic' => ['Finite Mathematics 1', 'Finite Mathematics 2', 'General Physics 1', 'General Physics 2', 'Business 1', 'Research Methods', 'Fundamentals of Data Analytics', 'Database Management', 'Design and Innovation'],
                'techpro' => ['Computer Programming (JAVA)', 'Computer Programming (ORACLE)', 'Computer Programming (.NET TECHNOLOGY)', 'Electronics Product Assembly and Servicing']
            ],
            'Information Technology' => [
                'description' => 'High scores in Technical-Vocational Aptitude (Visual Manipulative Skills & Clerical Ability) with a Strong Inclination for Systems Management and Security. This indicates strength in practical application, data organization, and network defense (Realistic & Conventional).',
                'cluster' => 'ICT',
                'courses' => ['IT', 'ICT Support', 'Computer Programming', 'Bioinformatics', 'Management Information System', 'Cybersecurity', 'Network Administration', 'Systems Analysis'],
                'academic' => ['Finite Mathematics 1 & 2', 'Business 1 & 2', 'Research Methods', 'Fundamentals of Data Analytics', 'Database Management', 'Design and Innovation'],
                'techpro' => ['Computer Programming (JAVA)', 'Computer Programming (ORACLE)', 'Computer Programming (.NET TECHNOLOGY)', 'Electronics Product Assembly and Servicing']
            ],
            'Civil Engineering' => [
                'description' => 'High Scientific Ability and Visual Manipulative Skills with a Strong Inclination for Infrastructure Planning and Large-Scale Physical Design. This reflects an interest in applied physics, sustainable design, and construction oversight (Realistic & Investigative).',
                'cluster' => 'Engineering',
                'courses' => ['Construction Management', 'Infrastructure', 'Environmental Engineering', 'System Engineering', 'Actuarial Science'],
                'academic' => ['Physics 1 & 2', 'Chemistry 1 & 2', 'Earth & Space Science 1 & 2', 'Finite Math'],
                'techpro' => ['Construction Operation', 'Sustainable Construction', 'Infrastructure Design', 'Technical Drafting', 'Visual graphic Design', 'Illustration', 'Computer Programing ( Java, .net, or oracle)']
            ],
            'Mechanical Engineering' => [
                'description' => 'High aptitude in Mathematical Ability and Scientific Ability with a Strong Inclination for Engineering Mechanics, Robotics, and Manufacturing Processes. This points to a preference for designing, building, and optimizing tangible mechanical products (Realistic & Investigative).',
                'cluster' => 'Engineering',
                'courses' => ['Manufacturing', 'Geodetic Engineering', 'Automotive', 'Robotics', 'Energy Systems', 'Construction Engineering & Management'],
                'academic' => ['General Physics 1 & 2', 'General Chemistry 1 & 2', 'Finite Mathematics 1 & 2', 'Earth and Space Science 1 & 2'],
                'techpro' => ['Automotive Servicing', 'Mechatronics', 'Robotics & Automation', 'Advanced Manufacturing', 'Manual Metal Arc Welding', 'Technical Drafting', 'Photovoltaic System Installation', 'Computer Programing ( Java, .net, or oracle)', 'Electronics Product Assembly and Servicing']
            ],
            'Electrical Engineering' => [
                'description' => 'High aptitude in Scientific Ability and Logical Reasoning with a Strong Inclination for Power Systems, Telecommunications, and Circuit Design. This suggests a core interest in the control and application of electrical energy and information transfer (Investigative & Realistic).',
                'cluster' => 'Engineering',
                'courses' => ['Power Systems', 'Electronics', 'Telecommunications', 'Industrial Engineering', 'Systems Engineering'],
                'academic' => ['General Physics 1 & 2', 'General Chemistry 1 & 2', 'Finite Mathematics'],
                'techpro' => ['Electronics product Assembly and Servicing', 'Mechatronics', 'Electrical System Installation', 'Manual', 'Metal Arc Welding', 'Power Systems', 'Electronic Design', 'Computer Programing ( Java, .net, or oracle)']
            ],
            'Nursing' => [
                'description' => 'High scores in Verbal Ability and Reading Comprehension with a Strong Inclination for Direct Patient Care, Wellness Advocacy, and Community Health. This aligns with a drive for social service, communication, and hands-on medical assistance (Social).',
                'cluster' => 'Health Sciences',
                'courses' => ['Healthcare', 'Medical Services', 'Public Health', 'Nutrition and Dietetics', 'Psychology', 'Physical Therapy (BSPT) / Occupational Therapy (BSOT)', 'Respiratory Therapy', 'Social Work'],
                'academic' => ['Chemistry 1 - 4', 'Biology 1-4', 'Field Exposure', 'Safety and First Aid', 'Research Methods', 'Empowerment Technologies', 'Work Immersion'],
                'techpro' => ['Caregiving (Child Care)', 'Caregiving (Adult care)']
            ],
            'Medical Technology / Medical Laboratory Science' => [
                'description' => 'High aptitude in Scientific Ability and Attention to Detail with a Strong Inclination for Laboratory Diagnostics and Clinical Analysis. This is indicative of a disciplined interest in investigative research, precision testing, and medical support (Investigative & Conventional).',
                'cluster' => 'Health Sciences',
                'courses' => ['Clinical Laboratory', 'Diagnostics', 'Research', 'Radiologic Technology', 'Environmental and Sanitary Engineering'],
                'academic' => ['Biology 1 & 2', 'Chemistry 1 & 2', 'Physics 1 & 2', 'Safety and First Aid', 'Fundamentals of data Analytics', 'Data management', 'Research Methods', 'Empowerment Technologies'],
                'techpro' => ['Illustration', 'Computer Programming (Java or .NET Technology)', 'Visual Graphic Design / Animation']
            ],
            'Pharmacy' => [
                'description' => 'High scores in Scientific Ability and Verbal Ability with a Strong Inclination for Drug Research, Medication Management, and Healthcare Consultation. This highlights a dual interest in the chemical science of drugs and patient interaction/education (Investigative & Social).',
                'cluster' => 'Health Sciences',
                'courses' => ['Pharmaceuticals', 'Drug Research', 'Healthcare', 'Clinical Research', 'Health Information Management (HIM)', 'Healthcare Administration / Management', 'Health Data Analytics'],
                'academic' => ['Pre-Calculus', 'Physics 1 & 2', 'Chemistry 1 & 2', 'Biology 1 & 2', 'Finite Mathematics 1 & 2', 'Business 1 & 2'],
                'techpro' => ['Medical transcription', 'Computer Programming (Java or .NET Technology) Visual Graphic Design / Animation']
            ],
            'Biology' => [
                'description' => 'High Scientific Ability and Moderate Inclination for Research, paired with a Strong Interest in Life Sciences, Biotechnology, and Environmental Studies. This is characteristic of a student interested in fundamental scientific inquiry and biological systems (Investigative).',
                'cluster' => 'Life Sciences',
                'courses' => ['Biotechnology', 'Environmental Science', 'Pre-Medicine', 'Molecular Biology', 'Biostatistics / Epidemiology', 'Nutrition and Dietetics', 'Health Information Management', 'Environmental and Sanitary Engineering'],
                'academic' => ['Biology 1 - 4', 'Chemistry 1 & 2', 'Physics 1 & 2', 'Pre-calculus and calculus', 'Empowerment Technologies', 'Research Methods'],
                'techpro' => ['Caregiving (Child Care)', 'Caregiving (Adult care)', 'Medical transcription', 'Computer Programming']
            ],
            'Chemistry' => [
                'description' => 'High aptitude in Scientific Ability and Logical Reasoning with a Strong Inclination for Chemical Analysis, Material Synthesis, and Industrial Quality Control. This suggests a primary interest in the structure and transformation of matter (Investigative & Realistic).',
                'cluster' => 'Physical Sciences',
                'courses' => ['Industrial Chemistry', 'Research', 'Quality Control', 'Medical Technology / Medical Laboratory Science', 'Radiologic Technology', 'Environmental and Sanitary Engineering', 'Nutrition and Dietetics', 'Biochemistry'],
                'academic' => ['Chemistry 1 - 4', 'Physics 1 & 2', 'Pre-calculus and calculus', 'Biology 1 & 2', 'research methods', 'Physics 1 & 2', 'Empowerment Technologies'],
                'techpro' => ['Aesthetic Services', 'Food Processing', 'Organic Agriculture Production', 'Computer Systems Servicing', 'Computer Programming (.NET or Java)']
            ],
            'Mathematics / Statistics' => [
                'description' => 'Excellent standing in Mathematical Ability and Logical Reasoning with a Strong Inclination for Abstract Modeling, Data Quantification, and Financial/Risk Analysis. This shows a preference for theoretical and applied quantitative reasoning (Investigative & Conventional).',
                'cluster' => 'Mathematical Sciences',
                'courses' => ['Applied Mathematics', 'Accountancy', 'Data Analysis', 'Actuarial Science', 'Education', 'Finance', 'Physics'],
                'academic' => ['Finite Mathematics 1 & 2', 'Trigonometry 1 & 2', 'Chemistry 1 & 2', 'Physics 1 & 2', 'Pre-calculus and calculus', 'Empowerment Technologies', 'Biology 1 & 2', 'research methods'],
                'techpro' => ['Computer Programming (.NET or Java)', 'Computer Systems Servicing']
            ],
            'Education major in Sciences' => [
                'description' => 'High Verbal Ability and Reading Comprehension with a Strong Inclination for STEM Teaching, Mentorship, and Curriculum Development. This reflects a passion for academic content delivery and social influence in an educational setting (Social & Investigative).',
                'cluster' => 'Education',
                'courses' => ['STEM Teaching', 'Curriculum Development', 'Educational Leadership'],
                'academic' => ['Chemistry 1 & 2', 'Physics 1 & 2', 'Pre-calculus and calculus', 'Biology 1 & 2', 'research methods', 'Physics 1 & 2', 'Empowerment Technologies'],
                'techpro' => ['animation', 'Illustration', 'Visual Graphic Design']
            ],
            'General STEM' => [
                'description' => 'General STEM aptitude suitable for various science and technology fields.',
                'cluster' => 'Science and Technology',
                'courses' => ['General Science', 'Integrated Technology'],
                'academic' => ['Pre-Calculus', 'General Biology', 'General Physics'],
                'techpro' => ['Basic Programming', 'Scientific Research']
            ]
        ];

        foreach ($mapping as $key => $val) {
            if (stripos($pathway, $key) !== false || stripos($key, $pathway) !== false) {
                return $val;
            }
        }

        return [
            'description' => 'A broad range of scientific and technical disciplines.',
            'cluster' => 'STEM General',
            'courses' => ['Related BS / BA courses'],
            'academic' => ['Advanced Science', 'Advanced Mathematics'],
            'techpro' => ['Technical Research', 'Technological Applications']
        ];
    }

    private static function getGeneralCoursesByTrack($track) {
        switch ($track) {
            case 'Science Technology, Engineering and Mathematics':
                return [];
            case 'Field Experience':
                return ['BS Hospitality Management', 'BS Nutrition and Dietetics', 'BS Tourism Management'];
            case 'Arts, social sciences and Humanities':
                return ['AB Psychology', 'AB Political Science', 'BS Criminology', 'BS Social Work', 'BFA Visual Arts'];
            case 'Business and Entrepreneurship':
                return ['BS Accountancy', 'BS Business Administration', 'BS Entrepreneurship'];
            case 'Sports, health and Wellness':
                return ['BS Nursing', 'BS Sports Science', 'BS Exercise Science', 'BS Physical Therapy'];
            default:
                return ['General Academic Courses'];
        }
    }
}
