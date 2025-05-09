<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/functions.php';

// Get date from URL, default to current date
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$format = isset($_GET['format']) ? $_GET['format'] : 'html';

// Initialize variables with default values
$assets = [
    'current' => [
        'categories' => [],
        'total' => 0
    ],
    'non_current' => [
        'categories' => [],
        'total' => 0
    ],
    'total' => 0
];

$liabilities = [
    'current' => [
        'categories' => [],
        'total' => 0
    ],
    'non_current' => [
        'categories' => [],
        'total' => 0
    ],
    'total' => 0
];

$equity = [
    'categories' => [],
    'total' => 0
];

try {
    $conn = getConnection();
    
    // Get Current Assets
    $sql = "SELECT 
            a.account_name,
            a.account_code,
            SUM(td.debit_amount - td.credit_amount) as balance
        FROM accounts a
        LEFT JOIN transaction_details td ON a.id = td.account_id
        LEFT JOIN transactions t ON td.transaction_id = t.id
        WHERE a.account_type = 'Asset'
        AND a.is_current = 1
        AND (t.transaction_date <= ? OR t.transaction_date IS NULL)
        AND (t.status = 'Posted' OR t.status IS NULL)
        GROUP BY a.id, a.account_name, a.account_code
        HAVING balance <> 0
        ORDER BY a.account_code";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $assets['current']['categories'][] = [
            'code' => $row['account_code'],
            'name' => $row['account_name'],
            'balance' => floatval($row['balance'])
        ];
        $assets['current']['total'] += floatval($row['balance']);
    }
    
    // Get Non-Current Assets
    $sql = "SELECT 
        a.account_name,
        a.account_code,
        SUM(td.debit_amount - td.credit_amount) as balance
    FROM accounts a
    LEFT JOIN transaction_details td ON a.id = td.account_id
    LEFT JOIN transactions t ON td.transaction_id = t.id
    WHERE a.account_type = 'Asset'
    AND a.account_code LIKE '1[0-1]%' -- Current assets typically start with 10 or 11
    AND (t.transaction_date <= ? OR t.transaction_date IS NULL)
    AND (t.status = 'Posted' OR t.status IS NULL)
    GROUP BY a.id, a.account_name, a.account_code
    HAVING balance <> 0
    ORDER BY a.account_code";

// Replace the non-current assets query with:
$sql = "SELECT 
        a.account_name,
        a.account_code,
        SUM(td.debit_amount - td.credit_amount) as balance
    FROM accounts a
    LEFT JOIN transaction_details td ON a.id = td.account_id
    LEFT JOIN transactions t ON td.transaction_id = t.id
    WHERE a.account_type = 'Asset'
    AND a.account_code LIKE '1[2-9]%' -- Non-current assets typically start with 12-19
    AND (t.transaction_date <= ? OR t.transaction_date IS NULL)
    AND (t.status = 'Posted' OR t.status IS NULL)
    GROUP BY a.id, a.account_name, a.account_code
    HAVING balance <> 0
    ORDER BY a.account_code";

// Replace the current liabilities query with:
$sql = "SELECT 
        a.account_name,
        a.account_code,
        SUM(td.credit_amount - td.debit_amount) as balance
    FROM accounts a
    LEFT JOIN transaction_details td ON a.id = td.account_id
    LEFT JOIN transactions t ON td.transaction_id = t.id
    WHERE a.account_type = 'Liability'
    AND a.account_code LIKE '2[0-1]%' -- Current liabilities typically start with 20 or 21
    AND (t.transaction_date <= ? OR t.transaction_date IS NULL)
    AND (t.status = 'Posted' OR t.status IS NULL)
    GROUP BY a.id, a.account_name, a.account_code
    HAVING balance <> 0
    ORDER BY a.account_code";

// Replace the non-current liabilities query with:
$sql = "SELECT 
        a.account_name,
        a.account_code,
        SUM(td.credit_amount - td.debit_amount) as balance
    FROM accounts a
    LEFT JOIN transaction_details td ON a.id = td.account_id
    LEFT JOIN transactions t ON td.transaction_id = t.id
    WHERE a.account_type = 'Liability'
    AND a.account_code LIKE '2[2-9]%' -- Non-current liabilities typically start with 22-29
    AND (t.transaction_date <= ? OR t.transaction_date IS NULL)
    AND (t.status = 'Posted' OR t.status IS NULL)
    GROUP BY a.id, a.account_name, a.account_code
    HAVING balance <> 0
    ORDER BY a.account_code";
    
    // Get Equity
    $sql = "SELECT 
            a.account_name,
            a.account_code,
            SUM(td.credit_amount - td.debit_amount) as balance
        FROM accounts a
        LEFT JOIN transaction_details td ON a.id = td.account_id
        LEFT JOIN transactions t ON td.transaction_id = t.id
        WHERE a.account_type = 'Equity'
        AND (t.transaction_date <= ? OR t.transaction_date IS NULL)
        AND (t.status = 'Posted' OR t.status IS NULL)
        GROUP BY a.id, a.account_name, a.account_code
        HAVING balance <> 0
        ORDER BY a.account_code";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $equity['categories'][] = [
            'code' => $row['account_code'],
            'name' => $row['account_name'],
            'balance' => floatval($row['balance'])
        ];
        $equity['total'] += floatval($row['balance']);
    }
    
    // Calculate working capital and other key metrics
    $workingCapital = $assets['current']['total'] - $liabilities['current']['total'];
    $currentRatio = $liabilities['current']['total'] > 0 ? 
                   $assets['current']['total'] / $liabilities['current']['total'] : 0;
    $debtToEquityRatio = $equity['total'] > 0 ? 
                        $liabilities['total'] / $equity['total'] : 0;
    
    // Generate analysis
    $analysis = sprintf(
        "Financial Position Analysis as of %s:\n\n" .
        "Working Capital: PHP %s\n" .
        "Current Ratio: %.2f\n" .
        "Debt to Equity Ratio: %.2f\n\n" .
        "The company's total assets of PHP %s are financed by PHP %s in liabilities and PHP %s in equity. " .
        "The working capital position %s adequate for current operations.",
        date('F j, Y', strtotime($date)),
        number_format($workingCapital, 2),
        $currentRatio,
        $debtToEquityRatio,
        number_format($assets['total'], 2),
        number_format($liabilities['total'], 2),
        number_format($equity['total'], 2),
        $workingCapital > 0 ? "appears" : "may not be"
    );
    
    if ($format === 'json') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'date' => $date,
                'assets' => $assets,
                'liabilities' => $liabilities,
                'equity' => $equity,
                'metrics' => [
                    'working_capital' => $workingCapital,
                    'current_ratio' => $currentRatio,
                    'debt_to_equity_ratio' => $debtToEquityRatio
                ],
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
        .section-title {
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        .subtotal-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .total-row {
            font-weight: bold;
            background-color: #e9ecef;
        }
        .account-group {
            border-bottom: 1px solid #dee2e6;
        }
        .account-code {
            color: #6c757d;
            font-size: 0.875rem;
        }
        .analysis-section {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-top: 2rem;
        }
        .metrics-section {
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .metric-item {
            text-align: center;
            padding: 1rem;
        }
        .metric-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
        }
        .metric-label {
            color: #6c757d;
            font-size: 0.875rem;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .card {
                border: none !important;
            }
            .analysis-section {
                break-before: page;
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
                    <h1 class="h2">Balance Sheet</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="input-group me-2">
                            <input type="date" class="form-control" id="dateSelect" value="<?php echo $date; ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="changeDate()">Go</button>
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
                    <!-- Key Metrics -->
                    <div class="row metrics-section">
                        <div class="col-md-4 metric-item">
                            <div class="metric-value">₱<?php echo number_format($workingCapital, 2); ?></div>
                            <div class="metric-label">Working Capital</div>
                        </div>
                        <div class="col-md-4 metric-item">
                            <div class="metric-value"><?php echo number_format($currentRatio, 2); ?></div>
                            <div class="metric-label">Current Ratio</div>
                        </div>
                        <div class="col-md-4 metric-item">
                            <div class="metric-value"><?php echo number_format($debtToEquityRatio, 2); ?></div>
                            <div class="metric-label">Debt to Equity Ratio</div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Statement of Financial Position</h5>
                            <small class="text-muted">As of <?php echo date('F j, Y', strtotime($date)); ?></small>
                        </div>
                        <div class="card-body">
                            <!-- Assets Section -->
                            <div class="statement-section">
                                <h3 class="section-title">Assets</h3>
                                
                                <!-- Current Assets -->
                                <div class="account-group mb-4">
                                    <h6 class="text-muted mb-3">Current Assets</h6>
                                    <table class="table table-sm">
                                        <?php foreach ($assets['current']['categories'] as $account): ?>
                                        <tr>
                                            <td>
                                                <span class="account-code"><?php echo htmlspecialchars($account['code']); ?></span>
                                                <?php echo htmlspecialchars($account['name']); ?>
                                            </td>
                                            <td class="text-end">₱<?php echo number_format($account['balance'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="subtotal-row">
                                            <td>Total Current Assets</td>
                                            <td class="text-end">₱<?php echo number_format($assets['current']['total'], 2); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <!-- Non-Current Assets -->
                                <div class="account-group mb-4">
                                    <h6 class="text-muted mb-3">Non-Current Assets</h6>
                                    <table class="table table-sm">
                                        <?php foreach ($assets['non_current']['categories'] as $account): ?>
                                        <tr>
                                            <td>
                                                <span class="account-code"><?php echo htmlspecialchars($account['code']); ?></span>
                                                <?php echo htmlspecialchars($account['name']); ?>
                                            </td>
                                            <td class="text-end">₱<?php echo number_format($account['balance'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="subtotal-row">
                                            <td>Total Non-Current Assets</td>
                                            <td class="text-end">₱<?php echo number_format($assets['non_current']['total'], 2); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <!-- Total Assets -->
                                <table class="table table-sm">
                                    <tr class="total-row">
                                        <th>Total Assets</th>
                                        <th class="text-end">₱<?php echo number_format($assets['total'], 2); ?></th>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Liabilities Section -->
                            <div class="statement-section">
                                <h3 class="section-title">Liabilities</h3>
                                
                                <!-- Current Liabilities -->
                                <div class="account-group mb-4">
                                    <h6 class="text-muted mb-3">Current Liabilities</h6>
                                    <table class="table table-sm">
                                        <?php foreach ($liabilities['current']['categories'] as $account): ?>
                                        <tr>
                                            <td>
                                                <span class="account-code"><?php echo htmlspecialchars($account['code']); ?></span>
                                                <?php echo htmlspecialchars($account['name']); ?>
                                            </td>
                                            <td class="text-end">₱<?php echo number_format($account['balance'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="subtotal-row">
                                            <td>Total Current Liabilities</td>
                                            <td class="text-end">₱<?php echo number_format($liabilities['current']['total'], 2); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <!-- Non-Current Liabilities -->
                                <div class="account-group mb-4">
                                    <h6 class="text-muted mb-3">Non-Current Liabilities</h6>
                                    <table class="table table-sm">
                                        <?php foreach ($liabilities['non_current']['categories'] as $account): ?>
                                        <tr>
                                            <td>
                                                <span class="account-code"><?php echo htmlspecialchars($account['code']); ?></span>
                                                <?php echo htmlspecialchars($account['name']); ?>
                                            </td>
                                            <td class="text-end">₱<?php echo number_format($account['balance'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="subtotal-row">
                                            <td>Total Non-Current Liabilities</td>
                                            <td class="text-end">₱<?php echo number_format($liabilities['non_current']['total'], 2); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <!-- Total Liabilities -->
                                <table class="table table-sm">
                                    <tr class="total-row">
                                        <th>Total Liabilities</th>
                                        <th class="text-end">₱<?php echo number_format($liabilities['total'], 2); ?></th>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Equity Section -->
                            <div class="statement-section">
                                <h3 class="section-title">Equity</h3>
                                <table class="table table-sm">
                                    <?php foreach ($equity['categories'] as $account): ?>
                                    <tr>
                                        <td>
                                            <span class="account-code"><?php echo htmlspecialchars($account['code']); ?></span>
                                            <?php echo htmlspecialchars($account['name']); ?>
                                        </td>
                                        <td class="text-end">₱<?php echo number_format($account['balance'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="total-row">
                                        <th>Total Equity</th>
                                        <th class="text-end">₱<?php echo number_format($equity['total'], 2); ?></th>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Total Liabilities and Equity -->
                            <div class="statement-section">
                                <table class="table table-sm">
                                    <tr class="total-row">
                                        <th>Total Liabilities and Equity</th>
                                        <th class="text-end">₱<?php echo number_format($liabilities['total'] + $equity['total'], 2); ?></th>
                                    </tr>
                                </table>
                            </div>

                            <!-- Analysis Section -->
                            <div class="analysis-section">
                                <h6 class="mb-3"><i class="bi bi-graph-up"></i> Financial Analysis</h6>
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
    function changeDate() {
        const date = document.getElementById('dateSelect').value;
        window.location.href = `report-balance-sheet.php?date=${date}`;
    }
    </script>
</body>
</html>