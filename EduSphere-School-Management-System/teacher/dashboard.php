<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['teacher']);

$pageTitle = 'Teacher Dashboard';
$activeMenu = 'dashboard.php';
$staff_id = get_staff_id($conn);
$uid = $_SESSION['user_id'];

$myClasses = $conn->query("SELECT DISTINCT c.id, c.name, c.section FROM class_subjects cs JOIN classes c ON cs.class_id=c.id WHERE cs.teacher_id=$uid");
$classCount = $myClasses->num_rows;

$today = date('Y-m-d');
$todayPeriods = $conn->query("SELECT t.*, c.name cname, c.section, s.name sname FROM timetable t
    JOIN classes c ON t.class_id=c.id JOIN subjects s ON t.subject_id=s.id
    WHERE t.teacher_id=$uid AND t.day_of_week='" . date('D', strtotime($today)) . "' ORDER BY t.period_no");

$pendingTasks = $conn->query("SELECT COUNT(*) c FROM staff_tasks WHERE staff_id=$staff_id AND status!='Completed'")->fetch_assoc()['c'];
$materialsCount = $conn->query("SELECT COUNT(*) c FROM study_materials WHERE teacher_id=$uid")->fetch_assoc()['c'];
$assignmentsCount = $conn->query("SELECT COUNT(*) c FROM assignments WHERE teacher_id=$uid")->fetch_assoc()['c'];

include '../includes/header.php';
?>
<div class="stat-grid">
    <div class="stat-card"><div class="stat-icon" style="background:#1F8A8A;">&#127979;</div><div><div class="stat-num"><?= $classCount ?></div><div class="stat-label">My Classes</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#F2A93B;">&#128203;</div><div><div class="stat-num"><?= $pendingTasks ?></div><div class="stat-label">Pending Tasks</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#2F5D8C;">&#128218;</div><div><div class="stat-num"><?= $materialsCount ?></div><div class="stat-label">Materials Uploaded</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#E4572E;">&#128221;</div><div><div class="stat-num"><?= $assignmentsCount ?></div><div class="stat-label">Assignments Posted</div></div></div>
</div>

<div class="card">
    <div class="card-title">Today's Classes (<?= date('l, d M Y') ?>)</div>
    <table class="tbl">
        <tr><th>Period</th><th>Time</th><th>Class</th><th>Subject</th></tr>
        <?php if ($todayPeriods->num_rows === 0): ?><tr><td colspan="4" class="text-center muted">No classes scheduled for today.</td></tr>
        <?php else: while ($p = $todayPeriods->fetch_assoc()): ?>
            <tr><td>P<?= $p['period_no'] ?></td><td><?= substr($p['start_time'],0,5).' - '.substr($p['end_time'],0,5) ?></td>
                <td><?= e($p['cname'].'-'.$p['section']) ?></td><td><?= e($p['sname']) ?></td></tr>
        <?php endwhile; endif; ?>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
