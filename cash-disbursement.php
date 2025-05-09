<?php
session_start();
require_once 'config/functions.php';
require_once 'config/disbursement_functions.php';

// Check if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Update database structure
$conn = getConnection();
try {
    // Create or update disbursements table
    $sql = "CREATE TABLE IF NOT EXISTS disbursements (
        id INT PRIMARY KEY AUTO_INCREMENT,
        voucher_number VARCHAR(50) NOT NULL,
        disbursement_date DATE NOT NULL,
        payee VARCHAR(255) NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        description TEXT,
        status ENUM('Pending', 'Completed', 'Voided') NOT NULL DEFAULT 'Pending',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        approved_by INT NULL,
        approved_at TIMESTAMP NULL,
        void_reason TEXT,
        FOREIGN KEY (created_by) REFERENCES users(id),
        FOREIGN KEY (approved_by) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $conn->query($sql);
} catch (Exception $e) {
    error_log("Error updating database structure: " . $e->getMessage());
} finally {
    closeConnection($conn);
}

// Handle approve/void actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_disbursement'])) {
        $result = approveDisbursement($_POST['disbursement_id'], $_SESSION['user_id']);
        $_SESSION[($result['success'] ? 'success_message' : 'error_message')] = $result['message'];
    } elseif (isset($_POST['void_disbursement'])) {
        $result = voidDisbursement($_POST['disbursement_id'], $_SESSION['user_id'], $_POST['void_reason']);
        $_SESSION[($result['success'] ? 'success_message' : 'error_message')] = $result['message'];
    }
}

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get disbursements
try {
    $disbursements = getDisbursements($status, $startDate, $endDate, $search);
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error retrieving disbursements: " . $e->getMessage();
    $disbursements = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/dt-1.11.5/datatables.min.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css"/>
    <style>
        .status-badge {
            font-size: 0.875em;
            padding: 0.5em 0.75em;
        }
        .modal-xl {
            max-width: 95%;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.25rem;
            margin-bottom: 1rem;
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
                    <h1 class="h2">Cash Disbursement</h1>
                    <div class="btn-toolbar">
                        <a href="add-disbursement.php" class="btn btn-primary me-2">
                            <i class="bi bi-plus-circle"></i> New Disbursement
                        </a>
                        <button type="button" class="btn btn-success" id="printTable">
                            <i class="bi bi-printer"></i> Print Table
                        </button>
                    </div>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="filter-section">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" name="status" id="status">
                                <option value="">All Status</option>
                                <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Completed" <?php echo $status === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="Voided" <?php echo $status === 'Voided' ? 'selected' : ''; ?>>Voided</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" value="<?php echo $startDate; ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" value="<?php echo $endDate; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search voucher, payee...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Search
                                </button>
                                <a href="cash-disbursement.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="disbursementsTable">
                                <thead>
                                    <tr>
                                        <th>Voucher #</th>
                                        <th>Date</th>
                                        <th>Payee</th>
                                        <th class="text-end">Amount</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Created By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($disbursements)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No disbursements found</td>
                                        </tr>
                                    <?php else: 
                                        foreach ($disbursements as $disbursement): 
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($disbursement['voucher_number']); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($disbursement['disbursement_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($disbursement['payee']); ?></td>
                                            <td class="text-end"><?php echo number_format($disbursement['amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($disbursement['description'] ?? ''); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getStatusColor($disbursement['status']); ?>">
                                                    <?php echo htmlspecialchars($disbursement['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($disbursement['created_by_name'] ?? 'System'); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-info view-details" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#disbursementModal"
                                                            data-id="<?php echo $disbursement['id']; ?>"
                                                            title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <?php if ($disbursement['status'] === 'Pending' && isAdmin($_SESSION['user_id'])): ?>
                                                        <button type="button" class="btn btn-success approve-disbursement" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#approveModal"
                                                                data-id="<?php echo $disbursement['id']; ?>"
                                                                title="Approve">
                                                            <i class="bi bi-check-circle"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-danger void-disbursement" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#voidModal"
                                                                data-id="<?php echo $disbursement['id']; ?>"
                                                                title="Void">
                                                            <i class="bi bi-x-circle"></i>
                                                        </button>
                                                    <?php endif; ?>
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

                <!-- Disbursement Details Modal -->
                <div class="modal fade" id="disbursementModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Disbursement Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div id="disbursementDetails">Loading...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Approve Modal -->
                <div class="modal fade" id="approveModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title">Approve Disbursement</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Are you sure you want to approve this disbursement?</p>
                                    <input type="hidden" name="disbursement_id" id="approve_disbursement_id">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="approve_disbursement" class="btn btn-success">Approve</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Void Modal -->
                <div class="modal fade" id="voidModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title">Void Disbursement</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="disbursement_id" id="void_disbursement_id">
                                    <div class="mb-3">
                                        <label for="void_reason" class="form-label">Reason for Voiding</label>
                                        <textarea class="form-control" name="void_reason" id="void_reason" rows="3" required></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="void_disbursement" class="btn btn-danger">Void</button>
                                </div>
                            </form>
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
        var table = $('#disbursementsTable').DataTable({
            "pageLength": 10,
            "order": [[1, "desc"]], // Sort by date column descending
            "language": {
                "emptyTable": "No disbursements found",
                "zeroRecords": "No matching records found"
            }
        });

        // Initialize modal data
        $('.approve-disbursement').click(function() {
            $('#approve_disbursement_id').val($(this).data('id'));
        });
        
        $('.void-disbursement').click(function() {
            $('#void_disbursement_id').val($(this).data('id'));
        });

        // Print functionality
        $('#printTable').on('click', function() {
            window.print();
        });

        // View details functionality
        $('.view-details').click(function() {
            var id = $(this).data('id');
            $('#disbursementDetails').load('get-disbursement-details.php?id=' + id);
        });
    });
    </script>
</body>
</html>