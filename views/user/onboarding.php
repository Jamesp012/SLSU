<?php
session_start();
// Check if user is logged in and is a student
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../../index.php");
    exit();
}

// If already completed, redirect to dashboard
if (isset($_SESSION['onboarding_completed']) && $_SESSION['onboarding_completed']) {
    header("Location: dashboard.php");
    exit();
}

require_once '../includes/header.php'; ?>

<style>
    body {
        background-color: #f8f9fa;
    }
    .onboarding-container {
        max-width: 700px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    .onboarding-card {
        background: #ffffff;
        border: none;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }
    .card-header-custom {
        background-color: #ffffff;
        border-bottom: 1px solid #edf2f7;
        padding: 1.5rem 2rem;
    }
    .card-header-custom h4 {
        color: #1a202c;
        font-weight: 700;
        margin-bottom: 0;
    }
    .card-body-custom {
        padding: 2rem;
    }
    .consent-box {
        background-color: #f7fafc;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid #edf2f7;
    }
    .consent-text {
        color: #4a5568;
        font-size: 0.95rem;
        line-height: 1.7;
        text-align: justify;
    }
    .info-list {
        column-count: 2;
        column-gap: 20px;
        list-style-position: inside;
        padding-left: 0;
        color: #4a5568;
        font-size: 0.9rem;
    }
    @media (max-width: 576px) {
        .info-list {
            column-count: 1;
        }
        .onboarding-container {
            margin: 1rem auto;
        }
        .card-body-custom {
            padding: 1.5rem;
        }
    }
    .form-label {
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    .form-control-custom {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        padding: 0.75rem 1rem;
        font-size: 1rem;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-control-custom:focus {
        border-color: #184226;
        box-shadow: 0 0 0 3px rgba(24, 66, 38, 0.1);
        outline: none;
    }
    .btn-primary-custom {
        background-color: #184226;
        border: none;
        border-radius: 8px;
        padding: 0.8rem 1.5rem;
        font-weight: 600;
        transition: background-color 0.2s;
    }
    .btn-primary-custom:hover:not(:disabled) {
        background-color: #2d7a46;
    }
    .btn-primary-custom:disabled {
        background-color: #a0aec0;
        cursor: not-allowed;
    }
    .form-check-label {
        font-size: 0.875rem;
        color: #4a5568;
        line-height: 1.5;
    }
</style>

<div class="onboarding-container">
    <div class="onboarding-card">
        <div class="card-header-custom text-center">
            <h4 id="stepTitle">Informed Consent</h4>
        </div>
        <div class="card-body-custom">
            <!-- Informed Consent Section -->
            <div id="consentSection">
                <div class="consent-box">
                    <div class="consent-text mb-3">
                        The undersigned, one of the applicants/enrollees/grantees of <strong>Southern Luzon State University</strong>, 
                        has given permission to the University in the collection, lawful use, and disclosure of my personal information/sensitive personal information 
                        which may include, but not limited to:
                    </div>
                    <ul class="info-list mb-3">
                        <li>Student Number</li>
                        <li>Full Name</li>
                        <li>Contact Number</li>
                        <li>Email address</li>
                        <li>Entrance tests</li>
                        <li>Academic performance</li>
                        <li>Address</li>
                        <li>LRN</li>
                    </ul>
                    <div class="consent-text">
                        I further confirm that the appropriate offices in the university are authorized to provide the above information to legitimate 
                        departments/institutions/third-party agencies/parties (e.g., authorized guardians/custodians) in relation to any lawful processing which may include, but not limited to 
                        academic pursuits, administrative tasks, and research and statistical analysis initiatives to observe strict compliance with the Data Privacy Act of 2012 and other related laws and issuances.
                    </div>
                </div>

                <div class="mb-4">
                    <label for="consent_name" class="form-label">FULL NAME (IN CAPITAL LETTERS)</label>
                    <input type="text" class="form-control form-control-lg form-control-custom text-uppercase" id="consent_name" 
                           placeholder="JANE DOE" autocomplete="off">
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="consent_checkbox">
                    <label class="form-check-label" for="consent_checkbox">
                        I understand that University is authorized to collect and process my personal information and sensitive personal information 
                        without the need of my consent pursuant to the relevant portions of Sections 4, 12, and 13 of the Data Privacy Act of 2012.
                    </label>
                </div>

                <div class="d-grid">
                    <button type="button" id="btnConsent" class="btn btn-primary btn-lg btn-primary-custom" disabled>Agree and Proceed</button>
                </div>
            </div>

            <!-- Profile Form Section -->
            <div id="profileSection" style="display: none;">
                <p class="text-muted text-center mb-4">Please provide your details to complete your registration.</p>
                
                <form id="onboardingForm">
                    <input type="hidden" name="action" value="complete_onboarding">
                    
                    <div class="mb-4">
                        <label for="lrn" class="form-label">LRN (Learner Reference Number)</label>
                        <input type="text" class="form-control form-control-lg form-control-custom" id="lrn" name="lrn" required 
                               placeholder="12-digit number" pattern="\d{12}" title="LRN must be exactly 12 digits">
                        <div class="form-text mt-2">Your unique 12-digit number provided by DepEd.</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="recent_school" class="form-label">Recent School Attended</label>
                        <input type="text" class="form-control form-control-lg form-control-custom" id="recent_school" name="recent_school" required 
                               placeholder="Name of your previous school">
                    </div>
                    
                    <div class="mb-4">
                        <label for="preferred_track" class="form-label">Preferred Academic Track</label>
                        <select class="form-select form-select-lg form-control-custom" id="preferred_track" name="preferred_track" required>
                            <option value="">Choose your track...</option>
                            <option value="Arts, social sciences and Humanities">Arts, social sciences and Humanities</option>
                            <option value="Science Technology, Engineering and Mathematics">Science Technology, Engineering and Mathematics</option>
                            <option value="Sports, health and Wellness">Sports, health and Wellness</option>
                            <option value="Business and Entrepreneurship">Business and Entrepreneurship</option>
                            <option value="Field Experience">Field Experience</option>
                        </select>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg btn-primary-custom">Submit and Finish</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<script>
$(document).ready(function() {
    // Informed Consent Validation
    function validateConsent() {
        const nameInput = $('#consent_name').val().trim();
        const isChecked = $('#consent_checkbox').is(':checked');
        
        // Check if name is in capital letters and not empty, and checkbox is checked
        if (nameInput.length > 0 && nameInput === nameInput.toUpperCase() && isChecked) {
            $('#btnConsent').prop('disabled', false);
        } else {
            $('#btnConsent').prop('disabled', true);
        }
    }

    $('#consent_name').on('input', function() {
        this.value = this.value.toUpperCase();
        validateConsent();
    });
    $('#consent_checkbox').on('change', validateConsent);

    // Transition from Consent to Profile Form
    $('#btnConsent').on('click', function() {
        $('#consentSection').fadeOut(300, function() {
            $('#profileSection').fadeIn(300);
            $('#stepTitle').text('Complete Your Student Profile');
        });
    });

    $('#onboardingForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        Swal.fire({
            title: 'Submitting Details',
            text: "Please wait...",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '../../controllers/user_contr.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
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
    });
});
</script>
