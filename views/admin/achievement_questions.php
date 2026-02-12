<?php
session_start();
// Check if admin is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

require_once '../includes/header.php';
require_once __DIR__ . '/../../models/AchievementQuestionModel.php';

$questionModel = new AchievementQuestionModel();
$allQuestions = $questionModel->getAllQuestions();

// Extract unique categories for filter
$categories = [];
if (!isset($allQuestions['error'])) {
    foreach ($allQuestions as $q) {
        if (!in_array($q['category'], $categories)) {
            $categories[] = $q['category'];
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Scholastic Ability Test Questions</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#questionModal" id="addNewBtn">
            <i class="fas fa-plus me-2"></i> Add New Question
        </button>
    </div>
</div>

<!-- Questions Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Manage Scholastic Ability Questions</h6>
        <div class="col-md-4">
            <select id="categoryFilter" class="form-select form-select-sm">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="questionsTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th width="8%">Q#</th>
                        <th width="15%">Category</th>
                        <th>Question Text</th>
                        <th width="10%">Answer</th>
                        <th width="12%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Question Modal -->
<div class="modal fade" id="questionModal" tabindex="-1" aria-labelledby="questionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="questionModalLabel">Add Scholastic Ability Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="questionForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_achievement_question">
                    <input type="hidden" name="question_id" id="question_id" value="">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="category" name="category" list="categoryList" required>
                            <datalist id="categoryList">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="question_number" class="form-label">Question Number</label>
                            <input type="number" class="form-control" id="question_number" name="question_number" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="question_text" class="form-label">Question Text</label>
                        <textarea class="form-control" id="question_text" name="question_text" rows="2" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="option_a" class="form-label">Option A</label>
                            <input type="text" class="form-control" id="option_a" name="option_a" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="option_b" class="form-label">Option B</label>
                            <input type="text" class="form-control" id="option_b" name="option_b" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="option_c" class="form-label">Option C</label>
                            <input type="text" class="form-control" id="option_c" name="option_c" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="option_d" class="form-label">Option D</label>
                            <input type="text" class="form-control" id="option_d" name="option_d" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="correct_answer" class="form-label">Correct Answer (A/B/C/D)</label>
                        <select class="form-select" id="correct_answer" name="correct_answer" required>
                            <option value="">Select...</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Save Question</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const questionsTable = $('#questionsTable').DataTable({
        "ajax": {
            "url": "../../controllers/admin_contr.php?action=fetch_achievement_questions",
            "data": function(d) {
                d.category = $('#categoryFilter').val();
            }
        },
        "columns": [
            { "data": "question_number" },
            { "data": "category" },
            { "data": "question_text" },
            { 
                "data": "correct_answer",
                "render": function(data) {
                    return `<span class="badge bg-success">${data}</span>`;
                }
            },
            {
                "data": null,
                "render": function(data, type, row) {
                    return `
                        <button class="btn btn-sm btn-info edit-q-btn" data-id="${row.id}" data-json='${JSON.stringify(row)}'><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger delete-q-btn" data-id="${row.id}"><i class="fas fa-trash"></i></button>
                    `;
                }
            }
        ],
        "order": [[0, "asc"]],
        "responsive": true
    });

    $('#categoryFilter').on('change', function() {
        questionsTable.ajax.reload();
    });

    // Reset modal on add
    $('#addNewBtn').on('click', function() {
        $('#questionModalLabel').text('Add Scholastic Ability Question');
        $('#questionForm')[0].reset();
        $('#question_id').val('');
        $('input[name="action"]').val('add_achievement_question');
    });

    // Edit Question
    $(document).on('click', '.edit-q-btn', function() {
        const data = $(this).data('json');
        $('#questionModalLabel').text('Edit Scholastic Ability Question');
        $('#question_id').val(data.id);
        $('#category').val(data.category);
        $('#question_number').val(data.question_number);
        $('#question_text').val(data.question_text);
        $('#option_a').val(data.option_a);
        $('#option_b').val(data.option_b);
        $('#option_c').val(data.option_c);
        $('#option_d').val(data.option_d);
        $('#correct_answer').val(data.correct_answer.toUpperCase());
        $('input[name="action"]').val('update_achievement_question');
        $('#questionModal').modal('show');
    });

    // Form Submission
    $('#questionForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.ajax({
            url: '../../controllers/admin_contr.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire('Success!', response.message, 'success');
                    $('#questionModal').modal('hide');
                    questionsTable.ajax.reload();
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error!', 'Could not connect to the server.', 'error');
            }
        });
    });

    // Delete Question
    $(document).on('click', '.delete-q-btn', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: "This question will be removed permanently.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#184226',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../../controllers/admin_contr.php',
                    type: 'POST',
                    data: { action: 'delete_achievement_question', id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire('Deleted!', response.message, 'success');
                            questionsTable.ajax.reload();
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    }
                });
            }
        });
    });
});
</script>
