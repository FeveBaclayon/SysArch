<?php
include "dataB.php";
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

// Load full user data from database
$user_id = $_SESSION["user_id"];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Built in logout handler
if(isset($_GET['logout'])) {
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
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        * {
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }

        /* Top Navigation Bar */
        .top-navbar {
            background-color: #13499b;
            color: white;
            padding: 10px 24px;
            margin: 0;
        }

        .top-navbar a {
            color: white;
            text-decoration: none;
            margin-left: 16px;
            font-size: 0.95rem;
        }

        .btn-logout {
            background-color: #ffc107;
            color: black !important;
            padding: 6px 16px;
            border-radius: 4px;
            font-weight: 500;
        }

        /* Card Headers */
        .card-header {
            background-color: #13499b;
            color: white;
            font-weight: 600;
            border: none;
        }

        .dashboard-card {
            border: 1px solid #cccccc;
            border-radius: 6px;
            box-shadow: none;
        }

        /* Student Info Card */
        .avatar {
            width: 140px;
            height: 140px;
            border-radius: 100%;
            margin: 16px auto;
            display: block;
            border: 1px solid #ddd;
        }

        .info-row {
            padding: 6px 16px;
            font-size: 0.95rem;
        }

        .info-row i {
            width: 20px;
            margin-right: 8px;
            color: #333;
        }

        .divider {
            border-bottom: 1px solid #ccc;
            margin: 8px 16px;
        }

        /* Announcements */
        .announcement-item {
            padding: 16px;
            border-bottom: 1px solid #ccc;
        }
        .announcement-item:last-child {
            border-bottom: none;
        }
        .announcement-date {
            font-weight: 600;
            margin-bottom: 12px;
        }
        .announcement-body {
            background-color: #f8f9fa;
            padding: 12px;
            font-size: 0.95rem;
        }

        /* Rules Panel */
        .rules-scroll {
            height: 380px;
            overflow-y: auto;
            padding: 16px;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        body {
            background-color: #ffffff;
        }

    </style>
</head>
<body>

<!-- Top Navigation Bar -->
<div class="top-navbar d-flex justify-content-between align-items-center">
    <div class="fw-bold">Dashboard</div>
    <div>
        <a href="#">Notification <i class="fa fa-caret-down"></i></a>
        <a href="dashboard.php">Home</a>
        <a href="Edit Profile.php">Edit Profile</a>
        <a href="#">History</a>
        <a href="#">Reservation</a>
        <a href="dashboard.php?logout=true" class="btn-logout">Log out</a>
    </div>
</div>

<!-- Main Dashboard Content -->
<div class="container-fluid p-4">
    <div class="row g-4">

        <!-- LEFT COLUMN: Student Information -->
        <div class="col-lg-4 col-md-12">
            <div class="card dashboard-card h-100">
                <div class="card-header">
                    <i class="fa fa-user me-2"></i> Student Information
                </div>
                <div class="card-body p-0">

                    <img src="Avatar.png" class="avatar" alt="Student Avatar">
                    
                    <div class="divider"></div>

                    <div class="info-row">
                        <i class="fa fa-user"></i>
                        <strong>Name:</strong> <?php echo htmlspecialchars($user['first_name'].' '.$user['last_name']); ?>
                    </div>

                    <div class="info-row">
                        <i class="fa fa-graduation-cap"></i>
                        <strong>Course:</strong> <?php echo htmlspecialchars($user['course']); ?>
                    </div>

                    <div class="info-row">
                        <i class="fa fa-level-up-alt"></i>
                        <strong>Year:</strong> <?php echo htmlspecialchars($user['course_level']); ?>
                    </div>

                    <div class="info-row">
                        <i class="fa fa-envelope"></i>
                        <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
                    </div>

                    <div class="info-row">
                        <i class="fa fa-map-marker-alt"></i>
                        <strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?>
                    </div>

                    <div class="info-row">
                        <i class="fa fa-clock"></i>
                        <strong>Session:</strong> 30
                    </div>

                </div>
            </div>
        </div>

        <!-- MIDDLE COLUMN: Announcements -->
        <div class="col-lg-4 col-md-12">
            <div class="card dashboard-card h-100">
                <div class="card-header">
                    <i class="fa fa-bullhorn me-2"></i> Announcement
                </div>
                <div class="card-body p-0">

                    <div class="announcement-item">
                        <div class="announcement-date">CCS Admin | 2026-Feb-11</div>
                        <div class="announcement-body">

                        </div>
                    </div>

                    <div class="announcement-item">
                        <div class="announcement-date">CCS Admin | 2024-May-08</div>
                        <div class="announcement-body">
                            Important Announcement We are excited to announce the launch of our new website! 🎉 Explore our latest products and services now!
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Rules and Regulations -->
        <div class="col-lg-4 col-md-12">
            <div class="card dashboard-card h-100">
                <div class="card-header">
                    Rules and Regulation
                </div>
                <div class="card-body p-0">

                    <div class="rules-scroll">
                        <div class="text-center fw-bold mb-3">
                            UNIVERSITY OF CEBU<br>
                            COLLEGE OF INFORMATION & COMPUTER STUDIES
                            <hr>
                            LABORATORY RULES AND REGULATIONS
                        </div>

                        <p>To avoid embarrassment and maintain camaraderie with your friends and superiors at our laboratories, please observe the following:</p>

                        <ol>
                            <li class="mb-2">Maintain silence, proper decorum, and discipline inside the laboratory. Mobile phones, walkmans and other personal pieces of equipment must be switched off.</li>
                            <li class="mb-2">Games are not allowed inside the lab. This includes computer-related games, card games and other games that may disturb the operation of the lab.</li>
                            <li class="mb-2">Surfing the Internet is allowed only with the permission of the instructor. Downloading and installing of software are strictly prohibited.</li>
                        </ol>

                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>