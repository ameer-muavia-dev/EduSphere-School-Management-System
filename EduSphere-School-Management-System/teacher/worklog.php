<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['teacher']);

$pageTitle = 'Daily Work Log';
$activeMenu = 'worklog.php';
$staff_id = get_staff_id($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $log_date = $_POST['log_date']; $summary = trim($_POST['task_summary']); $status = $_POST['status']; $remarks = trim($_POST['remarks']);
    $stmt = $conn->prepare('INSERT INTO daily_work_logs (staff_id, log_date, task_summary, status, remarks) VALUES (?,?,?,?,?)');
    $stmt->bind_param('issss', $staff_id, $log_date, $summary, $status, $remarks);
    $stmt->execute();
    set_flash('success', 'Work log submitted.');
    redirect('teacher/worklog.php');
}

$myTasks = $conn->query("SELECT * FROM staff_tasks WHERE staff_id=$staff_id ORDER BY assigned_date DESC LIMIT 10");
$myLogs = $conn->query("SELECT * FROM daily_work_logs WHERE staff_id=$staff_id ORDER BY log_date DESC LIMIT 15");
$myAtt = $conn->query("SELECT * FROM staff_attendance WHERE staff_id=$staff_id ORDER BY att_date DESC LIMIT 10");

include '../includes/header.php';
?>
<div class="page-head"><h2>Daily Work Log</h2></div>

<div class="grid-2" style="margin-bottom:20px;">
    <div class="card">
        <div class="card-title">Submit Today's Work</div>
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-group"><label>Date</label><input type="date" class="form-control" name="log_date" value="<?= date('Y-m-d') ?>"></div>
            <div class="form-group"><label>What did you do today?</label><textarea class="form-control" name="task_summary" required></textarea></div>
            <div class="form-group"><label>Status</label>
                <select class="form-control" name="status"><option>Completed</option><option>Pending</option></select>
            </div>
            <div class="form-group"><label>Remarks (optional)</label><input class="form-control" name="remarks"></div>
            <button class="btn btn-primary">Submit Log</button>
        </form>
    </div>
    <div class="card">
        <div class="card-title">Tasks Assigned to Me</div>
        <table class="tbl">
            <tr><th>Task</th><th>Date</th><th>Status</th></tr>
            <?php if ($myTasks->num_rows === 0): ?><tr><td colspan="3" class="text-center muted">No tasks assigned yet.</td></tr>
            <?php else: while ($t = $myTasks->fetch_assoc()): ?>
                <tr><td><?= e($t['title']) ?></td><td><?= fmt_date($t['assigned_date']) ?></td>
                    <td><span class="badge <?= ['Pending'=>'badge-amber','In Progress'=>'badge-blue','Completed'=>'badge-green'][$t['status']] ?>"><?= e($t['status']) ?></span></td></tr>
            <?php endwhile; endif; ?>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-title">My Recent Work Log History</div>
    <table class="tbl">
        <tr><th>Date</th><th>Summary</th><th>Status</th><th>Remarks</th></tr>
        <?php if ($myLogs->num_rows === 0): ?><tr><td colspan="4" class="text-center muted">No logs submitted yet.</td></tr>
        <?php else: while ($l = $myLogs->fetch_assoc()): ?>
            <tr><td><?= fmt_date($l['log_date']) ?></td><td><?= e($l['task_summary']) ?></td>
                <td><span class="badge <?= $l['status']==='Completed'?'badge-green':'badge-amber' ?>"><?= e($l['status']) ?></span></td><td><?= e($l['remarks']) ?></td></tr>
        <?php endwhile; endif; ?>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
