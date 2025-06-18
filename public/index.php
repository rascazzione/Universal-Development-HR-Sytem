<?php
/**
 * Index Page - Redirects to appropriate page
 * Performance Evaluation System
 */

require_once __DIR__ . '/../includes/auth.php';

// Redirect based on authentication status
if (isAuthenticated()) {
    redirect('/dashboard.php');
} else {
    redirect('/login.php');
}
?>