<?php
/**
 * Simple logout script
 */

session_start();

// Destroy session
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear BTTSESSID cookie specifically
setcookie('BTTSESSID', '', time() - 3600, '/');

// Redirect to login page
header('Location: /BTT/auth.php?action=login&message=logged_out');
exit();
?>
