<?php
include('includes/session_config.php');
session_start();

// 1. Clear all session variables
$_SESSION = array();

// 2. Kill the session cookie in the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the session on the server
session_destroy();

// 4. Force a hard redirect to the home page
header("Location: Home.php");
exit();
?>