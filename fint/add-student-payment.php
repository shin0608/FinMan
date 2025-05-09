<?php
session_start();
require_once 'config/functions.php';
require_once 'config/payment_functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success = '';
$error = '';

// Debug: Add logging
function debugLog($message) {
    error_log(date('Y-m-d H:i:s') . " - " . $message . "\n", 3, "debug.log");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    debugLog("Form submitted. POST data: " . print_r($_POST, true));
    
    $studentId = $_POST['student_id'] ?? '';
    $date = $_POST['payment_date'] ?? date('Y-m-d');
    $referenceNumber = $_POST['reference_number'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $paymentType = $_POST['payment_type'] ?? '';
    $paymentMethod = $_POST['payment_method'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($studentId) || empty($date) || empty($referenceNumber) || empty($amount) || empty($paymentType) || empty($paymentMethod)) {
        $error = 'Please fill in all required fields';
        debugLog("Validation failed: Missing required fields");
    } else {
        $conn = getConnection();
        
        try {
            // Start transaction
            $conn->begin_transaction();
            debugLog("Transaction started");

            // Get current balance for student
            $sql = "SELECT remaining_balance FROM student_payments 
                   WHERE student_id = ? 
                   ORDER BY payment_date DESC, id DESC 
                   LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $studentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $currentBalance = 0;
            
            if ($row = $result->fetch_assoc()) {
                $currentBalance = $row['remaining_balance'];
            }
            
            $newBalance = $currentBalance - $amount;
            debugLog("Current balance: $currentBalance, New balance: $newBalance");
            
            // Insert payment
            $sql = "INSERT INTO student_payments (
                        student_id,
                        payment_date,
                        reference_number,
                        amount,
                        payment_type,
                        payment_method,
                        remaining_balance,
                        description,
                        created_by,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                    
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param(
                'sssdssdsi',
                $studentId,
                $date,
                $referenceNumber,
                $amount,
                $paymentType,
                $paymentMethod,
                $newBalance,
                $description,
                $_SESSION['user_id']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            debugLog("Student payment inserted successfully");
            
            // Create transaction entry
            $sql = "INSERT INTO transactions (
                        transaction_date,
                        reference_number,
                        description,
                        amount,
                        type,
                        status,
                        created_by
                    ) VALUES (?, ?, ?, ?, 'Payment', 'Completed', ?)";
                    
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed for transactions: " . $conn->error);
            }
            
            $transDescription = "Payment received from student ID: " . $studentId;
            $stmt->bind_param('sssdi', 
                $date, 
                $referenceNumber, 
                $transDescription, 
                $amount, 
                $_SESSION['user_id']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed for transactions: " . $stmt->error);
            }
            debugLog("Transaction record inserted successfully");
            
            // Commit transaction
            $conn->commit();
            $success = 'Payment recorded successfully';
            debugLog("Transaction committed successfully");
            
            // Redirect to prevent form resubmission
            header("Location: student-payments.php?success=1");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Error recording payment: ' . $e->getMessage();
            debugLog("Error: " . $e->getMessage());
        } finally {
            closeConnection($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Record Student Payment</h1>
                    <div class="d-flex">
                        <a href="student-payments.php" class="btn btn-secondary me-2">
                            <i class="bi bi-arrow-left"></i> Back to Payments
                        </a>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="student_id" class="form-label">Student ID *</label>
                                    <input type="text" class="form-control" id="student_id" name="student_id" value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter a student ID.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="payment_date" class="form-label">Payment Date *</label>
                                    <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                           value="<?php echo htmlspecialchars($_POST['payment_date'] ?? date('Y-m-d')); ?>" required>
                                    <div class="invalid-feedback">Please select a date.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="reference_number" class="form-label">Reference Number *</label>
                                    <input type="text" class="form-control" id="reference_number" name="reference_number" 
                                           value="<?php echo htmlspecialchars($_POST['reference_number'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter a reference number.</div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="payment_type" class="form-label">Payment Type *</label>
                                    <select class="form-select" id="payment_type" name="payment_type" required>
                                        <option value="">Select type...</option>
                                        <option value="Tuition" <?php echo ($_POST['payment_type'] ?? '') === 'Tuition' ? 'selected' : ''; ?>>Tuition</option>
                                        <option value="Miscellaneous" <?php echo ($_POST['payment_type'] ?? '') === 'Miscellaneous' ? 'selected' : ''; ?>>Miscellaneous</option>
                                        <option value="Other" <?php echo ($_POST['payment_type'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a payment type.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="payment_method" class="form-label">Payment Method *</label>
                                    <select class="form-select" id="payment_method" name="payment_method" required>
                                        <option value="">Select method...</option>
                                        <option value="Cash" <?php echo ($_POST['payment_method'] ?? '') === 'Cash' ? 'selected' : ''; ?>>Cash</option>
                                        <option value="Check" <?php echo ($_POST['payment_method'] ?? '') === 'Check' ? 'selected' : ''; ?>>Check</option>
                                        <option value="Bank Transfer" <?php echo ($_POST['payment_method'] ?? '') === 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                        <option value="Online" <?php echo ($_POST['payment_method'] ?? '') === 'Online' ? 'selected' : ''; ?>>Online</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a payment method.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="amount" class="form-label">Amount *</label>
                                    <input type="number" step="0.01" class="form-control" id="amount" name="amount" 
                                           value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter an amount.</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-secondary me-2">Clear Form</button>
                                <button type="submit" class="btn btn-primary">Save Payment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
    // Form validation
    (function() {
        'use strict';
        
        var forms = document.querySelectorAll('.needs-validation');
        
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
    </script>
</body>
</html>