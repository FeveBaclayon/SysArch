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

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: students.php");
    exit();
}

$student_id = $_GET['id'];

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
    $remaining_sessions = trim($_POST["remaining_sessions"]);

    // Validation
    if (empty($id_number) || empty($last_name) || empty($first_name) || empty($course_level) || empty($email) || empty($course) || empty($address)) {
        $error_msg = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email format.";
    } else {
        // Check if ID number already exists for another student
        $check_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE id_number = ? AND id != ?");
        mysqli_stmt_bind_param($check_stmt, "si", $id_number, $student_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error_msg = "ID number already exists. Please use a different ID.";
        } else {
            // Update database
            $update_stmt = mysqli_prepare($conn, "UPDATE users SET 
                id_number = ?,
                last_name = ?,
                first_name = ?,
                middle_name = ?,
                course_level = ?,
                email = ?,
                course = ?,
                address = ?,
                remaining_sessions = ?
                WHERE id = ?");
            
            mysqli_stmt_bind_param($update_stmt, "sssssssssi", 
                $id_number, $last_name, $first_name, $middle_name, 
                $course_level, $email, $course, $address, 
                $remaining_sessions, $student_id
            );
            
            if (mysqli_stmt_execute($update_stmt)) {
                $success_msg = "Student information updated successfully!";
            } else {
                $error_msg = "Error updating student: " . mysqli_error($conn);
            }
            mysqli_stmt_close($update_stmt);
        }
        mysqli_stmt_close($check_stmt);
    }
}

// Fetch student data (AFTER form processing to avoid sync issues)
$fetch_stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? AND role = 'user'");
mysqli_stmt_bind_param($fetch_stmt, "i", $student_id);
mysqli_stmt_execute($fetch_stmt);
$fetch_result = mysqli_stmt_get_result($fetch_stmt);
$student = mysqli_fetch_assoc($fetch_result);
mysqli_stmt_close($fetch_stmt);

// If student not found
if (!$student) {
    header("Location: students.php");
    exit();
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
    <title>Edit Student - Admin</title>
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
        .btn-update { background-color: #0f4c96; color: white; font-weight: 600; padding: 10px 30px; }
        .btn-update:hover { background-color: #0c3577; color: white; }
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
                        <i class="fas fa-user-edit me-2"></i> Edit Student Information
                    </div>
                    <div class="card-body p-4">
                        <?php if(!empty($error_msg)): ?>
                            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error_msg; ?></div>
                        <?php endif; ?>
                        <?php if(!empty($success_msg)): ?>
                            <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label class="form-label">ID Number <span class="text-danger">*</span></label>
                                    <input type="text" name="id_number" class="form-control" value="<?php echo htmlspecialchars($student['id_number']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" name="middle_name" class="form-control" value="<?php echo htmlspecialchars($student['middle_name']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label class="form-label">Course <span class="text-danger">*</span></label>
                                    <select name="course" class="form-select" required>
                                        <option value="BSIT" <?php echo ($student['course']=='BSIT')?'selected':''; ?>>BSIT</option>
                                        <option value="BSCS" <?php echo ($student['course']=='BSCS')?'selected':''; ?>>BSCS</option>
                                        <option value="CSCIE" <?php echo ($student['course']=='CSCIE')?'selected':''; ?>>CSCIE</option>
                                        <option value="CST" <?php echo ($student['course']=='CST')?'selected':''; ?>>CST</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Year Level <span class="text-danger">*</span></label>
                                    <input type="number" name="course_level" min="1" max="5" class="form-control" value="<?php echo htmlspecialchars($student['course_level']); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address <span class="text-danger">*</span></label>
                                <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($student['address']); ?>" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Remaining Sessions</label>
                                <input type="number" name="remaining_sessions" min="0" max="100" class="form-control" value="<?php echo htmlspecialchars($student['remaining_sessions'] ?? 30); ?>">
                                <small class="text-muted">Default is 30 sessions per semester</small>
                            </div>

                            <hr>
                            <div class="d-flex justify-content-between">
                                <a href="students.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Students</a>
                                <button type="submit" class="btn btn-update"><i class="fas fa-save me-2"></i>Update Student</button>
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