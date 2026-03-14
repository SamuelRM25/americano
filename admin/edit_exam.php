<?php
session_start();
require_once '../config/database.php';
require_once 'includes/sidebar.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$exam_id = $_GET['id'] ?? null;
if (!$exam_id) {
    header('Location: exams.php');
    exit;
}

$success = '';
$error = '';

// Fetch Exam
$stmt = $pdo->prepare('SELECT e.*, g.name as grade_name, c.name as course_name FROM exams e JOIN grades g ON e.grade_id = g.id JOIN courses c ON e.course_id = c.id WHERE e.id = ?');
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

if (!$exam) {
    header('Location: exams.php');
    exit;
}

// Handle Exam update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_exam'])) {
    $title = $_POST['title'] ?? '';
    $desc = $_POST['description'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $grade_id = $_POST['grade_id'] ?? '';
    $course_id = $_POST['course_id'] ?? '';
    $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
    $duration = !empty($_POST['duration_minutes']) ? (int)$_POST['duration_minutes'] : null;

    if (!empty($title) && !empty($due_date)) {
        try {
            $stmt = $pdo->prepare('UPDATE exams SET title = ?, description = ?, due_date = ?, grade_id = ?, course_id = ?, start_time = ?, duration_minutes = ? WHERE id = ?');
            $stmt->execute([$title, $desc, $due_date, $grade_id, $course_id, $start_time, $duration, $exam_id]);
            $success = "Examen actualizado correctamente.";
            // Refresh exam data
            $stmt = $pdo->prepare('SELECT e.*, g.name as grade_name, c.name as course_name FROM exams e JOIN grades g ON e.grade_id = g.id JOIN courses c ON e.course_id = c.id WHERE e.id = ?');
            $stmt->execute([$exam_id]);
            $exam = $stmt->fetch();
        } catch (Exception $e) {
            $error = "Error al actualizar examen: " . $e->getMessage();
        }
    }
}

// Handle Question Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $text = $_POST['question_text'];
    $type = $_POST['question_type'];
    $points = $_POST['points'] ?? 1;
    $series = !empty($_POST['series']) ? trim($_POST['series']) : null;
    
    // Process options and correct answers dynamically
    $options = null;
    $correct_answers = null;

    if ($type === 'matching' && !empty($_POST['matching_concept']) && !empty($_POST['matching_definition'])) {
        $concepts = $_POST['matching_concept'];
        $definitions = $_POST['matching_definition'];
        $pairs = [];
        for ($i = 0; $i < count($concepts); $i++) {
            if (trim($concepts[$i]) !== '' && trim($definitions[$i]) !== '') {
                $pairs[trim($concepts[$i])] = trim($definitions[$i]);
            }
        }
        $options = json_encode(array_keys($pairs)); // Concepts
        $correct_answers = json_encode($pairs);     // Correct mappings
    } elseif ($type === 'multiple_choice' || $type === 'checkbox') {
        if (!empty($_POST['dynamic_options'])) {
            $opts = array_filter(array_map('trim', $_POST['dynamic_options']));
            $options = json_encode(array_values($opts));
            
            // Collect correct indices or values (assuming input names matching `correct_opt[]`)
            if (!empty($_POST['correct_opt'])) {
                // If it's a single value (radio), it might not be an array, but we force array for uniform JSON
                $corrects = is_array($_POST['correct_opt']) ? $_POST['correct_opt'] : [$_POST['correct_opt']];
                $correct_answers = json_encode(array_map('trim', $corrects));
            }
        }
    } elseif ($type === 'true_false') {
        $options = json_encode(['Verdadero', 'Falso']);
        if (!empty($_POST['correct_tf'])) {
            $correct_answers = json_encode([trim($_POST['correct_tf'])]);
        }
    } else {
        // Fallback or text/file_upload type: generic options if any (old method parsing)
        $options = !empty($_POST['options']) ? json_encode(array_map('trim', explode("\n", trim($_POST['options'])))) : null;
        if (!empty($_POST['correct_text'])) {
            $correct_answers = json_encode([trim($_POST['correct_text'])]);
        }
    }

    if (empty($series)) {
        // Auto-assign the most recently used series for this exam
        $lastSeriesStmt = $pdo->prepare('SELECT series FROM exam_questions WHERE exam_id = ? AND series IS NOT NULL ORDER BY id DESC LIMIT 1');
        $lastSeriesStmt->execute([$exam_id]);
        $lastSeries = $lastSeriesStmt->fetchColumn();
        if ($lastSeries !== false) {
            $series = $lastSeries;
        }
    }

    try {
        if (isset($_POST['question_id']) && !empty($_POST['question_id'])) {
            $stmt = $pdo->prepare('UPDATE exam_questions SET question_text = ?, question_type = ?, options = ?, correct_answers = ?, points = ?, series = ? WHERE id = ? AND exam_id = ?');
            $stmt->execute([$text, $type, $options, $correct_answers, $points, $series, $_POST['question_id'], $exam_id]);
            $success = "Pregunta actualizada correctamente.";
        } else {
            $stmt = $pdo->prepare('INSERT INTO exam_questions (exam_id, question_text, question_type, options, correct_answers, points, series) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$exam_id, $text, $type, $options, $correct_answers, $points, $series]);
            $success = "Pregunta añadida correctamente.";
        }
    } catch (Exception $e) {
        $error = "Error al procesar pregunta: " . $e->getMessage();
    }
}

// Handle Question Deletion
if (isset($_GET['delete_question'])) {
    $stmt = $pdo->prepare('DELETE FROM exam_questions WHERE id = ? AND exam_id = ?');
    $stmt->execute([$_GET['delete_question'], $exam_id]);
    header("Location: edit_exam.php?id=$exam_id&msg=q_deleted");
    exit;
}

if (isset($_GET['msg']) && $_GET['msg'] === 'q_deleted') {
    $success = "Pregunta eliminada.";
}

$questions = $pdo->prepare('SELECT * FROM exam_questions WHERE exam_id = ? ORDER BY COALESCE(series, \'zzzz\'), id ASC');
$questions->execute([$exam_id]);
$questions = $questions->fetchAll();

$grades = $pdo->query('SELECT * FROM grades')->fetchAll();
$courses = $pdo->query('SELECT * FROM courses')->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Examen - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc', 400: '#38bdf8',
                            500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1', 800: '#075985', 900: '#0c4a6e',
                        },
                        accent: {
                            50: '#f5f3ff', 100: '#ede9fe', 200: '#ddd6fe', 300: '#c4b5fd', 400: '#a78bfa',
                            500: '#8b5cf6', 600: '#7c3aed', 700: '#6d28d9', 800: '#5b21b6', 900: '#4c1d95',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out forwards',
                        'slide-up': 'slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { opacity: '0', transform: 'translateY(20px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }
    </style>
</head>

<body class="bg-[#f8fafc] h-screen flex overflow-hidden selection:bg-primary-500 selection:text-white">

    <?php render_admin_sidebar('exams'); ?>

    <main class="flex-1 min-h-0 overflow-y-auto bg-[#fafbfc] animate-fade-in custom-scrollbar">
        <div class="p-6 lg:p-12 max-w-7xl mx-auto">
            <nav class="mb-8 lg:mb-12">
                <a href="exams.php"
                    class="inline-flex items-center text-slate-400 hover:text-primary-600 font-bold uppercase tracking-widest text-xs transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Volver a Exámenes
                </a>
            </nav>

            <header class="mb-12">
                <h2 class="text-xs font-black text-primary-600 uppercase tracking-[0.3em] mb-2 leading-none">Módulo de
                    Examen</h2>
                <h1 class="text-5xl font-black text-slate-950 tracking-tighter italic">Editar <span
                        class="text-primary-500">
                        <?= $exam['title'] ?>
                    </span></h1>
            </header>

            <?php if ($success): ?>
                <div
                    class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-6 py-4 rounded-3xl text-sm font-bold mb-8 flex items-center animate-slide-up shadow-sm">
                    <i data-lucide="check-circle" class="w-5 h-5 mr-3"></i>
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div
                    class="bg-rose-50 border border-rose-100 text-rose-700 px-6 py-4 rounded-3xl text-sm font-bold mb-8 flex items-center animate-shake">
                    <i data-lucide="alert-circle" class="w-5 h-5 mr-3"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <!-- Exam Settings -->
                <div class="lg:col-span-1 border-b lg:border-none pb-8 lg:pb-0 mb-8 lg:mb-0 lg:sticky lg:top-12 self-start z-10">
                    <div
                        class="bg-white rounded-3xl lg:rounded-[3.5rem] p-6 lg:p-10 shadow-xl shadow-slate-200/50 border border-slate-100 animate-slide-up">
                        <h3 class="text-xl font-black text-slate-900 mb-8 flex items-center">
                            <i data-lucide="settings" class="w-5 h-5 mr-3 text-primary-500"></i> Configuración
                        </h3>
                        <form action="" method="POST" class="space-y-6">
                            <input type="hidden" name="update_exam" value="1">
                            <div class="space-y-2">
                                <label
                                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Título
                                    del Examen</label>
                                <input type="text" name="title" value="<?= $exam['title'] ?>" required
                                    class="w-full px-6 py-4 md:px-8 md:py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-2xl md:rounded-[2rem] outline-none focus:border-primary-500 font-bold transition-all text-sm tracking-tight text-slate-900">
                            </div>
                            <div class="space-y-2">
                                <label
                                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Instrucciones</label>
                                <textarea name="description"
                                    class="w-full px-6 py-4 md:px-8 md:py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-2xl md:rounded-[2rem] outline-none focus:border-primary-500 font-bold h-32 resize-none transition-all text-sm text-slate-600"><?= $exam['description'] ?></textarea>
                            </div>
                            <div class="space-y-2">
                                <label
                                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Grado</label>
                                <select name="grade_id" required
                                    class="w-full px-6 py-4 md:px-8 md:py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-2xl md:rounded-[2rem] outline-none focus:border-primary-500 font-black text-xs uppercase tracking-widest appearance-none">
                                    <?php foreach ($grades as $g): ?>
                                        <option value="<?= $g['id'] ?>" <?= $exam['grade_id'] == $g['id'] ? 'selected' : '' ?>>
                                            <?= $g['name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label
                                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Curso</label>
                                <select name="course_id" required
                                    class="w-full px-6 py-4 md:px-8 md:py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-2xl md:rounded-[2rem] outline-none focus:border-primary-500 font-black text-xs uppercase tracking-widest appearance-none">
                                    <?php foreach ($courses as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= $exam['course_id'] == $c['id'] ? 'selected' : '' ?>
                                            >
                                            <?= $c['name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label
                                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Fecha &
                                    Hora Límite</label>
                                <input type="datetime-local" name="due_date"
                                    value="<?= date('Y-m-d\TH:i', strtotime($exam['due_date'])) ?>" required
                                    class="w-full px-6 py-4 md:px-8 md:py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-2xl md:rounded-[2rem] outline-none focus:border-primary-500 font-black text-xs uppercase">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">
                                    Hora de Inicio (Acceso Habilitado)
                                </label>
                                <input type="datetime-local" name="start_time"
                                    value="<?= $exam['start_time'] ? date('Y-m-d\TH:i', strtotime($exam['start_time'])) : '' ?>"
                                    class="w-full px-6 py-4 md:px-8 md:py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-2xl md:rounded-[2rem] outline-none focus:border-primary-500 font-black text-xs uppercase">
                                <p class="text-[10px] text-slate-400 ml-4">Opcional. Si se define, el alumno no puede entrar antes de esta hora.</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">
                                    Duración (minutos)
                                </label>
                                <input type="number" name="duration_minutes" min="1" max="360"
                                    value="<?= $exam['duration_minutes'] ?? '' ?>"
                                    placeholder="Ejemplo: 60"
                                    class="w-full px-6 py-4 md:px-8 md:py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-2xl md:rounded-[2rem] outline-none focus:border-primary-500 font-black text-xs text-center">
                                <p class="text-[10px] text-slate-400 ml-4">Opcional. Si se define, el examen se envía automáticamente al terminar el tiempo.</p>
                            </div>
                            <button type="submit"
                                class="w-full bg-slate-950 text-white font-black py-6 rounded-[2rem] hover:bg-primary-600 shadow-xl transition-all uppercase tracking-[0.2em] text-xs">
                                Guardar Cambios
                            </button>
                        </form>
                    </div>
                </div>
                              <!-- Question Management -->
                <div class="lg:col-span-2 space-y-8">
                    <div class="bg-white rounded-3xl lg:rounded-[3.5rem] p-6 lg:p-10 shadow-xl shadow-slate-200/50 border border-slate-100 animate-slide-up">
                        <div class="flex items-center justify-between mb-10">
                            <h3 class="text-xl font-black text-slate-900 flex items-center">
                                <i data-lucide="list-checks" class="w-6 h-6 mr-3 text-accent-500"></i>
                                Banco de Preguntas
                                <span class="ml-3 text-sm font-bold text-slate-400">(<?= count($questions) ?> en total)</span>
                            </h3>
                            <button onclick="openNewQuestionModal()"
                                class="bg-accent-600 text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-accent-500 transition-all shadow-lg active:scale-95 flex items-center">
                                <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Nueva Pregunta
                            </button>
                        </div>

                        <?php if (empty($questions)): ?>
                            <div class="p-12 text-center rounded-[2.5rem] bg-slate-50 border-2 border-dashed border-slate-200 opacity-50">
                                <i data-lucide="clipboard-list" class="w-12 h-12 mx-auto mb-4 text-slate-300"></i>
                                <p class="font-bold text-slate-400 italic">No hay preguntas en este examen todavía.</p>
                                <p class="text-xs text-slate-300 mt-1">Haz clic en "+ Nueva Pregunta" para empezar.</p>
                            </div>
                        <?php else: ?>
                            <?php
                            // Group questions by series
                            $grouped = [];
                            foreach ($questions as $q) {
                                $key = $q['series'] ?? '— Sin Serie';
                                $grouped[$key][] = $q;
                            }
                            $typeLabels = [
                                'text'            => ['label' => 'Respuesta Corta',    'color' => 'bg-sky-100 text-sky-700'],
                                'paragraph'       => ['label' => 'Desarrollo',          'color' => 'bg-indigo-100 text-indigo-700'],
                                'multiple_choice' => ['label' => 'Opción Múltiple',    'color' => 'bg-violet-100 text-violet-700'],
                                'checkbox'        => ['label' => 'Casilla',             'color' => 'bg-purple-100 text-purple-700'],
                                'true_false'      => ['label' => 'V / F',               'color' => 'bg-emerald-100 text-emerald-700'],
                                'matching'        => ['label' => 'Relación',            'color' => 'bg-amber-100 text-amber-700'],
                                'file_upload'     => ['label' => 'Archivo',             'color' => 'bg-rose-100 text-rose-700'],
                            ];
                            $qNum = 0;
                            foreach ($grouped as $seriesName => $seriesQuestions):
                            ?>
                            <div class="mb-10">
                                <!-- Series Header -->
                                <div class="flex items-center gap-4 mb-5">
                                    <div class="h-px flex-1 bg-slate-100"></div>
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] px-4 py-1.5 bg-slate-50 rounded-full border border-slate-100">
                                        <?= htmlspecialchars($seriesName) ?>
                                    </span>
                                    <div class="h-px flex-1 bg-slate-100"></div>
                                </div>

                                <div class="space-y-3">
                                    <?php foreach ($seriesQuestions as $q):
                                        $qNum++;
                                        $tl = $typeLabels[$q['question_type']] ?? ['label' => $q['question_type'], 'color' => 'bg-slate-100 text-slate-500'];
                                        $opts = $q['options'] ? json_decode($q['options'], true) : [];
                                    ?>
                                    <div class="flex items-start gap-5 p-6 bg-slate-50/60 rounded-[2rem] border border-slate-100 hover:border-accent-200 hover:bg-white transition-all group">
                                        <!-- Number badge -->
                                        <div class="w-10 h-10 bg-white rounded-xl shadow-sm flex items-center justify-center font-black text-slate-500 text-sm flex-shrink-0 group-hover:bg-accent-600 group-hover:text-white transition-all">
                                            <?= $qNum ?>
                                        </div>
                                        <!-- Content -->
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-slate-800 text-sm leading-snug mb-2 group-hover:text-accent-600 transition-colors">
                                                <?= htmlspecialchars(substr($q['question_text'], 0, 120)) . (strlen($q['question_text']) > 120 ? '...' : '') ?>
                                            </p>
                                            <div class="flex items-center flex-wrap gap-2">
                                                <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-lg <?= $tl['color'] ?>">
                                                    <?= $tl['label'] ?>
                                                </span>
                                                <span class="text-[9px] font-bold text-slate-400 uppercase">
                                                    <?= $q['points'] ?> pto<?= $q['points'] != 1 ? 's' : '' ?>
                                                </span>
                                                <?php if ($opts): ?>
                                                    <?php foreach (array_slice($opts, 0, 4) as $opt): ?>
                                                        <span class="text-[8px] bg-white border border-slate-200 px-2 py-0.5 rounded-md font-bold text-slate-500">
                                                            <?= htmlspecialchars($opt) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                    <?php if (count($opts) > 4): ?>
                                                        <span class="text-[8px] text-slate-400 font-bold">+<?= count($opts) - 4 ?> más</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <!-- Actions -->
                                        <div class="flex items-center gap-2 mt-1">
                                            <button onclick='openEditModal(<?= json_encode($q) ?>)'
                                                class="p-2 text-slate-300 hover:text-accent-500 transition-colors flex-shrink-0">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </button>
                                            <a href="?id=<?= $exam_id ?>&delete_question=<?= $q['id'] ?>"
                                                onclick="return confirm('¿Eliminar esta pregunta?')"
                                                class="p-2 text-slate-300 hover:text-rose-500 transition-colors flex-shrink-0">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Question Modal -->
    <div id="add-q-modal"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 hidden flex items-center justify-center p-6 overflow-y-auto">
        <div class="bg-white w-full max-w-2xl rounded-[3.5rem] p-12 shadow-2xl relative animate-slide-up my-8">
            <header class="flex justify-between items-center mb-8">
                <div>
                    <h3 id="modal-title" class="text-3xl font-black text-slate-950 tracking-tighter italic">Nueva <span class="text-accent-500">Pregunta</span></h3>
                    <p id="modal-desc" class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">Pega texto para detectar el tipo automáticamente</p>
                </div>
                <button onclick="toggleModal('add-q-modal')"
                    class="p-4 bg-slate-100 text-slate-400 rounded-full hover:bg-slate-950 hover:text-white transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </header>

            <!-- Auto-detect banner -->
            <div id="detect-banner" class="hidden mb-6 px-6 py-3 bg-emerald-50 border border-emerald-100 rounded-2xl flex items-center gap-3 text-sm font-bold text-emerald-700">
                <i data-lucide="sparkles" class="w-4 h-4 flex-shrink-0"></i>
                <span id="detect-text">Tipo detectado automáticamente</span>
            </div>

            <form id="question-form" action="" method="POST" class="space-y-5">
                <input type="hidden" name="add_question" value="1">
                <input type="hidden" name="question_id" id="question_id_input" value="">

                <!-- Question text with paste detection -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Pregunta</label>
                    <textarea id="question_text_input" name="question_text" required rows="3"
                        placeholder="Escribe o pega la pregunta aquí..."
                        class="w-full px-6 py-4 md:px-8 md:py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-2xl md:rounded-[2rem] outline-none focus:border-accent-500 font-bold transition-all text-base tracking-tight resize-none"></textarea>
                </div>

                <!-- Series field -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Serie (opcional)</label>
                    <input type="text" name="series" id="series_input" list="existing-series"
                        placeholder="Ej: Serie A, Sección I, Comprensión Lectora..."
                        class="w-full px-6 py-4 md:px-8 md:py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-2xl md:rounded-[2rem] outline-none focus:border-accent-500 font-bold text-sm transition-all">
                    <datalist id="existing-series">
                        <?php
                        $existingSeries = array_unique(array_filter(array_column($questions, 'series')));
                        foreach ($existingSeries as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <p class="text-[10px] text-slate-400 ml-4">Agrupa preguntas bajo una misma sección o serie del examen.</p>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <!-- Type selector -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Tipo de Reactivo</label>
                        <select id="question_type_select" name="question_type" required onchange="handleTypeChange(this)"
                            class="w-full px-6 py-4 md:px-8 md:py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-2xl md:rounded-[2rem] outline-none focus:border-accent-500 font-black text-xs uppercase tracking-widest">
                            <option value="text">Respuesta Directa (Corta)</option>
                            <option value="paragraph">Desarrollo (Párrafo)</option>
                            <option value="multiple_choice">Opción Múltiple</option>
                            <option value="checkbox">Casilla de Verificación</option>
                            <option value="true_false">Verdadero / Falso</option>
                            <option value="matching">Relación de Columnas</option>
                            <option value="file_upload">Subida de Archivo (Evidencia)</option>
                        </select>
                    </div>
                    <!-- Points -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Puntuación</label>
                        <input type="number" name="points" value="1" min="1" step="0.5"
                            class="w-full px-6 py-4 md:px-8 md:py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-2xl md:rounded-[2rem] outline-none focus:border-accent-500 font-black text-xs text-center">
                    </div>
                </div>

                <!-- Dynamic Options Container -->
                <div id="dynamic-options-wrapper" class="space-y-4"></div>

                <button type="submit" id="submit-btn"
                    class="w-full bg-accent-600 text-white font-black py-6 rounded-[2rem] mt-2 hover:bg-accent-500 shadow-2xl shadow-accent-500/30 transition-all uppercase tracking-[0.2em] text-sm">
                    Añadir al Banco
                </button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function toggleModal(id) {
            const modal = document.getElementById(id);
            modal.classList.toggle('hidden');
            document.body.style.overflow = modal.classList.contains('hidden') ? 'auto' : 'hidden';
            if (modal.classList.contains('hidden')) {
                document.getElementById('question-form').reset();
                document.getElementById('question_id_input').value = '';
                document.getElementById('dynamic-options-wrapper').innerHTML = '';
            }
        }

        function getMatchingRowHtml(concept = '', def = '') {
            return `
                <div class="flex items-center gap-2 md:gap-4 animate-fade-in group">
                    <div class="flex-1">
                        <input type="text" name="matching_concept[]" value="${concept}" placeholder="Concepto" class="w-full px-5 py-3 bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] outline-none focus:border-accent-500 font-bold text-xs" required>
                    </div>
                    <i data-lucide="arrow-right-left" class="w-4 h-4 text-slate-300 flex-shrink-0"></i>
                    <div class="flex-1">
                        <input type="text" name="matching_definition[]" value="${def}" placeholder="Respuesta / Definición" class="w-full px-5 py-3 bg-slate-50 border-2 border-emerald-100 rounded-[1.5rem] outline-none focus:border-emerald-500 font-bold text-xs" required>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="p-2 text-rose-300 hover:text-rose-500 bg-rose-50/50 hover:bg-rose-100 rounded-xl transition-colors"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
            `;
        }
        
        function getChoiceRowHtml(inputType, val = '', isCorrect = false) {
            return `
                <div class="flex items-center gap-4 animate-fade-in">
                    <div class="w-8 flex justify-center">
                        <input type="${inputType}" name="correct_opt[]" value="${val}" ${isCorrect ? 'checked' : ''} class="w-5 h-5 text-accent-600 focus:ring-accent-500 border-slate-300 rounded${inputType === 'radio' ? '-full' : ''}">
                    </div>
                    <input type="text" name="dynamic_options[]" value="${val}" onkeyup="this.previousElementSibling.firstElementChild.value = this.value" placeholder="Opción" class="flex-1 px-5 py-3 bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] outline-none focus:border-accent-500 font-bold text-xs" required>
                    <button type="button" onclick="this.parentElement.remove()" class="p-2 text-rose-300 hover:text-rose-500 bg-rose-50/50 hover:bg-rose-100 rounded-xl transition-colors"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
            `;
        }

        function addMatchingRow() {
            document.getElementById('matching_rows').insertAdjacentHTML('beforeend', getMatchingRowHtml());
            lucide.createIcons();
        }

        function addChoiceRow(inputType) {
            document.getElementById('choice_rows').insertAdjacentHTML('beforeend', getChoiceRowHtml(inputType));
            lucide.createIcons();
        }

        function handleTypeChange(select) {
            const wrapper = document.getElementById('dynamic-options-wrapper');
            wrapper.innerHTML = '';
            
            if (select.value === 'matching') {
                wrapper.innerHTML = `
                    <div class="space-y-4">
                        <div class="flex items-center justify-between ml-4 mr-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Pares Correctos (Concepto <i data-lucide="arrow-right" class="inline w-3 h-3 mx-1"></i> Respuesta)</label>
                        </div>
                        <div id="matching_rows" class="space-y-3">
                            ${getMatchingRowHtml()}
                            ${getMatchingRowHtml()}
                            ${getMatchingRowHtml()}
                        </div>
                        <button type="button" onclick="addMatchingRow()" class="mt-2 text-[10px] text-accent-600 font-bold hover:text-accent-500 flex items-center bg-accent-50 px-4 py-2 rounded-xl transition-colors ml-1">
                            <i data-lucide="plus" class="w-3 h-3 mr-2"></i> Añadir Par
                        </button>
                        <p class="text-[10px] text-slate-400 ml-4 font-bold">NOTA: Estos pares formarán la calificación correcta. A los estudiantes se les mostrarán desordenados automáticamente.</p>
                    </div>
                `;
            } else if (select.value === 'multiple_choice' || select.value === 'checkbox') {
                const inputType = select.value === 'multiple_choice' ? 'radio' : 'checkbox';
                wrapper.innerHTML = `
                    <div class="space-y-4">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Opciones de Respuesta <span class="font-normal">(Marca la correcta para autocalificar)</span></label>
                        <div id="choice_rows" class="space-y-3">
                            ${getChoiceRowHtml(inputType)}
                            ${getChoiceRowHtml(inputType)}
                        </div>
                        <button type="button" onclick="addChoiceRow('${inputType}')" class="mt-2 text-[10px] text-accent-600 font-bold hover:text-accent-500 flex items-center bg-accent-50 px-4 py-2 rounded-xl transition-colors ml-1">
                            <i data-lucide="plus" class="w-3 h-3 mr-2"></i> Añadir Opción
                        </button>
                    </div>
                `;
            } else if (select.value === 'true_false') {
                wrapper.innerHTML = `
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Respuesta Correcta</label>
                        <select name="correct_tf" class="w-full px-6 py-4 bg-slate-50 border-2 border-emerald-100 rounded-[2rem] outline-none focus:border-emerald-500 font-black text-sm text-emerald-700">
                            <option value="Verdadero">Verdadera</option>
                            <option value="Falso">Falsa</option>
                        </select>
                    </div>
                `;
            } else if (select.value === 'text') {
                wrapper.innerHTML = `
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 text-emerald-600">Respuesta Correcta (Exacta)</label>
                        <input type="text" name="correct_text" placeholder="Ej: París" class="w-full px-6 py-4 bg-emerald-50 border-2 border-emerald-100 rounded-[2rem] outline-none focus:border-emerald-500 font-bold text-sm text-emerald-800">
                        <p class="text-[10px] text-slate-400 ml-4">La autocalificación verificará que el estudiante escriba exactamente esto (ignora mayúsculas).</p>
                    </div>
                `;
            }
            lucide.createIcons();
        }

        // ── Auto-detect question type on paste ──────────────────────
        function detectQuestionType(text) {
            const t = text.trim();

            // True/False signals
            if (/verdadero\s*[\/\-]\s*falso/i.test(t) || /true\s*[\/\-]\s*false/i.test(t)) {
                return 'true_false';
            }

            // File upload signals
            if (/sube?\s+(un\s+)?(archivo|imagen|foto|documento|evidencia)/i.test(t)) {
                return 'file_upload';
            }

            // Matching signals: "A - B" or "A:B" pattern list
            const matchingLines = t.split('\n').filter(l => /^.+\s*[-:]\s*.+$/.test(l.trim()));
            if (matchingLines.length >= 2 && matchingLines.length === t.split('\n').filter(l => l.trim()).length) {
                return 'matching';
            }

            // Multiple choice / checkbox: lines starting with a), b), 1., A-, etc.
            const optionLines = t.split('\n').filter(l => /^[a-zA-Z0-9][\).\-]\s+.+/.test(l.trim()));
            if (optionLines.length >= 2) {
                return 'multiple_choice';
            }

            // Paragraph: long text (> 120 chars) with no obvious structure
            if (t.length > 120 && !t.includes('?') && optionLines.length === 0) {
                return 'paragraph';
            }

            // Fill-in-the-blank
            if (/_{2,}/.test(t) || /\[\s*\]/.test(t)) {
                return 'text';
            }

            return 'text'; // default
        }

        function extractOptionsFromPaste(type, text) {
            const lines = text.split('\n').map(l => l.trim()).filter(Boolean);
            if (type === 'matching') {
                return lines.filter(l => /[-:]/.test(l)).join('\n');
            }
            if (type === 'multiple_choice' || type === 'checkbox') {
                return lines
                    .filter(l => /^[a-zA-Z0-9][\).\-]\s+/.test(l))
                    .map(l => l.replace(/^[a-zA-Z0-9][\).\-]\s+/, '').trim())
                    .join('\n');
            }
            return '';
        }

        function showDetectBanner(label) {
            const banner = document.getElementById('detect-banner');
            const txt = document.getElementById('detect-text');
            txt.textContent = '✦ Tipo detectado: ' + label;
            banner.classList.remove('hidden');
            setTimeout(() => banner.classList.add('hidden'), 4000);
        }

        const questionInput = document.getElementById('question_text_input');
        if (questionInput) {
            questionInput.addEventListener('paste', function(e) {
                // Only detect if we are not editing
                if (document.getElementById('question_id_input').value !== '') return;
                
                setTimeout(() => {
                    const text = questionInput.value;
                    const type = detectQuestionType(text);
                    const sel = document.getElementById('question_type_select');
                    sel.value = type;
                    handleTypeChange(sel);

                    // Pre-populate dynamic options if any
                    const rawOptsText = extractOptionsFromPaste(type, text);
                    if (rawOptsText) {
                        const lines = rawOptsText.split('\n');
                        const wrapper = document.getElementById('dynamic-options-wrapper');
                        
                        if (type === 'matching') {
                            const container = document.getElementById('matching_rows');
                            if (container) {
                                container.innerHTML = ''; // clear default
                                lines.forEach(l => {
                                    const parts = l.split(/[-:]/);
                                    if (parts.length >= 2) {
                                        container.insertAdjacentHTML('beforeend', getMatchingRowHtml(parts[0].trim(), parts[1].trim()));
                                    }
                                });
                                lucide.createIcons();
                            }
                        } else if (type === 'multiple_choice' || type === 'checkbox') {
                            const container = document.getElementById('choice_rows');
                            if (container) {
                                container.innerHTML = '';
                                const inputType = type === 'multiple_choice' ? 'radio' : 'checkbox';
                                lines.forEach(l => {
                                    container.insertAdjacentHTML('beforeend', getChoiceRowHtml(inputType, l));
                                });
                                lucide.createIcons();
                            }
                        }
                    }

                    const labels = {
                        'text': 'Respuesta Corta', 'paragraph': 'Desarrollo', 'multiple_choice': 'Opción Múltiple',
                        'checkbox': 'Casilla', 'true_false': 'Verdadero / Falso', 'matching': 'Relación de Columnas',
                        'file_upload': 'Subida de Archivo'
                    };
                    showDetectBanner(labels[type] || type);
                }, 50);
            });
        }

        function openNewQuestionModal() {
            document.getElementById('modal-title').innerHTML = 'Nueva <span class="text-accent-500">Pregunta</span>';
            document.getElementById('modal-desc').textContent = 'Pega texto para detectar el tipo automáticamente';
            document.getElementById('submit-btn').textContent = 'Añadir al Banco';
            document.getElementById('question_id_input').value = '';
            document.getElementById('question-form').reset();
            document.getElementById('dynamic-options-wrapper').innerHTML = '';
            toggleModal('add-q-modal');
        }

        function openEditModal(data) {
            document.getElementById('modal-title').innerHTML = 'Editar <span class="text-accent-500">Pregunta</span>';
            document.getElementById('modal-desc').textContent = 'Modifica los campos necesarios para actualizar la pregunta';
            document.getElementById('submit-btn').textContent = 'Guardar Cambios';
            document.getElementById('question_id_input').value = data.id;
            document.getElementById('question_text_input').value = data.question_text;
            document.getElementById('series_input').value = data.series || '';
            document.getElementById('question_type_select').value = data.question_type;
            const pointsInput = document.querySelector('input[name="points"]');
            if (pointsInput) pointsInput.value = data.points;

            handleTypeChange(document.getElementById('question_type_select'));

            // Populate dynamic content
            const options = data.options ? JSON.parse(data.options) : [];
            const correct = data.correct_answers ? JSON.parse(data.correct_answers) : null;

            if (data.question_type === 'matching') {
                const container = document.getElementById('matching_rows');
                container.innerHTML = '';
                if (correct) {
                    for (const [concept, definition] of Object.entries(correct)) {
                        container.insertAdjacentHTML('beforeend', getMatchingRowHtml(concept, definition));
                    }
                }
            } else if (data.question_type === 'multiple_choice' || data.question_type === 'checkbox') {
                const container = document.getElementById('choice_rows');
                container.innerHTML = '';
                const inputType = data.question_type === 'multiple_choice' ? 'radio' : 'checkbox';
                options.forEach(opt => {
                    const isCorrect = Array.isArray(correct) ? correct.includes(opt) : (correct === opt);
                    container.insertAdjacentHTML('beforeend', getChoiceRowHtml(inputType, opt, isCorrect));
                });
            } else if (data.question_type === 'true_false') {
                const tfSelect = document.querySelector('select[name="correct_tf"]');
                if (tfSelect && correct) tfSelect.value = correct[0];
            } else if (data.question_type === 'text') {
                const textInput = document.querySelector('input[name="correct_text"]');
                if (textInput && correct) textInput.value = correct[0];
            }

            toggleModal('add-q-modal');
            lucide.createIcons();
        }
    </script>
</body>

</html>