<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['student']);

$pageTitle = 'My Results';
$activeMenu = 'results.php';
$student_id = get_student_id($conn);

$exams = $conn->query("SELECT DISTINCT e.id, e.title, e.term, e.exam_date FROM exam_marks m JOIN exams e ON m.exam_id=e.id
    WHERE m.student_id=$student_id ORDER BY e.exam_date DESC");

include '../includes/header.php';
function grade_for($pct) {
    if ($pct >= 90) return 'A+'; if ($pct >= 80) return 'A'; if ($pct >= 70) return 'B';
    if ($pct >= 60) return 'C'; if ($pct >= 50) return 'D'; return 'F';
}
?>
<div class="page-head"><h2>My Results</h2></div>

<?php if ($exams->num_rows === 0): ?>
    <div class="card"><p class="muted">No results published yet.</p></div>
<?php else: while ($ex = $exams->fetch_assoc()):
    $marks = $conn->query("SELECT m.*, s.name sname FROM exam_marks m JOIN subjects s ON m.subject_id=s.id WHERE m.exam_id={$ex['id']} AND m.student_id=$student_id");
    $obt = 0; $tot = 0; $rows = [];
    while ($mm = $marks->fetch_assoc()) { $obt += $mm['marks_obtained']; $tot += $mm['total_marks']; $rows[] = $mm; }
    $pct = $tot > 0 ? round(($obt/$tot)*100, 1) : 0;
?>
    <div class="card" style="margin-bottom:18px;">
        <div class="page-head" style="margin-bottom:12px;">
            <div class="card-title" style="margin:0;"><?= e($ex['title']) ?> <span class="badge badge-blue"><?= e($ex['term']) ?></span></div>
            <div><b style="font-size:18px;color:var(--navy);"><?= $pct ?>%</b> <span class="badge badge-blue"><?= grade_for($pct) ?></span></div>
        </div>
        <table class="tbl">
            <tr><th>Subject</th><th>Marks Obtained</th><th>Total</th></tr>
            <?php foreach ($rows as $r): ?><tr><td><?= e($r['sname']) ?></td><td><?= $r['marks_obtained'] ?></td><td><?= $r['total_marks'] ?></td></tr><?php endforeach; ?>
        </table>
    </div>
<?php endwhile; endif; ?>
<?php include '../includes/footer.php'; ?>
