<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['parent']);

$pageTitle = 'Attendance Report';
$activeMenu = 'attendance.php';
$childrenArr = get_parent_children($conn);
if (empty($childrenArr)) { include '../includes/header.php'; echo '<div class="card"><p class="muted">No student linked to your account.</p></div>'; include '../includes/footer.php'; exit; }
$childId = (int)($_GET['child_id'] ?? $childrenArr[0]['id']);
verify_parent_child($conn, $childId, $childrenArr);

$month = (int)($_GET['month'] ?? date('n'));
$year = (int)($_GET['year'] ?? date('Y'));
$from = date('Y-m-01', strtotime("$year-$month-01")); $to = date('Y-m-t', strtotime("$year-$month-01"));
$pct = attendance_percent($conn, $childId, $from, $to);
$rows = $conn->query("SELECT * FROM student_attendance WHERE student_id=$childId AND att_date BETWEEN '$from' AND '$to' ORDER BY att_date DESC");
$counts = ['Present'=>0,'Absent'=>0,'Late'=>0,'Leave'=>0]; $list = [];
while ($r = $rows->fetch_assoc()) { $counts[$r['status']]++; $list[] = $r; }

include '../includes/header.php';
?>
<div class="page-head">
    <h2>Attendance Report</h2>
    <form method="get" style="display:flex;gap:8px;">
        <?php if (count($childrenArr) > 1): ?>
        <select class="form-control btn-sm" name="child_id" onchange="this.form.submit()">
            <?php foreach ($childrenArr as $c): ?><option value="<?= $c['id'] ?>" <?= $childId==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
        <?php endif; ?>
        <select class="form-control btn-sm" name="month" onchange="this.form.submit()"><?php for ($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $m==$month?'selected':'' ?>><?= month_name($m) ?></option><?php endfor; ?></select>
        <input class="form-control btn-sm" type="number" name="year" value="<?= $year ?>" style="max-width:90px;" onchange="this.form.submit()">
    </form>
</div>
<div class="stat-grid">
    <div class="stat-card"><div class="stat-icon" style="background:#1F8A8A;">%</div><div><div class="stat-num"><?= $pct !== null ? $pct.'%' : '-' ?></div><div class="stat-label">Monthly Attendance</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#2C6E1F;">&#10003;</div><div><div class="stat-num"><?= $counts['Present'] ?></div><div class="stat-label">Present</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#C0392B;">&times;</div><div><div class="stat-num"><?= $counts['Absent'] ?></div><div class="stat-label">Absent</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#B7791F;">&#9203;</div><div><div class="stat-num"><?= $counts['Late'] ?></div><div class="stat-label">Late</div></div></div>
</div>
<div class="card">
    <table class="tbl">
        <tr><th>Date</th><th>Status</th></tr>
        <?php if (empty($list)): ?><tr><td colspan="2" class="text-center muted">No records for this month.</td></tr>
        <?php else: foreach ($list as $r): $cls = ['Present'=>'badge-green','Absent'=>'badge-red','Late'=>'badge-amber','Leave'=>'badge-blue'][$r['status']]; ?>
            <tr><td><?= fmt_date($r['att_date']) ?></td><td><span class="badge <?= $cls ?>"><?= e($r['status']) ?></span></td></tr>
        <?php endforeach; endif; ?>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
