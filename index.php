<?php
session_start();

// Database configuration
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "final_project";

$conn = new mysqli($db_host, $db_user, $db_pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create DB if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $db_name";
if (!$conn->query($sql)) {
    die("Error creating database: " . $conn->error);
}

$conn->select_db($db_name);

// Create users table if not exists
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) DEFAULT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mobile VARCHAR(11) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (!$conn->query($sql)) {
    die("Error creating table: " . $conn->error);
}

$page = isset($_GET['page']) ? $_GET['page'] : 'register';
$error = '';
$success = '';

// Registration handler
$first_name = $middle_name = $last_name = $email = $mobile = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($first_name) || empty($last_name) || empty($email) || empty($mobile) || empty($password) || empty($confirm_password)) {
        $error = "All fields except middle name are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8 || 
              !preg_match("/[A-Z]/", $password) || 
              !preg_match("/[a-z]/", $password) || 
              !preg_match("/[0-9]/", $password) || 
              !preg_match("/[\W_]/", $password)) {
        $error = "Password must be at least 8 characters and contain uppercase, lowercase, number, and special character.";
    } elseif (strlen($last_name) < 2) {
        $error = "Last name must be at least 2 characters.";
    } elseif (!preg_match("/^0[0-9]{10}$/", $mobile)) {
        $error = "Mobile number must be 11 digits long and start with 0.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR mobile = ?");
        if (!$stmt) {
            $error = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("ss", $email, $mobile);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = "Email or mobile number already exists.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (first_name, middle_name, last_name, email, mobile, password) VALUES (?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    $error = "Insert prepare failed: " . $conn->error;
                } else {
                    $stmt->bind_param("ssssss", $first_name, $middle_name, $last_name, $email, $mobile, $hashed_password);
                    if ($stmt->execute()) {
                        $success = "Registration successful! Please login.";
                        $page = 'login';
                        $first_name = $middle_name = $last_name = $email = $mobile = '';
                    } else {
                        $error = "Insert failed: " . $stmt->error;
                    }
                }
            }
        }
    }
}

// Login handler
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, first_name, password FROM users WHERE email = ? OR mobile = ?");
        if (!$stmt) {
            $error = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['first_name'];
                    header("Location: welcome.php");
                    exit;
                } else {
                    $error = "Incorrect password.";
                }
            } else {
                $error = "No account found with that email or mobile.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo ucfirst($page); ?> - User Authentication System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <div class="form-container">
        <?php if ($error && strpos($error, 'Passwords do not match') === false): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($page === 'register'): ?>
            <h2 class="form-title">Create an Account</h2>
            <form method="post" action="index.php?page=register" id="registrationForm">
                <div class="mb-3">
                    <label class="form-label">First Name</label>
                    <input type="text" class="form-control" name="first_name" required minlength="2" value="<?php echo htmlspecialchars($first_name); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Middle Name (Optional)</label>
                    <input type="text" class="form-control" name="middle_name" value="<?php echo htmlspecialchars($middle_name); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Last Name</label>
                    <input type="text" class="form-control" name="last_name" required minlength="2" value="<?php echo htmlspecialchars($last_name); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" required value="<?php echo htmlspecialchars($email); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mobile Number</label>
                    <input type="text" class="form-control" name="mobile" pattern="^0[0-9]{10}$" required value="<?php echo htmlspecialchars($mobile); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" name="confirm_password" required>
                    <?php if (strpos($error, 'Passwords do not match') !== false): ?>
                        <div class="text-danger mt-1"><?php echo $error; ?></div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-custom" name="register">Register</button>
            </form>
            <div class="form-footer">
                Already have an account? <a href="index.php?page=login">Login here</a>
            </div>
        <?php else: ?>
            <h2 class="form-title">Login to Your Account</h2>
            <form method="post" action="index.php?page=login" id="loginForm">
                <div class="mb-3">
                    <label class="form-label">Username (Email or Mobile)</label>
                    <input type="text" class="form-control" name="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <button type="submit" class="btn btn-custom" name="login">Login</button>
            </form>
            <div class="form-footer">
                Don't have an account? <a href="index.php?page=register">Register here</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
</body>
</html>
