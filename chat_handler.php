<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

$is_admin = isset($_SESSION['admin_id']);
$is_student = isset($_SESSION['student_id']);

if (!$is_admin && !$is_student) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'get_messages') {
        $student_id = $_GET['student_id'] ?? null;
        $admin_id = $_GET['admin_id'] ?? null;

        if ($is_student) {
            $student_id = $_SESSION['student_id'];
        }
        if ($is_admin) {
            $admin_id = $_SESSION['admin_id'];
        }

        if (!$student_id || !$admin_id) {
            echo json_encode(['error' => 'Missing student_id or admin_id']);
            exit;
        }

        try {
            // Mark messages as read
            $mark_read = $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE student_id = ? AND admin_id = ? AND sender_type = ?");
            $mark_read->execute([$student_id, $admin_id, $is_admin ? 'student' : 'admin']);

            // Fetch messages
            $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE student_id = ? AND admin_id = ? ORDER BY created_at ASC");
            $stmt->execute([$student_id, $admin_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($messages);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $message = $data['message'] ?? '';
    $student_id = $data['student_id'] ?? null;
    $admin_id = $data['admin_id'] ?? null;

    if ($is_student) {
        $student_id = $_SESSION['student_id'];
    }
    if ($is_admin) {
        $admin_id = $_SESSION['admin_id'];
    }

    if (empty($message) || !$student_id || !$admin_id) {
        echo json_encode(['error' => 'Missing message, student_id or admin_id']);
        exit;
    }

    try {
        $sender_type = $is_admin ? 'admin' : 'student';
        $sender_id = $is_admin ? $_SESSION['admin_id'] : $_SESSION['student_id'];

        $stmt = $pdo->prepare("INSERT INTO chat_messages (sender_type, sender_id, student_id, admin_id, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$sender_type, $sender_id, $student_id, $admin_id, $message]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
