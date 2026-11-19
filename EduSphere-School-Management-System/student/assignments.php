<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['student']);

$pageTitle = 'Assignments';
$activeMenu = 'assignments.php';
$student_id = get_student_id($conn);
$info = $conn->query("SELECT class_id FROM students WHERE id=$student_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $assignment_id = (int)$_POST['assignment_id'];
    $filePath = null;
    if (!empty($_FILES['file']['name'])) {
        $allowed = ['pdf','doc','docx','jpg','jpeg','png','txt'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $_FILES['file']['size'] < 10 * 1024 * 1024) {
            $safeName = uniqid('sub_') . '.' . $ext;
            if (move_uploaded_file($_FILES['file']['tmp_name'], '../uploads/assignments/' . $safeName)) {
                $filePath = 'uploads/assignments/' . $safeName;
            }
        }
    }
    $stmt = $conn->prepare('INSERT INTO assignment_submissions (assignment_id, student_id, file_path) VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE file_path=VALUES(file_path), submitted_at=CURRENT_TIMESTAMP');
    $stmt->bind_param('iis', $assignment_id, $student_id, $filePath);
    $stmt->execute();
    set_flash('success', 'Assignment submitted.');
    redirect('student/assignments.php');
}

$assignments = $conn->query("SELECT a.*, s.name sname, sub.submitted_at, sub.marks, sub.feedback
    FROM assignments a JOIN subjects s ON a.subject_id=s.id
    LEFT JOIN assignment_submissions sub ON sub.assignment_id=a.id AND sub.student_id=$student_id
    WHERE a.class_id={$info['class_id']} ORDER BY a.due_date DESC");

include '../includes/header.php';
?>
<div class="page-head"><h2>Assignments</h2></div>
<div class="card">
    <table class="tbl">
        <tr><th>Title</th><th>Subject</th><th>Due Date</th><th>Status</th><th>Marks</th><th>Action</th></tr>
        <?php if ($assignments->num_rows === 0): ?><tr><td colspan="6" class="text-center muted">No assignments posted yet.</td></tr>
        <?php else: while ($a = $assignments->fetch_assoc()): ?>
            <tr>
                <td><?= e($a['title']) ?><?php if($a['description']): ?><div class="muted" style="font-size:12px;"><?= e($a['description']) ?></div><?php endif; ?></td>
                <td><?= e($a['sname']) ?></td><td><?= fmt_date($a['due_date']) ?></td>
                <td><?= $a['submitted_at'] ? '<span class="badge badge-green">Submitted</span>' : '<span class="badge badge-red">Pending</span>' ?></td>
                <td><?= $a['marks'] ?? '-' ?></td>
                <td>
                    <form method="post" enctype="multipart/form-data" style="display:flex;gap:6px;">
                        <?= csrf_field() ?><input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
                        <input type="file" name="file" required style="font-size:11.5px;max-width:150px;">
                        <button class="btn btn-teal btn-sm"><?= $a['submitted_at'] ? 'Resubmit' : 'Submit' ?></button>
                    </form>
                </td>
            </tr>
        <?php endwhile; endif; ?>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
