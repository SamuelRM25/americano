<?php
session_start();
require_once '../config/database.php';

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

    if (!empty($title) && !empty($due_date)) {
        try {
            $stmt = $pdo->prepare('UPDATE exams SET title = ?, description = ?, due_date = ?, grade_id = ?, course_id = ? WHERE id = ?');
            $stmt->execute([$title, $desc, $due_date, $grade_id, $course_id, $exam_id]);
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
    $options = !empty($_POST['options']) ? json_encode(array_map('trim', explode(',', $_POST['options']))) : null;

    try {
        $stmt = $pdo->prepare('INSERT INTO exam_questions (exam_id, question_text, question_type, options, points) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$exam_id, $text, $type, $options, $points]);
        $success = "Pregunta añadida correctamente.";
    } catch (Exception $e) {
        $error = "Error al añadir pregunta: " . $e->getMessage();
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

$questions = $pdo->prepare('SELECT * FROM exam_questions WHERE exam_id = ? ORDER BY id ASC');
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

<body class="bg-slate-50 min-h-screen">
    <div class="flex">
        <!-- Sidebar placeholder or just main content -->
        <main class="flex-1 p-8 lg:p-12 max-w-7xl mx-auto">
            <nav class="mb-12">
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
                <div class="lg:col-span-1">
                    <div
                        class="bg-white rounded-[3.5rem] p-10 shadow-xl shadow-slate-200/50 border border-slate-100 animate-slide-up">
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
                                    class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2rem] outline-none focus:border-primary-500 font-bold transition-all text-sm tracking-tight text-slate-900">
                            </div>
                            <div class="space-y-2">
                                <label
                                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Instrucciones</label>
                                <textarea name="description"
                                    class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2rem] outline-none focus:border-primary-500 font-bold h-32 resize-none transition-all text-sm text-slate-600"><?= $exam['description'] ?></textarea>
                            </div>
                            <div class="space-y-2">
                                <label
                                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Grado</label>
                                <select name="grade_id" required
                                    class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2rem] outline-none focus:border-primary-500 font-black text-xs uppercase tracking-widest appearance-none">
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
                                    class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2rem] outline-none focus:border-primary-500 font-black text-xs uppercase tracking-widest appearance-none">
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
                                    class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2rem] outline-none focus:border-primary-500 font-black text-xs uppercase">
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
                    <div
                        class="bg-white rounded-[3.5rem] p-10 shadow-xl shadow-slate-200/50 border border-slate-100 animate-slide-up">
                        <div class="flex items-center justify-between mb-10">
                            <h3 class="text-xl font-black text-slate-900 flex items-center">
                                <i data-lucide="list-checks" class="w-6 h-6 mr-3 text-accent-500"></i> Banco de
                                Preguntas
                            </h3>
                            <button onclick="toggleModal('add-q-modal')"
                                class="bg-accent-600 text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-accent-500 transition-all shadow-lg active:scale-95 flex items-center">
                                <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Nueva Pregunta
                            </button>
                        </div>

                        <div class="space-y-4">
                            <?php if (empty($questions)): ?>
                                <div
                                    class="p-12 text-center rounded-[2.5rem] bg-slate-50 border-2 border-dashed border-slate-200 opacity-50">
                                    <i data-lucide="clipboard-list" class="w-12 h-12 mx-auto mb-4 text-slate-300"></i>
                                    <p class="font-bold text-slate-400 italic">No hay preguntas en este examen todavía.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($questions as $q): ?>
                                    <div
                                        class="flex items-start gap-6 p-8 bg-slate-50/50 rounded-[2.5rem] border border-slate-50 hover:border-accent-200 transition-all group">
                                        <div
                                            class="w-12 h-12 bg-white text-slate-400 rounded-2xl shadow-sm flex items-center justify-center font-black italic text-lg flex-shrink-0 group-hover:bg-accent-600 group-hover:text-white transition-all">
                                            <?= $q['points'] ?>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-start justify-between mb-3">
                                                <p
                                                    class="font-bold text-slate-900 group-hover:text-accent-600 transition-colors">
                                                    <?= $q['question_text'] ?>
                                                </p>
                                                <a href="?id=<?= $exam_id ?>&delete_question=<?= $q['id'] ?>"
                                                    onclick="return confirm('¿Eliminar pregunta?')"
                                                    class="p-2 text-slate-300 hover:text-rose-500 transition-colors">
                                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                                </a>
                                            </div>
                                            <div class="flex items-center space-x-4">
                                                <span class="text-[9px] font-black uppercase text-slate-400 italic">
                                                    <?= str_replace('_', ' ', $q['question_type']) ?>
                                                </span>
                                                <?php if ($q['options']): ?>
                                                    <div class="flex items-center gap-1">
                                                        <?php foreach (json_decode($q['options']) as $opt): ?>
                                                            <span
                                                                class="text-[8px] bg-white border border-slate-200 px-2 py-0.5 rounded-lg font-bold text-slate-500">
                                                                <?= $opt ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="add-q-modal"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 hidden flex items-center justify-center p-6 overflow-y-auto">
        <div class="bg-white w-full max-w-2xl rounded-[3.5rem] p-12 shadow-2xl relative animate-slide-up">
            <header class="flex justify-between items-center mb-10">
                <h3 class="text-4xl font-black text-slate-950 tracking-tighter italic">Nueva <span
                        class="text-accent-500">Pregunta</span></h3>
                <button onclick="toggleModal('add-q-modal')"
                    class="p-4 bg-slate-100 text-slate-400 rounded-full hover:bg-slate-950 hover:text-white transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </header>
            <form action="" method="POST" class="space-y-6">
                <input type="hidden" name="add_question" value="1">
                <div class="space-y-6">
                    <input type="text" name="question_text" required placeholder="¿Cuál es la pregunta?"
                        class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2.5rem] outline-none focus:border-accent-500 font-bold transition-all text-lg tracking-tight">

                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Tipo de
                                Reactivo</label>
                            <select name="question_type" required onchange="handleTypeChange(this)"
                                class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2.5rem] outline-none focus:border-accent-500 font-black text-xs uppercase tracking-widest">
                                <option value="text">Respuesta Directa (Corta)</option>
                                <option value="paragraph">Desarrollo (Párrafo)</option>
                                <option value="multiple_choice">Opción Múltiple</option>
                                <option value="checkbox">Casilla de Verificación</option>
                                <option value="true_false">Verdadero / Falso</option>
                                <option value="matching">Relación de Columnas</option>
                                <option value="file_upload">Subida de Archivo (Evidencia)</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label
                                class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Puntuación</label>
                            <input type="number" name="points" value="1" min="1" step="0.5"
                                class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2.5rem] outline-none focus:border-accent-500 font-black text-xs text-center">
                        </div>
                    </div>

                    <div id="options-container" class="space-y-2 hidden">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Opciones
                            (separadas por coma)</label>
                        <input type="text" name="options" placeholder="Opción A, Opción B, Opción C..."
                            class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2.5rem] outline-none focus:border-accent-500 font-bold text-xs ">
                    </div>
                </div>
                <button type="submit"
                    class="w-full bg-accent-600 text-white font-black py-6 rounded-[2rem] mt-4 hover:bg-accent-500 shadow-2xl shadow-accent-500/30 transition-all uppercase tracking-[0.2em] text-sm">
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
        }

        function handleTypeChange(select) {
            const container = document.getElementById('options-container');
            const typesWithOptions = ['multiple_choice', 'checkbox', 'true_false', 'matching'];

            if (typesWithOptions.includes(select.value)) {
                container.classList.remove('hidden');
                if (select.value === 'true_false') {
                    container.querySelector('input').value = 'Verdadero, Falso';
                    container.querySelector('input').readOnly = true;
                } else {
                    container.querySelector('input').readOnly = false;
                    container.querySelector('input').value = '';
                }
            } else {
                container.classList.add('hidden');
            }
        }
    </script>
</body>

</html>