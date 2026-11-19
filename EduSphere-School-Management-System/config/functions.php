<?php
/**
 * Core helper functions: session, auth, role guard, CSRF, flash messages.
 * This file must be included AFTER db.php on every protected page.
 */

// ---- Production-safe error handling ----
// Still catch every bug (so real problems are never silently ignored),
// but never print raw PHP warnings/notices in the middle of a page.
// Set APP_DEBUG to true only while actively developing on your own machine.
define('APP_DEBUG', false);
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../php_errors.log');

// Catch-all: if anything unexpected ever slips through, show a calm
// friendly page instead of a raw PHP error trace, while still logging
// the real error to php_errors.log for debugging.
set_exception_handler(function ($e) {
    error_log('Uncaught exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) http_response_code(500);
    echo '<div style="font-family:sans-serif;max-width:600px;margin:60px auto;padding:24px;
        border:1px solid #E3E9F2;border-radius:12px;">
        <h2 style="color:#10294A;">Something went wrong</h2>
        <p style="color:#5B6B7F;">Please go back and try again. If this keeps happening, contact the admin.</p>
        </div>';
    exit;
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_URL', '/');

/** Redirect helper */
function redirect($path) {
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}

/** Escape output safely */
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/** Is a user logged in? */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/** Get logged-in user's role name */
function current_role() {
    return $_SESSION['role'] ?? null;
}

/** Require login, optionally restrict to a list of roles. Redirects otherwise. */
function require_role($allowed_roles = []) {
    if (!is_logged_in()) {
        redirect('index.php');
    }
    if (!empty($allowed_roles) && !in_array(current_role(), $allowed_roles, true)) {
        redirect(current_role() . '/dashboard.php');
    }
}

/** CSRF token helpers */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}
function verify_csrf() {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        die('Security check failed. Please go back and try again.');
    }
}

/** Flash messages (one-time success/error banners) */
function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
function get_flash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/** Prefix for linking back to root from a role sub-folder (admin/, teacher/, etc.) */
function root() { return '../'; }

/** Format a date nicely */
function fmt_date($date) {
    if (!$date) return '-';
    return date('d M Y', strtotime($date));
}

/** Format money */
function fmt_money($amount) {
    return 'Rs. ' . number_format((float)$amount, 0);
}

/** Month name from number */
function month_name($m) {
    return date('F', mktime(0, 0, 0, (int)$m, 1));
}

/** Get logged-in student's own "students" row id, if the current user is a student */
function get_student_id($conn) {
    $uid = $_SESSION['user_id'];
    $stmt = $conn->prepare('SELECT id FROM students WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res['id'] ?? null;
}

/** Get logged-in staff's own "staff" row id, if the current user is staff (teacher/accountant) */
function get_staff_id($conn) {
    $uid = $_SESSION['user_id'];
    $stmt = $conn->prepare('SELECT id FROM staff WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res['id'] ?? null;
}

/** Get all children (students array) linked to the logged-in parent */
function get_parent_children($conn) {
    $uid = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT s.id, s.roll_no, u.name, c.name cname, c.section, s.class_id
        FROM students s JOIN users u ON s.user_id=u.id JOIN classes c ON s.class_id=c.id
        WHERE s.parent_user_id = ? ORDER BY u.name");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($r = $res->fetch_assoc()) $out[] = $r;
    return $out;
}

/** Validate that a given student id belongs to the logged-in parent; dies if not */
function verify_parent_child($conn, $childId, $childrenArr) {
    foreach ($childrenArr as $c) if ($c['id'] == $childId) return true;
    die('Not authorized to view this student.');
}

/**
 * Show a clean, styled stop page (for "not found" / "not authorized" cases)
 * and halt execution. Used instead of a bare die('text') so the person
 * never sees raw unstyled text.
 */
function stop_page($title, $message, $code = 404) {
    http_response_code($code);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>' . e($title) . ' | EduSphere</title>
        <link rel="stylesheet" href="' . root() . 'assets/css/style.css"></head>
        <body style="background:var(--bg);">
        <div style="max-width:480px;margin:80px auto;text-align:center;padding:0 20px;">
            <div style="font-size:52px;margin-bottom:10px;">&#128274;</div>
            <h2 style="color:var(--navy);margin-bottom:8px;">' . e($title) . '</h2>
            <p class="muted" style="margin-bottom:24px;">' . e($message) . '</p>
            <a href="javascript:history.back()" class="btn btn-primary">&larr; Go Back</a>
        </div></body></html>';
    exit;
}

/** Attendance percentage for a student between two dates (inclusive) */
function attendance_percent($conn, $student_id, $from, $to) {
    $stmt = $conn->prepare("SELECT
            SUM(status IN ('Present','Late')) AS present_days,
            COUNT(*) AS total_days
        FROM student_attendance WHERE student_id = ? AND att_date BETWEEN ? AND ?");
    $stmt->bind_param('iss', $student_id, $from, $to);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if (!$r || $r['total_days'] == 0) return null;
    return round(($r['present_days'] / $r['total_days']) * 100, 1);
}
