<?php
require_once '../config/database.php';

echo "Starting migration...\n";

try {
    $pdo->beginTransaction();

    // 1. Add admin_id to courses
    echo "Checking for admin_id in courses table...\n";
    $result = $pdo->query("SHOW COLUMNS FROM courses LIKE 'admin_id'")->fetch();
    if (!$result) {
        echo "Adding admin_id to courses table...\n";
        $pdo->exec("ALTER TABLE courses ADD COLUMN admin_id INT NULL");
    } else {
        echo "admin_id already exists.\n";
    }

    // 2. Create student_courses table
    echo "Creating student_courses junction table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS student_courses (
        student_id INT NOT NULL,
        course_id INT NOT NULL,
        enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (student_id, course_id),
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 3. Migrate data
    $firstAdmin = $pdo->query("SELECT id FROM admins ORDER BY id ASC LIMIT 1")->fetchColumn();
    if ($firstAdmin) {
        echo "Assigning existing courses to first admin (ID: $firstAdmin)...\n";
        $stmt = $pdo->prepare("UPDATE courses SET admin_id = ? WHERE admin_id IS NULL");
        $stmt->execute([$firstAdmin]);
    }

    echo "Migrating student course associations...\n";
    $pdo->exec("INSERT IGNORE INTO student_courses (student_id, course_id)
                SELECT id, course_id FROM students WHERE course_id != 0 AND course_id IS NOT NULL");

    // 4. Drop course_id from students (checking if it exists first)
    echo "Cleaning up students table...\n";
    // We try to find the FK name
    $fkQuery = $pdo->prepare("SELECT CONSTRAINT_NAME 
                              FROM information_schema.KEY_COLUMN_USAGE 
                              WHERE TABLE_SCHEMA = DATABASE() 
                              AND TABLE_NAME = 'students' 
                              AND COLUMN_NAME = 'course_id' 
                              AND REFERENCED_TABLE_NAME IS NOT NULL");
    $fkQuery->execute();
    $fk = $fkQuery->fetchColumn();

    if ($fk) {
        echo "Dropping foreign key $fk...\n";
        $pdo->exec("ALTER TABLE students DROP FOREIGN KEY $fk");
    }

    $pdo->exec("ALTER TABLE students DROP COLUMN course_id");

    $pdo->commit();
    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
}
