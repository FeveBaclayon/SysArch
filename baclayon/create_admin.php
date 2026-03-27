<?php
include "dataB.php";

// Check if role column exists
$check_column = "SHOW COLUMNS FROM users LIKE 'role'";
$result = mysqli_query($conn, $check_column);

if(mysqli_num_rows($result) == 0) {
    // Add role column if it doesn't exist
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user' AFTER password");
    echo "✓ Added 'role' column to users table<br>";
}

// Hash the password
$password = "Admin@123";
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if admin exists
$check_admin = "SELECT * FROM users WHERE id_number = 'ADMIN001'";
$result = mysqli_query($conn, $check_admin);

if(mysqli_num_rows($result) > 0) {
    // Update existing admin
    $sql = "UPDATE users SET 
            last_name = 'Admin',
            first_name = 'CCS',
            middle_name = '',
            course_level = 0,
            password = ?,
            email = 'admin@ccs.edu',
            course = 'ADMIN',
            address = 'College of Computer Studies',
            role = 'admin'
            WHERE id_number = 'ADMIN001'";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $hashed_password);
    mysqli_stmt_execute($stmt);
    
    echo "✓ Admin user updated successfully!<br>";
} else {
    // Insert new admin
    $sql = "INSERT INTO users 
            (id_number, last_name, first_name, middle_name, course_level, password, email, course, address, role) 
            VALUES 
            ('ADMIN001', 'Admin', 'CCS', '', 0, ?, 'admin@ccs.edu', 'ADMIN', 'College of Computer Studies', 'admin')";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $hashed_password);
    mysqli_stmt_execute($stmt);
    
    echo "✓ Admin user created successfully!<br>";
}

echo "<br><strong>Login Credentials:</strong><br>";
echo "ID Number: <strong>ADMIN001</strong><br>";
echo "Password: <strong>Admin@123</strong><br><br>";
echo "<a href='index.php'>Go to Login</a>";

// Verify the password works
echo "<br><br><strong>Verification Test:</strong><br>";
$test_query = "SELECT password FROM users WHERE id_number = 'ADMIN001'";
$test_result = mysqli_query($conn, $test_query);
$test_user = mysqli_fetch_assoc($test_result);

if(password_verify("Admin@123", $test_user['password'])) {
    echo "✓ Password verification PASSED!";
} else {
    echo "✗ Password verification FAILED!";
}
?>