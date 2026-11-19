<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['parent']);

$pageTitle = 'Fee Status';
$activeMenu = 'fees.php';
$childrenArr = get_parent_children($conn);
if (empty($childrenArr)) { include '../includes/header.php'; echo '<div class="card"><p class="muted">No student linked to your account.</p></div>'; include '../includes/footer.php'; exit; }
$childId = (int)($_GET['child_id'] ?? $childrenArr[0]['id']);
verify_parent_child($conn, $childId, $childrenArr);

$vouchers = $conn->query("SELECT * FROM fee_vouchers WHERE student_id=$childId ORDER BY year DESC, month DESC");

include '../includes/header.php';
?>
<div class="page-head">
    <h2>Fee Status</h2>
    <?php if (count($childrenArr) > 1): ?>
    <form method="get"><select class="form-control" name="child_id" onchange="this.form.submit()">
        <?php foreach ($childrenArr as $c): ?><option value="<?= $c['id'] ?>" <?= $childId==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
    </select></form>
    <?php endif; ?>
</div>
<div class="card">
    <table class="tbl">
        <tr><th>Month</th><th>Amount</th><th>Discount</th><th>Paid</th><th>Status</th><th></th></tr>
        <?php if ($vouchers->num_rows === 0): ?><tr><td colspan="6" class="text-center muted">No fee vouchers yet.</td></tr>
        <?php else: while ($v = $vouchers->fetch_assoc()):
            $badge = ['Paid'=>'badge-green','Partial'=>'badge-amber','Unpaid'=>'badge-red'][$v['status']]; ?>
            <tr>
                <td><?= month_name($v['month']).' '.$v['year'] ?></td><td><?= fmt_money($v['amount']) ?></td>
                <td><?= fmt_money($v['discount']) ?></td><td><?= fmt_money($v['paid_amount']) ?></td>
                <td><span class="badge <?= $badge ?>"><?= e($v['status']) ?></span></td>
                <td><a href="../admin/receipt.php?id=<?= $v['id'] ?>" target="_blank" class="btn btn-outline btn-sm">View Receipt</a></td>
            </tr>
        <?php endwhile; endif; ?>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
