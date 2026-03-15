<?php
require_once __DIR__ . '/../config/database.php';

try {
    echo "Iniciando migración...\n";
    
    // Check if column exists first
    $check = $pdo->query("SHOW COLUMNS FROM exam_responses LIKE 'individual_scores'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE exam_responses ADD COLUMN individual_scores JSON DEFAULT NULL AFTER responses");
        echo "Éxito: Columna 'individual_scores' añadida a 'exam_responses'.\n";
    } else {
        echo "Aviso: La columna 'individual_scores' ya existe.\n";
    }
    
    echo "Migración completada.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
