<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['accountant']);

$pageTitle = 'Accountant Dashboard';
$activeMenu = 'dashboard.php';
$month = date('n'); $year = date('Y');

$feeRow = $conn->query("SELECT SUM(paid_amount) paid, SUM(amount-discount) due, COUNT(*) c FROM fee_vouchers WHERE month=$month AND year=$year")->fetch_assoc();
$collected = $feeRow['paid'] ?? 0;
$pending = ($feeRow['due'] ?? 0) - $collected;
$unpaidCount = $conn->query("SELECT COUNT(*) c FROM fee_vouchers WHERE month=$month AND year=$year AND status!='Paid'")->fetch_assoc()['c'];
$payrollRow = $conn->query("SELECT SUM(net_salary) total, SUM(status='Paid') paid_c, COUNT(*) c FROM payroll WHERE month=$month AND year=$year")->fetch_assoc();

include '../includes/header.php';
?>
<div class="stat-grid">
    <div class="stat-card"><div class="stat-icon" style="background:#1F8A8A;">&#128176;</div><div><div class="stat-num"><?= fmt_money($collected) ?></div><div class="stat-label">Fees Collected (<?= month_name($month) ?>)</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#C0392B;">&#9888;</div><div><div class="stat-num"><?= fmt_money(max(0,$pending)) ?></div><div class="stat-label">Fees Pending</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#F2A93B;">&#128203;</div><div><div class="stat-num"><?= $unpaidCount ?></div><div class="stat-label">Unpaid/Partial Vouchers</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#2F5D8C;">&#128179;</div><div><div class="stat-num"><?= fmt_money($payrollRow['total'] ?? 0) ?></div><div class="stat-label">Payroll (<?= month_name($month) ?>)</div></div></div>
</div>
<div class="grid-2">
    <div class="card">
        <div class="card-title">Quick Links</div>
        <a href="fees.php" class="btn btn-outline btn-sm" style="margin-right:8px;">Manage Fees</a>
        <a href="vouchers.php" class="btn btn-outline btn-sm" style="margin-right:8px;">Vouchers &amp; Receipts</a>
        <a href="payroll.php" class="btn btn-outline btn-sm">Staff Payroll</a>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
