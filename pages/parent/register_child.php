<?php
session_start();
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/functions.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'guardian') {
    header('Location: ../auth/login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $dob = trim($_POST['date_of_birth'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $health_id = trim($_POST['health_id'] ?? '');
    $guardian_id = $_SESSION['user_id'];

    $errors = [];

    // Comprehensive validation for each field
    
    // Name validation
    if (empty($name)) {
        $errors['name'] = "Child's name is required.";
    } elseif (!preg_match('/^[a-zA-Z\s\-\.]{2,50}$/', $name)) {
        $errors['name'] = "Name must be 2-50 characters with only letters and spaces.";
    }
    
    // Date of birth validation
    if (empty($dob)) {
        $errors['date_of_birth'] = "Date of birth is required.";
    } else {
        $dob_timestamp = strtotime($dob);
        $dob_date = date('Y-m-d', strtotime($dob));
        $today_date = date('Y-m-d');
        
        if ($dob_timestamp === false) {
            $errors['date_of_birth'] = "Invalid date format.";
        } elseif ($dob_date > $today_date) {
            $errors['date_of_birth'] = "Date of birth cannot be in the future.";
        }
    }
    
    // Gender validation
    if (empty($gender)) {
        $errors['gender'] = "Gender is required.";
    } elseif (!in_array($gender, ['Male', 'Female', 'Other'])) {
        $errors['gender'] = "Please select a valid gender.";
    }
    
    // Health ID validation
    if (empty($health_id)) {
        $errors['health_id'] = "Baby ID or Birth Certificate Number is required.";
    } elseif (!preg_match('/^[A-Za-z0-9\-]{5,20}$/', $health_id)) {
        $errors['health_id'] = "Health ID must be 5-20 characters with only letters, numbers, and hyphens.";
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM children WHERE health_id = ?");
        $stmt->execute([$health_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors['health_id'] = "This Health ID is already registered in the system.";
        }
    }

    $vaccinationCardPathRel = null;

    function uploadFile($fileKey, $required = false) {
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $maxFileSize = 5 * 1024 * 1024; 
        
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                // Debugging: log file upload array
                error_log('UPLOAD FILES: ' . print_r($_FILES, true));
                throw new Exception(ucwords(str_replace('_', ' ', $fileKey)) . " is required.");
            }
            return null;
        }

        $file = $_FILES[$fileKey];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception("File size exceeds maximum limit (5MB).");
                case UPLOAD_ERR_PARTIAL:
                    throw new Exception("File upload was incomplete.");
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new Exception("Temporary folder is missing.");
                case UPLOAD_ERR_CANT_WRITE:
                    throw new Exception("Failed to write file to disk.");
                default:
                    throw new Exception("File upload failed.");
            }
        }
        
        // Validate file type
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("Invalid file type. Only JPG, PNG, and PDF files are allowed.");
        }
        
        // Validate file size
        if ($file['size'] > $maxFileSize) {
            throw new Exception("File size exceeds maximum limit (5MB).");
        }
        
        // Validate file content (basic check)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            // Debugging: log file type and mime
            error_log('UPLOAD FILE TYPE: ' . $file['type'] . ' MIME: ' . $mimeType);
            throw new Exception("Invalid file content. Please upload a valid file.");
        }

        $targetDir = __DIR__ . '/../../uploads/children';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
        $targetPath = $targetDir . '/' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception("Failed to upload file. Please try again.");
        }

        return 'uploads/children/' . $fileName;
    }

    if (empty($errors)) {
        try {
            $vaccinationCardPathRel = uploadFile('vaccination_card', true);

            // Insert child
            $stmt = $conn->prepare("INSERT INTO children (name, date_of_birth, gender, health_id, guardian_id, vaccination_card_path) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $dob, $gender, $health_id, $guardian_id, $vaccinationCardPathRel]);

            // Get the inserted child_id
            $child_id = $conn->lastInsertId();

            // Insert vaccination card into vaccination_cards table
            $stmtCard = $conn->prepare("INSERT INTO vaccination_cards (child_id, card_path, uploaded_at, verified_at) VALUES (?, ?, NOW(), NULL)");
            $stmtCard->execute([$child_id, $vaccinationCardPathRel]);

            // Generate vaccination schedule
            if (!generateVaccinationSchedule($child_id, $dob, $conn)) {
                throw new Exception("Child registered but failed to generate vaccination schedule.");
            }

            logSystemEvent('Added child', $_SESSION['user_id']);

            $_SESSION['success'] = "Child registered successfully with vaccination schedule!";
            header("Location: register_child.php");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $_SESSION['form_data'] = $_POST;
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
            $_SESSION['form_data'] = $_POST;
        }
    } else {
        $_SESSION['field_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
    }

    header("Location: register_child.php");
    exit();
}

// Fetch form data again for repopulation
$form_data = $_SESSION['form_data'] ?? [];
$field_errors = $_SESSION['field_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['field_errors']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Child</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container { max-width: 800px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        input, select { width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; }
        input:focus, select:focus { outline: none; border-color: #3498db; box-shadow: 0 0 5px rgba(52, 152, 219, 0.3); }
        .btn { background-color: #3498db; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background-color: #2980b9; }
        .btn:disabled { background-color: #bdc3c7; cursor: not-allowed; }
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 5px; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-text { color: #dc3545; font-size: 0.9rem; margin-top: 0.3rem; }
        .field-error { border-color: #dc3545 !important; }
        .field-error:focus { box-shadow: 0 0 5px rgba(220, 53, 69, 0.3) !important; }
        .help-text { color: #6c757d; font-size: 0.85rem; margin-top: 0.3rem; }
        .file-input-wrapper { position: relative; }
        .file-input-wrapper input[type="file"] { padding: 0.5rem; }
        .file-info { margin-top: 0.5rem; font-size: 0.9rem; color: #6c757d; }
        .validation-summary { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; }
        .validation-summary ul { margin: 0; padding-left: 1.5rem; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../navbar.php'; ?>
<div class="container">
    <h2><i class="fas fa-baby"></i> Register New Child</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="registerChildForm" novalidate>
        <div class="form-group">
            <label for="name">Child's Full Name <span class="text-danger">*</span></label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($form_data['name'] ?? '') ?>" 
                   class="<?= !empty($field_errors['name']) ? 'field-error' : '' ?>"
                   maxlength="50" required>
            <?php if (!empty($field_errors['name'])): ?>
                <div class="error-text"><i class="fas fa-exclamation-circle"></i> <?= $field_errors['name'] ?></div>
            <?php endif; ?>
            <div class="help-text">Enter the child's full name (2-50 characters, letters, spaces, hyphens, and periods only)</div>
        </div>

        <div class="form-group">
            <label for="date_of_birth">Date of Birth <span class="text-danger">*</span></label>
            <input type="date" id="date_of_birth" name="date_of_birth" 
                   max="<?= date('Y-m-d') ?>" 
                   value="<?= htmlspecialchars($form_data['date_of_birth'] ?? '') ?>"
                   class="<?= !empty($field_errors['date_of_birth']) ? 'field-error' : '' ?>"
                   required>
            <?php if (!empty($field_errors['date_of_birth'])): ?>
                <div class="error-text"><i class="fas fa-exclamation-circle"></i> <?= $field_errors['date_of_birth'] ?></div>
            <?php endif; ?>
            <div class="help-text">Select the child's date of birth</div>
        </div>

        <div class="form-group">
            <label for="gender">Gender <span class="text-danger">*</span></label>
            <select name="gender" id="gender" 
                    class="<?= !empty($field_errors['gender']) ? 'field-error' : '' ?>"
                    required>
                <option value="">-- Select Gender --</option>
                <option value="Male" <?= (isset($form_data['gender']) && $form_data['gender'] === 'Male') ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= (isset($form_data['gender']) && $form_data['gender'] === 'Female') ? 'selected' : '' ?>>Female</option>
                <option value="Other" <?= (isset($form_data['gender']) && $form_data['gender'] === 'Other') ? 'selected' : '' ?>>Other</option>
            </select>
            <?php if (!empty($field_errors['gender'])): ?>
                <div class="error-text"><i class="fas fa-exclamation-circle"></i> <?= $field_errors['gender'] ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="health_id">Notice Number or Birth Certificate Number <span class="text-danger">*</span></label>
            <input type="text" id="health_id" name="health_id" 
                   value="<?= htmlspecialchars($form_data['health_id'] ?? '') ?>"
                   class="<?= !empty($field_errors['health_id']) ? 'field-error' : '' ?>"
                   maxlength="20" required>
            <?php if (!empty($field_errors['health_id'])): ?>
                <div class="error-text"><i class="fas fa-exclamation-circle"></i> <?= $field_errors['health_id'] ?></div>
            <?php endif; ?>
            <div class="help-text">Enter the official Notice Number or birth certificate number (5-20 characters, letters, numbers, and hyphens only)</div>
        </div>

        <div class="form-group">
            <label for="vaccination_card">Upload Vaccination Card <span class="text-danger">*</span></label>
            <div class="file-input-wrapper">
                <input type="file" name="vaccination_card" id="vaccination_card" 
                       accept=".jpg,.jpeg,.png,.pdf" required>
            </div>
            <div class="file-info">
                <i class="fas fa-info-circle"></i> Accepted formats: JPG, PNG, PDF (Max size: 5MB)
            </div>
        </div>

        <div class="form-group">
            <button type="submit" class="btn" id="submitBtn">
                <i class="fas fa-save"></i> Register Child
            </button>
            <a href="dashboard.php" class="btn" style="background-color: #6c757d; margin-left: 10px;">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerChildForm');
    const submitBtn = document.getElementById('submitBtn');
    
    // Real-time validation
    const fields = {
        name: {
            element: document.getElementById('name'),
            pattern: /^[a-zA-Z\s\-\.]{2,50}$/,
            message: "Name must be 2-50 characters with only letters, spaces, hyphens, and periods."
        },
        date_of_birth: {
            element: document.getElementById('date_of_birth'),
            validate: function(value) {
                if (!value) return "Date of birth is required.";
                const dob = new Date(value);
                const today = new Date();
                
                if (dob > today) return "Date of birth cannot be in the future.";
                return null;
            }
        },
        gender: {
            element: document.getElementById('gender'),
            validate: function(value) {
                if (!value) return "Gender is required.";
                return null;
            }
        },
        health_id: {
            element: document.getElementById('health_id'),
            pattern: /^[A-Za-z0-9\-]{5,20}$/,
            message: "Health ID must be 5-20 characters with only letters, numbers, and hyphens."
        },
        vaccination_card: {
            element: document.getElementById('vaccination_card'),
            validate: function() {
                const element = document.getElementById('vaccination_card');
                if (!element.files || !element.files[0]) return "Vaccination card is required.";
                const file = element.files[0];
                if (file) {
                    const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
                    const maxSize = 5 * 1024 * 1024; // 5MB
                    if (!allowedTypes.includes(file.type)) {
                        return "Invalid file type. Only JPG, PNG, and PDF files are allowed.";
                    }
                    if (file.size > maxSize) {
                        return "File size exceeds maximum limit (5MB).";
                    }
                }
                return null;
            }
        }
    };
    
    // Validation function
    function validateField(fieldName) {
        const field = fields[fieldName];
        const element = field.element;
        const value = element.value;
        
        let error = null;
        
        if (field.pattern) {
            if (!field.pattern.test(value)) {
                error = field.message;
            }
        } else if (field.validate) {
            error = field.validate(value);
        }
        
        // Update UI
        element.classList.remove('field-error');
        const errorDiv = element.parentNode.querySelector('.error-text');
        if (errorDiv) errorDiv.remove();
        
        if (error) {
            element.classList.add('field-error');
            const errorElement = document.createElement('div');
            errorElement.className = 'error-text';
            errorElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${error}`;
            element.parentNode.appendChild(errorElement);
            return false;
        }
        
        return true;
    }
    
    // Add event listeners for real-time validation
    Object.keys(fields).forEach(fieldName => {
        const element = fields[fieldName].element;
        element.addEventListener('blur', () => validateField(fieldName));
        element.addEventListener('input', () => {
            if (element.classList.contains('field-error')) {
                validateField(fieldName);
            }
        });
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        let isValid = true;
        const errors = [];
        
        // Validate all fields
        Object.keys(fields).forEach(fieldName => {
            if (!validateField(fieldName)) {
                isValid = false;
                errors.push(fields[fieldName].element.previousElementSibling.textContent.replace(' *', ''));
            }
        });
        
        if (!isValid) {
            // Show validation summary
            const summary = document.createElement('div');
            summary.className = 'validation-summary';
            summary.innerHTML = `
                <strong><i class="fas fa-exclamation-triangle"></i> Please correct the following errors:</strong>
                <ul>
                    ${errors.map(error => `<li>${error}</li>`).join('')}
                </ul>
            `;
            
            const existingSummary = form.querySelector('.validation-summary');
            if (existingSummary) existingSummary.remove();
            
            form.insertBefore(summary, form.firstChild);
            
            // Scroll to first error
            const firstError = form.querySelector('.field-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
            
            return false;
        }
        
        // Disable submit button to prevent double submission
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';
        
        // Submit form
        form.submit();
    });
    
    // File input change handler
    document.getElementById('vaccination_card').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const fileInfo = this.parentNode.nextElementSibling;
        
        if (file) {
            const size = (file.size / 1024 / 1024).toFixed(2);
            fileInfo.innerHTML = `
                <i class="fas fa-file"></i> Selected: ${file.name} (${size} MB)
            `;
        } else {
            fileInfo.innerHTML = `
                <i class="fas fa-info-circle"></i> Accepted formats: JPG, PNG, PDF (Max size: 5MB)
            `;
        }
    });
});
</script>
</body>
</html>