<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['accountant', 'admin']);

$pageTitle = 'Voucher & Receipts';
$activeMenu = 'vouchers.php';
$search = trim($_GET['q'] ?? '');

$sql = "SELECT v.*, s.roll_no, u.name, c.name cname, c.section
        FROM fee_vouchers v JOIN students s ON v.student_id=s.id JOIN users u ON s.user_id=u.id JOIN classes c ON s.class_id=c.id
        WHERE 1=1";
if ($search !== '') {
    $esc = $conn->real_escape_string($search);
    $sql .= " AND (u.name LIKE '%$esc%' OR s.roll_no LIKE '%$esc%')";
}
$sql .= " ORDER BY v.year DESC, v.month DESC LIMIT 100";
$vouchers = $conn->query($sql);

include '../includes/header.php';
?>
<div class="page-head"><h2>Voucher &amp; Receipts</h2></div>
<div class="card">
    <form method="get" style="margin-bottom:16px;">
        <input class="form-control" style="max-width:300px;" name="q" placeholder="Search by student name or roll no." value="<?= e($search) ?>">
        <button class="btn btn-outline" type="submit">Search</button>
    </form>
    <div class="table-wrap">
    <table class="tbl">
        <tr><th>Voucher #</th><th>Student</th><th>Class</th><th>Month</th><th>Payable</th><th>Paid</th><th>Status</th><th></th></tr>
        <?php if ($vouchers->num_rows === 0): ?><tr><td colspan="8" class="text-center muted">No vouchers found.</td></tr>
        <?php else: while ($v = $vouchers->fetch_assoc()):
            $badge = ['Paid'=>'badge-green','Partial'=>'badge-amber','Unpaid'=>'badge-red'][$v['status']]; ?>
            <tr>
                <td>#<?= $v['id'] ?></td><td><?= e($v['name']) ?> (<?= e($v['roll_no']) ?>)</td><td><?= e($v['cname'].'-'.$v['section']) ?></td>
                <td><?= month_name($v['month']).' '.$v['year'] ?></td><td><?= fmt_money($v['amount']-$v['discount']) ?></td><td><?= fmt_money($v['paid_amount']) ?></td>
                <td><span class="badge <?= $badge ?>"><?= e($v['status']) ?></span></td>
                <td><a href="../admin/receipt.php?id=<?= $v['id'] ?>" target="_blank" class="btn btn-outline btn-sm">Print Receipt</a></td>
            </tr>
        <?php endwhile; endif; ?>
    </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
