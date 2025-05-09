<?php
// Database connection
require_once __DIR__ . '/database.php';

// Authentication and User Management
require_once __DIR__ . '/auth_functions.php';

// Core Business Logic
require_once __DIR__ . '/common_functions.php';
require_once __DIR__ . '/account_functions.php';
require_once __DIR__ . '/transaction_functions.php';
require_once __DIR__ . '/disbursement_functions.php';
require_once __DIR__ . '/payment_functions.php';
require_once __DIR__ . '/reporting_functions.php';
require_once __DIR__ . '/utility_functions.php';
require_once __DIR__ . '/get_account.php';

function getBasePath() {
    return dirname(__DIR__);
}

function getConfigPath() {
    return getBasePath() . '/config';
}

function getIncludesPath() {
    return getBasePath() . '/includes';
}

function getReportsPath() {
    return getBasePath() . '/reports';
}

// Make sure functions are available globally
if (!function_exists('isAdmin')) {
    function isAdmin($userId) {
        if (!$userId) return false;
        
        $conn = getConnection();
        try {
            $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                return strtolower($row['role']) === 'admin';
            }
            return false;
        } catch (Exception $e) {
            error_log("Error in isAdmin function: " . $e->getMessage());
            return false;
        } finally {
            if ($conn) {
                $conn->close();
            }
        }
    }
}

if (!function_exists('hasAdminAccess')) {
    function hasAdminAccess() {
        return isset($_SESSION['user_id']) && isAdmin($_SESSION['user_id']);
    }
}

if (!function_exists('getStatusBadgeClass')) {
    function getStatusBadgeClass($status) {
        switch (strtolower($status)) {
            case 'approved':
                return 'success';
            case 'pending':
                return 'warning';
            case 'void':
                return 'danger';
            case 'draft':
                return 'secondary';
            default:
                return 'primary';
        }
    }
}