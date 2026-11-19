<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['student']);

$pageTitle = 'My Dashboard';
$activeMenu = 'dashboard.php';
$student_id = get_student_id($conn);
$info = $conn->query("SELECT s.*, c.name cname, c.section FROM students s JOIN classes c ON s.class_id=c.id WHERE s.id=$student_id")->fetch_assoc();

$monthStart = date('Y-m-01'); $monthEnd = date('Y-m-t');
$pct = attendance_percent($conn, $student_id, $monthStart, $monthEnd);

$latestExam = $conn->query("SELECT e.title, SUM(m.marks_obtained) obt, SUM(m.total_marks) tot
    FROM exam_marks m JOIN exams e ON m.exam_id=e.id WHERE m.student_id=$student_id GROUP BY e.id ORDER BY e.exam_date DESC LIMIT 1")->fetch_assoc();
$latestPct = ($latestExam && $latestExam['tot'] > 0) ? round(($latestExam['obt']/$latestExam['tot'])*100, 1) : null;

$dueAssignments = $conn->query("SELECT COUNT(*) c FROM assignments a WHERE a.class_id={$info['class_id']} AND a.due_date >= CURDATE()
    AND NOT EXISTS (SELECT 1 FROM assignment_submissions sub WHERE sub.assignment_id=a.id AND sub.student_id=$student_id)")->fetch_assoc()['c'];

$feeRow = $conn->query("SELECT * FROM fee_vouchers WHERE student_id=$student_id ORDER BY year DESC, month DESC LIMIT 1")->fetch_assoc();

$upcoming = $conn->query("SELECT * FROM events WHERE event_date >= CURDATE() AND audience IN ('All','Students') ORDER BY event_date LIMIT 5");

include '../includes/header.php';
?>
<div class="stat-grid">
    <div class="stat-card"><div class="stat-icon" style="background:#1F8A8A;">&#9989;</div><div><div class="stat-num"><?= $pct !== null ? $pct.'%' : '-' ?></div><div class="stat-label">This Month's Attendance</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#2F5D8C;">&#128221;</div><div><div class="stat-num"><?= $latestPct !== null ? $latestPct.'%' : '-' ?></div><div class="stat-label">Latest Test Score</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#E4572E;">&#128203;</div><div><div class="stat-num"><?= $dueAssignments ?></div><div class="stat-label">Pending Assignments</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#F2A93B;">&#128176;</div><div><div class="stat-num"><?= $feeRow ? e($feeRow['status']) : '-' ?></div><div class="stat-label">Latest Fee Status</div></div></div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">My Information</div>
        <table class="tbl">
            <tr><td>Name</td><td><b><?= e($_SESSION['name']) ?></b></td></tr>
            <tr><td>Roll No.</td><td><b><?= e($info['roll_no']) ?></b></td></tr>
            <tr><td>Class</td><td><b><?= e($info['cname'].'-'.$info['section']) ?></b></td></tr>
            <tr><td>Guardian</td><td><b><?= e($info['guardian_name']) ?></b></td></tr>
        </table>
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
</div>
<?php include '../includes/footer.php'; ?>
