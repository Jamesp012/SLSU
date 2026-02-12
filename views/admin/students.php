<?php
session_start();
// Check if admin is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

require_once '../includes/header.php';
require_once __DIR__ . '/../../models/StudentModel.php';

$studentModel = new StudentModel();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Students</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
            <i class="fas fa-plus me-2"></i> Add New Student
        </button>
    </div>
</div>

<!-- Students Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Registered Students</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="studentsTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Preferred Track</th>
                        <th>S.A. Stanine</th>
                        <th>STEM Stanines</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStudentModalLabel">Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addStudentForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_student">
                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="middle_name" class="form-label">Middle Name</label>
                        <input type="text" class="form-control" id="middle_name" name="middle_name">
                    </div>
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="alert alert-info py-2">
                        <small><i class="fas fa-info-circle me-1"></i> A temporary password will be generated and sent to the student's email.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveStudentBtn">Add Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View STEM Stanines Modal -->
<div class="modal fade" id="stemStaninesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">STEM Interest Stanines</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="stemStaninesContent"></div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    const studentsTable = $('#studentsTable').DataTable({
        "ajax": "../../controllers/admin_contr.php?action=fetch_students",
        "columns": [
            { 
                "data": null,
                "render": function(data) {
                    return `${data.last_name}, ${data.first_name} ${data.middle_name || ''}`;
                }
            },
            { "data": "email" },
            { 
                "data": "preferred_track",
                "render": function(data) {
                    return data ? `<span class="badge bg-primary">${data}</span>` : '<span class="text-muted">Not Selected</span>';
                }
            },
            { 
                "data": "achievement_stanine",
                "render": function(data) {
                    return data ? `<div class="text-center fw-bold fs-5">${data}</div>` : '<div class="text-center text-muted">-</div>';
                }
            },
            { 
                "data": "cognitive_stanines",
                "render": function(data) {
                    if (!data) return '<div class="text-center text-muted">No Data</div>';
                    return `<button class="btn btn-sm btn-outline-info view-stem-btn" data-json='${data}'>View Stanines</button>`;
                }
            },
            {
                "data": null,
                "render": function(data) {
                    return `
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${data.id}"><i class="fas fa-trash"></i></button>
                    `;
                }
            }
        ],
        "order": [[0, "asc"]]
    });

    // Handle viewing STEM stanines
    $(document).on('click', '.view-stem-btn', function() {
        const scores = $(this).data('json');
        let html = '<div class="row">';
        
        // Convert object to array for sorting
        const scoresArray = Object.values(scores);
        scoresArray.sort((a, b) => b.stanine - a.stanine);

        scoresArray.forEach(score => {
            const colorClass = score.stanine >= 7 ? 'text-success' : (score.stanine >= 4 ? 'text-warning' : 'text-danger');
            html += `
                <div class="col-md-6 mb-3">
                    <div class="d-flex justify-content-between border-bottom pb-1">
                        <span>${score.name}</span>
                        <span class="fw-bold ${colorClass}">Stanine: ${score.stanine}</span>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        $('#stemStaninesContent').html(html);
        $('#stemStaninesModal').modal('show');
    });

    $('#addStudentForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        $('#saveStudentBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');

        $.ajax({
            url: '../../controllers/admin_contr.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire('Success!', response.message, 'success');
                    $('#addStudentModal').modal('hide');
                    $('#addStudentForm')[0].reset();
                    studentsTable.ajax.reload();
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error!', 'Could not connect to the server.', 'error');
            },
            complete: function() {
                $('#saveStudentBtn').prop('disabled', false).html('Add Student');
            }
        });
    });

    // Handle student deletion
    $(document).on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete the student profile!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../../controllers/admin_contr.php',
                    type: 'POST',
                    data: {
                        action: 'delete_student',
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire('Deleted!', response.message, 'success');
                            studentsTable.ajax.reload();
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
});
</script>
