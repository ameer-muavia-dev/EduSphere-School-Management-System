<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['teacher']);

$pageTitle = 'Mark Attendance';
$activeMenu = 'attendance.php';
$uid = $_SESSION['user_id'];

$myClasses = $conn->query("SELECT DISTINCT c.id, c.name, c.section FROM class_subjects cs JOIN classes c ON cs.class_id=c.id WHERE cs.teacher_id=$uid ORDER BY c.name");
$classIds = [];
$myClassesArr = [];
while ($c = $myClasses->fetch_assoc()) { $classIds[] = $c['id']; $myClassesArr[] = $c; }

$classFilter = (int)($_GET['class_id'] ?? ($myClassesArr[0]['id'] ?? 0));
$date = $_GET['date'] ?? date('Y-m-d');

if (!in_array($classFilter, $classIds) && $classFilter != 0) {
    stop_page('Access Denied', 'You are not assigned to this class.', 403);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $post_class = (int)$_POST['class_id']; $post_date = $_POST['att_date'];
    if (!in_array($post_class, $classIds)) stop_page('Access Denied', 'You are not authorized to mark attendance for this class.', 403);
    foreach ($_POST['status'] ?? [] as $student_id => $status) {
        $student_id = (int)$student_id;
        $stmt = $conn->prepare('INSERT INTO student_attendance (student_id, att_date, status, marked_by) VALUES (?,?,?,?)
            ON DUPLICATE KEY UPDATE status=VALUES(status), marked_by=VALUES(marked_by)');
        $stmt->bind_param('issi', $student_id, $post_date, $status, $uid);
        $stmt->execute();
    }
    set_flash('success', 'Attendance saved for ' . fmt_date($post_date) . '.');
    redirect('teacher/attendance.php?class_id=' . $post_class . '&date=' . $post_date);
}

$students = $conn->query("SELECT s.id, s.roll_no, u.name FROM students s JOIN users u ON s.user_id=u.id WHERE s.class_id=$classFilter ORDER BY s.roll_no");
$existing = [];
$e = $conn->query("SELECT student_id, status FROM student_attendance WHERE att_date='" . $conn->real_escape_string($date) . "'");
while ($r = $e->fetch_assoc()) $existing[$r['student_id']] = $r['status'];

include '../includes/header.php';
?>
<div class="page-head"><h2>Mark Student Attendance</h2></div>

<form method="get" style="display:flex;gap:10px;margin-bottom:18px;">
    <select class="form-control" name="class_id" onchange="this.form.submit()" style="max-width:200px;">
        <?php foreach ($myClassesArr as $c): ?><option value="<?= $c['id'] ?>" <?= $classFilter==$c['id']?'selected':'' ?>><?= e($c['name'].'-'.$c['section']) ?></option><?php endforeach; ?>
    </select>
    <input class="form-control" type="date" name="date" value="<?= e($date) ?>" onchange="this.form.submit()" style="max-width:180px;">
</form>

<?php if (empty($myClassesArr)): ?>
    <div class="card"><p class="muted">You are not assigned to any class yet. Please contact the admin.</p></div>
<?php else: ?>
<div class="card">
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="class_id" value="<?= $classFilter ?>">
        <input type="hidden" name="att_date" value="<?= e($date) ?>">
        <table class="tbl">
            <tr><th>Roll No.</th><th>Name</th><th>Present</th><th>Absent</th><th>Late</th><th>Leave</th></tr>
            <?php if ($students->num_rows === 0): ?><tr><td colspan="6" class="text-center muted">No students in this class.</td></tr>
            <?php else: while ($s = $students->fetch_assoc()):
                $cur = $existing[$s['id']] ?? 'Present'; ?>
                <tr>
                    <td><?= e($s['roll_no']) ?></td><td><?= e($s['name']) ?></td>
                    <?php foreach (['Present','Absent','Late','Leave'] as $opt): ?>
                        <td class="text-center"><input type="radio" name="status[<?= $s['id'] ?>]" value="<?= $opt ?>" <?= $cur === $opt ? 'checked' : '' ?>></td>
                    <?php endforeach; ?>
                </tr>
            <?php endwhile; endif; ?>
        </table>
        <button class="btn btn-primary" style="margin-top:16px;" type="submit">Save Attendance</button>
    </form>
</div>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>
