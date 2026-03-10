<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? '';

    if ($action === 'check_registration') {
        $registered = false;
        if (isset($_SESSION['student_id'])) {
            $stmt = $pdo->prepare('SELECT id FROM student_credentials WHERE student_id = ?');
            $stmt->execute([$_SESSION['student_id']]);
            $registered = (bool) $stmt->fetch();
        } elseif (isset($_SESSION['admin_id'])) {
            $stmt = $pdo->prepare('SELECT id FROM admin_credentials WHERE admin_id = ?');
            $stmt->execute([$_SESSION['admin_id']]);
            $registered = (bool) $stmt->fetch();
        }

        echo json_encode(['registered' => $registered]);
        exit;
    }

    if ($action === 'register') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['credential_id'])) {
            if (isset($_SESSION['student_id'])) {
                $stmt = $pdo->prepare('INSERT INTO student_credentials (student_id, credential_id, public_key) VALUES (?, ?, ?)');
                $stmt->execute([$_SESSION['student_id'], $data['credential_id'], $data['public_key']]);
                echo json_encode(['success' => true]);
            } elseif (isset($_SESSION['admin_id'])) {
                $stmt = $pdo->prepare('INSERT INTO admin_credentials (admin_id, credential_id, public_key) VALUES (?, ?, ?)');
                $stmt->execute([$_SESSION['admin_id'], $data['credential_id'], $data['public_key']]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'No session']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid data']);
        }
        exit;
    }

    if ($action === 'login') {
        $data = json_decode(file_get_contents('php://input'), true);
        $credential_id = $data['credential_id'] ?? '';
        $type = $data['type'] ?? 'student'; // 'student' or 'admin'

        if ($type === 'student') {
            $stmt = $pdo->prepare('SELECT s.*, g.name as grade_name FROM students s JOIN student_credentials sc ON s.id = sc.student_id JOIN grades g ON s.grade_id = g.id WHERE sc.credential_id = ?');
            $stmt->execute([$credential_id]);
            $student = $stmt->fetch();

            if ($student) {
                $_SESSION['student_id'] = $student['id'];
                $_SESSION['student_name'] = $student['name'];
                $_SESSION['grade_id'] = $student['grade_id'];
                $_SESSION['grade_name'] = $student['grade_name'];
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Biometría no reconocida']);
            }
        } else {
            $stmt = $pdo->prepare('SELECT a.* FROM admins a JOIN admin_credentials ac ON a.id = ac.admin_id WHERE ac.credential_id = ?');
            $stmt->execute([$credential_id]);
            $admin = $stmt->fetch();

            if ($admin) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Biometría no reconocida']);
            }
        }
        exit;
    }

    echo json_encode(['error' => 'Acción no permitida']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
