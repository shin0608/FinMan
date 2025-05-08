<?php
session_start();

// Check if user is logged in and is admin

// Include necessary files
require_once 'config/functions.php';

// Get filter parameters
$username = $_GET['username'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$limit = $_GET['limit'] ?? 50; // Default to 50 records

// Function to get filtered access logs
function getFilteredAccessLogs($username = '', $startDate = '', $endDate = '', $limit = 50) {
    $conn = getConnection();
    
    $sql = "SELECT 
                l.id,
                l.user_id,
                u.username,
                u.full_name,
                l.login_time,
                l.logout_time,
                l.ip_address,
                l.user_agent
            FROM 
                user_access_logs l
                JOIN users u ON l.user_id = u.id
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    // Add username filter
    if (!empty($username)) {
        $sql .= " AND (u.username LIKE ? OR u.full_name LIKE ?)";
        $searchTerm = "%$username%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }
    
    // Add date range filter
    if (!empty($startDate)) {
        $sql .= " AND DATE(l.login_time) >= ?";
        $params[] = $startDate;
        $types .= "s";
    }
    
    if (!empty($endDate)) {
        $sql .= " AND DATE(l.login_time) <= ?";
        $params[] = $endDate;
        $types .= "s";
    }
    
    $sql .= " ORDER BY l.login_time DESC LIMIT ?";
    $params[] = (int)$limit;
    $types .= "i";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $logs = [];
    
    while($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    closeConnection($conn);
    return $logs;
}

// Get filtered access logs
$accessLogs = getFilteredAccessLogs($username, $startDate, $endDate, $limit);

// Set page title
$pageTitle = "System Logs";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .current-info-bar {
            font-family: 'Courier New', Courier, monospace;
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid #0d6efd;
        }
        .table th { 
            background-color: #f8f9fa;
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
                    <h1 class="h2">System Logs</h1>
                </div>

                <!-- Filter Section -->
                <div class="card filter-section mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Username or Full Name</label>
                                <input type="text" class="form-control" name="username" 
                                       value="<?php echo htmlspecialchars($username); ?>" 
                                       placeholder="Search user...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" 
                                       value="<?php echo htmlspecialchars($startDate); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date" 
                                       value="<?php echo htmlspecialchars($endDate); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Limit Results</label>
                                <select class="form-select" name="limit">
                                    <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50 entries</option>
                                    <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100 entries</option>
                                    <option value="200" <?php echo $limit == 200 ? 'selected' : ''; ?>>200 entries</option>
                                    <option value="500" <?php echo $limit == 500 ? 'selected' : ''; ?>>500 entries</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Search
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                                        <i class="bi bi-x-circle"></i> Clear
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Results Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Login Time</th>
                                        <th>Logout Time</th>
                                        <th>IP Address</th>
                                        <th>Browser</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($accessLogs)): ?>
                                        <?php foreach ($accessLogs as $log): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($log['full_name']) . ' (' . htmlspecialchars($log['username']) . ')'; ?></td>
                                            <td><?php echo date('Y-m-d H:i:s', strtotime($log['login_time'])); ?></td>
                                            <td><?php echo $log['logout_time'] ? date('Y-m-d H:i:s', strtotime($log['logout_time'])) : 'Active/No Logout'; ?></td>
                                            <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($log['user_agent'], 0, 50)) . (strlen($log['user_agent']) > 50 ? '...' : ''); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No access logs found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>

    <script>
    function clearFilters() {
        window.location.href = 'system-logs.php';
    }

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

    // Initialize datetime display
    updateDateTime();
    setInterval(updateDateTime, 1000);
    </script>
</body>
</html>