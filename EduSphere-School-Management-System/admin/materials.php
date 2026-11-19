<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['admin']);

$pageTitle = 'LMS & Study Material';
$activeMenu = 'materials.php';

if (isset($_GET['del'])) { $conn->query('DELETE FROM study_materials WHERE id=' . (int)$_GET['del']); set_flash('success', 'Material removed.'); redirect('admin/materials.php'); }
if (isset($_GET['del_a'])) { $conn->query('DELETE FROM assignments WHERE id=' . (int)$_GET['del_a']); set_flash('success', 'Assignment removed.'); redirect('admin/materials.php'); }

$materials = $conn->query("SELECT m.*, c.name cname, c.section, s.name sname, u.name tname
    FROM study_materials m JOIN classes c ON m.class_id=c.id JOIN subjects s ON m.subject_id=s.id JOIN users u ON m.teacher_id=u.id
    ORDER BY m.uploaded_at DESC");
$assignments = $conn->query("SELECT a.*, c.name cname, c.section, s.name sname, u.name tname,
    (SELECT COUNT(*) FROM assignment_submissions sub WHERE sub.assignment_id=a.id) sub_count,
    (SELECT COUNT(*) FROM students st WHERE st.class_id=a.class_id) class_count
    FROM assignments a JOIN classes c ON a.class_id=c.id JOIN subjects s ON a.subject_id=s.id JOIN users u ON a.teacher_id=u.id
    ORDER BY a.created_at DESC");

include '../includes/header.php';
?>
<div class="page-head"><h2>LMS &amp; Study Material</h2></div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-title">Study Material Uploaded by Teachers</div>
    <table class="tbl">
        <tr><th>Title</th><th>Class</th><th>Subject</th><th>Teacher</th><th>Uploaded</th><th></th></tr>
        <?php if ($materials->num_rows === 0): ?><tr><td colspan="6" class="text-center muted">No study material uploaded yet.</td></tr>
        <?php else: while ($m = $materials->fetch_assoc()): ?>
            <tr>
                <td><?= e($m['title']) ?></td><td><?= e($m['cname'].'-'.$m['section']) ?></td><td><?= e($m['sname']) ?></td>
                <td><?= e($m['tname']) ?></td><td><?= fmt_date($m['uploaded_at']) ?></td>
                <td><a href="?del=<?= $m['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this material?')">Remove</a></td>
            </tr>
        <?php endwhile; endif; ?>
    </table>
</div>

<div class="card">
    <div class="card-title">Assignments</div>
    <table class="tbl">
        <tr><th>Title</th><th>Class</th><th>Subject</th><th>Teacher</th><th>Due Date</th><th>Submissions</th><th></th></tr>
        <?php if ($assignments->num_rows === 0): ?><tr><td colspan="7" class="text-center muted">No assignments posted yet.</td></tr>
        <?php else: while ($a = $assignments->fetch_assoc()): ?>
            <tr>
                <td><?= e($a['title']) ?></td><td><?= e($a['cname'].'-'.$a['section']) ?></td><td><?= e($a['sname']) ?></td>
                <td><?= e($a['tname']) ?></td><td><?= fmt_date($a['due_date']) ?></td>
                <td><?= $a['sub_count'] ?> / <?= $a['class_count'] ?></td>
                <td><a href="?del_a=<?= $a['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this assignment?')">Remove</a></td>
            </tr>
        <?php endwhile; endif; ?>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
