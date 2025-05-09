<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/functions.php';

// Function to get transaction summaries excluding voided transactions
function getTransactionSummaries() {
    $conn = getConnection();
    
    // Common WHERE clause to exclude voided transactions
    $notVoidedWhere = "t.status != 'void' AND t.status != 'deleted'";
    
    // Get today's transactions
    $todaySQL = "SELECT 
                    COUNT(*) as count,
                    SUM(CASE WHEN td.debit_amount > 0 THEN td.debit_amount ELSE 0 END) as total_debit,
                    SUM(CASE WHEN td.credit_amount > 0 THEN td.credit_amount ELSE 0 END) as total_credit
                 FROM transactions t
                 JOIN transaction_details td ON t.id = td.transaction_id
                 WHERE $notVoidedWhere 
                 AND DATE(t.transaction_date) = CURDATE()";
    
    // Get this month's transactions
    $monthSQL = "SELECT 
                    COUNT(*) as count,
                    SUM(CASE WHEN td.debit_amount > 0 THEN td.debit_amount ELSE 0 END) as total_debit,
                    SUM(CASE WHEN td.credit_amount > 0 THEN td.credit_amount ELSE 0 END) as total_credit
                 FROM transactions t
                 JOIN transaction_details td ON t.id = td.transaction_id
                 WHERE $notVoidedWhere 
                 AND MONTH(t.transaction_date) = MONTH(CURDATE())
                 AND YEAR(t.transaction_date) = YEAR(CURDATE())";
    
    // Get total transactions
    $totalSQL = "SELECT 
                    COUNT(*) as count,
                    SUM(CASE WHEN td.debit_amount > 0 THEN td.debit_amount ELSE 0 END) as total_debit,
                    SUM(CASE WHEN td.credit_amount > 0 THEN td.credit_amount ELSE 0 END) as total_credit
                 FROM transactions t
                 JOIN transaction_details td ON t.id = td.transaction_id
                 WHERE $notVoidedWhere";
    
    // Get revenue accounts totals (typically income accounts)
    $revenueSQL = "SELECT 
                    SUM(CASE WHEN td.credit_amount > 0 THEN td.credit_amount ELSE 0 END) as total_revenue
                 FROM transactions t
                 JOIN transaction_details td ON t.id = td.transaction_id
                 JOIN accounts a ON td.account_id = a.id
                 WHERE $notVoidedWhere 
                 AND a.account_type IN ('Income', 'Revenue')
                 AND MONTH(t.transaction_date) = MONTH(CURDATE())
                 AND YEAR(t.transaction_date) = YEAR(CURDATE())";

    // Get expense accounts totals
    $expenseSQL = "SELECT 
                    SUM(CASE WHEN td.debit_amount > 0 THEN td.debit_amount ELSE 0 END) as total_expenses
                 FROM transactions t
                 JOIN transaction_details td ON t.id = td.transaction_id
                 JOIN accounts a ON td.account_id = a.id
                 WHERE $notVoidedWhere 
                 AND a.account_type = 'Expense'
                 AND MONTH(t.transaction_date) = MONTH(CURDATE())
                 AND YEAR(t.transaction_date) = YEAR(CURDATE())";

    // Get all-time profit/loss
    $profitLossSQL = "SELECT 
                        (SELECT SUM(CASE WHEN td.credit_amount > 0 THEN td.credit_amount ELSE 0 END)
                         FROM transactions t
                         JOIN transaction_details td ON t.id = td.transaction_id
                         JOIN accounts a ON td.account_id = a.id
                         WHERE $notVoidedWhere AND a.account_type IN ('Income', 'Revenue')) -
                        (SELECT SUM(CASE WHEN td.debit_amount > 0 THEN td.debit_amount ELSE 0 END)
                         FROM transactions t
                         JOIN transaction_details td ON t.id = td.transaction_id
                         JOIN accounts a ON td.account_id = a.id
                         WHERE $notVoidedWhere AND a.account_type = 'Expense') as total_profit_loss";
    
    $today = $conn->query($todaySQL)->fetch_assoc();
    $month = $conn->query($monthSQL)->fetch_assoc();
    $total = $conn->query($totalSQL)->fetch_assoc();
    $revenue = $conn->query($revenueSQL)->fetch_assoc();
    $expenses = $conn->query($expenseSQL)->fetch_assoc();
    $profitLoss = $conn->query($profitLossSQL)->fetch_assoc();
    
    closeConnection($conn);
    
    // Handle null values
    $revenue['total_revenue'] = $revenue['total_revenue'] ?? 0;
    $expenses['total_expenses'] = $expenses['total_expenses'] ?? 0;
    $profitLoss['total_profit_loss'] = $profitLoss['total_profit_loss'] ?? 0;
    
    return [
        'today' => $today,
        'month' => $month,
        'total' => $total,
        'revenue' => $revenue['total_revenue'],
        'expenses' => $expenses['total_expenses'],
        'profit_loss' => $profitLoss['total_profit_loss']
    ];
}

// Function to get recent transactions excluding voided ones
function getRecentTransactions($limit = 10) {
    $conn = getConnection();
    
    $sql = "SELECT 
                t.id,
                t.transaction_date,
                t.reference_number,
                t.entry_name,
                t.description,
                t.status,
                SUM(td.debit_amount) as total_debit,
                SUM(td.credit_amount) as total_credit,
                u.username as created_by
            FROM transactions t
            JOIN transaction_details td ON t.id = td.transaction_id
            LEFT JOIN users u ON t.created_by = u.id
            WHERE t.status != 'void' AND t.status != 'deleted'
            GROUP BY t.id
            ORDER BY t.transaction_date DESC, t.id DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    closeConnection($conn);
    return $transactions;
}

// Get summary data
$summaries = getTransactionSummaries();
$recentTransactions = getRecentTransactions();

// Get account balances
$accounts = getAllAccounts();

$pageTitle = "Dashboard";
?>

                


<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .summary-card {
            border-left: 4px solid #0d6efd;
        }
        .card-dash {
            border-radius: 15px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transition: transform 0.2s;
        }
        .card-dash:hover {
            transform: translateY(-5px);
        }
        .card-revenue {
            background: linear-gradient(45deg, #2196F3, #4CAF50);
        }
        .card-expenses {
            background: linear-gradient(45deg, #FF5722, #FFC107);
        }
        .card-profit {
            background: linear-gradient(45deg, #9C27B0, #E91E63);
        }
        .card-balance {
            background: linear-gradient(45deg, #00BCD4, #3F51B5);
        }
        .card-dash .card-body {
            padding: 1.5rem;
        }
        .card-dash .card-title {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.75rem;
        }
        .card-dash .card-value {
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .card-dash .card-change {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.8rem;
        }
        .card-dash i {
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
            color: rgba(255, 255, 255, 0.5);
            font-size: 2rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">



<!-- Four Summary Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-dash card-revenue">
                            <div class="card-body">
                                <i class="bi bi-currency-dollar"></i>
                                <h5 class="card-title">REVENUE</h5>
                                <div class="card-value">₱<?php echo number_format($summaries['revenue'], 2); ?></div>
                                <div class="card-change">
                                    <i class="bi bi-arrow-up"></i> This Month's Revenue
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-dash card-expenses">
                            <div class="card-body">
                                <i class="bi bi-cash-stack"></i>
                                <h5 class="card-title">EXPENSES</h5>
                                <div class="card-value">₱<?php echo number_format($summaries['expenses'], 2); ?></div>
                                <div class="card-change">
                                    <i class="bi bi-arrow-down"></i> This Month's Expenses
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-dash card-profit">
                            <div class="card-body">
                                <i class="bi bi-graph-up"></i>
                                <h5 class="card-title">NET PROFIT</h5>
                                <div class="card-value">₱<?php echo number_format($summaries['revenue'] - $summaries['expenses'], 2); ?></div>
                                <div class="card-change">
                                    <i class="bi bi-calendar3"></i> This Month's Profit/Loss
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-dash card-balance">
                            <div class="card-body">
                                <i class="bi bi-wallet2"></i>
                                <h5 class="card-title">TOTAL BALANCE</h5>
                                <div class="card-value">₱<?php echo number_format($summaries['profit_loss'], 2); ?></div>
                                <div class="card-change">
                                    <i class="bi bi-clock-history"></i> All-time Profit/Loss
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transaction Summaries -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card summary-card today">
                            <div class="card-body">
                                <h5 class="card-title">Today's Transactions</h5>
                                <p class="card-text mb-1">Count: <?php echo $summaries['today']['count']; ?></p>
                                <p class="card-text mb-1">Total Debit: <?php echo number_format($summaries['today']['total_debit'], 2); ?></p>
                                <p class="card-text">Total Credit: <?php echo number_format($summaries['today']['total_credit'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card summary-card month">
                            <div class="card-body">
                                <h5 class="card-title">This Month's Transactions</h5>
                                <p class="card-text mb-1">Count: <?php echo $summaries['month']['count']; ?></p>
                                <p class="card-text mb-1">Total Debit: <?php echo number_format($summaries['month']['total_debit'], 2); ?></p>
                                <p class="card-text">Total Credit: <?php echo number_format($summaries['month']['total_credit'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card summary-card total">
                            <div class="card-body">
                                <h5 class="card-title">All Time Transactions</h5>
                                <p class="card-text mb-1">Count: <?php echo $summaries['total']['count']; ?></p>
                                <p class="card-text mb-1">Total Debit: <?php echo number_format($summaries['total']['total_debit'], 2); ?></p>
                                <p class="card-text">Total Credit: <?php echo number_format($summaries['total']['total_credit'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Balances -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Account Balances</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Account Code</th>
                                                <th>Account Name</th>
                                                <th>Type</th>
                                                <th class="text-end">Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($accounts as $account): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($account['account_code']); ?></td>
                                                <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                                                <td><?php echo htmlspecialchars($account['account_type']); ?></td>
                                                <td class="text-end"><?php echo number_format($account['balance'], 2); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Recent Transactions</h5>
                                <a href="transactions-list.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Reference</th>
                                                <th>Entry Name</th>
                                                <th>Description</th>
                                                <th class="text-end">Debit</th>
                                                <th class="text-end">Credit</th>
                                                <th>Created By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentTransactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo date('Y-m-d', strtotime($transaction['transaction_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['reference_number']); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['entry_name']); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                                <td class="text-end"><?php echo number_format($transaction['total_debit'], 2); ?></td>
                                                <td class="text-end"><?php echo number_format($transaction['total_credit'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['created_by']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>