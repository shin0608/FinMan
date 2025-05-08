<?php
session_start();
require_once 'config/functions.php';
require_once 'config/disbursement_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get dashboard data
try {
    $totalAssets = getTotalAssets();
    $totalLiabilities = getTotalLiabilities();
    $totalEquity = getTotalEquity();
    $netIncome = getNetIncome();
} catch (Exception $e) {
    error_log("Error loading dashboard data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .dashboard-card {
            border-radius: 10px;
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .card-assets { border-left-color: #28a745; }
        .card-liabilities { border-left-color: #dc3545; }
        .card-equity { border-left-color: #17a2b8; }
        .card-income { border-left-color: #ffc107; }
        .system-info-bar {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 4px;
            border-left: 4px solid #0d6efd;
            font-family: 'Courier New', monospace;
            margin-bottom: 20px;
        }
        .system-info-bar i {
            margin-right: 5px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- System Info Bar -->
               
            

                <!-- Dashboard Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card dashboard-card card-assets h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="text-muted mb-2">Total Assets</h6>
                                        <h4 class="mb-0">₱<?php echo number_format($totalAssets, 2); ?></h4>
                                    </div>
                                    <div class="ms-3">
                                        <i class="bi bi-bank fs-2 text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card dashboard-card card-liabilities h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="text-muted mb-2">Total Liabilities</h6>
                                        <h4 class="mb-0">₱<?php echo number_format($totalLiabilities, 2); ?></h4>
                                    </div>
                                    <div class="ms-3">
                                        <i class="bi bi-cash-stack fs-2 text-danger"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card dashboard-card card-equity h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="text-muted mb-2">Total Equity</h6>
                                        <h4 class="mb-0">₱<?php echo number_format($totalEquity, 2); ?></h4>
                                    </div>
                                    <div class="ms-3">
                                        <i class="bi bi-pie-chart fs-2 text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card dashboard-card card-income h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="text-muted mb-2">Net Income</h6>
                                        <h4 class="mb-0">₱<?php echo number_format($netIncome, 2); ?></h4>
                                    </div>
                                    <div class="ms-3">
                                        <i class="bi bi-graph-up-arrow fs-2 text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    // Update datetime display
                    function updateDateTime() {
                        const now = new Date();
                        const formatted = now.getUTCFullYear() + '-' + 
                                        String(now.getUTCMonth() + 1).padStart(2, '0') + '-' + 
                                        String(now.getUTCDate()).padStart(2, '0') + ' ' + 
                                        String(now.getUTCHours()).padStart(2, '0') + ':' + 
                                        String(now.getUTCMinutes()).padStart(2, '0') + ':' + 
                                        String(now.getUTCSeconds()).padStart(2, '0');
                        document.getElementById('currentDateTime').textContent = formatted;
                    }

                    // Initialize datetime display and update every second
                    updateDateTime();
                    setInterval(updateDateTime, 1000);
                </script>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>