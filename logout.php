<?php
require_once 'init.php';

// Use the logoutUser function from init.php
logoutUser();

// Redirect to login page
header("Location: login.html");
exit();
?>
