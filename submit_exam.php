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
$stmt = $pdo->prepare('SELECT id, question_type, points, correct_answers FROM exam_questions WHERE exam_id = ?');
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll();

try {
    $pdo->beginTransaction();

    $responses_data = [];
    $individual_scores = [];
    $total_score = 0;

    foreach ($questions as $q) {
        $q_id = $q['id'];
        $type = $q['question_type'];
        $response = '';

        if ($type === 'file_upload') {
            if (isset($_FILES['q_' . $q_id]) && $_FILES['q_' . $q_id]['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/exams/';
                if (!is_dir($upload_dir))
                    mkdir($upload_dir, 0777, true);

                $original_name = pathinfo($_FILES['q_' . $q_id]['name'], PATHINFO_FILENAME);
                $extension = pathinfo($_FILES['q_' . $q_id]['name'], PATHINFO_EXTENSION);
                
                // Sanitize filename: remove special chars, replace spaces/dots with _, avoid double __
                $clean_name = preg_replace('/[^a-zA-Z0-9]/', '_', $original_name);
                $clean_name = preg_replace('/_+/', '_', $clean_name);
                $clean_name = trim($clean_name, '_');
                
                $file_name = time() . '_' . $clean_name . '.' . $extension;
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

        // Auto-grading logic
        $correct_answers_arr = json_decode($q['correct_answers'] ?? '[]', true);
        if (!empty($correct_answers_arr) && $type !== 'paragraph' && $type !== 'file_upload') {
            $student_correct = false;
            if ($type === 'text') {
                if (trim(mb_strtolower($response)) === trim(mb_strtolower($correct_answers_arr[0]))) {
                    $student_correct = true;
                }
            } elseif ($type === 'multiple_choice' || $type === 'true_false') {
                if (trim(mb_strtolower($response)) === trim(mb_strtolower($correct_answers_arr[0]))) {
                    $student_correct = true;
                }
            } elseif ($type === 'checkbox') {
                $student_arr = array_map('trim', explode(',', $response));
                $correct_arr = array_map('trim', $correct_answers_arr);
                sort($student_arr);
                sort($correct_arr);
                if ($student_arr === $correct_arr && !empty(array_filter($student_arr))) {
                    $student_correct = true;
                }
            } elseif ($type === 'matching') {
                $student_parts = array_map('trim', explode('|', $response));
                $correct_parts = array_map('trim', array_values($correct_answers_arr));
                if ($student_parts === $correct_parts && !empty(array_filter($student_parts))) {
                    $student_correct = true;
                }
            }
            
            $earned = $student_correct ? floatval($q['points']) : 0;
            $individual_scores[$q_id] = $earned;
            $total_score += $earned;
        }
    }

    $ins = $pdo->prepare('INSERT INTO exam_responses (exam_id, student_id, responses, individual_scores, score) VALUES (?, ?, ?, ?, ?)');
    $ins->execute([$exam_id, $student_id, json_encode($responses_data), json_encode($individual_scores), $total_score]);
    $pdo->commit();
    header('Location: exams.php?msg=success');
} catch (Exception $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    header('Location: exams.php?msg=error&detail=' . urlencode($e->getMessage()));
}
?>