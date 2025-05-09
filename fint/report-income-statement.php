<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/functions.php';

// Get period from URL, default to current month
$period = isset($_GET['period']) ? $_GET['period'] : date('Y-m');
$format = isset($_GET['format']) ? $_GET['format'] : 'html';

// Initialize variables with default values
$revenue = [
    'categories' => [],
    'total' => 0
];
$costOfSales = [
    'categories' => [],
    'total' => 0
];
$operatingExpenses = [
    'categories' => [],
    'total' => 0
];
$otherItems = [
    'income' => 0,
    'expenses' => 0
];
$grossProfit = 0;
$operatingIncome = 0;
$netIncome = 0;
$analysis = '';

try {
    // Parse period
    list($year, $month) = explode('-', $period);
    $startDate = "$year-$month-01";
    $endDate = date('Y-m-t', strtotime($startDate));
    
    $conn = getConnection();
    
    // Get Revenue
    $sql = "SELECT 
            a.account_name,
            SUM(CASE 
                WHEN a.account_type IN ('Revenue', 'Income') 
                THEN td.credit_amount - td.debit_amount
                ELSE 0 
            END) as amount
        FROM transactions t
        JOIN transaction_details td ON t.id = td.transaction_id
        JOIN accounts a ON td.account_id = a.id
        WHERE t.transaction_date BETWEEN ? AND ?
        AND t.status = 'Posted'
        AND a.account_type IN ('Revenue', 'Income')
        GROUP BY a.id, a.account_name
        HAVING amount <> 0
        ORDER BY a.account_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $revenue['categories'][] = [
            'name' => $row['account_name'],
            'amount' => floatval($row['amount'])
        ];
        $revenue['total'] += floatval($row['amount']);
    }
    
    // Get Cost of Sales
    $sql = "SELECT 
            a.account_name,
            SUM(td.debit_amount - td.credit_amount) as amount
        FROM transactions t
        JOIN transaction_details td ON t.id = td.transaction_id
        JOIN accounts a ON td.account_id = a.id
        WHERE t.transaction_date BETWEEN ? AND ?
        AND t.status = 'Posted'
        AND a.account_type = 'Cost of Sales'
        GROUP BY a.id, a.account_name
        HAVING amount <> 0
        ORDER BY a.account_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $costOfSales['categories'][] = [
            'name' => $row['account_name'],
            'amount' => floatval($row['amount'])
        ];
        $costOfSales['total'] += floatval($row['amount']);
    }
    
    // Calculate Gross Profit
    $grossProfit = $revenue['total'] - $costOfSales['total'];
    
    // Get Operating Expenses
    $sql = "SELECT 
            a.account_name,
            SUM(td.debit_amount - td.credit_amount) as amount
        FROM transactions t
        JOIN transaction_details td ON t.id = td.transaction_id
        JOIN accounts a ON td.account_id = a.id
        WHERE t.transaction_date BETWEEN ? AND ?
        AND t.status = 'Posted'
        AND a.account_type = 'Expense'
        GROUP BY a.id, a.account_name
        HAVING amount <> 0
        ORDER BY a.account_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $operatingExpenses['categories'][] = [
            'name' => $row['account_name'],
            'amount' => floatval($row['amount'])
        ];
        $operatingExpenses['total'] += floatval($row['amount']);
    }
    
    // Calculate Operating Income
    $operatingIncome = $grossProfit - $operatingExpenses['total'];
    
    // Get Other Income/Expenses
    $sql = "SELECT 
            a.account_type,
            SUM(CASE 
                WHEN a.account_type = 'Other Income' 
                THEN td.credit_amount - td.debit_amount
                ELSE td.debit_amount - td.credit_amount
            END) as amount
        FROM transactions t
        JOIN transaction_details td ON t.id = td.transaction_id
        JOIN accounts a ON td.account_id = a.id
        WHERE t.transaction_date BETWEEN ? AND ?
        AND t.status = 'Posted'
        AND a.account_type IN ('Other Income', 'Other Expense')
        GROUP BY a.account_type
        HAVING amount <> 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        if ($row['account_type'] === 'Other Income') {
            $otherItems['income'] = floatval($row['amount']);
        } else {
            $otherItems['expenses'] = floatval($row['amount']);
        }
    }
    
    // Calculate Net Income
    $netIncome = $operatingIncome + $otherItems['income'] - $otherItems['expenses'];
    
    // Generate analysis
    $profitMargin = $revenue['total'] > 0 ? ($netIncome / $revenue['total']) * 100 : 0;
    $analysis = sprintf(
        "Financial Analysis for %s:\n" .
        "Total revenue for the period was PHP %s, resulting in a gross profit of PHP %s (%.1f%% margin).\n" .
        "Operating expenses totaled PHP %s, leading to an operating income of PHP %s.\n" .
        "After considering other items, the net income for the period is PHP %s, representing a %.1f%% profit margin.",
        $period,
        number_format($revenue['total'], 2),
        number_format($grossProfit, 2),
        ($revenue['total'] > 0 ? ($grossProfit / $revenue['total']) * 100 : 0),
        number_format($operatingExpenses['total'], 2),
        number_format($operatingIncome, 2),
        number_format($netIncome, 2),
        $profitMargin
    );
    
    if ($format === 'json') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'period' => $period,
                'revenue' => $revenue,
                'cost_of_sales' => $costOfSales,
                'gross_profit' => $grossProfit,
                'operating_expenses' => $operatingExpenses,
                'operating_income' => $operatingIncome,
                'other_items' => $otherItems,
                'net_income' => $netIncome,
                'analysis' => $analysis
            ],
            'metadata' => [
                'generated_at' => date('Y-m-d H:i:s'),
                'generated_by' => $_SESSION['user_id']
            ]
        ]);
        exit;
    }
    
} catch (Exception $e) {
    if ($format === 'json') {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
    $error = $e->getMessage();
} finally {
    if (isset($conn)) {
        closeConnection($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .statement-section {
            margin-bottom: 2rem;
        }
        .statement-total {
            font-weight: bold;
            border-top: 2px solid #dee2e6;
        }
        .analysis-section {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-top: 2rem;
        }
        .statement-card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
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
                    <h1 class="h2">Income Statement</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="input-group me-2">
                            <input type="month" class="form-control" id="periodSelect" value="<?php echo htmlspecialchars($period); ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="changePeriod()">Go</button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php else: ?>
                    <div class="card statement-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Income Statement for <?php echo htmlspecialchars($period); ?></h5>
                            <div class="card-tools">
                                <small class="text-muted">Generated: <?php echo date('Y-m-d H:i:s'); ?></small>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Revenue Section -->
                            <div class="statement-section">
                                <h6 class="text-muted">Revenue</h6>
                                <table class="table">
                                    <?php foreach ($revenue['categories'] as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td class="text-end">₱<?php echo number_format($category['amount'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="statement-total">
                                        <td>Total Revenue</td>
                                        <td class="text-end">₱<?php echo number_format($revenue['total'], 2); ?></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Cost of Sales Section -->
                            <div class="statement-section">
                                <h6 class="text-muted">Cost of Sales</h6>
                                <table class="table">
                                    <?php foreach ($costOfSales['categories'] as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td class="text-end">₱<?php echo number_format($category['amount'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="statement-total">
                                        <td>Total Cost of Sales</td>
                                        <td class="text-end">₱<?php echo number_format($costOfSales['total'], 2); ?></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Gross Profit -->
                            <div class="statement-section">
                                <table class="table">
                                    <tr class="table-primary">
                                        <th>Gross Profit</th>
                                        <th class="text-end">₱<?php echo number_format($grossProfit, 2); ?></th>
                                    </tr>
                                </table>
                            </div>

                            <!-- Operating Expenses -->
                            <div class="statement-section">
                                <h6 class="text-muted">Operating Expenses</h6>
                                <table class="table">
                                    <?php foreach ($operatingExpenses['categories'] as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td class="text-end">₱<?php echo number_format($category['amount'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="statement-total">
                                        <td>Total Operating Expenses</td>
                                        <td class="text-end">₱<?php echo number_format($operatingExpenses['total'], 2); ?></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Operating Income -->
                            <div class="statement-section">
                                <table class="table">
                                    <tr class="table-info">
                                        <th>Operating Income</th>
                                        <th class="text-end">₱<?php echo number_format($operatingIncome, 2); ?></th>
                                    </tr>
                                </table>
                            </div>

                            <!-- Other Items -->
                            <div class="statement-section">
                                <h6 class="text-muted">Other Items</h6>
                                <table class="table">
                                    <tr>
                                        <td>Other Income</td>
                                        <td class="text-end">₱<?php echo number_format($otherItems['income'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Other Expenses</td>
                                        <td class="text-end">₱<?php echo number_format($otherItems['expenses'], 2); ?></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Net Income -->
                            <div class="statement-section">
                                <table class="table">
                                    <tr class="table-success">
                                        <th>Net Income</th>
                                        <th class="text-end">₱<?php echo number_format($netIncome, 2); ?></th>
                                    </tr>
                                </table>
                            </div>

                            <!-- Analysis -->
                            <div class="analysis-section">
                                <h6><i class="bi bi-graph-up"></i> Financial Analysis</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($analysis)); ?></p>
                            </div>
                        </div>
                        <div class="card-footer text-muted">
                            <div class="row">
                                <div class="col-md-6">
                                    <small>Generated by: <?php echo htmlspecialchars($_SESSION['user_id']); ?></small>
                                </div>
                                <div class="col-md-6 text-end">
                                    <small>Generated on: <?php echo date('Y-m-d H:i:s'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
    function changePeriod() {
        const period = document.getElementById('periodSelect').value;
        window.location.href = `report-income-statement.php?period=${period}`;
    }
    </script>
</body>
</html>