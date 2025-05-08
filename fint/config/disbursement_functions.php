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

function getDisbursements($status, $startDate, $endDate, $search) {
    $conn = getConnection();
    try {
        $sql = "SELECT 
                d.id,
                d.voucher_number,
                d.disbursement_date,
                d.payee,
                d.amount,
                d.description,
                d.status,
                d.created_at,
                d.void_reason,
                d.approved_by,
                d.approved_at,
                creator.username as created_by_name,
                approver.username as approver_name
            FROM disbursements d
            LEFT JOIN users creator ON d.created_by = creator.id
            LEFT JOIN users approver ON d.approved_by = approver.id
            WHERE 1=1";
        
        $params = [];
        $types = '';

        if ($status && $status !== 'All') {
            $sql .= " AND d.status = ?";
            $params[] = $status;
            $types .= 's';
        }

        if ($startDate) {
            $sql .= " AND DATE(d.disbursement_date) >= ?";
            $params[] = $startDate;
            $types .= 's';
        }

        if ($endDate) {
            $sql .= " AND DATE(d.disbursement_date) <= ?";
            $params[] = $endDate;
            $types .= 's';
        }

        if ($search) {
            $sql .= " AND (d.voucher_number LIKE ? OR d.payee LIKE ? OR d.description LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }

        $sql .= " ORDER BY d.created_at DESC";
        
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
        
        return $disbursements;

    } catch (Exception $e) {
        error_log("Error in getDisbursements: " . $e->getMessage());
        throw $e;
    } finally {
        if ($conn) {
            $conn->close();
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