<?php
// Define constants for file paths
define('ROOT_PATH', dirname(__FILE__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('REPORTS_PATH', ROOT_PATH . '/reports');

// Include essential files
require_once CONFIG_PATH . '/functions.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set('UTC');