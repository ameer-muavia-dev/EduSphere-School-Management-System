<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['admin']);

$pageTitle = 'Tests & Results';
$activeMenu = 'exams.php';
$classes = $conn->query('SELECT * FROM classes ORDER BY name, section');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = trim($_POST['title']); $class_id = (int)$_POST['class_id']; $term = trim($_POST['term']); $exam_date = $_POST['exam_date'];
    $stmt = $conn->prepare('INSERT INTO exams (title, class_id, term, exam_date) VALUES (?,?,?,?)');
    $stmt->bind_param('siss', $title, $class_id, $term, $exam_date);
    $stmt->execute();
    set_flash('success', 'Exam created. Teachers can now enter marks for it.');
    redirect('admin/exams.php');
}
if (isset($_GET['del'])) { $conn->query('DELETE FROM exams WHERE id=' . (int)$_GET['del']); set_flash('success', 'Exam deleted.'); redirect('admin/exams.php'); }

$exams = $conn->query("SELECT e.*, c.name cname, c.section,
    (SELECT COUNT(*) FROM exam_marks m WHERE m.exam_id=e.id) marks_count
    FROM exams e JOIN classes c ON e.class_id=c.id ORDER BY e.exam_date DESC");

include '../includes/header.php';
?>
<div class="page-head"><h2>Tests &amp; Results</h2></div>

<div class="card" style="margin-bottom:20px;max-width:700px;">
    <div class="card-title">Create a New Exam / Test</div>
    <form method="post" class="form-row">
        <?= csrf_field() ?>
        <div class="form-group"><label>Exam Title</label><input class="form-control" name="title" placeholder="e.g. Monthly Test - October" required></div>
        <div class="form-group"><label>Class</label>
            <select class="form-control" name="class_id" required>
                <?php while ($c = $classes->fetch_assoc()): ?><option value="<?= $c['id'] ?>"><?= e($c['name'].'-'.$c['section']) ?></option><?php endwhile; ?>
            </select>
        </div>
        <div class="form-group"><label>Term</label>
            <select class="form-control" name="term">
                <option>Weekly</option><option>Monthly</option><option>Mid-Term</option><option>Final</option>
            </select>
        </div>
        <div class="form-group"><label>Exam Date</label><input type="date" class="form-control" name="exam_date" value="<?= date('Y-m-d') ?>"></div>
        <div class="form-group" style="grid-column:1/-1;"><button class="btn btn-primary">Create Exam</button></div>
    </form>
</div>

<div class="card">
    <div class="card-title">All Exams</div>
    <table class="tbl">
        <tr><th>Title</th><th>Class</th><th>Term</th><th>Date</th><th>Marks Entered</th><th>Actions</th></tr>
        <?php if ($exams->num_rows === 0): ?><tr><td colspan="6" class="text-center muted">No exams created yet.</td></tr>
        <?php else: while ($ex = $exams->fetch_assoc()): ?>
            <tr>
                <td><?= e($ex['title']) ?></td><td><?= e($ex['cname'].'-'.$ex['section']) ?></td>
                <td><span class="badge badge-blue"><?= e($ex['term']) ?></span></td><td><?= fmt_date($ex['exam_date']) ?></td>
                <td><?= $ex['marks_count'] ?> entries</td>
                <td>
                    <a href="exam_results.php?id=<?= $ex['id'] ?>" class="btn btn-outline btn-sm">View Results</a>
                    <a href="?del=<?= $ex['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this exam and all its marks?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; endif; ?>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
