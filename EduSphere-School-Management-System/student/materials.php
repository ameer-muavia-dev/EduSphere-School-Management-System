<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['student']);

$pageTitle = 'Study Material';
$activeMenu = 'materials.php';
$student_id = get_student_id($conn);
$info = $conn->query("SELECT class_id FROM students WHERE id=$student_id")->fetch_assoc();

$materials = $conn->query("SELECT m.*, s.name sname, u.name tname FROM study_materials m
    JOIN subjects s ON m.subject_id=s.id JOIN users u ON m.teacher_id=u.id
    WHERE m.class_id={$info['class_id']} ORDER BY m.uploaded_at DESC");

include '../includes/header.php';
?>
<div class="page-head"><h2>Study Material</h2></div>
<div class="card">
    <table class="tbl">
        <tr><th>Title</th><th>Subject</th><th>Teacher</th><th>Uploaded</th><th>File</th></tr>
        <?php if ($materials->num_rows === 0): ?><tr><td colspan="5" class="text-center muted">No study material available yet.</td></tr>
        <?php else: while ($m = $materials->fetch_assoc()): ?>
            <tr>
                <td><?= e($m['title']) ?><?php if ($m['description']): ?><div class="muted" style="font-size:12px;"><?= e($m['description']) ?></div><?php endif; ?></td>
                <td><?= e($m['sname']) ?></td><td><?= e($m['tname']) ?></td><td><?= fmt_date($m['uploaded_at']) ?></td>
                <td><?= $m['file_path'] ? '<a href="../'.e($m['file_path']).'" target="_blank" class="btn btn-outline btn-sm">Download</a>' : '-' ?></td>
            </tr>
        <?php endwhile; endif; ?>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
