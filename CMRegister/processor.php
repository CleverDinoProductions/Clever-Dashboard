<?php
/**
 * User Registration Processor
 * Handles account creation from C# client
 */

// Set response type to plain text for C# client compatibility
header('Content-Type: text/plain');

// Prevent caching
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Database configuration
$host = 'localhost';
$dbname = 'user_registration';
$db_username = 'webapp_user';
$db_password = 'WebApp2026!Secure'; // Change this to your password

try {
    // Connect to database using PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Get parameters from GET request (as per your C# implementation)
    $login = isset($_GET['login']) ? trim($_GET['login']) : '';
    $pass = isset($_GET['pass']) ? $_GET['pass'] : '';
    $email = isset($_GET['email']) ? trim($_GET['email']) : '';
    
    // ===== VALIDATION =====
    
    // Check if all fields are provided
    if (empty($login) || empty($pass) || empty($email)) {
        echo "All fields are required";
        exit;
    }
    
    // Validate username length
    if (strlen($login) < 3) {
        echo "Username must be at least 3 characters";
        exit;
    }
    
    if (strlen($login) > 50) {
        echo "Username must be 50 characters or less";
        exit;
    }
    
    // Validate username format (alphanumeric and underscores only)
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $login)) {
        echo "Username can only contain letters, numbers, and underscores";
        exit;
    }
    
    // Validate password strength
    if (strlen($pass) < 6) {
        echo "Password must be at least 6 characters";
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email address format";
        exit;
    }
    
    // Validate email length
    if (strlen($email) > 100) {
        echo "Email address is too long";
        exit;
    }
    
    // ===== CHECK FOR EXISTING USERS =====
    
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->bindParam(':username', $login, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        echo "Username already exists";
        exit;
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        echo "Email already registered";
        exit;
    }
    
    // ===== CREATE USER ACCOUNT =====
    
    // Hash the password using bcrypt (PASSWORD_DEFAULT)
    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
    
    // Insert new user into database
    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, email, created_at, status) 
        VALUES (:username, :password, :email, NOW(), 'active')
    ");
    
    $stmt->bindParam(':username', $login, PDO::PARAM_STR);
    $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    
    if ($stmt->execute()) {
        // Success - return exact message your C# client expects
        echo "Account made!";
        
        // Optional: Log successful registration
        error_log("New user registered: " . $login . " (" . $email . ")");
    } else {
        echo "Registration failed. Please try again";
    }
    
} catch (PDOException $e) {
    // Log the actual error for debugging (not shown to user)
    error_log("Registration error: " . $e->getMessage());
    
    // Generic error message for user
    echo "Registration failed. Please try again later";
    
} catch (Exception $e) {
    // Catch any other errors
    error_log("Unexpected error: " . $e->getMessage());
    echo "An unexpected error occurred";
}

// Close database connection
$pdo = null;
?>
