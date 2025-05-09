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

// Initialize variables
$operating = [
    'inflows' => [
        'categories' => [],
        'total' => 0
    ],
    'outflows' => [
        'categories' => [],
        'total' => 0
    ],
    'net' => 0
];

$investing = [
    'inflows' => [
        'categories' => [],
        'total' => 0
    ],
    'outflows' => [
        'categories' => [],
        'total' => 0
    ],
    'net' => 0
];

$financing = [
    'inflows' => [
        'categories' => [],
        'total' => 0
    ],
    'outflows' => [
        'categories' => [],
        'total' => 0
    ],
    'net' => 0
];

try {
    $conn = getConnection();
    
    // Parse period
    list($year, $month) = explode('-', $period);
    $startDate = "$year-$month-01";
    $endDate = date('Y-m-t', strtotime($startDate));
    
    // Get opening cash balance
    $sql = "SELECT 
            SUM(CASE 
                WHEN td.debit_amount > td.credit_amount THEN td.debit_amount - td.credit_amount
                ELSE td.credit_amount - td.debit_amount
            END) as balance
        FROM accounts a
        LEFT JOIN transaction_details td ON a.id = td.account_id
        LEFT JOIN transactions t ON td.transaction_id = t.id
        WHERE a.account_type = 'Asset'
        AND a.account_code LIKE '100%' -- Cash and cash equivalents
        AND t.transaction_date < ?
        AND t.status = 'Posted'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $startDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $openingCash = floatval($result->fetch_assoc()['balance']);
    
    // Get Operating Activities
    $sql = "SELECT 
            a.account_name,
            a.account_code,
            SUM(CASE 
                WHEN td.credit_amount > td.debit_amount THEN td.credit_amount - td.debit_amount
                ELSE td.debit_amount - td.credit_amount
            END) as amount,
            CASE 
                WHEN td.credit_amount > td.debit_amount THEN 'inflow'
                ELSE 'outflow'
            END as flow_type
        FROM transactions t
        JOIN transaction_details td ON t.id = td.transaction_id
        JOIN accounts a ON td.account_id = a.id
        WHERE t.transaction_date BETWEEN ? AND ?
        AND t.status = 'Posted'
        AND a.account_type IN ('Revenue', 'Expense', 'Asset', 'Liability')
        AND a.account_code NOT LIKE '15%' -- Exclude non-current assets
        AND a.account_code NOT LIKE '17%' -- Exclude investments
        AND a.account_code NOT LIKE '20%' -- Exclude long-term liabilities
        GROUP BY a.id, a.account_name, a.account_code, flow_type
        HAVING amount <> 0
        ORDER BY a.account_code";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        if ($row['flow_type'] === 'inflow') {
            $operating['inflows']['categories'][] = [
                'code' => $row['account_code'],
                'name' => $row['account_name'],
                'amount' => floatval($row['amount'])
            ];
            $operating['inflows']['total'] += floatval($row['amount']);
        } else {
            $operating['outflows']['categories'][] = [
                'code' => $row['account_code'],
                'name' => $row['account_name'],
                'amount' => floatval($row['amount'])
            ];
            $operating['outflows']['total'] += floatval($row['amount']);
        }
    }
    
    $operating['net'] = $operating['inflows']['total'] - $operating['outflows']['total'];
    
    // Get Investing Activities
    $sql = "SELECT 
            a.account_name,
            a.account_code,
            SUM(CASE 
                WHEN td.credit_amount > td.debit_amount THEN td.credit_amount - td.debit_amount
                ELSE td.debit_amount - td.credit_amount
            END) as amount,
            CASE 
                WHEN td.credit_amount > td.debit_amount THEN 'inflow'
                ELSE 'outflow'
            END as flow_type
        FROM transactions t
        JOIN transaction_details td ON t.id = td.transaction_id
        JOIN accounts a ON td.account_id = a.id
        WHERE t.transaction_date BETWEEN ? AND ?
        AND t.status = 'Posted'
        AND (
            a.account_code LIKE '15%' -- Non-current assets
            OR a.account_code LIKE '17%' -- Investments
        )
        GROUP BY a.id, a.account_name, a.account_code, flow_type
        HAVING amount <> 0
        ORDER BY a.account_code";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        if ($row['flow_type'] === 'inflow') {
            $investing['inflows']['categories'][] = [
                'code' => $row['account_code'],
                'name' => $row['account_name'],
                'amount' => floatval($row['amount'])
            ];
            $investing['inflows']['total'] += floatval($row['amount']);
        } else {
            $investing['outflows']['categories'][] = [
                'code' => $row['account_code'],
                'name' => $row['account_name'],
                'amount' => floatval($row['amount'])
            ];
            $investing['outflows']['total'] += floatval($row['amount']);
        }
    }
    
    $investing['net'] = $investing['inflows']['total'] - $investing['outflows']['total'];
    
    // Get Financing Activities
    $sql = "SELECT 
            a.account_name,
            a.account_code,
            SUM(CASE 
                WHEN td.credit_amount > td.debit_amount THEN td.credit_amount - td.debit_amount
                ELSE td.debit_amount - td.credit_amount
            END) as amount,
            CASE 
                WHEN td.credit_amount > td.debit_amount THEN 'inflow'
                ELSE 'outflow'
            END as flow_type
        FROM transactions t
        JOIN transaction_details td ON t.id = td.transaction_id
        JOIN accounts a ON td.account_id = a.id
        WHERE t.transaction_date BETWEEN ? AND ?
        AND t.status = 'Posted'
        AND (
            a.account_code LIKE '20%' -- Long-term liabilities
            OR a.account_type = 'Equity'
        )
                GROUP BY a.id, a.account_name, a.account_code, flow_type
        HAVING amount <> 0
        ORDER BY a.account_code";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        if ($row['flow_type'] === 'inflow') {
            $financing['inflows']['categories'][] = [
                'code' => $row['account_code'],
                'name' => $row['account_name'],
                'amount' => floatval($row['amount'])
            ];
            $financing['inflows']['total'] += floatval($row['amount']);
        } else {
            $financing['outflows']['categories'][] = [
                'code' => $row['account_code'],
                'name' => $row['account_name'],
                'amount' => floatval($row['amount'])
            ];
            $financing['outflows']['total'] += floatval($row['amount']);
        }
    }
    
    $financing['net'] = $financing['inflows']['total'] - $financing['outflows']['total'];
    
    // Get closing cash balance
    $sql = "SELECT 
            SUM(CASE 
                WHEN td.debit_amount > td.credit_amount THEN td.debit_amount - td.credit_amount
                ELSE td.credit_amount - td.debit_amount
            END) as balance
        FROM accounts a
        LEFT JOIN transaction_details td ON a.id = td.account_id
        LEFT JOIN transactions t ON td.transaction_id = t.id
        WHERE a.account_type = 'Asset'
        AND a.account_code LIKE '100%' -- Cash and cash equivalents
        AND t.transaction_date <= ?
        AND t.status = 'Posted'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $closingCash = floatval($result->fetch_assoc()['balance']);
    
    // Calculate net cash change
    $netCashChange = $operating['net'] + $investing['net'] + $financing['net'];
    
    // Generate analysis
    $analysis = sprintf(
        "Cash Flow Analysis for %s:\n\n" .
        "The company's cash position %s by PHP %s during this period.\n\n" .
        "Operating Activities:\n" .
        "Net cash from operations was PHP %s, primarily from %s.\n\n" .
        "Investing Activities:\n" .
        "Net cash from investing was PHP %s, reflecting %s.\n\n" .
        "Financing Activities:\n" .
        "Net cash from financing was PHP %s, indicating %s.\n\n" .
        "Overall liquidity position appears to be %s based on the cash flow trends.",
        date('F Y', strtotime($period)),
        $netCashChange >= 0 ? "improved" : "decreased",
        number_format(abs($netCashChange), 2),
        number_format($operating['net'], 2),
        $operating['net'] >= 0 ? "strong operational performance" : "operational challenges",
        number_format($investing['net'], 2),
        $investing['net'] >= 0 ? "divestments or asset sales" : "capital investments",
        number_format($financing['net'], 2),
        $financing['net'] >= 0 ? "increased financing" : "debt repayment or distributions",
        $netCashChange >= 0 ? "healthy" : "requiring attention"
    );
    
    if ($format === 'json') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'period' => $period,
                'operating' => $operating,
                'investing' => $investing,
                'financing' => $financing,
                'opening_balance' => $openingCash,
                'closing_balance' => $closingCash,
                'net_change' => $netCashChange,
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
        .flow-category {
            margin-bottom: 1.5rem;
        }
        .flow-type {
            font-weight: bold;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        .account-code {
            color: #6c757d;
            font-size: 0.875rem;
        }
        .inflow {
            color: #28a745;
        }
        .outflow {
            color: #dc3545;
        }
        .net-amount {
            font-weight: bold;
            font-size: 1.1rem;
        }
        .total-section {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-top: 1rem;
        }
        .analysis-section {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-top: 2rem;
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
                    <h1 class="h2">Cash Flow Statement</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="input-group me-2">
                            <input type="month" class="form-control" id="periodSelect" value="<?php echo $period; ?>">
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
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Statement of Cash Flows</h5>
                            <small class="text-muted">For the month ended <?php echo date('F j, Y', strtotime($endDate)); ?></small>
                        </div>
                        <div class="card-body">
                            <!-- Opening Balance -->
                            <div class="statement-section">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Cash and Cash Equivalents, Beginning</strong></td>
                                        <td class="text-end">₱<?php echo number_format($openingCash, 2); ?></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Operating Activities -->
                            <div class="statement-section">
                                <h3 class="section-title">Operating Activities</h3>
                                
                                <!-- Inflows -->
                                <div class="flow-category">
                                    <h6 class="flow-type">Cash Inflows:</h6>
                                    <table class="table table-sm">
                                        <?php foreach ($operating['inflows']['categories'] as $category): ?>
                                        <tr>
                                            <td>
                                                <span class="account-code"><?php echo htmlspecialchars($category['code']); ?></span>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </td>
                                            <td class="text-end inflow">₱<?php echo number_format($category['amount'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-light">
                                            <td>Total Operating Inflows</td>
                                            <td class="text-end inflow">₱<?php echo number_format($operating['inflows']['total'], 2); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <!-- Outflows -->
                                <div class="flow-category">
                                    <h6 class="flow-type">Cash Outflows:</h6>
                                    <table class="table table-sm">
                                        <?php foreach ($operating['outflows']['categories'] as $category): ?>
                                        <tr>
                                            <td>
                                                <span class="account-code"><?php echo htmlspecialchars($category['code']); ?></span>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </td>
                                            <td class="text-end outflow">₱<?php echo number_format($category['amount'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-light">
                                            <td>Total Operating Outflows</td>
                                            <td class="text-end outflow">₱<?php echo number_format($operating['outflows']['total'], 2); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <!-- Net Operating Cash Flow -->
                                <table class="table table-sm">
                                    <tr class="table-primary">
                                        <th>Net Cash from Operating Activities</th>
                                        <th class="text-end net-amount">₱<?php echo number_format($operating['net'], 2); ?></th>
                                    </tr>
                                </table>
                            </div>

                            <!-- Investing Activities -->
                            <div class="statement-section">
                                <h3 class="section-title">Investing Activities</h3>
                                
                                <!-- Inflows -->
                                <div class="flow-category">
                                    <h6 class="flow-type">Cash Inflows:</h6>
                                    <table class="table table-sm">
                                        <?php foreach ($investing['inflows']['categories'] as $category): ?>
                                        <tr>
                                            <td>
                                                <span class="account-code"><?php echo htmlspecialchars($category['code']); ?></span>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </td>
                                            <td class="text-end inflow">₱<?php echo number_format($category['amount'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-light">
                                            <td>Total Investing Inflows</td>
                                            <td class="text-end inflow">₱<?php echo number_format($investing['inflows']['total'], 2); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <!-- Outflows -->
                                <div class="flow-category">
                                    <h6 class="flow-type">Cash Outflows:</h6>
                                    <table class="table table-sm">
                                        <?php foreach ($investing['outflows']['categories'] as $category): ?>
                                        <tr>
                                            <td>
                                                <span class="account-code"><?php echo htmlspecialchars($category['code']); ?></span>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </td>
                                            <td class="text-end outflow">₱<?php echo number_format($category['amount'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-light">
                                            <td>Total Investing Outflows</td>
                                            <td class="text-end outflow">₱<?php echo number_format($investing['outflows']['total'], 2); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <!-- Net Investing Cash Flow -->
                                <table class="table table-sm">
                                    <tr class="table-info">
                                        <th>Net Cash from Investing Activities</th>
                                        <th class="text-end net-amount">₱<?php echo number_format($investing['net'], 2); ?></th>
                                    </tr>
                                </table>
                            </div>

                            <!-- Financing Activities -->
                            <div class="statement-section">
                                <h3 class="section-title">Financing Activities</h3>
                                
                                <!-- Inflows -->
                                <div class="flow-category">
                                    <h6 class="flow-type">Cash Inflows:</h6>
                                    <table class="table table-sm">
                                        <?php foreach ($financing['inflows']['categories'] as $category): ?>
                                        <tr>
                                            <td>
                                                <span class="account-code"><?php echo htmlspecialchars($category['code']); ?></span>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </td>
                                            <td class="text-end inflow">₱<?php echo number_format($category['amount'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-light">
                                            <td>Total Financing Inflows</td>
                                            <td class="text-end inflow">₱<?php echo number_format($financing['inflows']['total'], 2); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <!-- Outflows -->
                                <div class="flow-category">
                                    <h6 class="flow-type">Cash Outflows:</h6>
                                    <table class="table table-sm">
                                        <?php foreach ($financing['outflows']['categories'] as $category): ?>
                                        <tr>
                                            <td>
                                                <span class="account-code"><?php echo htmlspecialchars($category['code']); ?></span>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </td>
                                            <td class="text-end outflow">₱<?php echo number_format($category['amount'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-light">
                                            <td>Total Financing Outflows</td>
                                            <td class="text-end outflow">₱<?php echo number_format($financing['outflows']['total'], 2); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <!-- Net Financing Cash Flow -->
                                <table class="table table-sm">
                                    <tr class="table-warning">
                                        <th>Net Cash from Financing Activities</th>
                                        <th class="text-end net-amount">₱<?php echo number_format($financing['net'], 2); ?></th>
                                    </tr>
                                </table>
                            </div>

                            <!-- Net Change in Cash -->
                            <div class="total-section">
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <td><strong>Net Change in Cash</strong></td>
                                        <td class="text-end net-amount">₱<?php echo number_format($netCashChange, 2); ?></td>
                                    </tr>
                                    <tr class="table-success">
                                        <th>Cash and Cash Equivalents, Ending</th>
                                        <th class="text-end">₱<?php echo number_format($closingCash, 2); ?></th>
                                    </tr>
                                </table>
                            </div>

                            <!-- Analysis Section -->
                            <div class="analysis-section">
                                <h6 class="mb-3"><i class="bi bi-graph-up"></i> Cash Flow Analysis</h6>
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
        window.location.href = `report-cash-flow.php?period=${period}`;
    }
    </script>
</body>
</html>