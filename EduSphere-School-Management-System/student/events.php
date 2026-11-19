<?php
require_once '../config/db.php';
require_once '../config/functions.php';
require_role(['student']);

$pageTitle = 'Events';
$activeMenu = 'events.php';
$events = $conn->query("SELECT * FROM events WHERE audience IN ('All','Students') ORDER BY event_date DESC");

include '../includes/header.php';
?>
<div class="page-head"><h2>Events &amp; Announcements</h2></div>
<div class="card">
    <table class="tbl">
        <tr><th>Title</th><th>Description</th><th>Date</th></tr>
        <?php if ($events->num_rows === 0): ?><tr><td colspan="3" class="text-center muted">No events yet.</td></tr>
        <?php else: while ($ev = $events->fetch_assoc()): ?>
            <tr><td><?= e($ev['title']) ?></td><td><?= e($ev['description']) ?></td><td><?= fmt_date($ev['event_date']) ?></td></tr>
        <?php endwhile; endif; ?>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
