<?php
include "dataB.php";
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Get date range filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get filtered records
$query = "SELECT s.*, u.id_number, CONCAT(u.first_name, ' ', u.last_name) as full_name, u.course
          FROM sit_in_records s 
          JOIN users u ON s.user_id = u.id 
          WHERE DATE(s.check_in_time) BETWEEN ? AND ?
          ORDER BY s.check_in_time DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$records_result = mysqli_stmt_get_result($stmt);

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total_records,
                COUNT(DISTINCT user_id) as unique_students,
                AVG(TIMESTAMPDIFF(MINUTE, check_in_time, COALESCE(check_out_time, NOW()))) as avg_duration_minutes
                FROM sit_in_records 
                WHERE DATE(check_in_time) BETWEEN ? AND ?";
$stats_stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stats_stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stats_stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stats_stmt));

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
    <title>Sit-in Reports - Admin</title>
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
        .stats-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .stats-number { font-size: 2rem; font-weight: bold; color: #0f4c96; }
        .table thead { background-color: #0f4c96; color: white; }
        .filter-section { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
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
                    <li class="nav-item"><a class="nav-link" href="sit_in_reports.php">Sit-in Reports</a></li>
                    <li class="nav-item"><a href="sit_in_reports.php?logout=true" class="btn btn-logout ms-3">Log out</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="container">
            <h2 class="page-header">Sit-in Reports</h2>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2"><i class="fas fa-filter me-2"></i>Filter</button>
                        <button type="button" class="btn btn-success" onclick="window.print()"><i class="fas fa-print me-2"></i>Print</button>
                    </div>
                </form>
            </div>

            <!-- Statistics -->
            <div class="row">
                <div class="col-md-4">
                    <div class="stats-card text-center">
                        <div class="stats-number"><?php echo $stats['total_records'] ?? 0; ?></div>
                        <div class="text-muted">Total Sit-ins</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card text-center">
                        <div class="stats-number"><?php echo $stats['unique_students'] ?? 0; ?></div>
                        <div class="text-muted">Unique Students</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card text-center">
                        <div class="stats-number"><?php echo round(($stats['avg_duration_minutes'] ?? 0) / 60, 1); ?></div>
                        <div class="text-muted">Avg Duration (hours)</div>
                    </div>
                </div>
            </div>

            <!-- Records Table -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Detailed Records</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ID Number</th>
                                    <th>Name</th>
                                    <th>Course</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Duration (mins)</th>
                                    <th>Lab</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($record = mysqli_fetch_assoc($records_result)): 
                                    // Calculate duration in minutes using PHP (not MySQL)
                                    $check_in = strtotime($record['check_in_time']);
                                    $check_out = $record['check_out_time'] ? strtotime($record['check_out_time']) : time();
                                    $duration = floor(($check_out - $check_in) / 60);
                                ?>
                                <tr>
                                    <td><?php echo $record['id']; ?></td>
                                    <td><?php echo htmlspecialchars($record['id_number']); ?></td>
                                    <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['course']); ?></td>
                                    <td><?php echo date('Y-m-d h:i A', strtotime($record['check_in_time'])); ?></td>
                                    <td><?php echo $record['check_out_time'] ? date('Y-m-d h:i A', strtotime($record['check_out_time'])) : 'Still Active'; ?></td>
                                    <td><?php echo $duration; ?></td>
                                    <td><?php echo htmlspecialchars($record['laboratory']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>