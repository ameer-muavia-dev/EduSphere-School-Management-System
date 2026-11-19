<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['teacher']);

$pageTitle = 'Parent Contact Log';
$activeMenu = 'contact_log.php';
$uid = $_SESSION['user_id'];

$myStudents = $conn->query("SELECT DISTINCT s.id, s.roll_no, u.name, c.name cname, c.section
    FROM class_subjects cs JOIN students s ON s.class_id=cs.class_id JOIN users u ON s.user_id=u.id JOIN classes c ON s.class_id=c.id
    WHERE cs.teacher_id=$uid ORDER BY u.name");
$studentsArr = []; while ($r = $myStudents->fetch_assoc()) $studentsArr[] = $r;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $student_id = (int)$_POST['student_id']; $contact_date = $_POST['contact_date']; $mode = $_POST['mode']; $notes = trim($_POST['notes']);
    $stmt = $conn->prepare('INSERT INTO parent_contact_log (student_id, staff_id, contact_date, mode, notes) VALUES (?,?,?,?,?)');
    $stmt->bind_param('iisss', $student_id, $uid, $contact_date, $mode, $notes);
    $stmt->execute();
    set_flash('success', 'Contact logged.');
    redirect('teacher/contact_log.php');
}

$logs = $conn->query("SELECT pc.*, s.roll_no, u.name FROM parent_contact_log pc
    JOIN students s ON pc.student_id=s.id JOIN users u ON s.user_id=u.id WHERE pc.staff_id=$uid ORDER BY pc.contact_date DESC LIMIT 20");

include '../includes/header.php';
?>
<div class="page-head"><h2>Parent Contact Log</h2></div>

<div class="card" style="margin-bottom:20px;max-width:700px;">
    <div class="card-title">Log a New Contact / Remark</div>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group"><label>Student</label>
                <select class="form-control" name="student_id" required>
                    <?php foreach ($studentsArr as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name'].' ('.$s['roll_no'].') - '.$s['cname'].'-'.$s['section']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Date</label><input type="date" class="form-control" name="contact_date" value="<?= date('Y-m-d') ?>"></div>
        </div>
        <div class="form-group"><label>Mode</label>
            <select class="form-control" name="mode"><option>Call</option><option>Meeting</option><option>Message</option><option>Remark</option></select>
        </div>
        <div class="form-group"><label>Notes</label><textarea class="form-control" name="notes" required placeholder="e.g. Discussed low attendance with parent"></textarea></div>
        <button class="btn btn-primary">Save</button>
    </form>
</div>

<div class="card">
    <div class="card-title">Recent Contact Log</div>
    <table class="tbl">
        <tr><th>Date</th><th>Student</th><th>Mode</th><th>Notes</th></tr>
        <?php if ($logs->num_rows === 0): ?><tr><td colspan="4" class="text-center muted">No contact logged yet.</td></tr>
        <?php else: while ($l = $logs->fetch_assoc()): ?>
            <tr><td><?= fmt_date($l['contact_date']) ?></td><td><?= e($l['name']).' ('.e($l['roll_no']).')' ?></td><td><span class="badge badge-blue"><?= e($l['mode']) ?></span></td><td><?= e($l['notes']) ?></td></tr>
        <?php endwhile; endif; ?>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
