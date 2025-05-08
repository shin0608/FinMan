<?php
session_start();
require_once 'config/functions.php';
require_once 'config/disbursement_functions.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    echo "Unauthorized access or invalid request";
    exit();
}

$disbursement = getDisbursementById($_GET['id']);

if (!$disbursement) {
    echo "Disbursement not found";
    exit();
}
?>

<div class="table-responsive">
    <table class="table table-bordered">
        <tr>
            <th width="200">Voucher Number</th>
            <td><?php echo htmlspecialchars($disbursement['voucher_number']); ?></td>
        </tr>
        <tr>
            <th>Date</th>
            <td><?php echo date('Y-m-d', strtotime($disbursement['disbursement_date'])); ?></td>
        </tr>
        <tr>
            <th>Payee</th>
            <td><?php echo htmlspecialchars($disbursement['payee']); ?></td>
        </tr>
        <tr>
            <th>Amount</th>
            <td>₱<?php echo number_format($disbursement['amount'], 2); ?></td>
        </tr>
        <tr>
            <th>Description</th>
            <td><?php echo nl2br(htmlspecialchars($disbursement['description'] ?? '')); ?></td>
        </tr>
        <tr>
            <th>Status</th>
            <td>
                <span class="badge bg-<?php echo getStatusColor($disbursement['status']); ?>">
                    <?php echo htmlspecialchars($disbursement['status']); ?>
                </span>
            </td>
        </tr>
        <tr>
            <th>Created By</th>
            <td>
                <?php echo htmlspecialchars($disbursement['created_by_name'] ?? 'System'); ?>
                on <?php echo date('Y-m-d H:i:s', strtotime($disbursement['created_at'])); ?>
            </td>
        </tr>
        <?php if ($disbursement['approved_by']): ?>
        <tr>
            <th><?php echo $disbursement['status'] === 'Voided' ? 'Voided' : 'Approved'; ?> By</th>
            <td>
                <?php echo htmlspecialchars($disbursement['approver_name']); ?>
                on <?php echo date('Y-m-d H:i:s', strtotime($disbursement['approved_at'])); ?>
            </td>
        </tr>
        <?php endif; ?>
        <?php if ($disbursement['void_reason']): ?>
        <tr>
            <th>Void Reason</th>
            <td><?php echo nl2br(htmlspecialchars($disbursement['void_reason'])); ?></td>
        </tr>
        <?php endif; ?>
    </table>
</div>