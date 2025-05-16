<?php
// sair.php

// Start the session if it's not already started
// This is crucial to be able to access and destroy session variables
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
// This will delete the session file on the server.
session_destroy();

// Alternatively, if you want to kill the session but keep the session cookie
// uncomment the following lines:
// if (ini_get("session.use_cookies")) {
//     $params = session_get_cookie_params();
//     setcookie(session_name(), '', time() - 42000,
//         $params["path"], $params["domain"],
//         $params["secure"], $params["httponly"]
//     );
// }

// Redirect to the homepage or login page
// You can change 'index.html' to the appropriate page you want to redirect to after logout
header("Location: index.php");
exit(); // Ensure that no further code is executed after the redirect

?>