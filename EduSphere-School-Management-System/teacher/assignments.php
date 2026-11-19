<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['teacher']);

$pageTitle = 'Assignments';
$activeMenu = 'assignments.php';
$uid = $_SESSION['user_id'];

$myAssign = $conn->query("SELECT cs.class_id, c.name cname, c.section, cs.subject_id, s.name sname
    FROM class_subjects cs JOIN classes c ON cs.class_id=c.id JOIN subjects s ON cs.subject_id=s.id WHERE cs.teacher_id=$uid");
$assignArr = []; while ($r = $myAssign->fetch_assoc()) $assignArr[] = $r;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form']) && $_POST['form'] === 'create') {
    verify_csrf();
    $class_id = (int)$_POST['class_id']; $subject_id = (int)$_POST['subject_id'];
    $title = trim($_POST['title']); $description = trim($_POST['description']); $due_date = $_POST['due_date'];
    $stmt = $conn->prepare('INSERT INTO assignments (class_id, subject_id, teacher_id, title, description, due_date) VALUES (?,?,?,?,?,?)');
    $stmt->bind_param('iiisss', $class_id, $subject_id, $uid, $title, $description, $due_date);
    $stmt->execute();
    set_flash('success', 'Assignment posted.');
    redirect('teacher/assignments.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form']) && $_POST['form'] === 'grade') {
    verify_csrf();
    $sub_id = (int)$_POST['submission_id']; $marks = $_POST['marks'] !== '' ? (float)$_POST['marks'] : null; $feedback = trim($_POST['feedback']);
    $stmt = $conn->prepare('UPDATE assignment_submissions SET marks=?, feedback=? WHERE id=?');
    $stmt->bind_param('dsi', $marks, $feedback, $sub_id);
    $stmt->execute();
    set_flash('success', 'Submission graded.');
    redirect('teacher/assignments.php?view=' . (int)$_POST['assignment_id']);
}

if (isset($_GET['del'])) { $conn->query("DELETE FROM assignments WHERE id=" . (int)$_GET['del'] . " AND teacher_id=$uid"); redirect('teacher/assignments.php'); }

$assignments = $conn->query("SELECT a.*, c.name cname, c.section, s.name sname,
    (SELECT COUNT(*) FROM assignment_submissions sub WHERE sub.assignment_id=a.id) sub_count,
    (SELECT COUNT(*) FROM students st WHERE st.class_id=a.class_id) class_count
    FROM assignments a JOIN classes c ON a.class_id=c.id JOIN subjects s ON a.subject_id=s.id
    WHERE a.teacher_id=$uid ORDER BY a.created_at DESC");

$viewId = (int)($_GET['view'] ?? 0);
$viewAssignment = null; $submissions = null;
if ($viewId) {
    $viewAssignment = $conn->query("SELECT a.*, c.name cname, c.section FROM assignments a JOIN classes c ON a.class_id=c.id WHERE a.id=$viewId AND a.teacher_id=$uid")->fetch_assoc();
    if ($viewAssignment) {
        $submissions = $conn->query("SELECT sub.*, s.roll_no, u.name FROM students s JOIN users u ON s.user_id=u.id
            LEFT JOIN assignment_submissions sub ON sub.assignment_id=$viewId AND sub.student_id=s.id
            WHERE s.class_id={$viewAssignment['class_id']} ORDER BY s.roll_no");
    }
}

include '../includes/header.php';
?>
<div class="page-head"><h2>Assignments</h2></div>

<?php if ($viewAssignment): ?>
    <div class="page-head"><h3 style="color:var(--navy);"><?= e($viewAssignment['title']) ?> &mdash; <?= e($viewAssignment['cname'].'-'.$viewAssignment['section']) ?></h3>
        <a href="assignments.php" class="btn btn-outline btn-sm">&larr; Back</a></div>
    <div class="card">
        <table class="tbl">
            <tr><th>Roll No.</th><th>Name</th><th>Submitted</th><th>Marks</th><th>Feedback</th><th>Grade</th></tr>
            <?php while ($s = $submissions->fetch_assoc()): ?>
                <tr>
                    <td><?= e($s['roll_no']) ?></td><td><?= e($s['name']) ?></td>
                    <td><?= $s['submitted_at'] ? '<span class="badge badge-green">'.fmt_date($s['submitted_at']).'</span>' : '<span class="badge badge-red">Not submitted</span>' ?></td>
                    <td><?= $s['marks'] ?? '-' ?></td><td><?= e($s['feedback'] ?? '') ?></td>
                    <td>
                        <?php if ($s['id']): ?>
                        <form method="post" style="display:flex;gap:6px;">
                            <?= csrf_field() ?><input type="hidden" name="form" value="grade">
                            <input type="hidden" name="submission_id" value="<?= $s['id'] ?>"><input type="hidden" name="assignment_id" value="<?= $viewId ?>">
                            <input type="number" step="0.5" class="form-control btn-sm" style="max-width:80px;" name="marks" value="<?= $s['marks'] ?? '' ?>">
                            <input class="form-control btn-sm" style="max-width:140px;" name="feedback" value="<?= e($s['feedback'] ?? '') ?>" placeholder="feedback">
                            <button class="btn btn-teal btn-sm">Save</button>
                        </form>
                        <?php else: ?><span class="muted">&mdash;</span><?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
<?php else: ?>

<div class="card" style="margin-bottom:20px;max-width:700px;">
    <div class="card-title">Post New Assignment</div>
    <form method="post">
        <?= csrf_field() ?><input type="hidden" name="form" value="create">
        <div class="form-row">
            <div class="form-group"><label>Class & Subject</label>
                <select class="form-control" name="class_subject" onchange="var v=this.value.split('|');document.getElementById('cid').value=v[0];document.getElementById('sid').value=v[1];">
                    <?php foreach ($assignArr as $a): ?><option value="<?= $a['class_id'] ?>|<?= $a['subject_id'] ?>"><?= e($a['cname'].'-'.$a['section'].' &middot; '.$a['sname']) ?></option><?php endforeach; ?>
                </select>
                <input type="hidden" id="cid" name="class_id" value="<?= $assignArr[0]['class_id'] ?? '' ?>">
                <input type="hidden" id="sid" name="subject_id" value="<?= $assignArr[0]['subject_id'] ?? '' ?>">
            </div>
            <div class="form-group"><label>Due Date</label><input type="date" class="form-control" name="due_date" required></div>
        </div>
        <div class="form-group"><label>Title</label><input class="form-control" name="title" required></div>
        <div class="form-group"><label>Description</label><textarea class="form-control" name="description"></textarea></div>
        <button class="btn btn-primary">Post Assignment</button>
    </form>
</div>

<div class="card">
    <div class="card-title">My Assignments</div>
    <table class="tbl">
        <tr><th>Title</th><th>Class</th><th>Subject</th><th>Due</th><th>Submissions</th><th></th></tr>
        <?php if ($assignments->num_rows === 0): ?><tr><td colspan="6" class="text-center muted">No assignments posted yet.</td></tr>
        <?php else: while ($a = $assignments->fetch_assoc()): ?>
            <tr>
                <td><?= e($a['title']) ?></td><td><?= e($a['cname'].'-'.$a['section']) ?></td><td><?= e($a['sname']) ?></td>
                <td><?= fmt_date($a['due_date']) ?></td><td><?= $a['sub_count'] ?> / <?= $a['class_count'] ?></td>
                <td><a href="?view=<?= $a['id'] ?>" class="btn btn-outline btn-sm">View / Grade</a>
                    <a href="?del=<?= $a['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Delete</a></td>
            </tr>
        <?php endwhile; endif; ?>
    </table>
</div>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>
