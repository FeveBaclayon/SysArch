<?php
include "dataB.php";
session_start();

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$success_msg = "";
$error_msg = "";

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get and sanitize inputs
    $last_name = trim($_POST["last_name"]);
    $first_name = trim($_POST["first_name"]);
    $middle_name = trim($_POST["middle_name"]);
    $course_level = trim($_POST["course_level"]);
    $email = trim($_POST["email"]);
    $course = trim($_POST["course"]);
    $address = trim($_POST["address"]);

    // Basic Validation
    if (empty($last_name) || empty($first_name) || empty($course_level) || empty($email) || empty($course) || empty($address)) {
        $error_msg = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email format.";
    } else {
        // Update Database using Prepared Statements for security
        $update_sql = "UPDATE users SET 
                       last_name = ?, 
                       first_name = ?, 
                       middle_name = ?, 
                       course_level = ?, 
                       email = ?, 
                       course = ?, 
                       address = ? 
                       WHERE id = ?";
                       
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "sssssssi", $last_name, $first_name, $middle_name, $course_level, $email, $course, $address, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Profile updated successfully!";
            // Update session name just in case they changed their first name
            $_SESSION["name"] = $first_name; 
        } else {
            $error_msg = "Error updating profile: " . mysqli_error($conn);
        }
    }
}

// Fetch Current User Data (Do this AFTER the update so it shows fresh data)
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | CCS Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        * {
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }

        body {
            background-color: #f4f6f9;
        }

        /* Top Navigation Bar (Matches Dashboard) */
        .top-navbar {
            background-color: #13499b;
            color: white;
            padding: 10px 24px;
            margin: 0;
        }

        .top-navbar a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            margin-left: 16px;
            font-size: 0.95rem;
            transition: color 0.2s;
        }

        .top-navbar a:hover, .top-navbar a.active {
            color: white;
            font-weight: 500;
        }

        .btn-logout {
            background-color: #ffc107;
            color: black !important;
            padding: 6px 16px;
            border-radius: 4px;
            font-weight: 500;
        }

        .btn-logout:hover {
            background-color: #e0a800;
            color: black !important;
        }

        /* Form Card Styling */
        .card-header-custom {
            background-color: #13499b;
            color: white;
            font-weight: 600;
            border-radius: 6px 6px 0 0 !important;
        }

        .profile-card {
            border: 1px solid #cccccc;
            border-radius: 6px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            background: white;
        }

        .form-label {
            font-weight: 500;
            color: #444;
            font-size: 0.9rem;
        }

        .form-control, .form-select {
            border-radius: 4px;
            padding: 10px 12px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #13499b;
            box-shadow: 0 0 0 0.2rem rgba(19, 73, 155, 0.25);
        }

        .btn-update {
            background-color: #13499b;
            color: white;
            font-weight: 600;
            padding: 10px 30px;
            border-radius: 4px;
            border: none;
        }

        .btn-update:hover {
            background-color: #0c3577;
            color: white;
        }

        /* Readonly styling */
        .form-control[readonly] {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
    </style>
</head>
<body>

<!-- Top Navigation Bar -->
<div class="top-navbar d-flex justify-content-between align-items-center">
    <div class="fw-bold">Dashboard</div>
    <div>
        <a href="#">Notification <i class="fa fa-caret-down"></i></a>
        <a href="dashboard.php">Home</a>
        <a href="Edit Profile.php" class="active">Edit Profile</a>
        <a href="#">History</a>
        <a href="#">Reservation</a>
        <a href="dashboard.php?logout=true" class="btn-logout">Log out</a>
    </div>
</div>

<!-- Main Content -->
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <div class="profile-card">
                <div class="card-header-custom p-3">
                    <i class="fas fa-user-edit me-2"></i> Edit Profile Information
                </div>
                
                <div class="card-body p-4">
                    
                    <!-- Alert Messages -->
                    <?php if(!empty($error_msg)): ?>
                        <div class="alert alert-danger shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error_msg; ?></div>
                    <?php endif; ?>

                    <?php if(!empty($success_msg)): ?>
                        <div class="alert alert-success shadow-sm"><i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?></div>
                    <?php endif; ?>

                    <!-- Edit Form -->
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                        
                        <div class="row mb-3">
                            <!-- ID Number (Readonly) -->
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label class="form-label">ID Number <small class="text-danger">(Unchangeable)</small></label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['id_number']); ?>" readonly>
                            </div>
                            
                            <!-- Email -->
                            <div class="col-md-8">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <!-- First Name -->
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            
                            <!-- Middle Name -->
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" class="form-control" value="<?php echo htmlspecialchars($user['middle_name']); ?>">
                            </div>

                            <!-- Last Name -->
                            <div class="col-md-4">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <!-- Course -->
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="form-label">Course <span class="text-danger">*</span></label>
                                <select name="course" class="form-select" required>
                                    <option value="BSIT" <?php echo ($user['course'] == 'BSIT') ? 'selected' : ''; ?>>BSIT</option>
                                    <option value="BSCS" <?php echo ($user['course'] == 'BSCS') ? 'selected' : ''; ?>>BSCS</option>
                                    <option value="CSCIE" <?php echo ($user['course'] == 'CSCIE') ? 'selected' : ''; ?>>CSCIE</option>
                                    <option value="CST" <?php echo ($user['course'] == 'CST') ? 'selected' : ''; ?>>CST</option>
                                </select>
                            </div>

                            <!-- Course Level -->
                            <div class="col-md-6">
                                <label class="form-label">Year Level <span class="text-danger">*</span></label>
                                <input type="number" name="course_level" min="1" max="5" class="form-control" value="<?php echo htmlspecialchars($user['course_level']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <!-- Address -->
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($user['address']); ?>" required>
                        </div>

                        <hr class="text-muted mb-4">

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="dashboard.php" class="text-decoration-none text-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Dashboard</a>
                            <button type="submit" class="btn btn-update"><i class="fas fa-save me-2"></i> Save Changes</button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>