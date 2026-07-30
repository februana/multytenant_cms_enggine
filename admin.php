<?php
/**
 * Admin Panel Entry Point
 * 
 * This file redirects to the admin panel located at /admin/
 * The actual admin implementation is in /admin/index.php
 * 
 * @package WeddingInvitation
 * @since 2.0.0
 */

// Redirect to the admin directory
header('Location: /admin/');
exit;
