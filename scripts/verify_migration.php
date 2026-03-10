<?php
require_once '../config/database.php';

echo "Verifying migration results...\n";

try {
    // Check courses.admin_id
    $courses = $pdo->query("SHOW COLUMNS FROM courses LIKE 'admin_id'")->fetch();
    echo "Courses admin_id: " . ($courses ? "EXISTS" : "MISSING") . "\n";

    // Check student_courses table
    $tables = $pdo->query("SHOW TABLES LIKE 'student_courses'")->fetch();
    echo "student_courses table: " . ($tables ? "EXISTS" : "MISSING") . "\n";

    // Check students.course_id
    $students = $pdo->query("SHOW COLUMNS FROM students LIKE 'course_id'")->fetch();
    echo "Students course_id: " . ($students ? "STILL EXISTS" : "GONE") . "\n";

    // Check data
    $scCount = $pdo->query("SELECT COUNT(*) FROM student_courses")->fetchColumn();
    echo "student_courses count: $scCount\n";

} catch (Exception $e) {
    echo "Verification failed: " . $e->getMessage() . "\n";
}
