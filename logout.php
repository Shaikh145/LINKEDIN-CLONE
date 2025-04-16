<?php
require_once 'functions.php';

// Destroy the session
session_start();
$_SESSION = array();
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>
