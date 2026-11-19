<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['admin']);

$pageTitle = 'Reports';
$activeMenu = 'reports.php';
$classes = $conn->query('SELECT * FROM classes ORDER BY name, section');
$classFilter = (int)($_GET['class_id'] ?? ($classes->fetch_assoc()['id'] ?? 0));
$classes->data_seek(0);
$weekStart = $_GET['week_start'] ?? date('Y-m-d', strtotime('monday this week'));
$weekEnd = date('Y-m-d', strtotime($weekStart . ' +5 days'));

$dates = [];
for ($i = 0; $i < 6; $i++) $dates[] = date('Y-m-d', strtotime($weekStart . " +$i days"));

$students = $conn->query("SELECT s.id, s.roll_no, u.name FROM students s JOIN users u ON s.user_id=u.id WHERE s.class_id=$classFilter ORDER BY s.roll_no");
$rows = [];
while ($st = $students->fetch_assoc()) {
    $att = [];
    foreach ($dates as $d) {
        $r = $conn->query("SELECT status FROM student_attendance WHERE student_id={$st['id']} AND att_date='$d'")->fetch_assoc();
        $att[$d] = $r['status'] ?? null;
    }
    $pct = attendance_percent($conn, $st['id'], $weekStart, $weekEnd);
    $rows[] = ['roll_no' => $st['roll_no'], 'name' => $st['name'], 'att' => $att, 'pct' => $pct];
}

$allStudents = $conn->query("SELECT s.id, s.roll_no, u.name, c.name cname, c.section FROM students s JOIN users u ON s.user_id=u.id JOIN classes c ON s.class_id=c.id ORDER BY c.name, s.roll_no");

include '../includes/header.php';
?>
<div class="page-head"><h2>Reports</h2></div>

<div class="card" style="margin-bottom:20px;">
    <div class="page-head" style="margin-bottom:14px;">
        <div class="card-title" style="margin:0;">Weekly Attendance Report (<?= fmt_date($weekStart) ?> &ndash; <?= fmt_date($weekEnd) ?>)</div>
        <form method="get" style="display:flex;gap:8px;">
            <select class="form-control btn-sm" name="class_id" onchange="this.form.submit()">
                <?php while ($c = $classes->fetch_assoc()): ?><option value="<?= $c['id'] ?>" <?= $classFilter==$c['id']?'selected':'' ?>><?= e($c['name'].'-'.$c['section']) ?></option><?php endwhile; ?>
            </select>
            <input class="form-control btn-sm" type="date" name="week_start" value="<?= $weekStart ?>" onchange="this.form.submit()">
            <button class="btn btn-outline btn-sm no-print" onclick="window.print();return false;">Print</button>
        </form>
    </div>
    <div class="table-wrap">
    <table class="tbl">
        <tr>
            <th>Roll No.</th><th>Name</th>
            <?php foreach ($dates as $d): ?><th><?= date('D d', strtotime($d)) ?></th><?php endforeach; ?>
            <th>Weekly %</th>
        </tr>
        <?php if (empty($rows)): ?><tr><td colspan="8" class="text-center muted">No students in this class.</td></tr>
        <?php else: foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['roll_no']) ?></td><td><?= e($r['name']) ?></td>
                <?php foreach ($dates as $d):
                    $s = $r['att'][$d]; $mark = ['Present'=>'P','Absent'=>'A','Late'=>'L','Leave'=>'Lv'][$s] ?? '-';
                    $cls = ['Present'=>'badge-green','Absent'=>'badge-red','Late'=>'badge-amber','Leave'=>'badge-blue'][$s] ?? 'badge-grey'; ?>
                    <td><span class="badge <?= $cls ?>"><?= $mark ?></span></td>
                <?php endforeach; ?>
                <td><b><?= $r['pct'] !== null ? $r['pct'].'%' : '-' ?></b></td>
            </tr>
        <?php endforeach; endif; ?>
    </table>
    </div>
</div>

<div class="card">
    <div class="card-title">Monthly Overall Education Report (per student)</div>
    <p class="muted" style="font-size:12.5px;margin-bottom:12px;">Combines attendance, test performance, assignments and fee status into one printable report.</p>
    <form method="get" action="student_report.php" style="display:flex;gap:8px;flex-wrap:wrap;">
        <select class="form-control" name="id" style="max-width:280px;" required>
            <?php while ($s = $allStudents->fetch_assoc()): ?>
                <option value="<?= $s['id'] ?>"><?= e($s['name'].' ('.$s['roll_no'].') - '.$s['cname'].'-'.$s['section']) ?></option>
            <?php endwhile; ?>
        </select>
        <select class="form-control" name="month" style="max-width:150px;">
            <?php for ($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $m==date('n')?'selected':'' ?>><?= month_name($m) ?></option><?php endfor; ?>
        </select>
        <input class="form-control" type="number" name="year" value="<?= date('Y') ?>" style="max-width:100px;">
        <button class="btn btn-primary btn-sm">Generate Report</button>
    </form>
</div>
<?php include '../includes/footer.php'; ?>
