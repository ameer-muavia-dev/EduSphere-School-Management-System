<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['parent']);

$pageTitle = 'Monthly Report';
$activeMenu = 'reports.php';
$childrenArr = get_parent_children($conn);
if (empty($childrenArr)) { include '../includes/header.php'; echo '<div class="card"><p class="muted">No student linked to your account.</p></div>'; include '../includes/footer.php'; exit; }

include '../includes/header.php';
?>
<div class="page-head"><h2>Monthly Overall Education Report</h2></div>
<div class="card" style="max-width:600px;">
    <p class="muted" style="margin-bottom:16px;">Select your child and the month to view a complete report covering attendance, test performance, assignments, fee status and teacher remarks.</p>
    <form method="get" action="../admin/student_report.php" style="display:flex;gap:8px;flex-wrap:wrap;">
        <select class="form-control" name="id" style="max-width:220px;">
            <?php foreach ($childrenArr as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name'].' - '.$c['cname'].'-'.$c['section']) ?></option><?php endforeach; ?>
        </select>
        <select class="form-control" name="month" style="max-width:150px;">
            <?php for ($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $m==date('n')?'selected':'' ?>><?= month_name($m) ?></option><?php endfor; ?>
        </select>
        <input class="form-control" type="number" name="year" value="<?= date('Y') ?>" style="max-width:100px;">
        <button class="btn btn-primary btn-sm">View Report</button>
    </form>
</div>
<?php include '../includes/footer.php'; ?>
