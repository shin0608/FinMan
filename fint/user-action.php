<?php
session_start();
require_once 'config/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $conn = getConnection();
    
    try {
        switch ($action) {
            case 'add':
                // Add new user
                $username = $_POST['username'];
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $name = $_POST['name'];
                $email = $_POST['email'];
                $role = $_POST['role'];
                
                $stmt = $conn->prepare("INSERT INTO users (username, password, name, email, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $username, $password, $name, $email, $role);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "User added successfully";
                    // Log the action
                    logAction($_SESSION['user_id'], 'add_user', "Added new user: $username");
                } else {
                    $_SESSION['error_message'] = "Error adding user";
                }
                header("Location: user-control.php");
                break;

            case 'delete':
                $userId = $_POST['user_id'];
                
                // Don't allow deleting own account
                if ($userId == $_SESSION['user_id']) {
                    echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
                    exit();
                }
                
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                
                if ($stmt->execute()) {
                    // Log the action
                    logAction($_SESSION['user_id'], 'delete_user', "Deleted user ID: $userId");
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error deleting user']);
                }
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
    } catch (Exception $e) {
        error_log("Error in user-actions.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    } finally {
        $conn->close();
    }
    exit();
}