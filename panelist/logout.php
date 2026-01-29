<?php
session_start();
// Unset all session variables specific to panelist
unset($_SESSION['panelist_id']);
unset($_SESSION['panelist_name']);
unset($_SESSION['panelist_role']);

// Optional: Destroy whole session if exclusive
// session_destroy();

// Redirect to Panelist Login
header("Location: login.php");
exit;
?>
