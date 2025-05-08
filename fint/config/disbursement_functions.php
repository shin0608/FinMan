<?php
require_once 'functions.php';

function generateNextVoucherNumber() {
    $conn = getConnection();
    try {
        // Get the current year and month
        $currentYear = date('Y');
        $currentMonth = date('m');
        
        // Find the last voucher number for the current year/month
        $sql = "SELECT voucher_number 
                FROM disbursements 
                WHERE YEAR(created_at) = ? 
                AND MONTH(created_at) = ?
                ORDER BY id DESC LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $currentYear, $currentMonth);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $lastNumber = intval(substr($row['voucher_number'], -4));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        // Format: DV-YYYYMM-####
        return sprintf("DV-%s%s-%04d", $currentYear, $currentMonth, $nextNumber);
    } catch (Exception $e) {
        error_log("Error generating voucher number: " . $e->getMessage());
        return "DV-" . date('Ym') . "-0001";
    } finally {
        closeConnection($conn);
    }
}


if (!function_exists('getRecentDisbursements')) {
    function getRecentDisbursements($limit = 10) {
        $conn = getConnection();
        try {
            $sql = "SELECT 
                d.id,
                d.voucher_number,
                d.disbursement_date,
                d.payee,
                d.amount,
                COALESCE(d.status, 'Pending') as status,
                COALESCE(u.username, 'System') as created_by_user
            FROM disbursements d
            LEFT JOIN users u ON d.created_by = u.id
            ORDER BY d.disbursement_date DESC, d.id DESC 
            LIMIT ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $disbursements = [];
            while ($row = $result->fetch_assoc()) {
                $disbursements[] = $row;
            }
            
            return $disbursements;
        } catch (Exception $e) {
            error_log("Error in getRecentDisbursements: " . $e->getMessage());
            return [];
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('getDisbursements')) {
    function getDisbursements($filters = [], $page = 1, $limit = 10) {
        $conn = getConnection();
        try {
            $offset = ($page - 1) * $limit;
            $whereConditions = [];
            $params = [];
            $types = "";

            // Base query
            $sql = "SELECT 
                    d.id,
                    d.voucher_number,
                    d.disbursement_date,
                    d.payee,
                    d.amount,
                    d.status,
                    d.void_reason,
                    d.created_at,
                    d.updated_at,
                    u.username as created_by_username
                FROM disbursements d
                LEFT JOIN users u ON d.created_by = u.id";

            // Add filters
            if (!empty($filters['start_date'])) {
                $whereConditions[] = "d.disbursement_date >= ?";
                $params[] = $filters['start_date'];
                $types .= "s";
            }
            
            if (!empty($filters['end_date'])) {
                $whereConditions[] = "d.disbursement_date <= ?";
                $params[] = $filters['end_date'];
                $types .= "s";
            }
            
            if (!empty($filters['status'])) {
                $whereConditions[] = "d.status = ?";
                $params[] = $filters['status'];
                $types .= "s";
            }
            
            if (!empty($filters['payee'])) {
                $whereConditions[] = "d.payee LIKE ?";
                $params[] = "%" . $filters['payee'] . "%";
                $types .= "s";
            }

            if (!empty($filters['voucher_number'])) {
                $whereConditions[] = "d.voucher_number LIKE ?";
                $params[] = "%" . $filters['voucher_number'] . "%";
                $types .= "s";
            }

            // Add WHERE clause if conditions exist
            if (!empty($whereConditions)) {
                $sql .= " WHERE " . implode(" AND ", $whereConditions);
            }

            // Add ordering
            $sql .= " ORDER BY d.disbursement_date DESC, d.id DESC";

            // Add pagination
            $sql .= " LIMIT ?, ?";
            $params[] = $offset;
            $params[] = $limit;
            $types .= "ii";

            // Prepare and execute the query
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            
            $result = $stmt->get_result();
            $disbursements = [];
            
            while ($row = $result->fetch_assoc()) {
                $disbursements[] = $row;
            }

            // Get total count for pagination
            $countSql = "SELECT COUNT(*) as total FROM disbursements d";
            if (!empty($whereConditions)) {
                $countSql .= " WHERE " . implode(" AND ", $whereConditions);
            }
            
            $countStmt = $conn->prepare($countSql);
            if (!empty($params)) {
                // Remove the last two parameters (offset and limit) for the count query
                array_pop($params);
                array_pop($params);
                if (!empty($params)) {
                    $countStmt->bind_param(substr($types, 0, -2), ...$params);
                }
            }
            $countStmt->execute();
            $totalRows = $countStmt->get_result()->fetch_assoc()['total'];

            return [
                'data' => $disbursements,
                'total' => $totalRows,
                'pages' => ceil($totalRows / $limit)
            ];

        } catch (Exception $e) {
            error_log("Error in getDisbursements: " . $e->getMessage());
            throw $e;
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('getDisbursementDetails')) {
    function getDisbursementDetails($id) {
        $conn = getConnection();
        try {
            $sql = "SELECT 
                    d.*,
                    u.username as created_by_username
                FROM disbursements d
                LEFT JOIN users u ON d.created_by = u.id
                WHERE d.id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return null;
            }
            
            return $result->fetch_assoc();
        } finally {
            closeConnection($conn);
        }
    }
}

function createDisbursement($data) {
    $conn = getConnection();
    try {
        $sql = "INSERT INTO disbursements (
                    voucher_number,
                    disbursement_date,
                    payee,
                    amount,
                    description,
                    status,
                    created_by
                ) VALUES (?, ?, ?, ?, ?, 'Pending', ?)";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'sssdsi',
            $data['voucher_number'],
            $data['disbursement_date'],
            $data['payee'],
            $data['amount'],
            $data['description'],
            $data['created_by']
        );
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Disbursement created successfully'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to create disbursement'
        ];
    } catch (Exception $e) {
        error_log("Error in createDisbursement: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error creating disbursement: ' . $e->getMessage()
        ];
    } finally {
        closeConnection($conn);
    }
}

function approveDisbursement($id, $admin_id) {
    $conn = getConnection();
    try {
        // Check if user is admin
        if (!isAdmin($admin_id)) {
            return [
                'success' => false,
                'message' => 'Only administrators can approve disbursements'
            ];
        }

        // Check current status
        $check_sql = "SELECT status FROM disbursements WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('i', $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $disbursement = $result->fetch_assoc();

        if (!$disbursement || $disbursement['status'] !== 'Pending') {
            return [
                'success' => false,
                'message' => 'Only pending disbursements can be approved'
            ];
        }

        $sql = "UPDATE disbursements 
                SET status = 'Completed', 
                    approved_by = ?, 
                    approved_at = CURRENT_TIMESTAMP 
                WHERE id = ? AND status = 'Pending'";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $admin_id, $id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            return [
                'success' => true,
                'message' => 'Disbursement approved successfully'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to approve disbursement'
        ];
    } catch (Exception $e) {
        error_log("Error in approveDisbursement: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    } finally {
        closeConnection($conn);
    }
}

function voidDisbursement($id, $admin_id, $reason) {
    $conn = getConnection();
    try {
        // Check if user is admin
        if (!isAdmin($admin_id)) {
            return [
                'success' => false,
                'message' => 'Only administrators can void disbursements'
            ];
        }

        // Check current status
        $check_sql = "SELECT status FROM disbursements WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('i', $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $disbursement = $result->fetch_assoc();

        if (!$disbursement || $disbursement['status'] !== 'Pending') {
            return [
                'success' => false,
                'message' => 'Only pending disbursements can be voided'
            ];
        }

        $sql = "UPDATE disbursements 
                SET status = 'Voided', 
                    void_reason = ?, 
                    approved_by = ?, 
                    approved_at = CURRENT_TIMESTAMP 
                WHERE id = ? AND status = 'Pending'";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sii', $reason, $admin_id, $id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            return [
                'success' => true,
                'message' => 'Disbursement voided successfully'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to void disbursement'
        ];
    } catch (Exception $e) {
        error_log("Error in voidDisbursement: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    } finally {
        closeConnection($conn);
    }
}

function getStatusColor($status) {
    switch ($status) {
        case 'Completed':
            return 'success';
        case 'Pending':
            return 'warning';
        case 'Voided':
            return 'danger';
        default:
            return 'secondary';
    }
}

function getDisbursementById($id) {
    $conn = getConnection();
    try {
        $sql = "SELECT 
                d.*,
                creator.username as created_by_name,
                approver.username as approver_name
            FROM disbursements d
            LEFT JOIN users creator ON d.created_by = creator.id
            LEFT JOIN users approver ON d.approved_by = approver.id
            WHERE d.id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error in getDisbursementById: " . $e->getMessage());
        return null;
    } finally {
        closeConnection($conn);
    }
}