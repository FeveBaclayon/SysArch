<?php
include "dataB.php";
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'admin') {
    header("Location: index.php");
    exit();
}

$success_msg = "";
$error_msg = "";

// Handle Delete Student
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ? AND role = 'user'");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $success_msg = "Student deleted successfully!";
    } else {
        $error_msg = "Error deleting student.";
    }
}

// Handle Reset All Sessions
if (isset($_POST['reset_all'])) {
    $stmt = mysqli_query($conn, "UPDATE users SET remaining_sessions = 30 WHERE role = 'user'");
    if ($stmt) {
        $success_msg = "All sessions reset to 30!";
    } else {
        $error_msg = "Error resetting sessions.";
    }
}

// Get all students
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$students_query = "SELECT * FROM users WHERE role = 'user'";
if (!empty($search)) {
    $students_query .= " AND (id_number LIKE '%$search%' OR CONCAT(first_name, ' ', last_name) LIKE '%$search%')";
}
$students_query .= " ORDER BY id DESC";
$students_result = mysqli_query($conn, $students_query);

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
    <title>Students Information - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background-color: #f4f6f9; }
        .top-navbar {
            background-color: #0f4c96;
            color: white;
            padding: 12px 24px;
        }
        .top-navbar .navbar-brand { color: white; font-weight: 600; }
        .top-navbar .nav-link { color: rgba(255,255,255,0.9) !important; margin-left: 15px; }
        .top-navbar .nav-link:hover { color: white !important; }
        .btn-logout { background-color: #ffc107; color: #000 !important; padding: 6px 16px; border-radius: 4px; }
        .content-wrapper { padding: 24px; }
        .page-header { text-align: center; margin-bottom: 30px; }
        .btn-add { background-color: #007bff; color: white; margin-right: 10px; }
        .btn-reset { background-color: #dc3545; color: white; }
        .table thead { background-color: #0f4c96; color: white; }
        .btn-edit { background-color: #007bff; color: white; padding: 4px 12px; margin-right: 5px; }
        .btn-delete { background-color: #dc3545; color: white; padding: 4px 12px; }
    </style>
</head>
<body>
    <nav class="top-navbar navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-university me-2"></i>College of Computer Studies Admin</a>
            <div class="collapse navbar-collapse justify-content-end">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="students.php">Students</a></li>
                    <li class="nav-item"><a class="nav-link" href="sit_in.php">Sit-in</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_sit_in_records.php">View Sit-in Records</a></li>
                    <li class="nav-item"><a href="students.php?logout=true" class="btn btn-logout ms-3">Log out</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="container">
            <h2 class="page-header">Students Information</h2>
            
            <?php if(!empty($success_msg)): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-danger"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <div class="mb-3">
                <a href="add_student.php" class="btn btn-add"><i class="fas fa-plus me-2"></i>Add Students</a>
                <form method="POST" style="display:inline;">
                    <button type="submit" name="reset_all" class="btn btn-reset" onclick="return confirm('Reset all student sessions to 30?')">
                        <i class="fas fa-sync-alt me-2"></i>Reset All Session
                    </button>
                </form>
            </div>

            <div class="row mb-3">
                <div class="col-md-2">
                    <select class="form-select">
                        <option>10</option>
                        <option>25</option>
                        <option>50</option>
                    </select>
                </div>
                <div class="col-md-3 offset-md-7">
                    <form method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>

            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Year Level</th>
                        <th>Course</th>
                        <th>Remaining Session</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($student = mysqli_fetch_assoc($students_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['id_number']); ?></td>
                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['course_level']); ?></td>
                        <td><?php echo htmlspecialchars($student['course']); ?></td>
                        <td><?php echo htmlspecialchars($student['remaining_sessions']); ?></td>
                        <td>
                            <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-edit btn-sm">Edit</a>
                            <a href="students.php?delete=<?php echo $student['id']; ?>" class="btn btn-delete btn-sm" onclick="return confirm('Delete this student?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>