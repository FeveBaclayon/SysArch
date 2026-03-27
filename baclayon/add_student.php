<?php
include "dataB.php";
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'admin') {
    header("Location: index.php");
    exit();
}

$success_msg = "";
$error_msg = "";
$field_errors = [];

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_number = trim($_POST["id_number"]);
    $last_name = trim($_POST["last_name"]);
    $first_name = trim($_POST["first_name"]);
    $middle_name = trim($_POST["middle_name"]);
    $course_level = trim($_POST["course_level"]);
    $email = trim($_POST["email"]);
    $course = trim($_POST["course"]);
    $address = trim($_POST["address"]);
    $password = $_POST["password"];
    $repeat_password = $_POST["repeat_password"];
    $remaining_sessions = trim($_POST["remaining_sessions"]);

    // Validation
    if (empty($id_number)) $field_errors['id_number'] = "Required";
    elseif (!ctype_digit($id_number)) $field_errors['id_number'] = "Numbers only";
    
    if (empty($last_name)) $field_errors['last_name'] = "Required";
    if (empty($first_name)) $field_errors['first_name'] = "Required";
    if (empty($course_level)) $field_errors['course_level'] = "Required";
    if (empty($email)) $field_errors['email'] = "Required";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $field_errors['email'] = "Invalid format";
    if (empty($course)) $field_errors['course'] = "Required";
    if (empty($address)) $field_errors['address'] = "Required";
    
    // Password validation
    if (empty($password)) $field_errors['password'] = "Required";
    else {
        $pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";
        if (!preg_match($pattern, $password)) {
            $field_errors['password'] = "Min 8 chars, upper, lower, num, special";
        }
    }
    
    if (empty($repeat_password)) $field_errors['repeat_password'] = "Required";
    elseif ($password !== $repeat_password) $field_errors['repeat_password'] = "Passwords don't match";

    // Check if ID number already exists
    if (empty($field_errors) && !empty($id_number)) {
        $check_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE id_number = ?");
        mysqli_stmt_bind_param($check_stmt, "s", $id_number);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $field_errors['id_number'] = "ID already registered";
        }
        mysqli_stmt_close($check_stmt);
    }

    // Insert new student
    if (empty($field_errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = "user";
        
        $insert_stmt = mysqli_prepare($conn, "INSERT INTO users 
            (id_number, last_name, first_name, middle_name, course_level, password, email, course, address, role, remaining_sessions) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        mysqli_stmt_bind_param($insert_stmt, "ssssisssssi", 
            $id_number, $last_name, $first_name, $middle_name, 
            $course_level, $hashed_password, $email, $course, 
            $address, $role, $remaining_sessions
        );
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $success_msg = "Student added successfully!";
            // Clear form
            $id_number = $last_name = $first_name = $middle_name = $email = $address = "";
            $course_level = "1";
            $course = "";
        } else {
            $error_msg = "Error adding student: " . mysqli_error($conn);
        }
        mysqli_stmt_close($insert_stmt);
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background-color: #f4f6f9; }
        .top-navbar { background-color: #0f4c96; color: white; padding: 12px 24px; }
        .top-navbar .navbar-brand { color: white; font-weight: 600; }
        .top-navbar a { color: white; text-decoration: none; margin-left: 15px; }
        .top-navbar a:hover { text-decoration: underline; }
        .btn-logout { background-color: #ffc107; color: #000 !important; padding: 6px 16px; border-radius: 4px; }
        .card-header-custom { background-color: #0f4c96; color: white; font-weight: 600; padding: 15px; }
        .form-label { font-weight: 500; color: #444; }
        .btn-add { background-color: #0f4c96; color: white; font-weight: 600; padding: 10px 30px; }
        .btn-add:hover { background-color: #0c3577; color: white; }
        .error-bubble {
            position: absolute;
            right: -12px;
            top: 50%;
            transform: translateX(100%) translateY(-50%);
            background-color: #dc3545;
            color: white;
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 5px;
            white-space: nowrap;
        }
        .input-wrapper { position: relative; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <nav class="top-navbar navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php"><i class="fas fa-university me-2"></i>CCS Admin</a>
            <div>
                <a href="admin_dashboard.php">Home</a>
                <a href="students.php">Students</a>
                <a href="sit_in.php">Sit-in</a>
                <a href="view_sit_in_records.php">Records</a>
                <a href="admin_dashboard.php?logout=true" class="btn btn-logout">Log out</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header-custom">
                        <i class="fas fa-user-plus me-2"></i> Add New Student
                    </div>
                    <div class="card-body p-4">
                        <?php if(!empty($error_msg)): ?>
                            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error_msg; ?></div>
                        <?php endif; ?>
                        <?php if(!empty($success_msg)): ?>
                            <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?> 
                            <a href="students.php" class="ms-2">View Students</a></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label class="form-label">ID Number <span class="text-danger">*</span></label>
                                    <input type="text" name="id_number" class="form-control <?php echo isset($field_errors['id_number']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($id_number ?? ''); ?>" required>
                                    <?php if(isset($field_errors['id_number'])): ?>
                                        <span class="error-bubble"><?php echo $field_errors['id_number']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control <?php echo isset($field_errors['email']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                    <?php if(isset($field_errors['email'])): ?>
                                        <span class="error-bubble"><?php echo $field_errors['email']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="form-control <?php echo isset($field_errors['first_name']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($first_name ?? ''); ?>" required>
                                    <?php if(isset($field_errors['first_name'])): ?>
                                        <span class="error-bubble"><?php echo $field_errors['first_name']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" name="middle_name" class="form-control" value="<?php echo htmlspecialchars($middle_name ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control <?php echo isset($field_errors['last_name']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($last_name ?? ''); ?>" required>
                                    <?php if(isset($field_errors['last_name'])): ?>
                                        <span class="error-bubble"><?php echo $field_errors['last_name']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label class="form-label">Course <span class="text-danger">*</span></label>
                                    <select name="course" class="form-select <?php echo isset($field_errors['course']) ? 'is-invalid' : ''; ?>" required>
                                        <option value="">Select Course</option>
                                        <option value="BSIT" <?php echo (isset($course) && $course=='BSIT')?'selected':''; ?>>BSIT</option>
                                        <option value="BSCS" <?php echo (isset($course) && $course=='BSCS')?'selected':''; ?>>BSCS</option>
                                        <option value="CSCIE" <?php echo (isset($course) && $course=='CSCIE')?'selected':''; ?>>CSCIE</option>
                                        <option value="CST" <?php echo (isset($course) && $course=='CST')?'selected':''; ?>>CST</option>
                                    </select>
                                    <?php if(isset($field_errors['course'])): ?>
                                        <span class="error-bubble"><?php echo $field_errors['course']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Year Level <span class="text-danger">*</span></label>
                                    <input type="number" name="course_level" min="1" max="5" class="form-control <?php echo isset($field_errors['course_level']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($course_level ?? '1'); ?>" required>
                                    <?php if(isset($field_errors['course_level'])): ?>
                                        <span class="error-bubble"><?php echo $field_errors['course_level']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address <span class="text-danger">*</span></label>
                                <input type="text" name="address" class="form-control <?php echo isset($field_errors['address']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($address ?? ''); ?>" required>
                                <?php if(isset($field_errors['address'])): ?>
                                    <span class="error-bubble"><?php echo $field_errors['address']; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password" class="form-control <?php echo isset($field_errors['password']) ? 'is-invalid' : ''; ?>" required>
                                    <?php if(isset($field_errors['password'])): ?>
                                        <span class="error-bubble"><?php echo $field_errors['password']; ?></span>
                                    <?php endif; ?>
                                    <small class="text-muted">Min 8 chars, uppercase, lowercase, number, special char</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" name="repeat_password" class="form-control <?php echo isset($field_errors['repeat_password']) ? 'is-invalid' : ''; ?>" required>
                                    <?php if(isset($field_errors['repeat_password'])): ?>
                                        <span class="error-bubble"><?php echo $field_errors['repeat_password']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Remaining Sessions</label>
                                <input type="number" name="remaining_sessions" min="0" max="100" class="form-control" value="<?php echo htmlspecialchars($remaining_sessions ?? '30'); ?>">
                                <small class="text-muted">Default is 30 sessions per semester</small>
                            </div>

                            <hr>
                            <div class="d-flex justify-content-between">
                                <a href="students.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Students</a>
                                <button type="submit" class="btn btn-add"><i class="fas fa-user-plus me-2"></i>Add Student</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>