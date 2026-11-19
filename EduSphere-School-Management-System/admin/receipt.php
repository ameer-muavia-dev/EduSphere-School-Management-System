<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['admin', 'accountant', 'parent']);

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT v.*, s.roll_no, u.name, c.name cname, c.section
    FROM fee_vouchers v JOIN students s ON v.student_id=s.id JOIN users u ON s.user_id=u.id JOIN classes c ON s.class_id=c.id
    WHERE v.id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$v = $stmt->get_result()->fetch_assoc();
if (!$v) { stop_page('Voucher Not Found', 'This fee voucher does not exist.'); }

// If a parent is viewing, make sure this voucher belongs to their child
if (current_role() === 'parent') {
    $chk = $conn->prepare('SELECT id FROM students WHERE id=? AND parent_user_id=?');
    $chk->bind_param('ii', $v['student_id'], $_SESSION['user_id']);
    $chk->execute();
    if ($chk->get_result()->num_rows === 0) { stop_page('Access Denied', 'You are not authorized to view this receipt.', 403); }
}

$payments = $conn->query("SELECT * FROM fee_payments WHERE voucher_id=$id ORDER BY paid_date");
$payable = $v['amount'] - $v['discount'];
$balance = $payable - $v['paid_amount'];
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Fee Receipt</title>
<link rel="stylesheet" href="<?= root() ?>assets/css/style.css">
<style>
body{background:#fff;padding:30px;} .box{max-width:620px;margin:auto;border:1px solid var(--border);border-radius:12px;padding:30px;}
.hd{display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid var(--navy);padding-bottom:14px;margin-bottom:20px;}
.hd h2{color:var(--navy);margin:0;}
.row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed var(--border);font-size:14px;}
.total{font-size:18px;font-weight:800;color:var(--navy);}
</style></head>
<body>
<div class="box">
    <div class="hd">
        <div><h2>EduSphere</h2><div class="muted" style="font-size:12px;">University of Layyah &middot; Fee Receipt</div></div>
        <div class="text-right"><b>Voucher #<?= $v['id'] ?></b><br><span class="muted"><?= month_name($v['month']).' '.$v['year'] ?></span></div>
    </div>
    <div class="row"><span>Student</span><b><?= e($v['name']) ?> (<?= e($v['roll_no']) ?>)</b></div>
    <div class="row"><span>Class</span><b><?= e($v['cname'].'-'.$v['section']) ?></b></div>
    <div class="row"><span>Due Date</span><b><?= fmt_date($v['due_date']) ?></b></div>
    <div class="row"><span>Fee Amount</span><b><?= fmt_money($v['amount']) ?></b></div>
    <div class="row"><span>Discount</span><b><?= fmt_money($v['discount']) ?></b></div>
    <div class="row"><span>Payable</span><b><?= fmt_money($payable) ?></b></div>
    <div class="row"><span>Paid</span><b style="color:#2C6E1F;"><?= fmt_money($v['paid_amount']) ?></b></div>
    <div class="row total"><span>Balance</span><span><?= fmt_money($balance) ?></span></div>

    <h4 style="margin-top:20px;color:var(--navy);">Payment History</h4>
    <table class="tbl">
        <tr><th>Date</th><th>Amount</th><th>Method</th></tr>
        <?php if ($payments->num_rows === 0): ?><tr><td colspan="3" class="muted text-center">No payments recorded yet.</td></tr>
        <?php else: while ($p = $payments->fetch_assoc()): ?>
            <tr><td><?= fmt_date($p['paid_date']) ?></td><td><?= fmt_money($p['amount_paid']) ?></td><td><?= e($p['method']) ?></td></tr>
        <?php endwhile; endif; ?>
    </table>
    <p class="muted no-print" style="margin-top:20px;font-size:12px;">This is a computer-generated receipt from EduSphere.</p>
    <button class="btn btn-primary no-print" onclick="window.print()" style="margin-top:10px;">Print</button>
</div>
</body></html>
