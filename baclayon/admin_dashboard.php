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

// Handle Announcement Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_announcement'])) {
    $announcement_content = trim($_POST['announcement']);
    
    if (!empty($announcement_content)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO announcements (content, posted_by, created_at) VALUES (?, ?, NOW())");
        $posted_by = "CCS Admin";
        mysqli_stmt_bind_param($stmt, "ss", $announcement_content, $posted_by);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Announcement posted successfully!";
        } else {
            $error_msg = "Error posting announcement.";
        }
    } else {
        $error_msg = "Announcement cannot be empty.";
    }
}

// Handle Announcement Delete
if (isset($_GET['delete_announcement'])) {
    $ann_id = $_GET['delete_announcement'];
    $stmt = mysqli_prepare($conn, "DELETE FROM announcements WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $ann_id);
    mysqli_stmt_execute($stmt);
    header("Location: admin_dashboard.php");
    exit();
}

// Get Statistics
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'user') as total_students,
        (SELECT COUNT(*) FROM sit_in_records WHERE status = 'active') as currently_sit_in,
        (SELECT COUNT(*) FROM sit_in_records) as total_sit_in
";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get Course Distribution for Pie Chart
$course_query = "
    SELECT course, COUNT(*) as count 
    FROM users 
    WHERE role = 'user'
    GROUP BY course
";
$course_result = mysqli_query($conn, $course_query);
$course_data = [];
$course_colors = ['#007bff', '#dc3545', '#ffc107', '#28a745', '#17a2b8', '#6610f2'];
$color_index = 0;
while ($row = mysqli_fetch_assoc($course_result)) {
    $course_data[] = [
        'course' => $row['course'],
        'count' => $row['count'],
        'color' => $course_colors[$color_index % count($course_colors)]
    ];
    $color_index++;
}

// Get Announcements
$announcements_query = "SELECT * FROM announcements ORDER BY created_at DESC";
$announcements_result = mysqli_query($conn, $announcements_query);

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
    <title>Admin Dashboard - CCS Sit-in Monitoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }
        body {
            background-color: #f4f6f9;
        }
        /* Top Navigation Bar */
        .top-navbar {
            background-color: #0f4c96;
            color: white;
            padding: 12px 24px;
        }
        .top-navbar .navbar-brand {
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .top-navbar .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-size: 0.9rem;
            margin-left: 15px;
        }
        .top-navbar .nav-link:hover {
            color: white !important;
        }
        .btn-logout {
            background-color: #ffc107;
            color: #000 !important;
            padding: 6px 16px;
            border-radius: 4px;
            font-weight: 500;
        }
        .btn-logout:hover {
            background-color: #e0a800;
        }
        /* Main Content */
        .content-wrapper {
            padding: 24px;
        }
        /* Cards */
        .card-header-custom {
            background-color: #007bff;
            color: white;
            font-weight: 600;
            padding: 12px 16px;
            border-radius: 6px 6px 0 0 !important;
        }
        .dashboard-card {
            border: 1px solid #ddd;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            background: white;
            margin-bottom: 24px;
        }
        /* Statistics */
        .stat-item {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .stat-item:last-child {
            border-bottom: none;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f4c96;
        }
        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            padding: 20px;
        }
        /* Announcement Form */
        .announcement-form textarea {
            min-height: 80px;
            resize: vertical;
        }
        .btn-submit-announcement {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 24px;
        }
        .btn-submit-announcement:hover {
            background-color: #218838;
        }
        /* Posted Announcements */
        .announcement-item {
            padding: 16px;
            border-bottom: 1px solid #eee;
        }
        .announcement-item:last-child {
            border-bottom: none;
        }
        .announcement-header {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }
        .announcement-meta {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 8px;
        }
        .announcement-content {
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 4px;
            font-size: 0.95rem;
            color: #555;
        }
        .btn-delete-announcement {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 4px 12px;
            font-size: 0.85rem;
            margin-top: 8px;
        }
        /* Search Modal */
        .modal-header {
            background-color: #0f4c96;
            color: white;
        }
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        .search-box {
            padding: 20px;
        }
        .search-box input {
            padding: 12px;
            font-size: 1rem;
        }
        .search-box .btn {
            padding: 12px 24px;
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="top-navbar navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-university me-2"></i>
                College of Computer Studies Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#searchModal">Search</a></li>
                    <li class="nav-item"><a class="nav-link" href="students.php">Students</a></li>
                    <li class="nav-item"><a class="nav-link" href="sit_in.php">Sit-in</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_sit_in_records.php">View Sit-in Records</a></li>
                    <li class="nav-item"><a class="nav-link" href="sit_in_reports.php">Sit-in Reports</a></li>
                    <li class="nav-item"><a class="nav-link" href="feedback_reports.php">Feedback Reports</a></li>
                    <li class="nav-item"><a class="nav-link" href="reservation.php">Reservation</a></li>
                    <li class="nav-item">
                        <a href="admin_dashboard.php?logout=true" class="btn btn-logout ms-3">Log out</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <!-- Left Column: Statistics -->
                <div class="col-lg-5 col-md-12">
                    <div class="dashboard-card">
                        <div class="card-header-custom">
                            <i class="fas fa-chart-pie me-2"></i>Statistics
                        </div>
                        <div class="card-body">
                            <div class="stat-item">
                                <div class="stat-label">Students Registered: <strong><?php echo $stats['total_students']; ?></strong></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Currently Sit-in: <strong><?php echo $stats['currently_sit_in']; ?></strong></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Total Sit-in: <strong><?php echo $stats['total_sit_in']; ?></strong></div>
                            </div>
                            <!-- Pie Chart -->
                            <div class="chart-container">
                                <canvas id="courseChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Announcements -->
                <div class="col-lg-7 col-md-12">
                    <div class="dashboard-card">
                        <div class="card-header-custom">
                            <i class="fas fa-bullhorn me-2"></i>Announcement
                        </div>
                        <div class="card-body">
                            <!-- Success/Error Messages -->
                            <?php if(!empty($success_msg)): ?>
                                <div class="alert alert-success"><?php echo $success_msg; ?></div>
                            <?php endif; ?>
                            <?php if(!empty($error_msg)): ?>
                                <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                            <?php endif; ?>

                            <!-- Post New Announcement -->
                            <form method="POST" class="announcement-form mb-4">
                                <div class="mb-3">
                                    <label class="form-label">New Announcement</label>
                                    <textarea name="announcement" class="form-control" placeholder="Type your announcement here..." required></textarea>
                                </div>
                                <button type="submit" name="submit_announcement" class="btn btn-submit-announcement">
                                    <i class="fas fa-paper-plane me-2"></i>Submit
                                </button>
                            </form>

                            <hr>

                            <!-- Posted Announcements -->
                            <h5 class="mb-3"><i class="fas fa-list me-2"></i>Posted Announcement</h5>
                            <div class="announcements-list">
                                <?php 
                                if (mysqli_num_rows($announcements_result) > 0):
                                    while($ann = mysqli_fetch_assoc($announcements_result)): 
                                ?>
                                <div class="announcement-item">
                                    <div class="announcement-header">
                                        <?php echo htmlspecialchars($ann['posted_by']); ?> | 
                                        <small><?php echo date('Y-M-d', strtotime($ann['created_at'])); ?></small>
                                    </div>
                                    <div class="announcement-content">
                                        <?php echo nl2br(htmlspecialchars($ann['content'])); ?>
                                    </div>
                                    <button class="btn btn-delete-announcement" onclick="deleteAnnouncement(<?php echo $ann['id']; ?>)">
                                        <i class="fas fa-trash me-1"></i>Delete
                                    </button>
                                </div>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>No announcements posted yet.</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Modal -->
    <div class="modal fade" id="searchModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-search me-2"></i>Search Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body search-box">
                    <form action="search_student.php" method="GET">
                        <div class="mb-3">
                            <input type="text" name="search" class="form-control" placeholder="Search by ID number or name..." required>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Pie Chart
        const ctx = document.getElementById('courseChart').getContext('2d');
        const courseData = <?php echo json_encode($course_data); ?>;
        
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: courseData.map(item => item.course),
                datasets: [{
                    data: courseData.map(item => item.count),
                    backgroundColor: courseData.map(item => item.color),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed + ' students';
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // Delete Announcement
        function deleteAnnouncement(id) {
            if (confirm('Are you sure you want to delete this announcement?')) {
                window.location.href = 'admin_dashboard.php?delete_announcement=' + id;
            }
        }
    </script>
</body>
</html>