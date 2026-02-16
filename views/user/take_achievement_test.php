<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../../index.php");
    exit();
}

require_once __DIR__ . '/../../models/AchievementQuestionModel.php';
require_once __DIR__ . '/../../models/AchievementScoreModel.php';
require_once __DIR__ . '/../../models/StudentModel.php';

$questionModel = new AchievementQuestionModel();
$scoreModel = new AchievementScoreModel();
$studentModel = new StudentModel();

// Check if already taken
$email = $_SESSION['email'];
$student = $studentModel->getStudentByEmail($email);

if ($student && $scoreModel->hasTakenTest($student['id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once '../includes/header.php';

$questions = $questionModel->getAllQuestions();
if (isset($questions['error'])) {
    $error = "Scholastic Ability Test table not found. Please contact administrator to run the setup SQL.";
    $questions = [];
}

// Group questions by category
$categories = [];
foreach ($questions as $q) {
    $categories[$q['category']][] = $q;
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg border-0 rounded-lg">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h2 class="mb-0">Scholastic Ability Test</h2>
                    <p class="mb-0 mt-2">Please answer all questions carefully. Passing criteria: Stanine 4 or greater.</p>
                    
                    <!-- DEBUG BUTTONS -->
                    <div class="mt-3 no-print">
                        <button type="button" id="debugPass" class="btn btn-sm btn-success border-white me-2">Debug: Pass (High Score)</button>
                        <button type="button" id="debugFail" class="btn btn-sm btn-danger border-white">Debug: Fail (Low Score)</button>
                    </div>
                </div>
                <div class="card-body p-5">


                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger text-center">
                            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
                        </div>
                        <div class="text-center mt-4">
                            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                        </div>
                    <?php else: ?>
                        <form id="achievementTestForm">
                            <input type="hidden" name="action" value="submit_test">
                            
                            <?php 
                            $globalCounter = 1;
                            foreach ($categories as $categoryName => $catQuestions): 
                            ?>
                                <h4 class="text-primary border-bottom pb-2 mb-4 mt-5"><?php echo $categoryName; ?></h4>
                                
                                <?php foreach ($catQuestions as $q): ?>
                                    <div class="mb-5 p-4 border rounded bg-light">
                                        <p class="fw-bold mb-3">
                                            <?php echo $globalCounter++; ?>. <?php echo nl2br(htmlspecialchars($q['question_text'])); ?>
                                        </p>
                                        
                                        <div class="ms-3">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]" value="a" id="q_<?php echo $q['id']; ?>_a" required>
                                                <label class="form-check-label" for="q_<?php echo $q['id']; ?>_a">
                                                    a) <?php echo htmlspecialchars($q['choice_a']); ?>
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]" value="b" id="q_<?php echo $q['id']; ?>_b">
                                                <label class="form-check-label" for="q_<?php echo $q['id']; ?>_b">
                                                    b) <?php echo htmlspecialchars($q['choice_b']); ?>
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]" value="c" id="q_<?php echo $q['id']; ?>_c">
                                                <label class="form-check-label" for="q_<?php echo $q['id']; ?>_c">
                                                    c) <?php echo htmlspecialchars($q['choice_c']); ?>
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]" value="d" id="q_<?php echo $q['id']; ?>_d">
                                                <label class="form-check-label" for="q_<?php echo $q['id']; ?>_d">
                                                    d) <?php echo htmlspecialchars($q['choice_d']); ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>

                            <div class="d-grid gap-2 mt-5">
                                <button type="submit" class="btn btn-primary btn-lg py-3 shadow">Submit Examination</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

    <style>
        /* Focus Mode: Hide Navigation */
        .sidebar, .navbar-top {
            display: none !important;
        }
        .main-content {
            margin-left: 0 !important;
            width: 100% !important;
            padding-top: 0 !important;
        }
        body {
            background-color: #f8f9fa;
        }

        .track-btn {
            transition: all 0.2s;
            border-width: 2px;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 120px;
        }
        .track-btn:hover {
            color: #FFD700 !important;
            border-color: #FFD700 !important;
            background-color: transparent !important;
        }
        .track-btn:hover i, .track-btn:hover span {
            color: #FFD700 !important;
        }
        .track-btn.active {
            background-color: #184226 !important;
            color: white !important;
            border-color: #184226 !important;
        }
        .track-btn.active i, .track-btn.active span {
            color: white !important;
        }
    </style>

<script>
$(document).ready(function() {
    const STORAGE_KEY = 'scholastic_ability_answers';

    // Restore answers from localStorage
    function restoreAnswers() {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            const answers = JSON.parse(saved);
            Object.keys(answers).forEach(name => {
                $(`input[name="${name}"][value="${answers[name]}"]`).prop('checked', true);
            });
        }
    }

    restoreAnswers();

    // DEBUG LOGIC
    const correctAnswers = <?php 
        $ansMap = [];
        foreach ($questions as $q) {
            $ansMap[$q['id']] = strtolower($q['correct_answer']);
        }
        echo json_encode($ansMap); 
    ?>;

    $('#debugPass').on('click', function() {
        $('input[type="radio"]').prop('checked', false);
        Object.keys(correctAnswers).forEach(qId => {
            const val = correctAnswers[qId].trim().toLowerCase();
            $(`input[name="answers[${qId}]"][value="${val}"]`).prop('checked', true);
        });
        // Scroll to top and submit
        window.scrollTo(0, 0);
        $('#achievementTestForm').submit();
    });

    $('#debugFail').on('click', function() {
        $('input[type="radio"]').prop('checked', false);
        Object.keys(correctAnswers).forEach(qId => {
            const correctVal = correctAnswers[qId].trim().toLowerCase();
            const wrongVal = (correctVal === 'a') ? 'b' : 'a';
            $(`input[name="answers[${qId}]"][value="${wrongVal}"]`).prop('checked', true);
        });
        window.scrollTo(0, 0);
        $('#achievementTestForm').submit();
    });

    // Save answer to localStorage on change
    $('input[type="radio"]').on('change', function() {
        const saved = localStorage.getItem(STORAGE_KEY);
        const answers = saved ? JSON.parse(saved) : {};
        answers[$(this).attr('name')] = $(this).val();
        localStorage.setItem(STORAGE_KEY, JSON.stringify(answers));
    });



    function handleTestResponse(response) {
        if (response.status === 'success') {
            localStorage.removeItem(STORAGE_KEY);
            const isPassed = response.is_passed;
            const stanine = response.stanine;
            const score = response.score;
            const total = response.total;
            const recommendations = response.recommendations;
            const categoryScores = response.category_scores;

            let categoryHtml = '<div class="table-responsive mt-3 mb-4"><table class="table table-sm table-bordered small"><thead><tr class="bg-light"><th>Examination Part</th><th class="text-center">Raw Score</th><th class="text-center">Percentile</th><th class="text-center">Stanine</th></tr></thead><tbody>';
            for (const cat in categoryScores) {
                categoryHtml += `<tr>
                    <td>${cat}</td>
                    <td class="text-center">${categoryScores[cat].score} / ${categoryScores[cat].total}</td>
                    <td class="text-center">${categoryScores[cat].percentile}%</td>
                    <td class="text-center"><span class="badge bg-primary">${categoryScores[cat].stanine}</span></td>
                </tr>`;
            }
            categoryHtml += '</tbody></table></div>';

            let recommendationsHtml = '<ul class="list-group list-group-flush small">';
            if (recommendations && recommendations[0] && recommendations[0].courses) {
                recommendations[0].courses.forEach(course => {
                    recommendationsHtml += `<li class="list-group-item py-1 bg-transparent border-0"><i class="fas fa-graduation-cap me-2 text-primary"></i> ${course}</li>`;
                });
            }
            recommendationsHtml += '</ul>';

            // Result Modal
            Swal.fire({
                title: isPassed ? '<h2 class="text-success fw-bold">Congratulations!</h2>' : '<h2 class="text-primary fw-bold">Test Completed</h2>',
                html: `
                    <div class="py-4 text-center">
                        <div class="display-1 fw-bold mb-3 ${isPassed ? 'text-success' : 'text-primary'}">${stanine}</div>
                        <div class="h4 text-muted mb-4">Average Stanine</div>
                        
                        <h6 class="fw-bold text-dark text-start">Score Breakdown:</h6>
                        ${categoryHtml}

                        <div class="row justify-content-center mb-4">
                            <div class="col-6">
                                <div class="p-3 bg-white rounded shadow-sm border">
                                    <div class="h2 mb-0 fw-bold">${score} / ${total}</div>
                                    <div class="small text-muted text-uppercase">Raw Score</div>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 border rounded bg-light mb-4 text-start">
                            <h5 class="fw-bold text-primary"><i class="fas fa-info-circle me-2"></i> ${isPassed ? 'Eligibility: STEM Strand' : 'Eligibility: Technical-Vocational / Academic'}</h5>
                            <p class="mb-3 text-secondary small">
                                ${isPassed 
                                    ? 'You have qualified for the STEM strand! You may now proceed to the Interest-Based Assessment.' 
                                    : 'Your score suggests that the Technical-Vocational track or other Academic Strands are the most suitable pathways for you.'}
                            </p>
                            <hr>
                            <h6 class="fw-bold text-dark small mb-2">Recommended Career Pathways:</h6>
                            ${recommendationsHtml}
                        </div>
                    </div>
                `,
                width: '600px',
                padding: '1rem',
                confirmButtonText: 'Go to Dashboard',
                confirmButtonColor: '#184226',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'dashboard.php';
                }
            });
        } else {
            Swal.fire('Error!', response.message, 'error');
        }
    }
    
    $('#achievementTestForm').on('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to submit your answers?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#184226',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, submit it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = $(this).serialize();
                
                Swal.fire({
                    title: 'Submitting Test',
                    text: 'Please wait...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: '../../controllers/achievement_contr.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        handleTestResponse(response);
                    },
                    error: function() {
                        Swal.fire('Error!', 'Could not connect to the server.', 'error');
                    }
                });
            }
        });
    });
});
</script>
