<?php
include "dataB.php";
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Ensure reservation table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    laboratory VARCHAR(100),
    purpose TEXT,
    reservation_date DATE,
    time_from TIME,
    time_to TIME,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Handle Approve/Reject securely
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if (in_array($action, ['approve', 'reject'])) {
        $new_status = ($action === 'approve') ? 'approved' : 'rejected';
        $stmt = mysqli_prepare($conn, "UPDATE reservations SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_status, $id);
        mysqli_stmt_execute($stmt);
    }
    header("Location: reservation.php");
    exit();
}

// Fetch all reservations
$reservations_query = "SELECT r.*, u.id_number, CONCAT(u.first_name, ' ', u.last_name) as full_name
    FROM reservations r
    LEFT JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC";
$reservations_result = mysqli_query($conn, $reservations_query);

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
    <title>Reservation Management - Admin</title>
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
        .badge-pending { background-color: #ffc107; color: #000; }
        .badge-approved { background-color: #28a745; color: white; }
        .badge-rejected { background-color: #dc3545; color: white; }
        .btn-approve { background-color: #28a745; color: white; padding: 4px 12px; }
        .btn-reject { background-color: #dc3545; color: white; padding: 4px 12px; }
        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e7f1ff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #007bff;
        }
        .pulse-dot {
            width: 8px;
            height: 8px;
            background-color: #28a745;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(40, 167, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
        }
    </style>
</head>
<body>
<nav class="top-navbar navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="fas fa-university me-2"></i>College of Computer Studies Admin</a>
        <div class="collapse navbar-collapse justify-content-end">
            <ul class="navbar-nav align-items-center">
                <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="reservation.php">Reservation</a></li>
                <li class="nav-item"><a href="reservation.php?logout=true" class="btn btn-logout ms-3">Log out</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="content-wrapper">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Laboratory Reservations</h2>
            <div class="live-indicator">
                <span class="pulse-dot"></span>
                Auto-Refresh: <span id="countdown">15</span>s
                <button class="btn btn-sm btn-outline-primary ms-2" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh Now
                </button>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ID Number</th>
                                <th>Name</th>
                                <th>Laboratory</th>
                                <th>PC</th> <!-- ✅ NEW -->
                                <th>Date</th>
                                <th>Time</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($reservations_result) > 0): ?>
                                <?php while($res = mysqli_fetch_assoc($reservations_result)): ?>
                                <tr class="<?php echo $res['status'] == 'pending' ? 'table-warning' : ''; ?>">
                                    <td>#<?php echo $res['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($res['full_name'] ?: 'Unknown'); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($res['id_number'] ?: 'N/A'); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($res['laboratory']); ?></td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($res['reservation_date'])); ?><br>
                                        <small class="text-muted"><?php echo date('h:i A', strtotime($res['time_from'])) . ' - ' . date('h:i A', strtotime($res['time_to'])); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($res['purpose']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $res['status']; ?>">
                                            <?php echo ucfirst($res['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($res['status'] == 'pending'): ?>
                                        <a href="?action=approve&id=<?php echo $res['id']; ?>" class="btn btn-approve btn-sm" onclick="return confirm('Approve this reservation?')">
                                            <i class="fas fa-check me-1"></i>Approve
                                        </a>
                                        <a href="?action=reject&id=<?php echo $res['id']; ?>" class="btn btn-reject btn-sm" onclick="return confirm('Reject this reservation?')">
                                            <i class="fas fa-times me-1"></i>Reject
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted"><i class="fas fa-lock me-1"></i>Decided</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                        No reservation requests yet.
                                    </td>
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
<script>
    // Live Auto-Refresh Countdown
    let seconds = 15;
    const countdownEl = document.getElementById('countdown');
    
    const timer = setInterval(() => {
        seconds--;
        countdownEl.textContent = seconds;
        if (seconds <= 0) {
            clearInterval(timer);
            location.reload();
        }
    }, 1000);
</script>
</body>
</html> 