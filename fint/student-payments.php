<?php
session_start();
require_once 'config/functions.php';
require_once 'config/payment_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get filter parameters
$studentId = isset($_GET['student_id']) ? $_GET['student_id'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Get student payments with filters
function getStudentPayments($filters) {
    $conn = getConnection();
    $whereConditions = [];
    $params = [];
    $types = '';
    
    try {
        $sql = "SELECT 
                sp.*,
                u.username as created_by_name
                FROM student_payments sp
                LEFT JOIN users u ON sp.created_by = u.id";

        if (!empty($filters['student_id'])) {
            $whereConditions[] = "sp.student_id LIKE ?";
            $params[] = "%" . $filters['student_id'] . "%";
            $types .= "s";
        }
        
        if (!empty($filters['start_date'])) {
            $whereConditions[] = "sp.payment_date >= ?";
            $params[] = $filters['start_date'];
            $types .= "s";
        }
        
        if (!empty($filters['end_date'])) {
            $whereConditions[] = "sp.payment_date <= ?";
            $params[] = $filters['end_date'];
            $types .= "s";
        }
        
        if (!empty($filters['status'])) {
            $whereConditions[] = "sp.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
        
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        $sql .= " ORDER BY sp.payment_date DESC, sp.id DESC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $payments = [];
        
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        
        return $payments;
    } finally {
        closeConnection($conn);
    }
}

$payments = getStudentPayments([
    'student_id' => $studentId,
    'start_date' => $startDate,
    'end_date' => $endDate,
    'status' => $status
]);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .filter-section {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.25rem;
            margin-bottom: 1rem;
        }
        .action-buttons {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Student Payments</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="add-student-payment.php" class="btn btn-primary me-2">
                            <i class="bi bi-plus-circle"></i> New Payment
                        </a>
                        <button type="button" class="btn btn-success" id="printTable">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>

                <div class="filter-section">
                    <form class="row g-3" method="GET">
                        <div class="col-md-3">
                            <label for="student_id" class="form-label">Student ID</label>
                            <input type="text" class="form-control" id="student_id" name="student_id" 
                                   value="<?php echo htmlspecialchars($studentId); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo htmlspecialchars($startDate); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo htmlspecialchars($endDate); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All</option>
                                <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Completed" <?php echo $status === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="Voided" <?php echo $status === 'Voided' ? 'selected' : ''; ?>>Voided</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Search
                                </button>
                                <a href="student-payments.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="paymentsTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reference #</th>
                                        <th>Student ID</th>
                                        <th>Payment Type</th>
                                        <th>Method</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-end">Remaining Balance</th>
                                        <th>Status</th>
                                        <th>Created By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($payments)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center">No payments found</td>
                                        </tr>
                                    <?php else: 
                                        foreach ($payments as $payment): 
                                    ?>
                                        <tr>
                                            <td><?php echo date('Y-m-d', strtotime($payment['payment_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($payment['reference_number']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['payment_type']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                            <td class="text-end"><?php echo number_format($payment['amount'], 2); ?></td>
                                            <td class="text-end"><?php echo number_format($payment['remaining_balance'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getStatusColor($payment['status']); ?>">
                                                    <?php echo htmlspecialchars($payment['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($payment['created_by_name'] ?? 'System'); ?></td>
                                            <td class="action-buttons">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-info view-details" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#paymentModal"
                                                            data-id="<?php echo $payment['id']; ?>"
                                                            title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <a href="print-receipt.php?id=<?php echo $payment['id']; ?>" 
                                                       class="btn btn-secondary"
                                                       target="_blank"
                                                       title="Print Receipt">
                                                        <i class="bi bi-printer"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php 
                                        endforeach; 
                                    endif; 
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script type="text/javascript" src="https://cdn.datatables.net/v/bs5/dt-1.11.5/datatables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#paymentsTable').DataTable({
            "pageLength": 10,
            "order": [[0, "desc"]], // Sort by date column descending
            "language": {
                "emptyTable": "No payments found",
                "zeroRecords": "No matching records found"
            }
        });

        // Print functionality
        $('#printTable').on('click', function() {
            window.print();
        });

        // View details functionality
        $('.view-details').click(function() {
            var id = $(this).data('id');
            $('#paymentDetails').load('get-payment-details.php?id=' + id);
        });
    });
    </script>
</body>
</html>