<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth.php';

$errors = [];
$name = $email = $password = $phone = $location = $id_number = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $id_number = trim($_POST['id_number'] ?? '');
    $role = 'Guardian';

    $errors = validateRegistrationInputs($name, $email, $password, $phone, $location, $id_number);

    if (empty($errors)) {
        try {
            if (registerUser($name, $email, $password, $phone, $role, $location, $id_number)) {
                header('Location: login.php');
                exit();
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors['email'] = "This email is already registered. Please use a different email or login.";
            } else {
                $errors['general'] = "Registration failed. Please try again.";
            }
        }
    }
}

function validateRegistrationInputs($name, $email, $password, $phone, $location, $id_number) {
    $errors = [];

    if (empty($name)) {
        $errors['name'] = "Full name is required";
    } elseif (!preg_match('/^[a-zA-Z \-]{2,50}$/', $name)) {
        $errors['name'] = "Name must be 2-50 characters with only letters, spaces, and hyphens";
    }

    if (empty($email)) {
        $errors['email'] = "Email address is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address (e.g., user@email.com)";
    } elseif (strlen($email) > 100) {
        $errors['email'] = "Email address is too long (maximum 100 characters)";
    }

    if (empty($password)) {
        $errors['password'] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors['password'] = "Password must be at least 8 characters long";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors['password'] = "Password must contain at least one uppercase letter";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors['password'] = "Password must contain at least one lowercase letter";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors['password'] = "Password must contain at least one number";
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors['password'] = "Password must contain at least one special character";
    } elseif (strlen($password) > 72) {
        $errors['password'] = "Password is too long (maximum 72 characters)";
    }

    if (empty($phone)) {
        $errors['phone'] = "Phone number is required";
    } elseif (!preg_match('/^\+?[0-9]{10,15}$/', $phone)) {
        $errors['phone'] = "Please enter a valid phone number (10-15 digits, + optional)";
    }

    if (empty($location)) {
        $errors['location'] = "Please select your location (County)";
    }

    if (empty($id_number)) {
        $errors['id_number'] = "National ID or Passport Number is required";
    } elseif (!preg_match('/^[A-Za-z0-9]{5,20}$/', $id_number)) {
        $errors['id_number'] = "Please enter a valid ID or Passport Number (5-20 alphanumeric characters)";
    }

    return $errors;
}
?>

<!-- HTML Section -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Child Vaccination System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #166088;
            --accent-color: #4fc3f7;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --danger-color: #dc3545;
            --success-color: #28a745;
        }
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background-color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .nav-logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            text-decoration: none;
        }
        .container {
            padding: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
        }
        .card {
            background: white;
            padding: 2.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            max-width: 500px;
            width: 100%;
        }
        h2 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        .form-control:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(79, 195, 247, 0.2);
        }
        .is-invalid {
            border-color: var(--danger-color);
        }
        .invalid-feedback {
            color: var(--danger-color);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .btn {
            background: var(--primary-color);
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 6px;
            width: 100%;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.2s;
        }
        .btn:hover {
            background: var(--secondary-color);
        }
        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
        }
        .password-hint {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        .form-note {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a class="nav-logo" href="index.php">ChildVax</a>
    <div><a href="login.php">Login</a></div>
</nav>

<div class="container">
    <div class="card">
        <h2>Create Account</h2>

        <?php if (isset($errors['general'])): ?>
            <div class="alert-danger"><?php echo htmlspecialchars($errors['general']); ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST" novalidate>
            <!-- Name -->
            <div class="form-group">
                <label for="name">Full Name</label>
                <input class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>"
                       type="text" name="name" id="name"
                       value="<?php echo htmlspecialchars($name); ?>">
                <?php if (isset($errors['name'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                <?php endif; ?>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email">Email</label>
                <input class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                       type="email" name="email" id="email"
                       value="<?php echo htmlspecialchars($email); ?>">
                <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                <?php endif; ?>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password">Password</label>
                <input class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                       type="password" name="password" id="password">
                <?php if (isset($errors['password'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                <?php endif; ?>
                <div class="password-hint">
                    Password must be at least 8 characters long and include:
                    <ul>
                        <li>One uppercase letter</li>
                        <li>One lowercase letter</li>
                        <li>One number</li>
                        <li>One special character</li>
                    </ul>
                </div>
            </div>

            <!-- ID Number -->
            <div class="form-group">
                <label for="id_number">National ID or Passport Number</label>
                <input class="form-control <?php echo isset($errors['id_number']) ? 'is-invalid' : ''; ?>"
                       type="text" name="id_number" id="id_number"
                       value="<?php echo htmlspecialchars($id_number); ?>">
                <?php if (isset($errors['id_number'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['id_number']; ?></div>
                <?php endif; ?>
            </div>

            <!-- Phone -->
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>"
                       type="tel" name="phone" id="phone"
                       value="<?php echo htmlspecialchars($phone); ?>">
                <?php if (isset($errors['phone'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                <?php endif; ?>
            </div>

            <!-- Location -->
            <div class="form-group">
                <label for="location">Location (County)</label>
                <select class="form-control <?php echo isset($errors['location']) ? 'is-invalid' : ''; ?>"
                        name="location" id="location">
                    <option value="">-- Select County --</option>
                    <?php
                    $counties = ["Baringo", "Bomet", "Bungoma", "Busia", "Elgeyo-Marakwet", "Embu", "Garissa", "Homa Bay", "Isiolo", "Kajiado", "Kakamega", "Kericho", "Kiambu", "Kilifi", "Kirinyaga", "Kisii", "Kisumu", "Kitui", "Kwale", "Laikipia", "Lamu", "Machakos", "Makueni", "Mandera", "Marsabit", "Meru", "Migori", "Mombasa", "Murang'a", "Nairobi", "Nakuru", "Nandi", "Narok", "Nyamira", "Nyandarua", "Nyeri", "Samburu", "Siaya", "Taita Taveta", "Tana River", "Tharaka-Nithi", "Trans Nzoia", "Turkana", "Uasin Gishu", "Vihiga", "Wajir", "West Pokot"];
                    foreach ($counties as $c) {
                        $selected = ($location === $c) ? 'selected' : '';
                        echo "<option value=\"$c\" $selected>$c</option>";
                    }
                    ?>
                </select>
                <?php if (isset($errors['location'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['location']; ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn">Register</button>
        </form>
    </div>
</div>

</body>
</html>
