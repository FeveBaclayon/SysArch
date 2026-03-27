<?php
include "dataB.php";
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'admin') {
    header("Location: index.php");
    exit();
}

$success_msg = "";
$error_msg = "";

// Handle Sit-In Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_sit_in'])) {
    $user_id = $_POST['user_id'];
    $laboratory = trim($_POST['laboratory']);
    $purpose = trim($_POST['purpose']);
    
    // Check if user has remaining sessions
    $check_stmt = mysqli_prepare($conn, "SELECT remaining_sessions FROM users WHERE id = ?");
    mysqli_stmt_bind_param($check_stmt, "i", $user_id);
    mysqli_stmt_execute($check_stmt);
    $user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($check_stmt));
    
    if ($user_data['remaining_sessions'] > 0) {
        $stmt = mysqli_prepare($conn, "INSERT INTO sit_in_records (user_id, laboratory, purpose, status, check_in_time) VALUES (?, ?, ?, 'active', NOW())");
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $laboratory, $purpose);
        
        if (mysqli_stmt_execute($stmt)) {
            // Decrement remaining sessions
            mysqli_query($conn, "UPDATE users SET remaining_sessions = remaining_sessions - 1 WHERE id = $user_id");
            $success_msg = "Sit-in recorded successfully!";
        } else {
            $error_msg = "Error recording sit-in.";
        }
    } else {
        $error_msg = "Student has no remaining sessions!";
    }
}

// Get active sit-ins
$sit_in_query = "SELECT s.*, u.id_number, CONCAT(u.first_name, ' ', u.last_name) as full_name 
                 FROM sit_in_records s 
                 JOIN users u ON s.user_id = u.id 
                 WHERE s.status = 'active'
                 ORDER BY s.check_in_time DESC";
$sit_in_result = mysqli_query($conn, $sit_in_query);

// Get all students for modal
$students_query = "SELECT * FROM users WHERE role = 'user' ORDER BY last_name";
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
    <title>Current Sit-in - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background-color: #f4f6f9; }
        .top-navbar { background-color: #0f4c96; color: white; padding: 12px 24px; }
        .top-navbar .navbar-brand { color: white; font-weight: 600; }
        .top-navbar .nav-link { color: rgba(255,255,255,0.9) !important; margin-left: 15px; }
        .top-navbar .nav-link:hover { color: white !important; }
        .btn-logout { background-color: #ffc107; color: #000 !important; padding: 6px 16px; border-radius: 4px; }
        .content-wrapper { padding: 24px; }
        .page-header { text-align: center; margin-bottom: 30px; }
        .table thead { background-color: #0f4c96; color: white; }
        .btn-sit-in { background-color: #28a745; color: white; margin-bottom: 20px; }
        .btn-checkout { background-color: #ffc107; color: #000; padding: 4px 12px; }
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
                    <li class="nav-item"><a href="sit_in.php?logout=true" class="btn btn-logout ms-3">Log out</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="container">
            <h2 class="page-header">Current Sit-in</h2>
            
            <?php if(!empty($success_msg)): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-danger"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <button class="btn btn-sit-in" data-bs-toggle="modal" data-bs-target="#sitInModal">
                <i class="fas fa-plus me-2"></i>Sit In
            </button>

            <div class="row mb-3">
                <div class="col-md-2">
                    <select class="form-select">
                        <option>10</option>
                        <option>25</option>
                        <option>50</option>
                    </select>
                </div>
                <div class="col-md-3 offset-md-7">
                    <input type="text" class="form-control" placeholder="Search...">
                </div>
            </div>

            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Sit ID Number</th>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Purpose</th>
                        <th>Sit Lab</th>
                        <th>Session</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($sit_in_result) > 0): ?>
                        <?php while($record = mysqli_fetch_assoc($sit_in_result)): ?>
                        <tr>
                            <td><?php echo $record['id']; ?></td>
                            <td><?php echo htmlspecialchars($record['id_number']); ?></td>
                            <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['purpose']); ?></td>
                            <td><?php echo htmlspecialchars($record['laboratory']); ?></td>
                            <td><?php echo date('h:i A', strtotime($record['check_in_time'])); ?></td>
                            <td><span class="badge bg-success"><?php echo $record['status']; ?></span></td>
                            <td>
                                <a href="checkout.php?id=<?php echo $record['id']; ?>" class="btn btn-checkout btn-sm">
                                    <i class="fas fa-sign-out-alt me-1"></i>Check Out
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sit In Modal -->
    <div class="modal fade" id="sitInModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sit In Form</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ID Number</label>
                            <select name="user_id" class="form-select" required>
                                <option value="">Select Student</option>
                                <?php while($student = mysqli_fetch_assoc($students_result)): ?>
                                    <option value="<?php echo $student['id']; ?>" 
                                            data-name="<?php echo $student['first_name'] . ' ' . $student['last_name']; ?>"
                                            data-sessions="<?php echo $student['remaining_sessions']; ?>">
                                        <?php echo $student['id_number']; ?> - <?php echo $student['first_name'] . ' ' . $student['last_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Student Name</label>
                            <input type="text" class="form-control" id="studentName" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Purpose</label>
                            <input type="text" name="purpose" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Lab</label>
                            <input type="text" name="laboratory" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Remaining Session</label>
                            <input type="text" class="form-control" id="remainingSessions" readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="submit_sit_in" class="btn btn-primary">Sit In</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('select[name="user_id"]').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            document.getElementById('studentName').value = selected.dataset.name || '';
            document.getElementById('remainingSessions').value = selected.dataset.sessions || '';
        });
    </script>
</body>
</html>