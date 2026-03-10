<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['student_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: exams.php');
    exit;
}

$student_id = $_SESSION['student_id'];
$exam_id = $_POST['exam_id'];

// Check if already taken to prevent double submission
$check = $pdo->prepare('SELECT id FROM exam_responses WHERE exam_id = ? AND student_id = ? LIMIT 1');
$check->execute([$exam_id, $student_id]);
if ($check->fetch()) {
    header('Location: exams.php?msg=already_taken');
    exit;
}

// Fetch all questions for this exam to process them
$stmt = $pdo->prepare('SELECT id, question_type FROM exam_questions WHERE exam_id = ?');
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll();

try {
    $pdo->beginTransaction();

    $responses_data = [];

    foreach ($questions as $q) {
        $q_id = $q['id'];
        $type = $q['question_type'];
        $response = '';

        if ($type === 'file_upload') {
            if (isset($_FILES['q_' . $q_id]) && $_FILES['q_' . $q_id]['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/exams/';
                if (!is_dir($upload_dir))
                    mkdir($upload_dir, 0777, true);

                $file_name = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['q_' . $q_id]['name']);
                $target_path = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['q_' . $q_id]['tmp_name'], $target_path)) {
                    $response = $target_path;
                }
            }
        } elseif ($type === 'matching') {
            $matching_parts = [];
            foreach ($_POST as $key => $val) {
                if (strpos($key, 'q_' . $q_id . '_matching_') === 0) {
                    $matching_parts[] = $val;
                }
            }
            $response = implode(' | ', $matching_parts);
        } elseif ($type === 'checkbox') {
            if (isset($_POST['q_' . $q_id])) {
                $response = implode(', ', (array) $_POST['q_' . $q_id]);
            }
        } else {
            $response = $_POST['q_' . $q_id] ?? '';
        }

        $responses_data[$q_id] = $response;
    }

    $ins = $pdo->prepare('INSERT INTO exam_responses (exam_id, student_id, responses) VALUES (?, ?, ?)');
    $ins->execute([$exam_id, $student_id, json_encode($responses_data)]);
    $pdo->commit();
    header('Location: exams.php?msg=success');
} catch (Exception $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    header('Location: exams.php?msg=error&detail=' . urlencode($e->getMessage()));
}
?>