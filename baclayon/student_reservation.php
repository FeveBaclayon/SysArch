<?php
include "dataB.php";
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$success_msg = "";
$error_msg = "";
$info_msg = "";

// Capture form inputs
$lab = trim($_POST['laboratory'] ?? '');
$pc = trim($_POST['pc_number'] ?? '');
$purpose = trim($_POST['purpose'] ?? '');
$date = $_POST['reservation_date'] ?? '';
$time_from = $_POST['time_from'] ?? '';
$time_to = $_POST['time_to'] ?? '';

$occupied_pcs = [];
$availability_checked = false;

// 🔹 1️⃣ CHECK AVAILABILITY
if (isset($_POST['check_availability'])) {
    if (empty($date) || empty($time_from) || empty($time_to)) {
        $error_msg = "⚠️ Please select Date, Start Time, and End Time first.";
    } elseif (strtotime($time_from) >= strtotime($time_to)) {
        $error_msg = "⚠️ End time must be after start time.";
    } else {
        $availability_checked = true;
        $info_msg = "✅ Availability checked for <strong>$date</strong> from <strong>$time_from</strong> to <strong>$time_to</strong>";
        
        // Fetch occupied PCs
        $stmt = mysqli_prepare($conn, "SELECT pc_number FROM reservations 
            WHERE reservation_date = ? AND status IN ('approved', 'pending') 
            AND time_from < ? AND time_to > ?");
        mysqli_stmt_bind_param($stmt, "sss", $date, $time_to, $time_from);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($res)) {
            $occupied_pcs[] = $row['pc_number'];
        }
    }
}

// 🔹 2️⃣ SUBMIT RESERVATION
if (isset($_POST['submit_reservation'])) {
    if (empty($lab) || empty($pc) || empty($purpose) || empty($date) || empty($time_from) || empty($time_to)) {
        $error_msg = "⚠️ All fields are required.";
    } elseif (strtotime($time_from) >= strtotime($time_to)) {
        $error_msg = "⚠️ End time must be after start time.";
    } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
        $error_msg = "⚠️ Reservation date cannot be in the past.";
    } else {
        // Strict overlap check
        $check_stmt = mysqli_prepare($conn, "SELECT id FROM reservations 
            WHERE pc_number = ? AND reservation_date = ? AND status IN ('approved', 'pending') 
            AND time_from < ? AND time_to > ?");
        mysqli_stmt_bind_param($check_stmt, "ssss", $pc, $date, $time_to, $time_from);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error_msg = "❌ This PC was just booked! Choose another PC or adjust time.";
        } else {
            $insert_stmt = mysqli_prepare($conn, "INSERT INTO reservations 
                (user_id, laboratory, pc_number, purpose, reservation_date, time_from, time_to, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            mysqli_stmt_bind_param($insert_stmt, "issssss", $user_id, $lab, $pc, $purpose, $date, $time_from, $time_to);
            if (mysqli_stmt_execute($insert_stmt)) {
                $success_msg = "✅ Reservation submitted successfully! Waiting for admin approval.";
                $lab = $pc = $purpose = $date = $time_from = $time_to = '';
                $availability_checked = false;
                $occupied_pcs = [];
            } else {
                $error_msg = "❌ Database error: " . mysqli_error($conn);
            }
        }
    }
}

// Fetch History
$history_query = "SELECT * FROM reservations WHERE user_id = ? ORDER BY created_at DESC LIMIT 15";
$history_stmt = mysqli_prepare($conn, $history_query);
mysqli_stmt_bind_param($history_stmt, "i", $user_id);
mysqli_stmt_execute($history_stmt);
$history_result = mysqli_stmt_get_result($history_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve Laboratory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { font-family: 'Segoe UI', Tahoma, sans-serif; }
        .top-navbar { background-color: #13499b; color: white; padding: 10px 24px; margin: 0; }
        .top-navbar a { color: white; text-decoration: none; margin-left: 16px; font-size: 0.95rem; }
        .btn-logout { background-color: #ffc107; color: black !important; padding: 6px 16px; border-radius: 4px; font-weight: 500; }
        .card-header { background-color: #13499b; color: white; font-weight: 600; border: none; }
        .dashboard-card { border: 1px solid #cccccc; border-radius: 6px; box-shadow: none; margin-bottom: 20px; }
        .badge-pending { background-color: #ffc107; color: #000; }
        .badge-approved { background-color: #28a745; color: #fff; }
        .badge-rejected { background-color: #dc3545; color: #fff; }
        .info-box { background-color: #d1e7dd; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 0.9rem; }
    </style>
</head>
<body>
<div class="top-navbar d-flex justify-content-between align-items-center">
    <div class="fw-bold">Student Reservation</div>
    <div>
        <a href="dashboard.php"><i class="fas fa-arrow-left me-1"></i> Back to Dashboard</a>
        <a href="dashboard.php?logout=true" class="btn-logout">Log out</a>
    </div>
</div>

<div class="container py-4">
    <div class="row g-4">
        <!-- Reservation Form -->
        <div class="col-lg-5">
            <div class="card dashboard-card">
                <div class="card-header"><i class="fas fa-calendar-plus me-2"></i> New Reservation</div>
                <div class="card-body">
                    <?php if(!empty($success_msg)): ?> <div class="alert alert-success"><?php echo $success_msg; ?></div> <?php endif; ?>
                    <?php if(!empty($error_msg)): ?> <div class="alert alert-danger"><?php echo $error_msg; ?></div> <?php endif; ?>
                    <?php if(!empty($info_msg)): ?> <div class="info-box"><i class="fas fa-info-circle me-2"></i><?php echo $info_msg; ?></div> <?php endif; ?>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" autocomplete="off">
                        
                        <!-- 🔹 LABORATORY DROPDOWN (524, 526, 528, 544) 🔹 -->
                        <div class="mb-3">
                            <label class="form-label">Laboratory <span class="text-danger">*</span></label>
                            <select name="laboratory" class="form-select" required>
                                <option value="">Select Laboratory</option>
                                <option value="524" <?php echo ($lab === '524') ? 'selected' : ''; ?>>Lab 524</option>
                                <option value="526" <?php echo ($lab === '526') ? 'selected' : ''; ?>>Lab 526</option>
                                <option value="528" <?php echo ($lab === '528') ? 'selected' : ''; ?>>Lab 528</option>
                                <option value="544" <?php echo ($lab === '544') ? 'selected' : ''; ?>>Lab 544</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="reservation_date" class="form-control" value="<?php echo $date; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Time From</label>
                                <input type="time" name="time_from" class="form-control" value="<?php echo $time_from; ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Time To</label>
                                <input type="time" name="time_to" class="form-control" value="<?php echo $time_to; ?>" required>
                            </div>
                        </div>
                        
                        <p class="text-muted mb-2"><small>Step 1: Click "Check Availability" → Step 2: Pick a free PC → Step 3: Submit</small></p>
                        
                        <div class="mb-3">
                            <label class="form-label">PC Number</label>
                            <select name="pc_number" class="form-select" required>
                                <option value="">Select a PC</option>
                                <?php 
                                for($i=1; $i<=30; $i++): 
                                    $pc_opt = "PC-" . str_pad($i, 2, '0', STR_PAD_LEFT);
                                    $is_occupied = in_array($pc_opt, $occupied_pcs);
                                    $selected = ($pc_opt === $pc) ? 'selected' : '';
                                    $style = $is_occupied ? 'style="color:#999; background:#f8f9fa;"' : '';
                                ?>
                                <option value="<?php echo $pc_opt; ?>" 
                                    <?php echo $selected . ' ' . $style; ?>
                                    <?php echo $is_occupied ? 'disabled' : ''; ?>>
                                    <?php echo $pc_opt . ($is_occupied ? ' (Occupied)' : ''); ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <!-- 🔹 PURPOSE DROPDOWN (Programming Languages) 🔹 -->
                        <div class="mb-3">
                            <label class="form-label">Purpose <span class="text-danger">*</span></label>
                            <select name="purpose" class="form-select" required>
                                <option value="">Select Purpose</option>
                                <optgroup label="Programming Languages">
                                    <option value="C" <?php echo ($purpose === 'C') ? 'selected' : ''; ?>>C</option>
                                    <option value="C#" <?php echo ($purpose === 'C#') ? 'selected' : ''; ?>>C#</option>
                                    <option value="C++" <?php echo ($purpose === 'C++') ? 'selected' : ''; ?>>C++</option>
                                    <option value="Java" <?php echo ($purpose === 'Java') ? 'selected' : ''; ?>>Java</option>
                                    <option value="Python" <?php echo ($purpose === 'Python') ? 'selected' : ''; ?>>Python</option>
                                    <option value="JavaScript" <?php echo ($purpose === 'JavaScript') ? 'selected' : ''; ?>>JavaScript</option>
                                    <option value="PHP" <?php echo ($purpose === 'PHP') ? 'selected' : ''; ?>>PHP</option>
                                    <option value="Swift" <?php echo ($purpose === 'Swift') ? 'selected' : ''; ?>>Swift</option>
                                    <option value="Kotlin" <?php echo ($purpose === 'Kotlin') ? 'selected' : ''; ?>>Kotlin</option>
                                    <option value="Go" <?php echo ($purpose === 'Go') ? 'selected' : ''; ?>>Go</option>
                                    <option value="Rust" <?php echo ($purpose === 'Rust') ? 'selected' : ''; ?>>Rust</option>
                                </optgroup>
                                <optgroup label="Other">
                                    <option value="Web Development" <?php echo ($purpose === 'Web Development') ? 'selected' : ''; ?>>Web Development</option>
                                    <option value="Database Design" <?php echo ($purpose === 'Database Design') ? 'selected' : ''; ?>>Database Design</option>
                                    <option value="Research" <?php echo ($purpose === 'Research') ? 'selected' : ''; ?>>Research</option>
                                    <option value="Project Work" <?php echo ($purpose === 'Project Work') ? 'selected' : ''; ?>>Project Work</option>
                                    <option value="Exam/Quiz" <?php echo ($purpose === 'Exam/Quiz') ? 'selected' : ''; ?>>Exam/Quiz</option>
                                    <option value="Others" <?php echo ($purpose === 'Others') ? 'selected' : ''; ?>>Others (Specify in notes)</option>
                                </optgroup>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Additional Notes (Optional)</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Any special requirements or details..."></textarea>
                        </div>
                        
                        <button type="submit" name="check_availability" class="btn btn-outline-primary w-100 mb-2">
                            <i class="fas fa-search me-2"></i> Check Availability
                        </button>
                        <button type="submit" name="submit_reservation" class="btn btn-primary w-100" <?php echo empty($pc) ? 'disabled' : ''; ?>>
                            <i class="fas fa-paper-plane me-2"></i> Submit Request
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reservation History -->
        <div class="col-lg-7">
            <div class="card dashboard-card">
                <div class="card-header"><i class="fas fa-history me-2"></i> My Reservation History</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Lab</th>
                                    <th>PC</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($history_result) > 0): ?>
                                    <?php while($res = mysqli_fetch_assoc($history_result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($res['laboratory']); ?></td>
                                        <td><?php echo htmlspecialchars($res['pc_number'] ?? '-'); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($res['reservation_date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($res['time_from'])) . ' - ' . date('h:i A', strtotime($res['time_to'])); ?></td>
                                        <td><?php echo htmlspecialchars($res['purpose']); ?></td>
                                        <td><span class="badge badge-<?php echo $res['status']; ?>"><?php echo ucfirst($res['status']); ?></span></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No reservations yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>