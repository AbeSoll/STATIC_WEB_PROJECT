<?php
// Include database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "staticweb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start the session
session_start();

// Check for the token in the URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Validate token format
    if (ctype_xdigit($token) && strlen($token) === 32) { // Token should be 32 hex characters
        // Use prepared statement to avoid SQL injection
        $stmt = $conn->prepare("SELECT * FROM users WHERE verification_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Mark the user as verified
            $update_stmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);
            if ($update_stmt->execute()) {
                // Set a session message
                $_SESSION['message'] = "Your email has been verified successfully. You can now log in.";
                header("Location: index.php"); // Redirect to login page
                exit();
            } else {
                $_SESSION['message'] = "Failed to verify your email. Please try again.";
            }
        } else {
            $_SESSION['message'] = "Invalid or expired token.";
        }
    } else {
        $_SESSION['message'] = "Invalid token format.";
    }
} else {
    $_SESSION['message'] = "No token provided.";
}

// Redirect to login page with error message
header("Location: index.php");
exit();
?>
