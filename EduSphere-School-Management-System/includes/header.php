<?php
// Expects $pageTitle and $activeMenu to be set before include.
// Expects require_role() to have already been called on the page.
$role = current_role();

$menus = [
    'admin' => [
        ['dashboard.php', 'Dashboard', '&#128200;'],
        ['students.php', 'Students', '&#127891;'],
        ['staff.php', 'Staff', '&#128100;'],
        ['classes.php', 'Classes & Subjects', '&#127979;'],
        ['attendance.php', 'Attendance', '&#9989;'],
        ['fees.php', 'Fee Management', '&#128176;'],
        ['payroll.php', 'Staff Salary', '&#128179;'],
        ['exams.php', 'Tests & Results', '&#128221;'],
        ['timetable.php', 'Timetable', '&#128197;'],
        ['materials.php', 'LMS & Study Material', '&#128218;'],
        ['events.php', 'Events & Announcements', '&#128227;'],
        ['reports.php', 'Reports', '&#128203;'],
        ['tasks.php', 'Staff Daily Work', '&#128221;'],
    ],
    'teacher' => [
        ['dashboard.php', 'Dashboard', '&#128200;'],
        ['attendance.php', 'Mark Attendance', '&#9989;'],
        ['marks.php', 'Enter Marks', '&#128221;'],
        ['materials.php', 'Study Material', '&#128218;'],
        ['assignments.php', 'Assignments', '&#128203;'],
        ['timetable.php', 'My Timetable', '&#128197;'],
        ['worklog.php', 'Daily Work Log', '&#128203;'],
        ['contact_log.php', 'Parent Contact Log', '&#128222;'],
    ],
    'student' => [
        ['dashboard.php', 'Dashboard', '&#128200;'],
        ['attendance.php', 'My Attendance', '&#9989;'],
        ['results.php', 'My Results', '&#128221;'],
        ['materials.php', 'Study Material', '&#128218;'],
        ['assignments.php', 'Assignments', '&#128203;'],
        ['timetable.php', 'Timetable', '&#128197;'],
        ['events.php', 'Events', '&#128227;'],
    ],
    'parent' => [
        ['dashboard.php', 'Dashboard', '&#128200;'],
        ['attendance.php', 'Attendance Report', '&#9989;'],
        ['results.php', 'Results', '&#128221;'],
        ['fees.php', 'Fee Status', '&#128176;'],
        ['reports.php', 'Monthly Report', '&#128203;'],
        ['messages.php', 'Message Teacher', '&#128172;'],
        ['events.php', 'Events', '&#128227;'],
    ],
    'accountant' => [
        ['dashboard.php', 'Dashboard', '&#128200;'],
        ['fees.php', 'Fee Management', '&#128176;'],
        ['vouchers.php', 'Voucher & Receipts', '&#129534;'],
        ['payroll.php', 'Staff Payroll', '&#128179;'],
    ],
];

$roleLabels = ['admin' => 'Administrator', 'teacher' => 'Teacher', 'student' => 'Student', 'parent' => 'Parent', 'accountant' => 'Accountant'];
$initials = '';
foreach (explode(' ', $_SESSION['name'] ?? '') as $w) { $initials .= strtoupper(substr($w, 0, 1)); }
$initials = substr($initials, 0, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'EduSphere') ?> | EduSphere</title>
<link rel="stylesheet" href="<?= root() ?>assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">Edu<span>Sphere</span></div>
        <div class="role-tag"><?= e($roleLabels[$role] ?? $role) ?> Panel</div>
        <nav>
            <?php foreach ($menus[$role] as $item): ?>
                <a href="<?= e($item[0]) ?>" class="<?= ($activeMenu ?? '') === $item[0] ? 'active' : '' ?>">
                    <span class="icon"><?= $item[2] ?></span> <?= e($item[1]) ?>
                </a>
            <?php endforeach; ?>
            <a href="<?= root() ?>logout.php" style="margin-top:12px;border-top:1px solid rgba(255,255,255,0.12);padding-top:16px;">
                <span class="icon">&#128274;</span> Logout
            </a>
        </nav>
    </aside>
    <div class="main">
        <div class="topbar no-print">
            <h1><?= e($pageTitle ?? '') ?></h1>
            <div class="user-chip">
                <span><?= e($_SESSION['name'] ?? '') ?></span>
                <div class="avatar"><?= e($initials) ?></div>
            </div>
        </div>
        <div class="content">
            <?php $flash = get_flash(); if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>
