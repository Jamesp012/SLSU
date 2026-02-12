<?php
// helpers/CareerHelper.php

class CareerHelper {
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
                $courses = self::getCoursesByPathway($pathwayName);
                $electives = self::getElectivesByPathway($pathwayName);
                $recommendations[] = [
                    'pathway' => $pathwayName,
                    'stanine' => $data['stanine'],
                    'courses' => $courses,
                    'academic_electives' => $electives['academic'],
                    'techpro_electives' => $electives['techpro'],
                    'cluster' => $electives['cluster']
                ];
            }
            return $recommendations;
        }

        if ($track === 'STEM') {
            $electives = self::getElectivesByPathway('General STEM');
            return [
                [
                    'pathway' => 'General STEM', 
                    'courses' => ['Computer Science', 'Information Technology', 'Engineering', 'Nursing', 'Biology', 'Education'],
                    'academic_electives' => $electives['academic'],
                    'techpro_electives' => $electives['techpro'],
                    'cluster' => $electives['cluster']
                ]
            ];
        }

        $courses = self::getGeneralCoursesByTrack($track);
        return [
            [
                'pathway' => $track, 
                'courses' => $courses,
                'academic_electives' => ['General Education'],
                'techpro_electives' => ['Basic Digital Literacy'],
                'cluster' => 'General Academics'
            ]
        ];
    }

    private static function getElectivesByPathway($pathway) {
        $mapping = [
            'Computer Science' => [
                'cluster' => 'ICT and Engineering',
                'academic' => ['Discrete Mathematics', 'Programming Logic'],
                'techpro' => ['Python Programming', 'Web Development']
            ],
            'Information Technology' => [
                'cluster' => 'ICT',
                'academic' => ['Computer Concepts', 'Network Fundamentals'],
                'techpro' => ['System Administration', 'Database Management']
            ],
            'Civil Engineering' => [
                'cluster' => 'Engineering',
                'academic' => ['General Physics 1 & 2', 'Pre-Calculus'],
                'techpro' => ['AutoCAD', 'Structural Modeling']
            ],
            'Mechanical Engineering' => [
                'cluster' => 'Engineering',
                'academic' => ['General Physics', 'Basic Calculus'],
                'techpro' => ['Machine Shop', 'Thermodynamics Lab']
            ],
            'Electrical Engineering' => [
                'cluster' => 'Engineering',
                'academic' => ['General Physics 2', 'Circuit Theory'],
                'techpro' => ['Electrical Wiring', 'Electronics']
            ],
            'Nursing' => [
                'cluster' => 'Health Sciences',
                'academic' => ['Anatomy and Physiology', 'General Biology 2'],
                'techpro' => ['First Aid and Basic Life Support', 'Caregiving']
            ],
            'Medical Technology / Medical Laboratory Science' => [
                'cluster' => 'Health Sciences',
                'academic' => ['Organic Chemistry', 'Microbiology'],
                'techpro' => ['Laboratory Techniques', 'Phlebotomy']
            ],
            'Pharmacy' => [
                'cluster' => 'Health Sciences',
                'academic' => ['General Chemistry 2', 'Botany'],
                'techpro' => ['Compounding', 'Pharmacology Basics']
            ],
            'Biology' => [
                'cluster' => 'Life Sciences',
                'academic' => ['General Biology 1 & 2', 'Genetics'],
                'techpro' => ['Microscopy', 'Field Biology']
            ],
            'Chemistry' => [
                'cluster' => 'Physical Sciences',
                'academic' => ['General Chemistry 1 & 2', 'Analytical Chemistry'],
                'techpro' => ['Laboratory Safety', 'Chemical Instrumentation']
            ],
            'Mathematics / Statistics' => [
                'cluster' => 'Mathematical Sciences',
                'academic' => ['Pre-Calculus', 'Basic Calculus'],
                'techpro' => ['Statistical Software (SPSS/R)', 'Data Analysis']
            ],
            'Education major in Sciences' => [
                'cluster' => 'Education',
                'academic' => ['Earth Science', 'Physical Science'],
                'techpro' => ['Teaching Strategies', 'Instructional Media']
            ],
            'General STEM' => [
                'cluster' => 'Science and Technology',
                'academic' => ['Pre-Calculus', 'General Biology'],
                'techpro' => ['Basic Programming', 'Scientific Research']
            ]
        ];

        foreach ($mapping as $key => $val) {
            if (stripos($pathway, $key) !== false || stripos($key, $pathway) !== false) {
                return $val;
            }
        }

        return [
            'cluster' => 'STEM General',
            'academic' => ['Advanced Science', 'Advanced Mathematics'],
            'techpro' => ['Technical Research', 'Technological Applications']
        ];
    }

    private static function getCoursesByPathway($pathway) {
        $stemPathways = [
            'Computer Science' => ['BS Computer Science', 'BS Information Systems', 'BS Data Science'],
            'Information Technology' => ['BS Information Technology', 'BS Cybersecurity', 'BS Web Development'],
            'Civil Engineering' => ['BS Civil Engineering', 'BS Architecture'],
            'Mechanical Engineering' => ['BS Mechanical Engineering', 'BS Automotive Engineering'],
            'Electrical Engineering' => ['BS Electrical Engineering', 'BS Electronics Engineering'],
            'Nursing' => ['BS Nursing'],
            'Medical Technology / Medical Laboratory Science' => ['BS Medical Technology', 'BS Physical Therapy'],
            'Pharmacy' => ['BS Pharmacy'],
            'Biology' => ['BS Biology', 'Doctor of Medicine (Pre-med)'],
            'Chemistry' => ['BS Chemistry', 'BS Chemical Engineering'],
            'Mathematics / Statistics' => ['BS Mathematics', 'BS Statistics', 'BS Actuarial Science'],
            'Education major in Sciences' => ['BSEd major in Science', 'BSEd major in Mathematics']
        ];

        foreach ($stemPathways as $key => $val) {
            if (stripos($pathway, $key) !== false || stripos($key, $pathway) !== false) {
                return $val;
            }
        }
        return ['Related BS / BA courses'];
    }

    private static function getGeneralCoursesByTrack($track) {
        switch ($track) {
            case 'ICT':
                return ['BS Information Technology', 'BS Computer Science', 'BS Information Systems'];
            case 'HE':
                return ['BS Hospitality Management', 'BS Nutrition and Dietetics', 'BS Tourism Management'];
            case 'ARTS AND DESIGN':
                return ['BFA Visual Arts', 'BS Architecture', 'AB Multimedia Arts'];
            case 'ABM':
                return ['BS Accountancy', 'BS Business Administration', 'BS Entrepreneurship'];
            case 'HUMSS':
                return ['AB Psychology', 'AB Political Science', 'BS Criminology', 'BS Social Work'];
            default:
                return ['General Academic Courses'];
        }
    }
}
