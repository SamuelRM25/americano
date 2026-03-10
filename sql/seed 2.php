<?php
require_once 'config/database.php';

// Seed students for testing
$students = [
    ['Juan Perez', 'EST-2026-001', 1, 1], // Primero Básico, Tecnologías (Meca)
    ['Maria Lopez', 'EST-2026-002', 2, 1], // Segundo Básico, Tecnologías (Meca)
    ['Carlos Ruiz', 'EST-2026-003', 3, 2], // Tercero Básico, Tecnologías
];

try {
    $pdo->beginTransaction();

    foreach ($students as $studentData) {
        $stmt = $pdo->prepare("INSERT INTO students (name, code, grade_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name)");
        $stmt->execute([$studentData[0], $studentData[1], $studentData[2]]);

        $studentId = $pdo->lastInsertId();
        if (!$studentId) {
            $stmt = $pdo->prepare("SELECT id FROM students WHERE code = ?");
            $stmt->execute([$studentData[1]]);
            $studentId = $stmt->fetchColumn();
        }

        $courseId = $studentData[3];
        $stmt = $pdo->prepare("INSERT IGNORE INTO student_courses (student_id, course_id) VALUES (?, ?)");
        $stmt->execute([$studentId, $courseId]);
    }

    // Add a sample assignment
    $stmt = $pdo->prepare("INSERT INTO assignments (title, description, due_date, grade_id, course_id) VALUES 
        ('Tarea 1: Conceptos Básicos', 'Investigar sobre los componentes de una computadora y subir un PDF.', DATE_ADD(NOW(), INTERVAL 7 DAY), 1, 1)");
    $stmt->execute();

    $pdo->commit();
    echo "Seeding completed successfully.\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error seeding: " . $e->getMessage() . "\n";
}
