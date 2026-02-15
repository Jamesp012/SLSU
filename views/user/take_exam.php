<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../../index.php");
    exit();
}

require_once __DIR__ . '/../../models/STEMQuestionModel.php';
require_once __DIR__ . '/../../models/STEMScoreModel.php';
require_once __DIR__ . '/../../models/StudentModel.php';
require_once __DIR__ . '/../../models/AchievementScoreModel.php';

$questionModel = new STEMQuestionModel();
$scoreModel = new STEMScoreModel();
$studentModel = new StudentModel();
$achievementScoreModel = new AchievementScoreModel();

$email = $_SESSION['email'];
$student = $studentModel->getStudentByEmail($email);

// Must pass Scholastic Ability Test first
if ($student) {
    $achievementScore = $achievementScoreModel->getScoreByStudentId($student['id']);
    if (!$achievementScore || !$achievementScore['is_passed']) {
        header("Location: dashboard.php");
        exit();
    }
}

// Check if already taken
if ($student && $scoreModel->hasTakenTest($student['id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once '../includes/header.php';
?>
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
        transition: all 0.3s ease;
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
<?php
$questions = $questionModel->getAllQuestions();
if (isset($questions['error'])) {
    $error = "STEM test questions not found. Please contact administrator.";
    $questions = [];
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg border-0 rounded-lg">
                <div class="card-header bg-success text-white text-center py-4">
                    <h2 class="mb-0">Interest-Based Assessment</h2>
                    <p class="mb-0 mt-2">STEM Strand Admission Exam</p>
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
                        <div class="alert alert-info mb-4 d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-info-circle me-2"></i> This test will help determine your aptitude for various STEM pathways.
                            </div>
                        </div>

                        <form id="stemTestForm">
                            <input type="hidden" name="action" value="submit_test">
                            
                            <?php foreach ($questions as $index => $q): ?>
                                <div class="mb-5 p-4 border rounded bg-light">
                                    <p class="fw-bold mb-3">
                                        <?php echo ($index + 1); ?>. <?php echo htmlspecialchars($q['question_text']); ?>
                                        <?php if(isset($q['stem_pathways']['name'])): ?>
                                            <span class="badge bg-secondary float-end"><?php echo htmlspecialchars($q['stem_pathways']['name']); ?></span>
                                        <?php endif; ?>
                                    </p>
                                    
                                    <div class="ms-3">
                                        <?php 
                                            $choices = [
                                                'like' => 'Like',
                                                'neutral' => 'Neutral',
                                                'dislike' => 'Dislike'
                                            ];
                                            foreach ($choices as $val => $text): 
                                        ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]" value="<?php echo $val; ?>" id="q_<?php echo $q['id'] . '_' . $val; ?>" required>
                                                <label class="form-check-label" for="q_<?php echo $q['id'] . '_' . $val; ?>">
                                                    <?php echo $text; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="d-grid gap-2 mt-5">
                                <button type="submit" class="btn btn-success btn-lg py-3 shadow">Submit Interest-Based Assessment</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    const STORAGE_KEY = 'interest_assessment_answers';

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

    // Save answer to localStorage on change
    $('input[type="radio"]').on('change', function() {
        const saved = localStorage.getItem(STORAGE_KEY);
        const answers = saved ? JSON.parse(saved) : {};
        answers[$(this).attr('name')] = $(this).val();
        localStorage.setItem(STORAGE_KEY, JSON.stringify(answers));
    });



    $('#stemTestForm').on('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Submit Interest-Based Assessment?',
            text: "Please make sure you have answered all questions.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
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
                    url: '../../controllers/stem_test_contr.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            localStorage.removeItem(STORAGE_KEY);
                            let recommendationsHtml = '<div class="text-start mt-4">';
                            response.recommendations.forEach((rec, idx) => {
                                recommendationsHtml += `
                                    <div class="mb-3 p-2 border rounded bg-white shadow-sm">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold text-primary small">#${idx+1} ${rec.pathway}</span>
                                            <span class="badge bg-success">Stanine: ${rec.stanine}</span>
                                        </div>
                                        <div class="small text-muted" style="font-size: 0.75rem;">Courses: ${rec.courses.slice(0, 3).join(', ')}...</div>
                                    </div>
                                `;
                            });
                            recommendationsHtml += '</div>';

                            Swal.fire({
                                icon: 'success',
                                title: 'Test Completed!',
                                html: `
                                    <div class="py-2 text-center">
                                        <div class="mb-2">
                                            <i class="fas fa-award fa-3x text-warning"></i>
                                        </div>
                                        <h5 class="fw-bold">Your STEM Interest Profile</h5>
                                        ${recommendationsHtml}
                                        <hr class="my-2">
                                        <p class="small">Your results have been saved. You can now proceed to select your track.</p>
                                    </div>
                                `,
                                width: '550px',
                                confirmButtonText: 'Proceed to Track Selection',
                                confirmButtonColor: '#184226',
                                allowOutsideClick: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    showTrackSelection();
                                }
                            });
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Could not connect to the server.', 'error');
                    }
                });
            }
        });
    });

    function showTrackSelection() {
        Swal.fire({
            title: 'Final Track Selection',
            html: `
                <div class="text-center mb-4">
                    <p class="text-muted">Based on your interest assessment results, please confirm your final specialization track:</p>
                </div>
                <!-- Desktop View -->
                <div class="d-none d-md-grid gap-3" style="grid-template-columns: repeat(2, 1fr);">
                    <button class="btn btn-outline-primary p-3 track-btn" data-value="Arts, social sciences and Humanities">
                        <i class="fas fa-book-reader mb-2 fa-2x"></i>
                        <span>Arts, social sciences and Humanities</span>
                    </button>
                    <button class="btn btn-outline-primary p-3 track-btn" data-value="Science Technology, Engineering and Mathematics">
                        <i class="fas fa-atom mb-2 fa-2x"></i>
                        <span>Science Technology, Engineering and Mathematics</span>
                    </button>
                    <button class="btn btn-outline-primary p-3 track-btn" data-value="Sports, health and Wellness">
                        <i class="fas fa-heartbeat mb-2 fa-2x"></i>
                        <span>Sports, health and Wellness</span>
                    </button>
                    <button class="btn btn-outline-primary p-3 track-btn" data-value="Business and Entrepreneurship">
                        <i class="fas fa-briefcase mb-2 fa-2x"></i>
                        <span>Business and Entrepreneurship</span>
                    </button>
                    <button class="btn btn-outline-primary p-3 track-btn" data-value="Field Experience" style="grid-column: span 2;">
                        <i class="fas fa-map-marked-alt mb-2 fa-2x"></i>
                        <span>Field Experience</span>
                    </button>
                </div>
                <!-- Mobile View -->
                <div class="d-md-none">
                    <select id="mobileTrackSelect" class="form-select form-select-lg py-3">
                        <option value="">Choose a track...</option>
                        <option value="Arts, social sciences and Humanities">Arts, social sciences and Humanities</option>
                        <option value="Science Technology, Engineering and Mathematics">Science Technology, Engineering and Mathematics</option>
                        <option value="Sports, health and Wellness">Sports, health and Wellness</option>
                        <option value="Business and Entrepreneurship">Business and Entrepreneurship</option>
                        <option value="Field Experience">Field Experience</option>
                    </select>
                </div>
            `,
            showConfirmButton: true,
            confirmButtonText: 'Confirm Selection',
            confirmButtonColor: '#198754',
            width: '600px',
            allowOutsideClick: false,
            preConfirm: () => {
                let selected = '';
                if (window.innerWidth >= 768) {
                    selected = $('.track-btn.active').data('value');
                } else {
                    selected = $('#mobileTrackSelect').val();
                }
                
                if (!selected) {
                    Swal.showValidationMessage('Please select a track before proceeding');
                    return false;
                }
                return selected;
            },
            didOpen: () => {
                $('.track-btn').on('click', function() {
                    $('.track-btn').removeClass('active');
                    $(this).addClass('active');
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const track = result.value;
                
                Swal.fire({
                    title: 'Saving Selection',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: '../../controllers/achievement_contr.php', // Using existing update_track logic
                    type: 'POST',
                    data: {
                        action: 'update_track',
                        track: track
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Your specialization track has been saved.',
                                confirmButtonColor: '#198754'
                            }).then(() => {
                                window.location.href = 'dashboard.php';
                            });
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Could not connect to the server.', 'error');
                    }
                });
            }
        });
    }
});
</script>
