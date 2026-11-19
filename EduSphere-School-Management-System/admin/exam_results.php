<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['admin']);

$exam_id = (int)($_GET['id'] ?? 0);
$exam = $conn->query("SELECT e.*, c.name cname, c.section FROM exams e JOIN classes c ON e.class_id=c.id WHERE e.id=$exam_id")->fetch_assoc();
if (!$exam) { stop_page('Exam Not Found', 'This exam does not exist.'); }

$pageTitle = 'Exam Results';
$activeMenu = 'exams.php';

$subjects = $conn->query("SELECT DISTINCT s.id, s.name FROM class_subjects cs JOIN subjects s ON cs.subject_id=s.id WHERE cs.class_id={$exam['class_id']} ORDER BY s.name");
$subjectList = [];
while ($s = $subjects->fetch_assoc()) $subjectList[] = $s;

$students = $conn->query("SELECT s.id, s.roll_no, u.name FROM students s JOIN users u ON s.user_id=u.id WHERE s.class_id={$exam['class_id']} ORDER BY s.roll_no");
$results = [];
while ($st = $students->fetch_assoc()) {
    $marks = $conn->query("SELECT subject_id, marks_obtained, total_marks FROM exam_marks WHERE exam_id=$exam_id AND student_id={$st['id']}");
    $obtained = 0; $total = 0; $bySubject = [];
    while ($m = $marks->fetch_assoc()) {
        $obtained += $m['marks_obtained']; $total += $m['total_marks'];
        $bySubject[$m['subject_id']] = $m['marks_obtained'];
    }
    $pct = $total > 0 ? round(($obtained / $total) * 100, 1) : null;
    $results[] = ['id' => $st['id'], 'roll_no' => $st['roll_no'], 'name' => $st['name'], 'obtained' => $obtained, 'total' => $total, 'pct' => $pct, 'by_subject' => $bySubject];
}
usort($results, fn($a, $b) => ($b['pct'] ?? -1) <=> ($a['pct'] ?? -1));
$pos = 1;

function grade_for($pct) {
    if ($pct === null) return '-';
    if ($pct >= 90) return 'A+'; if ($pct >= 80) return 'A'; if ($pct >= 70) return 'B';
    if ($pct >= 60) return 'C'; if ($pct >= 50) return 'D'; return 'F';
}

include '../includes/header.php';
?>
<div class="page-head">
    <h2><?= e($exam['title']) ?> &mdash; <?= e($exam['cname'].'-'.$exam['section']) ?></h2>
    <a href="exams.php" class="btn btn-outline btn-sm">&larr; Back to Exams</a>
</div>
<div class="card">
    <div class="table-wrap">
    <table class="tbl">
        <tr>
            <th>Position</th><th>Roll No.</th><th>Name</th>
            <?php foreach ($subjectList as $sub): ?><th><?= e($sub['name']) ?></th><?php endforeach; ?>
            <th>Obtained / Total</th><th>Percentage</th><th>Grade</th>
        </tr>
        <?php foreach ($results as $r): ?>
            <tr>
                <td><?= $r['pct'] !== null ? $pos++ : '-' ?></td>
                <td><?= e($r['roll_no']) ?></td><td><?= e($r['name']) ?></td>
                <?php foreach ($subjectList as $sub): ?>
                    <td><?= isset($r['by_subject'][$sub['id']]) ? $r['by_subject'][$sub['id']] : '-' ?></td>
                <?php endforeach; ?>
                <td><?= $r['obtained'] . ' / ' . $r['total'] ?></td>
                <td><?= $r['pct'] !== null ? $r['pct'] . '%' : '-' ?></td>
                <td><span class="badge badge-blue"><?= grade_for($r['pct']) ?></span></td>
            </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
