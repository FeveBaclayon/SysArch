<?php
include "dataB.php";
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// 🔹 Handle Feedback Submission 🔹
$feedback_success = "";
$feedback_error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_feedback'])) {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    if (!empty($subject) && !empty($message)) {
        $feedback_stmt = mysqli_prepare($conn, "INSERT INTO feedback (user_id, subject, message, created_at) VALUES (?, ?, ?, NOW())");
        mysqli_stmt_bind_param($feedback_stmt, "iss", $user_id, $subject, $message);
        if (mysqli_stmt_execute($feedback_stmt)) {
            $feedback_success = "Feedback submitted successfully!";
        } else {
            $feedback_error = "Error submitting feedback.";
        }
    } else {
        $feedback_error = "Subject and message cannot be empty.";
    }
}

// 🔹 Fetch Sit-in History 🔹
$history_query = "SELECT * FROM sit_in_records WHERE user_id = ? ORDER BY check_in_time DESC LIMIT 15";
$history_stmt = mysqli_prepare($conn, $history_query);
mysqli_stmt_bind_param($history_stmt, "i", $user_id);
mysqli_stmt_execute($history_stmt);
$history_result = mysqli_stmt_get_result($history_stmt);

// Built in logout handler
if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head >
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { font-family: 'Segoe UI', Tahoma, sans-serif; }
        .top-navbar { background-color: #13499b; color: white; padding: 10px 24px; margin: 0; }
        .top-navbar a { color: white; text-decoration: none; margin-left: 16px; font-size: 0.95rem; }
        .btn-logout { background-color: #ffc107; color: black !important; padding: 6px 16px; border-radius: 4px; font-weight: 500; }
        .card-header { background-color: #13499b; color: white; font-weight: 600; border: none; }
        .dashboard-card { border: 1px solid #cccccc; border-radius: 6px; box-shadow: none; margin-bottom: 20px; }
        .avatar { width: 140px; height: 140px; border-radius: 100%; margin: 16px auto; display: block; border: 1px solid #ddd; }
        .info-row { padding: 6px 16px; font-size: 0.95rem; }
        .info-row i { width: 20px; margin-right: 8px; color: #333; }
        .divider { border-bottom: 1px solid #ccc; margin: 8px 16px; }
        .announcement-item { padding: 16px; border-bottom: 1px solid #ccc; }
        .announcement-item:last-child { border-bottom: none; }
        .announcement-date { font-weight: 600; margin-bottom: 12px; }
        .announcement-body { background-color: #f8f9fa; padding: 12px; font-size: 0.95rem; }
        .rules-scroll { height: 380px; overflow-y: auto; padding: 16px; font-size: 0.95rem; line-height: 1.6; }
        body { background-color: #ffffff; }
        
        /* New Styles for History & Feedback */
        .history-table th { background-color: #e9ecef; font-size: 0.9rem; }
        .history-table td { font-size: 0.9rem; vertical-align: middle; }
        .badge-active { background-color: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
        .badge-completed { background-color: #6c757d; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
        .btn-feedback { background-color: #0d6efd; color: white; font-weight: 600; }
        .btn-feedback:hover { background-color: #0b5ed7; color: white; }
        .modal-header { background-color: #13499b; color: white; }
        .modal-header .btn-close { filter: invert(1); }
        /* Reservation Button in Navbar */
        .reservation-btn {
            background-color: #ffc107;
            color: #000 !important;
            padding: 6px 14px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-left: 16px;
            transition: all 0.2s;
        }
        .reservation-btn:hover {
            background-color: #e0a800;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>

<!-- Top Navigation Bar -->
<div class="top-navbar d-flex justify-content-between align-items-center">
    <div class="fw-bold">Dashboard</div>
    <div class="d-flex align-items-center flex-wrap">
        <a href="#">Notification <i class="fa fa-caret-down"></i></a>
        <a href="dashboard.php">Home</a>
        <a href="Edit Profile.php">Edit Profile</a>
        <a href="#">History</a>
        
        <!-- ✅ NEW RESERVATION BUTTON -->
        <a href="student_reservation.php" class="reservation-btn">
            <i class="fas fa-calendar-check me-1"></i> Reservation
        </a>
        <a href="dashboard.php?logout=true" class="btn-logout ms-3">Log out</a>
    </div>
</div>

<!-- Main Dashboard Content -->
<div class="container-fluid p-4">
    <?php if(!empty($feedback_success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($feedback_success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(!empty($feedback_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($feedback_error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- LEFT COLUMN: Student Information -->
        <div class="col-lg-4 col-md-12">
            <div class="card dashboard-card h-100">
                <div class="card-header"><i class="fa fa-user me-2"></i> Student Information</div>
                <div class="card-body p-0">
                    <img src="Avatar.png" class="avatar" alt="Student Avatar">
                    <div class="divider"></div>
                    <div class="info-row"><i class="fa fa-user"></i><strong>Name:</strong> <?php echo htmlspecialchars($user['first_name'].' '.$user['last_name']); ?></div>
                    <div class="info-row"><i class="fa fa-graduation-cap"></i><strong>Course:</strong> <?php echo htmlspecialchars($user['course']); ?></div>
                    <div class="info-row"><i class="fa fa-level-up-alt"></i><strong>Year:</strong> <?php echo htmlspecialchars($user['course_level']); ?></div>
                    <div class="info-row"><i class="fa fa-envelope"></i><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></div>
                    <div class="info-row"><i class="fa fa-map-marker-alt"></i><strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?></div>
                    <div class="info-row"><i class="fa fa-clock"></i><strong>Sessions:</strong> <?php echo htmlspecialchars($user['remaining_sessions'] ?? 30); ?></div>
                </div>
            </div>
        </div>

        <!-- MIDDLE COLUMN: Announcements -->
        <div class="col-lg-4 col-md-12">
            <div class="card dashboard-card h-100">
                <div class="card-header"><i class="fa fa-bullhorn me-2"></i> Announcement</div>
                <div class="card-body p-0">
                    <?php
                    $ann_query = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 10";
                    $ann_result = mysqli_query($conn, $ann_query);
                    if ($ann_result && mysqli_num_rows($ann_result) > 0): 
                        while($ann = mysqli_fetch_assoc($ann_result)): 
                    ?>
                    <div class="announcement-item">
                        <div class="announcement-date">
                            <?php echo htmlspecialchars($ann['posted_by']); ?> | 
                            <?php echo date('Y-M-d', strtotime($ann['created_at'])); ?>
                        </div>
                        <div class="announcement-body">
                            <?php echo nl2br(htmlspecialchars($ann['content'])); ?>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i><p>No announcements yet.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Rules & Regulations -->
        <div class="col-lg-4 col-md-12">
            <div class="card dashboard-card h-100">
                <div class="card-header">Rules and Regulation</div>
                <div class="card-body p-0">
                    <div class="rules-scroll">
                        <div class="text-center fw-bold mb-3">UNIVERSITY OF CEBU<br>COLLEGE OF INFORMATION & COMPUTER STUDIES<hr>LABORATORY RULES AND REGULATIONS</div>
                        <p>To avoid embarrassment and maintain camaraderie with your friends and superiors at our laboratories, please observe the following:</p>
                        <ol>
                            <li class="mb-2">Maintain silence, proper decorum, and discipline inside the laboratory.</li>
                            <li class="mb-2">Games are not allowed inside the lab.</li>
                            <li class="mb-2">Surfing the Internet is allowed only with instructor permission. Downloading software is prohibited.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- NEW ROW: Sit-in History & Feedback -->
    <div class="row mt-4 g-4">
        <div class="col-lg-8 col-md-12">
            <div class="card dashboard-card">
                <div class="card-header"><i class="fas fa-history me-2"></i> Sit-in History</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table history-table mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Laboratory</th>
                                    <th>Purpose</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($history_result) > 0): ?>
                                    <?php while($rec = mysqli_fetch_assoc($history_result)): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d', strtotime($rec['check_in_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($rec['laboratory']); ?></td>
                                        <td><?php echo htmlspecialchars($rec['purpose']); ?></td>
                                        <td><?php echo date('h:i A', strtotime($rec['check_in_time'])); ?></td>
                                        <td><?php echo $rec['check_out_time'] ? date('h:i A', strtotime($rec['check_out_time'])) : '-'; ?></td>
                                        <td>
                                            <span class="<?php echo $rec['status'] == 'active' ? 'badge-active' : 'badge-completed'; ?>">
                                                <?php echo ucfirst($rec['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No sit-in records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-12">
            <div class="card dashboard-card">
                <div class="card-header"><i class="fas fa-comment-dots me-2"></i> Feedback</div>
                <div class="card-body text-center p-4">
                    <i class="fas fa-headset fa-4x text-primary mb-3"></i>
                    <h5 class="mb-2">Have a suggestion or issue?</h5>
                    <p class="text-muted mb-4">Your feedback helps us improve the laboratory experience for all students.</p>
                    <button class="btn btn-feedback w-100" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                        <i class="fas fa-paper-plane me-2"></i>Open Feedback Form
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Feedback Modal (Popup Window) -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Submit Feedback</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="e.g., Lab equipment issue, Suggestion..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Message</label>
                        <textarea name="message" class="form-control" rows="5" placeholder="Describe your feedback in detail..." required></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="submit_feedback" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Send Feedback
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>