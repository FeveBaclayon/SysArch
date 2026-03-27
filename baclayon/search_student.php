<?php
include "dataB.php";
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'admin') {
    header("Location: index.php");
    exit();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$students = [];
$search_performed = false;
$debug_info = "";

if (!empty($search)) {
    $search_performed = true;
    
    // Simple search query - search across multiple fields
    $search_param = "%$search%";
    
    $query = "SELECT id, id_number, first_name, middle_name, last_name, course, course_level, email, address, role, remaining_sessions 
              FROM users 
              WHERE role = 'user' 
              AND (
                  id_number LIKE ? 
                  OR first_name LIKE ? 
                  OR last_name LIKE ? 
                  OR email LIKE ?
              )
              ORDER BY id_number ASC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssss", $search_param, $search_param, $search_param, $search_param);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
    
    // Debug info (remove after testing)
    $debug_info = "Search term: '$search' | Found: " . count($students) . " student(s)";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background-color: #f4f6f9; }
        .top-navbar {
            background-color: #0f4c96;
            color: white;
            padding: 12px 24px;
        }
        .top-navbar .navbar-brand { color: white; font-weight: 600; }
        .top-navbar .nav-link { color: rgba(255,255,255,0.9) !important; margin-left: 15px; }
        .top-navbar .nav-link:hover { color: white !important; }
        .content-wrapper { padding: 24px; }
        .page-header { text-align: center; margin-bottom: 30px; }
        .table thead { background-color: #0f4c96; color: white; }
        .btn-back { background-color: #6c757d; color: white; }
        .search-info { 
            background: #e7f1ff; 
            padding: 12px 20px; 
            border-radius: 6px; 
            margin-bottom: 20px; 
            border-left: 4px solid #007bff; 
        }
        .debug-box {
            background: #fff3cd;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 0.85rem;
            border: 1px solid #ffc107;
        }
    </style>
</head>
<body>
    <nav class="top-navbar navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-university me-2"></i>College of Computer Studies Admin
            </a>
            <a href="admin_dashboard.php" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="container">
            <h3 class="page-header"><i class="fas fa-search me-2"></i>Search Results</h3>
            
            <!-- Debug Info (Remove after fixing) -->
            <?php if(!empty($debug_info)): ?>
            <div class="debug-box">
                <i class="fas fa-bug me-2"></i><?php echo $debug_info; ?>
            </div>
            <?php endif; ?>
            
            <!-- Search Info Box -->
            <?php if($search_performed): ?>
            <div class="search-info">
                <strong>Search Term:</strong> "<?php echo htmlspecialchars($search); ?>" | 
                <strong>Found:</strong> <?php echo count($students); ?> student(s)
            </div>
            <?php endif; ?>
            
            <?php if (!$search_performed): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Please enter a search term to find students.
                </div>
            <?php elseif (count($students) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID Number</th>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Year Level</th>
                                <th>Email</th>
                                <th>Sessions Left</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($student['id_number']); ?></strong></td>
                                <td>
                                    <?php 
                                        $fullname = trim($student['first_name'] . ' ' . $student['last_name']);
                                        echo htmlspecialchars($fullname); 
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($student['course']); ?></td>
                                <td><?php echo htmlspecialchars($student['course_level']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['remaining_sessions'] ?? 'N/A'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No students found matching "<strong><?php echo htmlspecialchars($search); ?></strong>".
                    <br><br>
                    <small class="text-muted">
                        <strong>Tips:</strong><br>
                        1. Check if the student has <code>role = 'user'</code> (not 'admin')<br>
                        2. Try searching by partial ID (e.g., "2024" instead of full ID)<br>
                        3. Check for typos in the search term<br>
                        4. Verify the student exists in the database
                    </small>
                </div>
                
                <!-- Debug: Show all users for testing -->
                <div class="mt-4">
                    <h5><i class="fas fa-database me-2"></i>All Users in Database:</h5>
                    <?php
                    $all_users = mysqli_query($conn, "SELECT id_number, first_name, last_name, role FROM users");
                    if(mysqli_num_rows($all_users) > 0):
                    ?>
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>ID Number</th>
                                <th>Name</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($u = mysqli_fetch_assoc($all_users)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($u['id_number']); ?></td>
                                <td><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></td>
                                <td>
                                    <span class="badge <?php echo $u['role'] == 'admin' ? 'bg-danger' : 'bg-success'; ?>">
                                        <?php echo $u['role']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>