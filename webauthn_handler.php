<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'check_registration') {
    if (!isset($_SESSION['student_id'])) {
        echo json_encode(['registered' => false, 'error' => 'No session']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id FROM student_credentials WHERE student_id = ?');
    $stmt->execute([$_SESSION['student_id']]);
    $registered = (bool) $stmt->fetch();

    echo json_encode(['registered' => $registered]);
    exit;
}

if ($action === 'register') {
    // In a real WebAuthn flow, this would involve verifying the attestation.
    // Here we simulate the successful registration for the premium experience.
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['credential_id']) && isset($_SESSION['student_id'])) {
        $stmt = $pdo->prepare('INSERT INTO student_credentials (student_id, credential_id, public_key) VALUES (?, ?, ?)');
        $stmt->execute([
            $_SESSION['student_id'],
            $data['credential_id'],
            $data['public_key']
        ]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
    }
    exit;
}

if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $credential_id = $data['credential_id'] ?? '';

    $stmt = $pdo->prepare('SELECT students.* FROM students 
                           JOIN student_credentials ON students.id = student_credentials.student_id 
                           WHERE student_credentials.credential_id = ?');
    $stmt->execute([$credential_id]);
    $student = $stmt->fetch();

    if ($student) {
        $_SESSION['student_id'] = $student['id'];
        $_SESSION['student_name'] = $student['nombre'];
        $_SESSION['grade_id'] = $student['grade_id'];
        $_SESSION['course_id'] = $student['course_id'];

        // Fetch grade and course names
        $stmt = $pdo->prepare('SELECT nombre FROM grades WHERE id = ?');
        $stmt->execute([$student['grade_id']]);
        $_SESSION['grade_name'] = $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT nombre FROM courses WHERE id = ?');
        $stmt->execute([$student['course_id']]);
        $_SESSION['course_name'] = $stmt->fetchColumn();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Biometría no reconocida']);
    }
    exit;
}

echo json_encode(['error' => 'Acción no permitida']);
