<?php
include "dataB.php";
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Get all sit-in records (completed and active)
$records_query = "SELECT s.*, u.id_number, CONCAT(u.first_name, ' ', u.last_name) as full_name 
                  FROM sit_in_records s 
                  JOIN users u ON s.user_id = u.id 
                  ORDER BY s.check_in_time DESC";
$records_result = mysqli_query($conn, $records_query);

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
    <title>View Sit-in Records - Admin</title>
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
        .badge-active { background-color: #28a745; }
        .badge-completed { background-color: #6c757d; }
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
                    <li class="nav-item"><a href="view_sit_in_records.php?logout=true" class="btn btn-logout ms-3">Log out</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="container">
            <h2 class="page-header">Sit-in Records</h2>

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
                        <th>ID</th>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Purpose</th>
                        <th>Lab</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($record = mysqli_fetch_assoc($records_result)): ?>
                    <tr>
                        <td><?php echo $record['id']; ?></td>
                        <td><?php echo htmlspecialchars($record['id_number']); ?></td>
                        <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($record['purpose']); ?></td>
                        <td><?php echo htmlspecialchars($record['laboratory']); ?></td>
                        <td><?php echo date('Y-m-d h:i A', strtotime($record['check_in_time'])); ?></td>
                        <td><?php echo $record['check_out_time'] ? date('Y-m-d h:i A', strtotime($record['check_out_time'])) : '-'; ?></td>
                        <td>
                            <span class="badge <?php echo $record['status'] == 'active' ? 'badge-active' : 'badge-completed'; ?>">
                                <?php echo $record['status']; ?>
                            </span>
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