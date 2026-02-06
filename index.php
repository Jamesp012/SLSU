<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SLSU Entrance Exam Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #184226 0%, #0a1f12 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 2.5rem;
            border-radius: 1.5rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            background: white;
            border-top: 5px solid #f0b508;
        }
        .login-logo {
            font-size: 3.5rem;
            color: #184226;
            margin-bottom: 1rem;
        }
        .btn-primary {
            background-color: #184226;
            border-color: #184226;
        }
        .btn-primary:hover {
            background-color: #2d7a46;
            border-color: #2d7a46;
        }
        .form-control:focus {
            border-color: #184226;
            box-shadow: 0 0 0 0.25rem rgba(24, 66, 38, 0.25);
        }
    </style>
</head>
<body>
    <div class="login-card text-center">
        <div class="login-logo">
            <i class="fas fa-university"></i>
        </div>
        <h3 class="mb-4">Exam Portal Login</h3>
        
        <form id="loginForm">
            <div class="mb-3 text-start">
                <label for="role" class="form-label">I am a:</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="student">Student</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <div class="mb-3 text-start">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" autocomplete="email" required>
            </div>
            <div class="mb-3 text-start">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">Sign In</button>
        </form>
        
        <div class="mt-4 text-muted">
            <small>Don't have an account? Contact Admin</small>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = $(this).serialize();
                
                $.ajax({
                    url: 'controllers/auth_contr.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Login Successful',
                                text: 'Redirecting...',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = response.redirect;
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Login Failed',
                                text: response.message
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An unexpected error occurred.'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
