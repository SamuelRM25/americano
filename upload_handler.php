<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['student_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$student_id = $_SESSION['student_id'];
$assignment_id = $_POST['assignment_id'];

if (isset($_FILES['submission']) && $_FILES['submission']['error'] === UPLOAD_ERR_OK) {
    $file_tmp = $_FILES['submission']['tmp_name'];
    $file_name = $_FILES['submission']['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if (in_array($file_ext, $allowed_exts)) {
        $new_filename = uniqid('sub_') . '_' . $student_id . '.' . $file_ext;
        $upload_dir = 'uploads/assignments/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $dest_path = $upload_dir . $new_filename;

        if (move_uploaded_at_file($file_tmp, $dest_path)) {
            $stmt = $pdo->prepare('INSERT INTO submissions (assignment_id, student_id, file_path, file_type) VALUES (?, ?, ?, ?)');
            $stmt->execute([$assignment_id, $student_id, $dest_path, $file_ext]);

            header('Location: assignments.php?msg=success');
            exit;
        } else {
            header('Location: assignments.php?msg=error&error=Error+al+guardar+archivo');
            exit;
        }
    } else {
        header('Location: assignments.php?msg=error&error=Tipo+de+archivo+no+permitido');
        exit;
    }
} else {
    header('Location: assignments.php?msg=error&error=Error+en+el+archivo+subido');
    exit;
}
exit;

function move_uploaded_at_file($tmp, $dest)
{
    // Utility wrapper for clarity or in case of paths issues
    return move_uploaded_file($tmp, $dest);
}
