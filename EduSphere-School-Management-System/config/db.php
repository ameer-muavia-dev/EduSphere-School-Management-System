<?php
/**
 * Database connection
 * Change these 4 values if your MySQL username/password/host is different.
 */
$DB_HOST = 'localhost';
$DB_USER = 'sms_user';
$DB_PASS = 'sms_pass123';
$DB_NAME = 'school_sms';

// PHP 8.1+ makes mysqli throw exceptions by default on connection failure,
// so we wrap this in try/catch to always show a clean, friendly message
// instead of a raw uncaught-exception error page.
try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die('<div style="font-family:sans-serif;max-width:600px;margin:60px auto;padding:24px;
        border:1px solid #E3E9F2;border-radius:12px;">
        <h2 style="color:#10294A;">Database connection failed</h2>
        <p style="color:#5B6B7F;">EduSphere could not connect to the MySQL database. Please check that:</p>
        <ul style="color:#5B6B7F;">
            <li>MySQL / MariaDB is running (start it from the XAMPP Control Panel)</li>
            <li>The <code>school_sms</code> database has been imported (see <code>database/schema.sql</code>)</li>
            <li>The credentials in <code>config/db.php</code> are correct</li>
        </ul>
        </div>');
}
