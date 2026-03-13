<?php

session_start();
include "dataB.php";

$error_msg = "";
$success_msg = "";
$id_number = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $id_number = trim($_POST["id_number"]);
    $password = $_POST["password"];

    /* EMPTY FIELD CHECK */

    if(empty($id_number) || empty($password)){
        $error_msg = "Please enter both ID number and password.";
    }

    /* CHECK DATABASE */

    if(empty($error_msg)){

        // Using prepared statements is much safer!
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id_number = ?");
        mysqli_stmt_bind_param($stmt, "s", $id_number);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if(mysqli_num_rows($result) == 1){
            $user = mysqli_fetch_assoc($result);

            if(password_verify($password, $user["password"])){
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["id_number"] = $user["id_number"];
                $_SESSION["name"] = $user["first_name"];
                $_SESSION["role"] = $user["role"]; // Important!

                if($user["role"] === 'admin'){
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                $error_msg = "Incorrect password.";
            }
        } else {
            $error_msg = "ID number not registered.";
        }

    }

}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CCS Sit-in Monitoring</title>
    <style>
        /* General Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #ffffff;
        }

        /* --- Header Styling --- */
        .navbar {
            background-color: #0f4c96; /* Deep Blue from screenshot */
            color: white;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-title {
            font-size: 1.1rem;
            font-weight: 500;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-size: 0.95rem;
        }

        .nav-links a:hover {
            text-decoration: underline;
        }

        /* --- Main Content Layout --- */
        .main-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px;
            gap: 80px; /* Space between Logo and Form */
            flex-wrap: wrap;
        }

        /* Logo Side */
        .logo-wrapper {
            max-width: 400px;
            text-align: center;
        }

        .logo-wrapper img {
            width: 100%;
            height: auto;
            /* Fallback if image is missing */
            min-height: 300px; 
            object-fit: contain;
        }

        /* Form Side */
        .login-form-wrapper {
            width: 350px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        /* Input Fields */
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: #0f4c96;
            box-shadow: 0 0 5px rgba(15, 76, 150, 0.2);
        }

        /* Labels positioned BELOW inputs as per screenshot */
        .field-label {
            display: block;
            margin-top: 5px;
            font-size: 0.9rem;
            color: #333;
        }

        /* Options Row (Remember me / Forgot Password) */
        .options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 0.9rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }

        .forgot-link {
            color: #333;
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        /* Login Button */
        .btn-login {
            background-color: #007bff; /* Bright Blue */
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 4px;
            font-size: 1.1rem;
            cursor: pointer;
            width: 100px;
            display: block;
            margin: 0 auto; /* Centers the button */
        }

        .btn-login:hover {
            background-color: #0056b3;
        }

        /* Register Link Area */
        .register-area {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .register-area span {
            color: black;
        }

        .register-area a {
            color: #dc3545; /* Red color */
            text-decoration: none;
        }

        .register-area a:hover {
            text-decoration: underline;
        }

        /* Error Message Style */
        .error-box {
            color: red;
            font-size: 0.9rem;
            margin-bottom: 15px;
            text-align: center;
        }

        /* --- Footer --- */
        .footer {
            background-color: #0f4c96;
            color: white;
            text-align: center;
            padding: 15px;
            font-size: 0.9rem;
            margin-top: auto;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            .main-container {
                gap: 40px;
            }
        }
    </style>
</head>
<body>

    <!-- Top Navigation Bar -->
    <div class="navbar">
        <div class="navbar-title">College of Computer Studies Sit-in Monitoring System</div>
        <div class="nav-links">
            <a href="#">Home</a>
            <a href="#">Community &#9662;</a> <!-- Down arrow symbol -->
            <a href="#">About</a>
            <a href="index.php">Login</a>
            <a href="Register.php">Register</a>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-container">
        
        <!-- Left Side: Logo Image -->
        <div class="logo-wrapper">
            <!-- REPLACE 'logo.png' WITH YOUR ACTUAL IMAGE FILE PATH -->
            <img src="CCS_LOGO.png" alt="CCS Shield Logo">
        </div>

        <!-- Right Side: Login Form -->
        <div class="login-form-wrapper">
            
            <?php if(!empty($error_msg)): ?>
                <div class="error-box"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                
                <!-- ID Number Input -->
                <div class="form-group">
                    <label class="field-label">ID Number</label>    
                        <input type="text" name="id_number" class="form-control" placeholder="Enter a valid id number" value="<?php echo htmlspecialchars($id_number); ?>" required>  
                </div>

                <!-- Password Input -->
                <div class="form-group">
                    <label class="field-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="options-row">
                    <label class="remember-me">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-login">Login</button>

                <!-- Register Link -->
                <div class="register-area">
                    Don't have an account? <a href="Register.php">Register</a>
                </div>

            </form>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        &copy; 2024 College of Computer Studies
    </div>

</body>
</html>