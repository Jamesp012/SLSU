<?php
session_start();
// Check if admin is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

require_once '../includes/header.php';
require_once __DIR__ . '/../../models/STEMQuestionModel.php';

$questionModel = new STEMQuestionModel();
$pathways = $questionModel->getAllPathways();

$tableError = false;
if (isset($pathways['error'])) {
    $tableError = $pathways['message'] ?? 'Database tables missing';
    $pathways = [];
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Interest-Based Assessment Questions</h1>
    <?php if (!$tableError): ?>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#questionModal" id="addNewBtn">
            <i class="fas fa-plus me-2"></i> Add New Question
        </button>
    </div>
    <?php endif; ?>
</div>

<?php if ($tableError): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Setup Required:</strong> The database tables for STEM Questions have not been created yet. 
    Please run the provided SQL in your Supabase SQL Editor to continue.
</div>
<?php endif; ?>

<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i> 
    <strong>Pattern Note:</strong> Questions are asked alternately per pathway. 
    (e.g., Q1, Q13, Q25, Q37, Q49 belong to Pathway 1: <?php echo $pathways[0]['name'] ?? 'N/A'; ?>)
</div>

<!-- Questions Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Manage Questions</h6>
        <div class="col-md-4">
            <select id="pathwayFilter" class="form-select form-select-sm">
                <option value="">All Pathways</option>
                <?php foreach ($pathways as $pw): ?>
                    <option value="<?php echo $pw['id']; ?>"><?php echo htmlspecialchars($pw['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="questionsTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th width="10%">Q#</th>
                        <th width="25%">Pathway</th>
                        <th>Question Text</th>
                        <th width="15%">Actions</th>
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
                <h5 class="modal-title" id="questionModalLabel">Add Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="questionForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_question">
                    <input type="hidden" name="question_id" id="question_id" value="">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="pathway_id" class="form-label">Pathway</label>
                            <select class="form-select" id="pathway_id" name="pathway_id" required>
                                <option value="">Select Pathway...</option>
                                <?php foreach ($pathways as $pw): ?>
                                    <option value="<?php echo $pw['id']; ?>"><?php echo htmlspecialchars($pw['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="question_number" class="form-label">Question Number (1-240)</label>
                            <input type="number" class="form-control" id="question_number" name="question_number" min="1" max="240" required>
                            <div class="form-text">Follow the 1, 13, 25 pattern for consistency.</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="question_text" class="form-label">Question Text</label>
                        <textarea class="form-control" id="question_text" name="question_text" rows="3" required placeholder="e.g., I enjoy solving complex logic problems using code."></textarea>
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
            "url": "../../controllers/admin_contr.php?action=fetch_questions",
            "data": function(d) {
                d.pathway_id = $('#pathwayFilter').val();
            }
        },
        "columns": [
            { "data": "question_number" },
            { 
                "data": "stem_pathways",
                "render": function(data) {
                    return data ? data.name : 'N/A';
                }
            },
            { "data": "question_text" },
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

    $('#pathwayFilter').on('change', function() {
        questionsTable.ajax.reload();
    });

    // Reset modal on add
    $('#addNewBtn').on('click', function() {
        $('#questionModalLabel').text('Add New Question');
        $('#questionForm')[0].reset();
        $('#question_id').val('');
        $('input[name="action"]').val('add_question');
    });

    // Edit Question
    $(document).on('click', '.edit-q-btn', function() {
        const data = $(this).data('json');
        $('#questionModalLabel').text('Edit Question');
        $('#question_id').val(data.id);
        $('#pathway_id').val(data.pathway_id);
        $('#question_number').val(data.question_number);
        $('#question_text').val(data.question_text);
        $('input[name="action"]').val('update_question');
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
                    data: { action: 'delete_question', id: id },
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
