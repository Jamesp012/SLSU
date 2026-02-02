$(document).ready(function() {
    // Initialize DataTable for Student Profiles
    const studentsTable = $('#studentsTable').DataTable({
        "ajax": "../../controllers/admin_contr.php?action=fetch_students",
        "columns": [
            { "data": "lrn" },
            { 
                "data": null,
                "render": function(data, type, row) {
                    return `${row.first_name} ${row.middle_name ? row.middle_name[0] + '.' : ''} ${row.last_name}`;
                }
            },
            { "data": "email" },
            { "data": "recent_school" },
            { "data": "preferred_track" },
            {
                "data": null,
                "render": function(data, type, row) {
                    return `
                        <button class="btn btn-sm btn-info edit-btn" data-id="${row.id}"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}"><i class="fas fa-trash"></i></button>
                    `;
                }
            }
        ],
        "responsive": true,
        "drawCallback": function(settings) {
            $('#totalStudents').text(settings.json ? settings.json.data.length : 0);
        }
    });

    // Add Student Form Submission
    $('#addStudentForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        Swal.fire({
            title: 'Confirm Registration',
            text: "Are you sure you want to add this student?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, register student'
        }).then((result) => {
            if (result.isConfirmed) {
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
                    }
                });
            }
        });
    });
});
