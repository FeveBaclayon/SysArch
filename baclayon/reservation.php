<?php
include "dataB.php";
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Create reservation table if not exists
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

// Handle status update
if (isset($_GET['approve'])) {
    $id = $_GET['approve'];
    mysqli_query($conn, "UPDATE reservations SET status = 'approved' WHERE id = $id");
    header("Location: reservation.php");
    exit();
}

if (isset($_GET['reject'])) {
    $id = $_GET['reject'];
    mysqli_query($conn, "UPDATE reservations SET status = 'rejected' WHERE id = $id");
    header("Location: reservation.php");
    exit();
}

// Get reservations
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
    <title>Reservation - Admin</title>
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
        .badge-approved { background-color: #28a745; }
        .badge-rejected { background-color: #dc3545; }
        .btn-approve { background-color: #28a745; color: white; padding: 4px 12px; margin-right: 5px; }
        .btn-reject { background-color: #dc3545; color: white; padding: 4px 12px; }
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
            <h2 class="page-header">Laboratory Reservations</h2>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ID Number</th>
                                    <th>Name</th>
                                    <th>Laboratory</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($res = mysqli_fetch_assoc($reservations_result)): ?>
                                <tr>
                                    <td><?php echo $res['id']; ?></td>
                                    <td><?php echo htmlspecialchars($res['id_number'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($res['full_name'] ?: 'Guest'); ?></td>
                                    <td><?php echo htmlspecialchars($res['laboratory']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($res['reservation_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($res['time_from'])) . ' - ' . date('h:i A', strtotime($res['time_to'])); ?></td>
                                    <td><?php echo htmlspecialchars($res['purpose']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $res['status']; ?>">
                                            <?php echo $res['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($res['status'] == 'pending'): ?>
                                            <a href="reservation.php?approve=<?php echo $res['id']; ?>" class="btn btn-approve btn-sm" onclick="return confirm('Approve this reservation?')">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="reservation.php?reject=<?php echo $res['id']; ?>" class="btn btn-reject btn-sm" onclick="return confirm('Reject this reservation?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
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