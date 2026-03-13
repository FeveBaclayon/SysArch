<?php
include "dataB.php";
session_start();

// SECURITY CHECK: Kick out if not logged in OR if not an admin
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'admin') {
    // Destroy session and send to login just to be safe
    session_destroy();
    header("Location: index.php");
    exit();
}

// Built-in logout handler
if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Fetch some quick stats for the admin dashboard
$student_count_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$student_count = mysqli_fetch_assoc($student_count_query)['count'];

// Fetch recently registered students
$recent_students = mysqli_query($conn, "SELECT id_number, first_name, last_name, course, course_level FROM users WHERE role = 'student' ORDER BY id DESC LIMIT 5");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | CCS Sit-in Monitoring</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        * { font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background-color: #f4f6f9; }

        /* Top Navigation Bar - Matches previous design */
        .top-navbar {
            background-color: #13499b;
            color: white;
            padding: 10px 24px;
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
        }

        /* Admin Cards */
        .stat-card {
            background: white;
            border-radius: 6px;
            border: 1px solid #ddd;
            border-left: 5px solid #13499b;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .stat-card .icon {
            font-size: 2.5rem;
            color: #13499b;
            opacity: 0.2;
        }
        .stat-card .details h3 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
        }
        .stat-card .details p {
            margin: 0;
            color: #666;
            font-weight: 500;
        }

        /* Table Card */
        .card-header-custom {
            background-color: #13499b;
            color: white;
            font-weight: 600;
            border-radius: 6px 6px 0 0;
            padding: 12px 20px;
        }
        .table-card {
            background: white;
            border-radius: 6px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }
    </style>
</head>
<body>

<!-- Top Navigation Bar -->
<div class="top-navbar d-flex justify-content-between align-items-center">
    <div class="fw-bold"><i class="fas fa-shield-alt me-2"></i> Admin Panel</div>
    <div>
        <a href="admin_dashboard.php" class="active">Dashboard</a>
        <a href="#">Search</a>
        <a href="#">View Sit-ins</a>
        <a href="#">Announcements</a>
        <a href="#">Reports</a>
        <a href="?logout=true" class="btn-logout">Log out</a>
    </div>
</div>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Welcome, Admin <?php echo htmlspecialchars($_SESSION['name']); ?></h2>
    </div>

    <!-- Quick Stats Row -->
    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="stat-card border-primary">
                <div class="details">
                    <p>Total Students</p>
                    <h3><?php echo $student_count; ?></h3>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #28a745;">
                <div class="details">
                    <p>Active Sit-ins</p>
                    <h3>0</h3> <!-- Placeholder -->
                </div>
                <div class="icon" style="color: #28a745;"><i class="fas fa-desktop"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #ffc107;">
                <div class="details">
                    <p>Reservations</p>
                    <h3>0</h3> <!-- Placeholder -->
                </div>
                <div class="icon" style="color: #ffc107;"><i class="fas fa-calendar-check"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #dc3545;">
                <div class="details">
                    <p>Pending Approvals</p>
                    <h3>0</h3> <!-- Placeholder -->
                </div>
                <div class="icon" style="color: #dc3545;"><i class="fas fa-exclamation-circle"></i></div>
            </div>
        </div>
    </div>

    <!-- Data Tables Row -->
    <div class="row">
        <div class="col-12">
            <div class="table-card">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-user-plus me-2"></i> Recently Registered Students</span>
                    <button class="btn btn-sm btn-light">View All</button>
                </div>
                <div class="table-responsive p-3">
                    <table class="table table-hover table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID Number</th>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Year</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($recent_students) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($recent_students)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id_number']); ?></td>
                                        <td><?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['course']); ?></td>
                                        <td><?php echo htmlspecialchars($row['course_level']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></button>
                                            <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">No students found.</td>
                                </tr>
                            <?php endif; ?>
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