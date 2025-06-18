<?php
/**
 * Logout Page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../includes/auth.php';

// Logout user
logout();

// Redirect to login page
redirect('/login.php');
?>