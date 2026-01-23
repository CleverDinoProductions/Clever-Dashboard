<?php
/**
 * User Login Processor
 * Returns "yes" for successful login to match C# client expectations
 */

header('Content-Type: text/plain');
header('Cache-Control: no-cache, must-revalidate');

// Database configuration
$host = 'localhost';
$dbname = 'user_registration';
$db_username = 'webapp_user';
$db_password = 'WebApp2026!Secure';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Get parameters (matching your C# parameter names: Name and Pass)
    $username = isset($_GET['Name']) ? trim($_GET['Name']) : '';
    $password = isset($_GET['Pass']) ? $_GET['Pass'] : '';
    
    // Validation
    if (empty($username) || empty($password)) {
        echo "Username and password required";
        exit;
    }
    
    // Query user from database
    $stmt = $pdo->prepare("SELECT id, password, status FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user exists
    if (!$user) {
        echo "Password incorrect!";
        exit;
    }
    
    // Check if account is active
    if ($user['status'] !== 'active') {
        echo "Account is " . $user['status'];
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        echo "Password incorrect!";
        exit;
    }
    
    // Update last login timestamp
    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
    $updateStmt->bindParam(':id', $user['id'], PDO::PARAM_INT);
    $updateStmt->execute();
    
    // Return "yes" to match C# switch statement
    echo "yes";
    
    error_log("User logged in: " . $username . " (ID: " . $user['id'] . ")");
    
} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    echo "Login failed. Please try again later";
} catch (Exception $e) {
    error_log("Unexpected login error: " . $e->getMessage());
    echo "An unexpected error occurred";
}

$pdo = null;
?>
