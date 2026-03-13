<?php

include "dataB.php";

$field_errors = [];
$success_message = "";
$database_error = "";

// Sticky values
$id_number = "";
$last_name = "";
$first_name = "";
$middle_name = "";
$course_level = "1";
$email = "";
$course = "";
$address = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Capture inputs
    $id_number = trim($_POST["id_number"]);
    $last_name = trim($_POST["last_name"]);
    $first_name = trim($_POST["first_name"]);
    $middle_name = trim($_POST["middle_name"]);
    $course_level = trim($_POST["course_level"]);
    $password = $_POST["password"];
    $repeat_password = $_POST["repeat_password"];
    $email = trim($_POST["email"]);
    $course = trim($_POST["course"]);
    $address = trim($_POST["address"]);

    /* -------------------------
       VALIDATION CHECKS
    --------------------------*/

    if(empty($id_number)){
        $field_errors['id_number'] = "Required";
    } elseif(!ctype_digit($id_number)){
        $field_errors['id_number'] = "Numbers only";
    }

    if(empty($last_name)){
        $field_errors['last_name'] = "Required";
    }

    if(empty($first_name)){
        $field_errors['first_name'] = "Required";
    }

    if(empty($course_level)){
        $field_errors['course_level'] = "Required";
    }

    if(empty($email)){
        $field_errors['email'] = "Required";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $field_errors['email'] = "Invalid format";
    }

    if(empty($course)){
        $field_errors['course'] = "Required";
    }

    if(empty($address)){
        $field_errors['address'] = "Required";
    }

    // Password Logic
    if(empty($password)){
        $field_errors['password'] = "Required";
    } else {
        $pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";
        if(!preg_match($pattern,$password)){
            $field_errors['password'] = "Min 8 chars, upper, lower, num, special";
        }
    }

    if(empty($repeat_password)){
        $field_errors['repeat_password'] = "Required";
    } elseif($password !== $repeat_password){
        $field_errors['repeat_password'] = "Passwords don't match";
    }

    /* -------------------------
       CHECK IF ID EXISTS
    --------------------------*/

    if(empty($field_errors) && !empty($id_number)){
        $check = "SELECT * FROM users WHERE id_number='$id_number'";
        $result = mysqli_query($conn,$check);

        if(mysqli_num_rows($result) > 0){
            $field_errors['id_number'] = "ID already registered";
        }
    }

    /* -------------------------
       INSERT USER
    --------------------------*/

    if(empty($field_errors)){

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users 
        (id_number,last_name,first_name,middle_name,course_level,password,email,course,address)
        VALUES
        ('$id_number','$last_name','$first_name','$middle_name','$course_level','$hashed_password','$email','$course','$address')";

        if(mysqli_query($conn,$sql)){
            $success_message = "Registration successful! You can now login.";
            $id_number = $last_name = $first_name = $middle_name = $email = $address = "";
            $course_level = "1";
        }else{
            $database_error = "Database error: " . mysqli_error($conn);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | CCS Monitoring</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navbar Styling */
        .navbar-custom {
            background-color: #0c4ea2; 
            padding: 12px 20px;
        }
        .navbar-brand {
            color: white !important;
            font-weight: 600;
            font-size: 1rem;
        }
        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            font-size: 0.9rem;
            margin-left: 15px;
        }
        .nav-link:hover, .nav-link.active {
            color: white !important;
            font-weight: 500;
        }

        /* Main Container */
        .main-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        /* Form Panel */
        .form-panel {
            max-width: 420px;
            width: 100%;
        }
        
        h2.main-title {
            font-weight: 800;
            color: #333;
            margin-bottom: 30px;
        }

        /* Input Wrapper - Relative for tooltip positioning */
        .input-wrapper {
            position: relative;
            margin-bottom: 18px;
        }

        .form-label {
            font-weight: 500;
            font-size: 0.85rem;
            color: #555;
            margin-bottom: 4px;
            display: block;
        }

        .form-control, .form-select {
            padding: 10px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            width: 100%;
        }

        .form-control.is-invalid, .form-select.is-invalid {
            border-color: #dc3545;
            background-image: none;
        }

        /* ===== FLOATING ERROR BUBBLE ===== */
        .error-bubble {
            position: absolute;
            right: -12px;
            top: 50%;
            transform: translateX(100%) translateY(-50%);
            background-color: #dc3545;
            color: white;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 5px;
            white-space: nowrap;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
            animation: fadeIn 0.3s ease;
        }

        /* Arrow pointing left */
        .error-bubble::before {
            content: "";
            position: absolute;
            left: -6px;
            top: 50%;
            transform: translateY(-50%);
            border-width: 6px;
            border-style: solid;
            border-color: transparent #dc3545 transparent transparent;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(100%) translateY(-50%) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateX(100%) translateY(-50%) scale(1);
            }
        }

        /* Adjust bubble position for label */
        .input-wrapper .error-bubble {
            top: calc(50% + 10px); /* Account for label height */
        }

        .btn-register-custom {
            background-color: #0c4ea2; 
            color: white;
            font-weight: 600;
            padding: 10px;
            border-radius: 4px;
            border: none;
            width: 100%;
            margin-top: 10px;
        }

        .btn-register-custom:hover {
            background-color: #0a3d7d;
        }

        /* Illustration Panel */
        .illustration-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        footer {
            background-color: #0c4ea2;
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: auto;
            font-size: 0.85rem;
        }

        /* Responsive: Hide bubble on small screens, show below input instead */
        @media (max-width: 768px) {
            .error-bubble {
                position: relative;
                right: auto;
                top: auto;
                transform: none;
                display: inline-block;
                margin-top: 5px;
            }
            .error-bubble::before {
                display: none;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="#">
            <i class="fas fa-university me-2"></i>
            CCS Sit-in Monitoring System
        </a>

        <button class="navbar-toggler text-white" type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Community</a></li>
                <li class="nav-item"><a class="nav-link" href="#">About</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php">Login</a></li>
                <li class="nav-item"><a class="nav-link active" href="Register.php">Register</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="container main-container">
    <div class="row w-100 justify-content-center align-items-center">
        
        <!-- Left Side: Registration Form -->
        <div class="col-lg-5 col-md-8">
            <div class="form-panel">
                <h2 class="text-center main-title">Sign up</h2>

                <?php if(!empty($database_error)): ?>
                    <div class="alert alert-danger small mb-3">
                        <?php echo $database_error; ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($success_message) && $success_message !== ""): ?>
                    <div class="alert alert-success small mb-3">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    
                    <!-- ID Number -->
                    <div class="input-wrapper">
                        <label class="form-label">ID Number</label>
                        <input type="text" class="form-control <?php echo isset($field_errors['id_number']) ? 'is-invalid' : ''; ?>" name="id_number" value="<?php echo htmlspecialchars($id_number); ?>">
                        <?php if(isset($field_errors['id_number'])): ?>
                            <span class="error-bubble"><?php echo $field_errors['id_number']; ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Last Name -->
                    <div class="input-wrapper">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control <?php echo isset($field_errors['last_name']) ? 'is-invalid' : ''; ?>" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>">
                        <?php if(isset($field_errors['last_name'])): ?>
                            <span class="error-bubble"><?php echo $field_errors['last_name']; ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- First Name -->
                    <div class="input-wrapper">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control <?php echo isset($field_errors['first_name']) ? 'is-invalid' : ''; ?>" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>">
                        <?php if(isset($field_errors['first_name'])): ?>
                            <span class="error-bubble"><?php echo $field_errors['first_name']; ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Middle Name -->
                    <div class="input-wrapper">
                        <label class="form-label">Middle Name <small class="text-muted">(Optional)</small></label>
                        <input type="text" class="form-control" name="middle_name" value="<?php echo htmlspecialchars($middle_name); ?>">
                    </div>

                    <!-- Course Level -->
                    <div class="input-wrapper">
                        <label class="form-label">Course Level</label>
                        <input type="number" min="1" max="4" class="form-control <?php echo isset($field_errors['course_level']) ? 'is-invalid' : ''; ?>" name="course_level" value="<?php echo htmlspecialchars($course_level); ?>">
                        <?php if(isset($field_errors['course_level'])): ?>
                            <span class="error-bubble"><?php echo $field_errors['course_level']; ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Password -->
                    <div class="input-wrapper">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control <?php echo isset($field_errors['password']) ? 'is-invalid' : ''; ?>" name="password">
                        <?php if(isset($field_errors['password'])): ?>
                            <span class="error-bubble"><?php echo $field_errors['password']; ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Repeat Password -->
                    <div class="input-wrapper">
                        <label class="form-label">Repeat Password</label>
                        <input type="password" class="form-control <?php echo isset($field_errors['repeat_password']) ? 'is-invalid' : ''; ?>" name="repeat_password">
                        <?php if(isset($field_errors['repeat_password'])): ?>
                            <span class="error-bubble"><?php echo $field_errors['repeat_password']; ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div class="input-wrapper">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control <?php echo isset($field_errors['email']) ? 'is-invalid' : ''; ?>" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        <?php if(isset($field_errors['email'])): ?>
                            <span class="error-bubble"><?php echo $field_errors['email']; ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Course -->
                    <div class="input-wrapper">
                        <label class="form-label">Course</label>
                        <select class="form-select <?php echo isset($field_errors['course']) ? 'is-invalid' : ''; ?>" name="course">
                            <option value="">Select Course</option>
                            <option value="BSIT" <?php echo ($course=='BSIT') ? 'selected' : ''; ?>>BSIT</option>
                            <option value="BSCS" <?php echo ($course=='BSCS') ? 'selected' : ''; ?>>BSCS</option>
                            <option value="CSCIE" <?php echo ($course=='CSCIE') ? 'selected' : ''; ?>>CSCIE</option>
                            <option value="CST" <?php echo ($course=='CST') ? 'selected' : ''; ?>>CST</option>
                        </select>
                        <?php if(isset($field_errors['course'])): ?>
                            <span class="error-bubble"><?php echo $field_errors['course']; ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Address -->
                    <div class="input-wrapper">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control <?php echo isset($field_errors['address']) ? 'is-invalid' : ''; ?>" name="address" value="<?php echo htmlspecialchars($address); ?>">
                        <?php if(isset($field_errors['address'])): ?>
                            <span class="error-bubble"><?php echo $field_errors['address']; ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn btn-register-custom">Register</button>

                    <div class="text-center mt-3">
                        <small class="text-muted">Already have an account? <a href="index.php" class="fw-bold text-decoration-none">Log in here</a></small>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Side: Illustration -->
        <div class="col-lg-5 d-none d-lg-block illustration-panel">
            <img src="CCS_LOGO.png" alt="Illustration" style="max-height: 500px; opacity: 0.9;">
        </div>

    </div>
</div>

<!-- Footer -->
<footer>
    <p class="mb-0">&copy; 2024 College of Computer Studies</p>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>