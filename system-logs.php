<?php
session_start();
require_once 'config/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get filter parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$searchDate = isset($_GET['search_date']) ? $_GET['search_date'] : '';
$searchUser = isset($_GET['search_user']) ? $_GET['search_user'] : '';
$limit = 20;
$offset = ($page - 1) * $limit;

function getSystemLogs($offset, $limit, $searchDate = '', $searchUser = '') {
    $conn = getConnection();
    try {
        $sql = "SELECT l.*, u.name as user_name, u.username 
                FROM system_logs l 
                LEFT JOIN users u ON l.user_id = u.id 
                WHERE 1=1";
        $params = [];
        $types = "";
        
        if ($searchDate) {
            $sql .= " AND DATE(l.created_at) = ?";
            $params[] = $searchDate;
            $types .= "s";
        }
        
        if ($searchUser) {
            $sql .= " AND (u.name LIKE ? OR u.username LIKE ?)";
            $searchTerm = "%$searchUser%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ss";
        }
        
        $sql .= " ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } finally {
        $conn->close();
    }
}

$logs = getSystemLogs($offset, $limit, $searchDate, $searchUser);
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
                    <h1 class="h2">System Logs</h1>
                </div>

                <!-- Search Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Date</label>
                                <input type="date" class="form-control" name="search_date" value="<?php echo htmlspecialchars($searchDate); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">User</label>
                                <input type="text" class="form-control" name="search_user" 
                                       value="<?php echo htmlspecialchars($searchUser); ?>" 
                                       placeholder="Search by name or username...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Search
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Logs Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($log['user_name'] . ' (' . $log['username'] . ')'); ?></td>
                                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                                        <td><?php echo htmlspecialchars($log['details']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <?php
                $totalLogs = getTotalSystemLogs($searchDate, $searchUser);
                $totalPages = ceil($totalLogs / $limit);
                if ($totalPages > 1):
                ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search_date=<?php echo urlencode($searchDate); ?>&search_user=<?php echo urlencode($searchUser); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>