<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['student']);

$pageTitle = 'My Attendance';
$activeMenu = 'attendance.php';
$student_id = get_student_id($conn);
$month = (int)($_GET['month'] ?? date('n'));
$year = (int)($_GET['year'] ?? date('Y'));
$from = date('Y-m-01', strtotime("$year-$month-01")); $to = date('Y-m-t', strtotime("$year-$month-01"));

$pct = attendance_percent($conn, $student_id, $from, $to);
$rows = $conn->query("SELECT * FROM student_attendance WHERE student_id=$student_id AND att_date BETWEEN '$from' AND '$to' ORDER BY att_date DESC");
$counts = ['Present'=>0,'Absent'=>0,'Late'=>0,'Leave'=>0];
$list = [];
while ($r = $rows->fetch_assoc()) { $counts[$r['status']]++; $list[] = $r; }

include '../includes/header.php';
?>
<div class="page-head">
    <h2>My Attendance</h2>
    <form method="get" style="display:flex;gap:8px;">
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
    <div class="card-title"><?= month_name($month) ?> <?= $year ?> &ndash; Daily Record</div>
    <table class="tbl">
        <tr><th>Date</th><th>Status</th></tr>
        <?php if (empty($list)): ?><tr><td colspan="2" class="text-center muted">No attendance records for this month.</td></tr>
        <?php else: foreach ($list as $r):
            $cls = ['Present'=>'badge-green','Absent'=>'badge-red','Late'=>'badge-amber','Leave'=>'badge-blue'][$r['status']]; ?>
            <tr><td><?= fmt_date($r['att_date']) ?></td><td><span class="badge <?= $cls ?>"><?= e($r['status']) ?></span></td></tr>
        <?php endforeach; endif; ?>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
