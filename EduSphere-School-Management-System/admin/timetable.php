<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['admin']);

$pageTitle = 'Timetable';
$activeMenu = 'timetable.php';
$classes = $conn->query('SELECT * FROM classes ORDER BY name, section');
$classFilter = (int)($_GET['class_id'] ?? ($classes->fetch_assoc()['id'] ?? 0));
$classes->data_seek(0);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $class_id = (int)$_POST['class_id']; $subject_id = (int)$_POST['subject_id']; $teacher_id = (int)$_POST['teacher_id'];
    $day = $_POST['day_of_week']; $period = (int)$_POST['period_no']; $start = $_POST['start_time']; $end = $_POST['end_time'];

    // Clash detection: same class already has a period at this day+period, OR the teacher is already teaching elsewhere at this day+period
    $clash = $conn->query("SELECT t.*, c.name cname, c.section FROM timetable t JOIN classes c ON t.class_id=c.id
        WHERE t.day_of_week='$day' AND t.period_no=$period AND (t.class_id=$class_id OR t.teacher_id=$teacher_id)")->fetch_assoc();

    if ($clash) {
        $error = ($clash['class_id'] == $class_id)
            ? "This class already has a period scheduled at Period $period on $day."
            : "This teacher is already teaching {$clash['cname']}-{$clash['section']} at Period $period on $day.";
    } else {
        $stmt = $conn->prepare('INSERT INTO timetable (class_id,subject_id,teacher_id,day_of_week,period_no,start_time,end_time) VALUES (?,?,?,?,?,?,?)');
        $stmt->bind_param('iiisiss', $class_id, $subject_id, $teacher_id, $day, $period, $start, $end);
        $stmt->execute();
        set_flash('success', 'Timetable entry added.');
        redirect('admin/timetable.php?class_id=' . $class_id);
    }
}
if (isset($_GET['del'])) { $conn->query('DELETE FROM timetable WHERE id=' . (int)$_GET['del']); set_flash('success', 'Entry removed.'); redirect('admin/timetable.php?class_id=' . $classFilter); }

$subjects = $conn->query("SELECT s.id, s.name FROM class_subjects cs JOIN subjects s ON cs.subject_id=s.id WHERE cs.class_id=$classFilter");
$teachers = $conn->query("SELECT id, name FROM users WHERE role_id=2 ORDER BY name");

$days = ['Mon','Tue','Wed','Thu','Fri'];
$grid = [];
$entries = $conn->query("SELECT t.*, s.name sname, u.name tname FROM timetable t
    JOIN subjects s ON t.subject_id=s.id LEFT JOIN users u ON t.teacher_id=u.id
    WHERE t.class_id=$classFilter ORDER BY t.period_no, t.day_of_week");
while ($r = $entries->fetch_assoc()) { $grid[$r['day_of_week']][$r['period_no']] = $r; }
$maxPeriod = 8;

include '../includes/header.php';
?>
<div class="page-head">
    <h2>Timetable</h2>
    <form method="get">
        <select class="form-control" name="class_id" onchange="this.form.submit()">
            <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>" <?= $classFilter == $c['id'] ? 'selected' : '' ?>><?= e($c['name'].'-'.$c['section']) ?></option>
            <?php endwhile; ?>
        </select>
    </form>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:20px;">
    <div class="card-title">Add Period</div>
    <form method="post" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
        <?= csrf_field() ?><input type="hidden" name="class_id" value="<?= $classFilter ?>">
        <div><label style="font-size:12px;">Subject</label><br>
            <select class="form-control" name="subject_id" required style="max-width:160px;">
                <?php $subjects->data_seek(0); while ($s = $subjects->fetch_assoc()): ?><option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option><?php endwhile; ?>
            </select>
        </div>
        <div><label style="font-size:12px;">Teacher</label><br>
            <select class="form-control" name="teacher_id" required style="max-width:170px;">
                <?php $teachers->data_seek(0); while ($t = $teachers->fetch_assoc()): ?><option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option><?php endwhile; ?>
            </select>
        </div>
        <div><label style="font-size:12px;">Day</label><br>
            <select class="form-control" name="day_of_week" style="max-width:110px;"><?php foreach ($days as $d): ?><option><?= $d ?></option><?php endforeach; ?></select>
        </div>
        <div><label style="font-size:12px;">Period No.</label><br><input class="form-control" type="number" name="period_no" min="1" max="8" value="1" style="max-width:90px;"></div>
        <div><label style="font-size:12px;">Start</label><br><input class="form-control" type="time" name="start_time" required style="max-width:120px;"></div>
        <div><label style="font-size:12px;">End</label><br><input class="form-control" type="time" name="end_time" required style="max-width:120px;"></div>
        <button class="btn btn-primary btn-sm">Add</button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="tbl">
        <tr><th>Period</th><?php foreach ($days as $d): ?><th><?= $d ?></th><?php endforeach; ?></tr>
        <?php for ($p = 1; $p <= $maxPeriod; $p++):
            $rowHasData = false;
            foreach ($days as $d) if (isset($grid[$d][$p])) $rowHasData = true;
            if (!$rowHasData && $p > 5) continue;
        ?>
            <tr>
                <td><b>P<?= $p ?></b></td>
                <?php foreach ($days as $d): $cell = $grid[$d][$p] ?? null; ?>
                    <td>
                        <?php if ($cell): ?>
                            <div><b><?= e($cell['sname']) ?></b></div>
                            <div class="muted" style="font-size:11.5px;"><?= e($cell['tname'] ?? '') ?></div>
                            <a href="?del=<?= $cell['id'] ?>&class_id=<?= $classFilter ?>" class="muted" style="font-size:11px;" onclick="return confirm('Remove this period?')">Remove</a>
                        <?php else: ?><span class="muted">&mdash;</span><?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endfor; ?>
    </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
