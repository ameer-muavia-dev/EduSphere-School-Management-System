<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['teacher']);

$pageTitle = 'My Timetable';
$activeMenu = 'timetable.php';
$uid = $_SESSION['user_id'];

$days = ['Mon','Tue','Wed','Thu','Fri'];
$grid = [];
$entries = $conn->query("SELECT t.*, c.name cname, c.section, s.name sname FROM timetable t
    JOIN classes c ON t.class_id=c.id JOIN subjects s ON t.subject_id=s.id WHERE t.teacher_id=$uid ORDER BY t.period_no");
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
                    <td><?php if ($cell): ?><b><?= e($cell['sname']) ?></b><div class="muted" style="font-size:11.5px;"><?= e($cell['cname'].'-'.$cell['section']) ?> &middot; <?= substr($cell['start_time'],0,5) ?></div>
                    <?php else: ?><span class="muted">&mdash;</span><?php endif; ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endfor; ?>
    </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
