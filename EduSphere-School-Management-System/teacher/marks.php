<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['teacher']);

$pageTitle = 'Enter Marks';
$activeMenu = 'marks.php';
$uid = $_SESSION['user_id'];

$myExams = $conn->query("SELECT DISTINCT e.id, e.title, e.class_id, c.name cname, c.section
    FROM exams e JOIN classes c ON e.class_id=c.id JOIN class_subjects cs ON cs.class_id=e.class_id
    WHERE cs.teacher_id=$uid ORDER BY e.exam_date DESC");
$examsArr = []; while ($r = $myExams->fetch_assoc()) $examsArr[] = $r;

$examId = (int)($_GET['exam_id'] ?? ($examsArr[0]['id'] ?? 0));
$exam = null;
foreach ($examsArr as $ex) if ($ex['id'] == $examId) $exam = $ex;

$mySubjects = [];
if ($exam) {
    $subs = $conn->query("SELECT s.id, s.name FROM class_subjects cs JOIN subjects s ON cs.subject_id=s.id WHERE cs.class_id={$exam['class_id']} AND cs.teacher_id=$uid");
    while ($r = $subs->fetch_assoc()) $mySubjects[] = $r;
}
$subjectId = (int)($_GET['subject_id'] ?? ($mySubjects[0]['id'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $post_exam = (int)$_POST['exam_id']; $post_subject = (int)$_POST['subject_id']; $total = (float)$_POST['total_marks'];
    foreach ($_POST['marks'] ?? [] as $student_id => $obtained) {
        if ($obtained === '') continue;
        $student_id = (int)$student_id; $obtained = (float)$obtained;
        $stmt = $conn->prepare('INSERT INTO exam_marks (exam_id, student_id, subject_id, marks_obtained, total_marks) VALUES (?,?,?,?,?)
            ON DUPLICATE KEY UPDATE marks_obtained=VALUES(marks_obtained), total_marks=VALUES(total_marks)');
        $stmt->bind_param('iiidd', $post_exam, $student_id, $post_subject, $obtained, $total);
        $stmt->execute();
    }
    set_flash('success', 'Marks saved successfully.');
    redirect("teacher/marks.php?exam_id=$post_exam&subject_id=$post_subject");
}

$students = [];
if ($exam) {
    $st = $conn->query("SELECT s.id, s.roll_no, u.name FROM students s JOIN users u ON s.user_id=u.id WHERE s.class_id={$exam['class_id']} ORDER BY s.roll_no");
    while ($r = $st->fetch_assoc()) $students[] = $r;
}
$existing = [];
if ($exam && $subjectId) {
    $ex2 = $conn->query("SELECT student_id, marks_obtained, total_marks FROM exam_marks WHERE exam_id=$examId AND subject_id=$subjectId");
    while ($r = $ex2->fetch_assoc()) $existing[$r['student_id']] = $r;
}

include '../includes/header.php';
?>
<div class="page-head"><h2>Enter Exam Marks</h2></div>

<?php if (empty($examsArr)): ?>
    <div class="card"><p class="muted">No exams are available for your classes yet. Ask the admin to create one.</p></div>
<?php else: ?>
<form method="get" style="display:flex;gap:10px;margin-bottom:18px;">
    <select class="form-control" name="exam_id" onchange="this.form.submit()" style="max-width:280px;">
        <?php foreach ($examsArr as $ex): ?><option value="<?= $ex['id'] ?>" <?= $examId==$ex['id']?'selected':'' ?>><?= e($ex['title'].' - '.$ex['cname'].'-'.$ex['section']) ?></option><?php endforeach; ?>
    </select>
    <select class="form-control" name="subject_id" onchange="this.form.submit()" style="max-width:200px;">
        <?php foreach ($mySubjects as $s): ?><option value="<?= $s['id'] ?>" <?= $subjectId==$s['id']?'selected':'' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
    </select>
</form>

<div class="card">
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="exam_id" value="<?= $examId ?>">
        <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
        <div class="form-group" style="max-width:200px;"><label>Total Marks</label>
            <input type="number" class="form-control" name="total_marks" value="<?= $existing ? reset($existing)['total_marks'] : 100 ?>">
        </div>
        <table class="tbl">
            <tr><th>Roll No.</th><th>Name</th><th>Marks Obtained</th></tr>
            <?php if (empty($students)): ?><tr><td colspan="3" class="text-center muted">No students in this class.</td></tr>
            <?php else: foreach ($students as $s): ?>
                <tr>
                    <td><?= e($s['roll_no']) ?></td><td><?= e($s['name']) ?></td>
                    <td><input type="number" step="0.5" class="form-control" style="max-width:120px;" name="marks[<?= $s['id'] ?>]" value="<?= $existing[$s['id']]['marks_obtained'] ?? '' ?>"></td>
                </tr>
            <?php endforeach; endif; ?>
        </table>
        <button class="btn btn-primary" style="margin-top:16px;">Save Marks</button>
    </form>
</div>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>
