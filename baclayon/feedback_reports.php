<?php
include "dataB.php";
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Create feedback table if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    subject VARCHAR(255),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Get feedback
$feedback_query = "SELECT f.*, u.id_number, CONCAT(u.first_name, ' ', u.last_name) as full_name 
                   FROM feedback f 
                   LEFT JOIN users u ON f.user_id = u.id 
                   ORDER BY f.created_at DESC";
$feedback_result = mysqli_query($conn, $feedback_query);

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
    <title>Feedback Reports - Admin</title>
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
        .feedback-card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .feedback-header { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .feedback-subject { font-weight: 600; color: #0f4c96; }
        .feedback-meta { font-size: 0.85rem; color: #666; }
        .feedback-message { background: #f8f9fa; padding: 15px; border-radius: 4px; }
    </style>
</head>
<body>
    <nav class="top-navbar navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-university me-2"></i>College of Computer Studies Admin</a>
            <div class="collapse navbar-collapse justify-content-end">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="feedback_reports.php">Feedback Reports</a></li>
                    <li class="nav-item"><a href="feedback_reports.php?logout=true" class="btn btn-logout ms-3">Log out</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="container">
            <h2 class="page-header">Feedback Reports</h2>
            
            <?php if(mysqli_num_rows($feedback_result) > 0): ?>
                <?php while($feedback = mysqli_fetch_assoc($feedback_result)): ?>
                <div class="feedback-card">
                    <div class="feedback-header">
                        <div class="feedback-subject">
                            <i class="fas fa-comment me-2"></i>
                            <?php echo htmlspecialchars($feedback['subject'] ?: 'No Subject'); ?>
                        </div>
                        <div class="feedback-meta">
                            From: <?php echo $feedback['full_name'] ? htmlspecialchars($feedback['full_name']) : 'Guest'; ?> 
                            (<?php echo $feedback['id_number'] ? htmlspecialchars($feedback['id_number']) : 'N/A'; ?>) | 
                            <?php echo date('Y-m-d h:i A', strtotime($feedback['created_at'])); ?>
                        </div>
                    </div>
                    <div class="feedback-message">
                        <?php echo nl2br(htmlspecialchars($feedback['message'])); ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No feedback submitted yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>