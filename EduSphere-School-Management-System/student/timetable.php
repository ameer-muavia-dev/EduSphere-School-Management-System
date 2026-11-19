<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['student']);

$pageTitle = 'Timetable';
$activeMenu = 'timetable.php';
$student_id = get_student_id($conn);
$info = $conn->query("SELECT class_id FROM students WHERE id=$student_id")->fetch_assoc();

$days = ['Mon','Tue','Wed','Thu','Fri'];
$grid = [];
$entries = $conn->query("SELECT t.*, s.name sname, u.name tname FROM timetable t
    JOIN subjects s ON t.subject_id=s.id LEFT JOIN users u ON t.teacher_id=u.id
    WHERE t.class_id={$info['class_id']} ORDER BY t.period_no");
while ($r = $entries->fetch_assoc()) { $grid[$r['day_of_week']][$r['period_no']] = $r; }

include '../includes/header.php';
?>
<div class="page-head"><h2>My Timetable</h2></div>
<div class="card">
    <div class="table-wrap">
    <table class="tbl">
        <tr><th>Period</th><?php foreach ($days as $d): ?><th><?= $d ?></th><?php endforeach; ?></tr>
        <?php for ($p = 1; $p <= 8; $p++):
            $rowHasData = false; foreach ($days as $d) if (isset($grid[$d][$p])) $rowHasData = true;
            if (!$rowHasData) continue; ?>
            <tr>
                <td><b>P<?= $p ?></b></td>
                <?php foreach ($days as $d): $cell = $grid[$d][$p] ?? null; ?>
                    <td><?php if ($cell): ?><b><?= e($cell['sname']) ?></b><div class="muted" style="font-size:11.5px;"><?= e($cell['tname'] ?? '') ?></div>
                    <?php else: ?><span class="muted">&mdash;</span><?php endif; ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endfor; ?>
    </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
