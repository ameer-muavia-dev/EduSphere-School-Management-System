<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['admin']);

$pageTitle = 'Staff Daily Work';
$activeMenu = 'tasks.php';
$staffList = $conn->query("SELECT st.id, u.name FROM staff st JOIN users u ON st.user_id=u.id ORDER BY u.name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $staff_id = (int)$_POST['staff_id']; $title = trim($_POST['title']); $assigned_date = $_POST['assigned_date'];
    $uid = $_SESSION['user_id'];
    $stmt = $conn->prepare('INSERT INTO staff_tasks (staff_id, title, assigned_date, assigned_by) VALUES (?,?,?,?)');
    $stmt->bind_param('issi', $staff_id, $title, $assigned_date, $uid);
    $stmt->execute();
    set_flash('success', 'Task assigned.');
    redirect('admin/tasks.php');
}
if (isset($_GET['del'])) { $conn->query('DELETE FROM staff_tasks WHERE id=' . (int)$_GET['del']); redirect('admin/tasks.php'); }

$tasks = $conn->query("SELECT t.*, u.name FROM staff_tasks t JOIN staff st ON t.staff_id=st.id JOIN users u ON st.user_id=u.id ORDER BY t.assigned_date DESC LIMIT 30");
$logs = $conn->query("SELECT l.*, u.name FROM daily_work_logs l JOIN staff st ON l.staff_id=st.id JOIN users u ON st.user_id=u.id ORDER BY l.log_date DESC LIMIT 30");

include '../includes/header.php';
?>
<div class="page-head"><h2>Staff Daily Work</h2></div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">Assign a Task</div>
        <form method="post" style="margin-bottom:16px;">
            <?= csrf_field() ?>
            <div class="form-group"><label>Staff Member</label>
                <select class="form-control" name="staff_id" required>
                    <?php while ($s = $staffList->fetch_assoc()): ?><option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option><?php endwhile; ?>
                </select>
            </div>
            <div class="form-group"><label>Task</label><input class="form-control" name="title" required></div>
            <div class="form-group"><label>Assigned Date</label><input type="date" class="form-control" name="assigned_date" value="<?= date('Y-m-d') ?>"></div>
            <button class="btn btn-primary btn-sm">Assign Task</button>
        </form>
        <table class="tbl">
            <tr><th>Staff</th><th>Task</th><th>Date</th><th>Status</th><th></th></tr>
            <?php while ($t = $tasks->fetch_assoc()):
                $b = ['Pending'=>'badge-amber','In Progress'=>'badge-blue','Completed'=>'badge-green'][$t['status']]; ?>
                <tr><td><?= e($t['name']) ?></td><td><?= e($t['title']) ?></td><td><?= fmt_date($t['assigned_date']) ?></td>
                    <td><span class="badge <?= $b ?>"><?= e($t['status']) ?></span></td>
                    <td><a href="?del=<?= $t['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove?')">X</a></td></tr>
            <?php endwhile; ?>
        </table>
    </div>

    <div class="card">
        <div class="card-title">Daily Work Logs (submitted by staff)</div>
        <table class="tbl">
            <tr><th>Staff</th><th>Date</th><th>Summary</th><th>Status</th></tr>
            <?php if ($logs->num_rows === 0): ?><tr><td colspan="4" class="text-center muted">No logs submitted yet.</td></tr>
            <?php else: while ($l = $logs->fetch_assoc()): ?>
                <tr><td><?= e($l['name']) ?></td><td><?= fmt_date($l['log_date']) ?></td><td><?= e($l['task_summary']) ?></td>
                    <td><span class="badge <?= $l['status']==='Completed'?'badge-green':'badge-amber' ?>"><?= e($l['status']) ?></span></td></tr>
            <?php endwhile; endif; ?>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
