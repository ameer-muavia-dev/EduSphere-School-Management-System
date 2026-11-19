<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['admin']);

$pageTitle = 'Attendance Overview';
$activeMenu = 'attendance.php';
$type = $_GET['type'] ?? 'student';
$date = $_GET['date'] ?? date('Y-m-d');
$classFilter = (int)($_GET['class_id'] ?? 0);
$classes = $conn->query('SELECT * FROM classes ORDER BY name, section');

include '../includes/header.php';
?>
<div class="page-head"><h2>Attendance Overview</h2></div>

<div style="display:flex;gap:10px;margin-bottom:18px;">
    <a href="?type=student&date=<?= $date ?>" class="btn <?= $type === 'student' ? 'btn-primary' : 'btn-outline' ?> btn-sm">Student Attendance</a>
    <a href="?type=staff&date=<?= $date ?>" class="btn <?= $type === 'staff' ? 'btn-primary' : 'btn-outline' ?> btn-sm">Staff Attendance</a>
</div>

<?php if ($type === 'student'): ?>
    <div class="card">
        <form method="get" style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
            <input type="hidden" name="type" value="student">
            <input class="form-control" style="max-width:180px;" type="date" name="date" value="<?= e($date) ?>" onchange="this.form.submit()">
            <select class="form-control" style="max-width:200px;" name="class_id" onchange="this.form.submit()">
                <option value="0">All Classes</option>
                <?php while ($c = $classes->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>" <?= $classFilter == $c['id'] ? 'selected' : '' ?>><?= e($c['name'].'-'.$c['section']) ?></option>
                <?php endwhile; ?>
            </select>
        </form>
        <?php
        $sql = "SELECT s.roll_no, u.name, c.name cname, c.section, a.status
                FROM students s JOIN users u ON s.user_id=u.id JOIN classes c ON s.class_id=c.id
                LEFT JOIN student_attendance a ON a.student_id=s.id AND a.att_date='" . $conn->real_escape_string($date) . "'
                WHERE 1=1";
        if ($classFilter) $sql .= " AND s.class_id=$classFilter";
        $sql .= " ORDER BY c.name, s.roll_no";
        $rows = $conn->query($sql);
        $present = 0; $absent = 0; $late = 0; $leave = 0; $unmarked = 0; $total = 0;
        $rows2 = [];
        while ($r = $rows->fetch_assoc()) {
            $rows2[] = $r; $total++;
            switch ($r['status']) {
                case 'Present': $present++; break;
                case 'Absent': $absent++; break;
                case 'Late': $late++; break;
                case 'Leave': $leave++; break;
                default: $unmarked++;
            }
        }
        ?>
        <div class="stat-grid" style="margin-bottom:18px;">
            <div class="stat-card"><div class="stat-icon" style="background:#2C6E1F;">&#10003;</div><div><div class="stat-num"><?= $present ?></div><div class="stat-label">Present</div></div></div>
            <div class="stat-card"><div class="stat-icon" style="background:#C0392B;">&times;</div><div><div class="stat-num"><?= $absent ?></div><div class="stat-label">Absent</div></div></div>
            <div class="stat-card"><div class="stat-icon" style="background:#B7791F;">&#9203;</div><div><div class="stat-num"><?= $late ?></div><div class="stat-label">Late</div></div></div>
            <div class="stat-card"><div class="stat-icon" style="background:#5B6B7F;">?</div><div><div class="stat-num"><?= $unmarked ?></div><div class="stat-label">Not Marked</div></div></div>
        </div>
        <table class="tbl">
            <tr><th>Roll No.</th><th>Name</th><th>Class</th><th>Status</th></tr>
            <?php foreach ($rows2 as $r): $st = $r['status'] ?? 'Not Marked';
                $cls = ['Present'=>'badge-green','Absent'=>'badge-red','Late'=>'badge-amber','Leave'=>'badge-blue','Not Marked'=>'badge-grey'][$st]; ?>
                <tr>
                    <td><?= e($r['roll_no']) ?></td><td><?= e($r['name']) ?></td><td><?= e($r['cname'].'-'.$r['section']) ?></td>
                    <td><span class="badge <?= $cls ?>"><?= e($st) ?></span></td>
                </tr>
            <?php endforeach; if (empty($rows2)): ?><tr><td colspan="4" class="text-center muted">No students found.</td></tr><?php endif; ?>
        </table>
    </div>
<?php else: ?>
    <div class="card">
        <form method="get" style="display:flex;gap:10px;margin-bottom:16px;">
            <input type="hidden" name="type" value="staff">
            <input class="form-control" style="max-width:180px;" type="date" name="date" value="<?= e($date) ?>" onchange="this.form.submit()">
        </form>
        <?php
        $sql = "SELECT u.name, st.designation, sa.check_in, sa.check_out, sa.status
                FROM staff st JOIN users u ON st.user_id=u.id
                LEFT JOIN staff_attendance sa ON sa.staff_id=st.id AND sa.att_date='" . $conn->real_escape_string($date) . "'
                ORDER BY u.name";
        $rows = $conn->query($sql);
        ?>
        <table class="tbl">
            <tr><th>Name</th><th>Designation</th><th>Check In</th><th>Check Out</th><th>Status</th></tr>
            <?php while ($r = $rows->fetch_assoc()): $st = $r['status'] ?? 'Not Marked';
                $cls = ['Present'=>'badge-green','Absent'=>'badge-red','Late'=>'badge-amber','Leave'=>'badge-blue','Not Marked'=>'badge-grey'][$st]; ?>
                <tr>
                    <td><?= e($r['name']) ?></td><td><?= e($r['designation']) ?></td>
                    <td><?= $r['check_in'] ? e(substr($r['check_in'],0,5)) : '-' ?></td>
                    <td><?= $r['check_out'] ? e(substr($r['check_out'],0,5)) : '-' ?></td>
                    <td><span class="badge <?= $cls ?>"><?= e($st) ?></span></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>
