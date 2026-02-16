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
    <h1 class="h2">Students & Reports</h1>
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
                <h5 class="modal-title" id="modalTitle">Stanines Breakdown</h5>
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
                "render": function(data, type, row) {
                    if (!data) {
                        return `<div class="text-center"><button class="btn btn-sm btn-secondary disabled" title="No test data">View Stanines</button></div>`;
                    }
                    return `<div class="text-center"><button class="btn btn-sm btn-success view-sa-btn" data-json='${row.cognitive_stanines}'>View Stanines</button></div>`;
                }
            },
            { 
                "data": "cognitive_stanines",
                "render": function(data) {
                    let hasStem = false;
                    if (data) {
                        try {
                            const scores = typeof data === 'string' ? JSON.parse(data) : data;
                            hasStem = Object.keys(scores).some(key => !isNaN(key));
                        } catch(e) { console.error("STEM check error", e); }
                    }

                    if (!hasStem) {
                        return `<div class="text-center"><button class="btn btn-sm btn-secondary disabled" title="No STEM interest data">View Stanines</button></div>`;
                    }
                    
                    return `<div class="text-center"><button class="btn btn-sm btn-success view-stem-btn" data-json='${data}'>View Stanines</button></div>`;
                }
            },
            {
                "data": null,
                "render": function(data) {
                    let printBtn = '';
                    if (data.achievement_stanine) {
                        printBtn = `<a href="../user/print_result.php?student_id=${data.id}" target="_blank" class="btn btn-sm btn-info me-1" title="Print Result"><i class="fas fa-print"></i></a>`;
                    }
                    return `
                        ${printBtn}
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${data.id}" title="Delete Student"><i class="fas fa-trash"></i></button>
                    `;
                }
            }
        ],
        "order": [[0, "asc"]]
    });

    const getStanineInfo = (stanine) => {
        const map = {
            1: { range: '1 – 3', interpretation: 'Very Low' },
            2: { range: '4 – 10', interpretation: 'Low' },
            3: { range: '11 – 22', interpretation: 'Below Average' },
            4: { range: '23 – 39', interpretation: 'Slightly Below Average' },
            5: { range: '40 – 59', interpretation: 'Average' },
            6: { range: '60 – 76', interpretation: 'Slightly Above Average' },
            7: { range: '77 – 88', interpretation: 'Above Average' },
            8: { range: '89 – 95', interpretation: 'High' },
            9: { range: '96 – 99', interpretation: 'Very High' }
        };
        return map[stanine] || { range: 'N/A', interpretation: 'N/A' };
    };

    // Handle viewing STEM stanines
    $(document).on('click', '.view-stem-btn', function() {
        const scores = $(this).data('json');
        let html = '<div class="row">';
        
        // Convert object to array for sorting and filter for STEM (numeric keys)
        const stemScores = [];
        for (let key in scores) {
            if (!isNaN(key)) {
                stemScores.push(scores[key]);
            }
        }
        
        if (stemScores.length === 0) {
            html += '<div class="col-12 text-center py-4 text-muted">No STEM Interest data found.</div>';
        } else {
            stemScores.sort((a, b) => b.stanine - a.stanine);
            stemScores.forEach(score => {
                const stanine = score.stanine || 0;
                const info = getStanineInfo(stanine);
                const colorClass = stanine >= 7 ? 'bg-success' : (stanine >= 4 ? 'bg-warning' : 'bg-danger');
                const displayName = score.name.replace(/^STEM PATHWAY \d+\.\s*/i, '');
                
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="card bg-light h-100">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold small text-truncate" style="max-width: 180px;">${displayName}</h6>
                                    <div class="text-end">
                                        <span class="badge ${colorClass} text-white">Stanine: ${stanine}</span>
                                        <div class="text-muted" style="font-size: 0.65rem;">${info.interpretation}</div>
                                    </div>
                                </div>
                                <div class="progress mt-2" style="height: 6px;">
                                    <div class="progress-bar ${colorClass}" role="progressbar" style="width: ${stanine * 11}%"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted" style="font-size: 0.7rem;">Score: ${score.raw_score}/40</small>
                                    <small class="text-muted" style="font-size: 0.7rem;">Range: ${info.range}%</small>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
        }
        html += '</div>';
        $('#modalTitle').html('<i class="fas fa-microscope me-2"></i> STEM Interest Stanines');
        $('#stemStaninesContent').html(html);
        $('#stemStaninesModal').modal('show');
    });

    // Handle viewing S.A. Breakdown
    $(document).on('click', '.view-sa-btn', function() {
        let scores = $(this).data('json');
        
        // Handle double encoding if necessary
        if (typeof scores === 'string') {
            try {
                scores = JSON.parse(scores);
            } catch(e) { console.error("Parse error", e); }
        }

        let html = '<div class="row">';
        const saCategories = ['Scientific Ability', 'Verbal Comprehension', 'Numerical Ability'];
        let found = false;
        
        saCategories.forEach(cat => {
            if (scores && scores[cat]) {
                found = true;
                const score = scores[cat];
                const stanine = score.stanine || 0;
                const info = getStanineInfo(stanine);
                const colorClass = stanine >= 7 ? 'bg-success' : (stanine >= 4 ? 'bg-warning' : 'bg-danger');
                
                html += `
                    <div class="col-md-12 mb-3">
                        <div class="card border-left-primary shadow-sm">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0 fw-bold text-primary">${cat}</h6>
                                    <div class="text-end">
                                        <span class="badge ${colorClass} text-white fs-6">Stanine: ${stanine}</span>
                                        <div class="text-muted small">${info.interpretation}</div>
                                    </div>
                                </div>
                                <div class="progress mb-2" style="height: 12px;">
                                    <div class="progress-bar ${colorClass}" 
                                         role="progressbar" style="width: ${stanine * 11}%"></div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="small text-muted font-weight-bold">Raw Score: ${score.score}/${score.total}</span>
                                    <span class="small text-muted">Percentile Range: ${info.range}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        });
        
        if (!found) {
            html += `
                <div class="col-12 text-center py-4">
                    <i class="fas fa-exclamation-circle fa-3x text-warning mb-3 opacity-25"></i>
                    <p class="text-muted">No breakdown data found for this student. <br><small>The student may need to retake the test to save categorized scores.</small></p>
                </div>
            `;
        }
        
        html += '</div>';
        $('#modalTitle').html('<i class="fas fa-brain me-2"></i> Scholastic Ability Breakdown');
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
