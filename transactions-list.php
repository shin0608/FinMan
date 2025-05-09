<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/functions.php';

// Get all transactions
function getAllTransactions() {
    $conn = getConnection();
    $sql = "SELECT 
                t.id,
                t.transaction_date,
                t.reference_number,
                t.entry_name,
                t.description,
                SUM(td.debit_amount) as total_debit,
                SUM(td.credit_amount) as total_credit
            FROM transactions t
            LEFT JOIN transaction_details td ON t.id = td.transaction_id
            GROUP BY t.id
            ORDER BY t.transaction_date DESC, t.id DESC";
    
    $result = $conn->query($sql);
    $transactions = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
    }
    
    closeConnection($conn);
    return $transactions;
}

$transactions = getAllTransactions();
$pageTitle = "Transactions List";
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
                    <h1 class="h2">Transactions</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reference</th>
                                        <th>Entry Name</th>
                                        <th>Description</th>
                                        <th>Debit Total</th>
                                        <th>Credit Total</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d', strtotime($transaction['transaction_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['reference_number']); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['entry_name']); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                        <td><?php echo number_format($transaction['total_debit'], 2); ?></td>
                                        <td><?php echo number_format($transaction['total_credit'], 2); ?></td>
                                        <td>
                                            <a href="transactions-view.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="transactions-edit.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>