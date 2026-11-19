<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['parent']);

$pageTitle = 'Parent Dashboard';
$activeMenu = 'dashboard.php';
$uid = $_SESSION['user_id'];

$childrenArr = get_parent_children($conn);
$childId = (int)($_GET['child_id'] ?? ($childrenArr[0]['id'] ?? 0));
if ($childId) verify_parent_child($conn, $childId, $childrenArr);

include '../includes/header.php';

if (empty($childrenArr)) {
    echo '<div class="card"><p class="muted">No student record is linked to your account yet. Please contact the school admin.</p></div>';
    include '../includes/footer.php';
    exit;
}
?>
<div class="page-head">
    <h2>Welcome, <?= e($_SESSION['name']) ?></h2>
    <?php if (count($childrenArr) > 1): ?>
    <form method="get">
        <select class="form-control" name="child_id" onchange="this.form.submit()">
            <?php foreach ($childrenArr as $c): ?><option value="<?= $c['id'] ?>" <?= $childId==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
    </form>
    <?php endif; ?>
</div>

<?php
$monthStart = date('Y-m-01'); $monthEnd = date('Y-m-t');
$pct = attendance_percent($conn, $childId, $monthStart, $monthEnd);
$latestExam = $conn->query("SELECT e.title, SUM(m.marks_obtained) obt, SUM(m.total_marks) tot
    FROM exam_marks m JOIN exams e ON m.exam_id=e.id WHERE m.student_id=$childId GROUP BY e.id ORDER BY e.exam_date DESC LIMIT 1")->fetch_assoc();
$latestPct = ($latestExam && $latestExam['tot'] > 0) ? round(($latestExam['obt']/$latestExam['tot'])*100, 1) : null;
$feeRow = $conn->query("SELECT * FROM fee_vouchers WHERE student_id=$childId ORDER BY year DESC, month DESC LIMIT 1")->fetch_assoc();
$upcoming = $conn->query("SELECT * FROM events WHERE event_date >= CURDATE() AND audience IN ('All','Parents') ORDER BY event_date LIMIT 5");
$child = null; foreach ($childrenArr as $c) if ($c['id'] == $childId) $child = $c;
?>
<div class="stat-grid">
    <div class="stat-card"><div class="stat-icon" style="background:#1F8A8A;">&#9989;</div><div><div class="stat-num"><?= $pct !== null ? $pct.'%' : '-' ?></div><div class="stat-label">This Month's Attendance</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#2F5D8C;">&#128221;</div><div><div class="stat-num"><?= $latestPct !== null ? $latestPct.'%' : '-' ?></div><div class="stat-label">Latest Test Score</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#F2A93B;">&#128176;</div><div><div class="stat-num"><?= $feeRow ? e($feeRow['status']) : '-' ?></div><div class="stat-label">Latest Fee Status</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#E4572E;">&#127891;</div><div><div class="stat-num"><?= e($child['cname'].'-'.$child['section']) ?></div><div class="stat-label">Class</div></div></div>
</div>

<div class="card">
    <div class="card-title">Upcoming Events</div>
    <?php if ($upcoming->num_rows === 0): ?><p class="muted">No upcoming events.</p>
    <?php else: while ($ev = $upcoming->fetch_assoc()): ?>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);">
            <span><?= e($ev['title']) ?></span><span class="muted"><?= fmt_date($ev['event_date']) ?></span>
        </div>
    <?php endwhile; endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
