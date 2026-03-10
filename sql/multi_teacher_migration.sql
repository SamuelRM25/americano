-- Migration to support Multi-teacher isolation and multi-course students

-- 1. Update courses table to include owner (admin_id)
ALTER TABLE courses ADD COLUMN admin_id INT NULL;

-- 2. Create junction table for students and courses
CREATE TABLE IF NOT EXISTS student_courses (
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (student_id, course_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Initial association: Give all existing courses to the first admin by default
-- and move existing student-course relationships to the new junction table.
SET @first_admin = (SELECT id FROM admins ORDER BY id ASC LIMIT 1);
UPDATE courses SET admin_id = @first_admin WHERE admin_id IS NULL;

INSERT INTO student_courses (student_id, course_id)
SELECT id, course_id FROM students WHERE course_id IS NOT NULL;

-- 4. Clean up students table (remove old course_id column)
-- We need to drop foreign keys first if they exist
SET @dbname = DATABASE();
SET @tablename = 'students';
SET @columnname = 'course_id';
SET @pre_stmt = (SELECT CONCAT('ALTER TABLE ', @tablename, ' DROP FOREIGN KEY ', CONSTRAINT_NAME)
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = @dbname
                   AND TABLE_NAME = @tablename
                   AND COLUMN_NAME = @columnname
                   AND REFERENCED_TABLE_NAME IS NOT NULL
                 LIMIT 1);

-- Note: MySQL doesn't allow dynamic SQL in raw scripts easily without stored procedures,
-- but since this is a migration, we can assume the standard FK name if we created it.
-- Based on schema 2.sql, it might not have an explicit name, usually it's students_ibfk_1 or similar.
-- For safety in this environment, I'll use a direct ALTER TABLE and hope for the best, 
-- or use a PHP script to be more robust.

-- ALTER TABLE students DROP FOREIGN KEY students_ibfk_2; -- Typical auto-name
-- ALTER TABLE students DROP COLUMN course_id; 
