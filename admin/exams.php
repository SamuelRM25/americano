<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$success = '';
$error = '';

// Handle Exam Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_exam'])) {
    $title = $_POST['title'] ?? '';
    $desc = $_POST['description'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $grade_id = $_POST['grade_id'] ?? '';
    $course_id = $_POST['course_id'] ?? '';

    if (!empty($title) && !empty($due_date)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO exams (title, description, due_date, grade_id, course_id) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$title, $desc, $due_date, $grade_id, $course_id]);
            $success = "Examen creado correctamente.";
        } catch (Exception $e) {
            $error = "Error al crear examen: " . $e->getMessage();
        }
    }
}

// Handle Exam update (Keep for small AJAX if needed, but primary is edit_exam.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_exam'])) {
    // This could be removed if the modal is gone, but keeping the logic just in case.
}

// Handle Exam Deletion
if (isset($_GET['delete_exam'])) {
    try {
        $stmt = $pdo->prepare('DELETE FROM exams WHERE id = ?');
        $stmt->execute([$_GET['delete_exam']]);
        header('Location: exams.php?msg=deleted');
        exit;
    } catch (Exception $e) {
        $error = "No se puede eliminar el examen (posiblemente tiene respuestas).";
    }
}

// Handle Question Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $exam_id = $_POST['exam_id'];
    $text = $_POST['question_text'];
    $type = $_POST['question_type'];
    $points = $_POST['points'] ?? 1;
    $options = $_POST['options'] ? json_encode(explode(',', $_POST['options'])) : null;

    try {
        $stmt = $pdo->prepare('INSERT INTO exam_questions (exam_id, question_text, question_type, options, points) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$exam_id, $text, $type, $options, $points]);
        $success = "Pregunta añadida.";
    } catch (Exception $e) {
        $error = "Error al añadir pregunta: " . $e->getMessage();
    }
}

// Handle Question Deletion
if (isset($_GET['delete_question'])) {
    $stmt = $pdo->prepare('DELETE FROM exam_questions WHERE id = ?');
    $stmt->execute([$_GET['delete_question']]);
    header('Location: exams.php?msg=q_deleted');
    exit;
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted')
        $success = "Examen eliminado.";
}

// Fetch grades and teacher's specific courses
$grades = $pdo->query('SELECT * FROM grades')->fetchAll();
$courses = $pdo->prepare('SELECT * FROM courses WHERE admin_id = ?');
$courses->execute([$_SESSION['admin_id']]);
$courses = $courses->fetchAll();

// Filtering logic
$filter_grade = $_GET['grade_id'] ?? '';
$filter_course = $_GET['course_id'] ?? '';

$query = 'SELECT e.*, g.name as grade_name, c.name as course_name 
          FROM exams e 
          JOIN grades g ON e.grade_id = g.id 
          JOIN courses c ON e.course_id = c.id
          WHERE c.admin_id = ?';

$params = [$_SESSION['admin_id']];
if ($filter_grade) {
    $query .= ' AND e.grade_id = ?';
    $params[] = $filter_grade;
}
if ($filter_course) {
    $query .= ' AND e.course_id = ?';
    $params[] = $filter_course;
}

$query .= ' ORDER BY e.due_date DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$exams = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Exámenes - Admin</title>
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

        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .sidebar-item-active {
            background: rgba(14, 165, 233, 0.1);
            border-left: 4px solid #0ea5e9;
            color: white;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
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

<body class="bg-[#f8fafc] min-h-screen flex overflow-hidden">
    <!-- Admin Sidebar -->
    <aside class="w-80 bg-slate-950 text-white flex flex-col border-r border-white/5 shadow-2xl z-30 hidden lg:flex">
        <div class="p-8">
            <div class="flex items-center space-x-4 mb-12">
                <div class="bg-primary-500 p-3 rounded-2xl shadow-xl shadow-primary-500/20">
                    <i data-lucide="shield-check" class="w-8 h-8 text-white"></i>
                </div>
                <div>
                    <span class="text-xl font-black tracking-tighter block leading-none italic uppercase">ADMIN</span>
                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest leading-none">Colegio
                        Americano</span>
                </div>
            </div>

            <nav class="space-y-2">
                <a href="dashboard.php"
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all text-slate-400 hover:text-white hover:bg-white/5 group">
                    <i data-lucide="users" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Estudiantes</span>
                </a>
                <a href="assignments.php"
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all text-slate-400 hover:text-white hover:bg-white/5 group">
                    <i data-lucide="book-open" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Asignar Tareas</span>
                </a>
                <a href="exams.php"
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all text-slate-400 hover:text-white hover:bg-white/5 group">
                    <i data-lucide="clipboard-list" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Asignar Exámenes</span>
                </a>
                <a href="chat.php"
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all sidebar-item-active group">
                    <i data-lucide="message-square" class="w-5 h-5 text-accent-400"></i>
                    <span class="font-bold">Centro de Mensajes</span>
                </a>
                <a href="submissions.php"
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all text-slate-400 hover:text-white hover:bg-white/5 group">
                    <i data-lucide="check-square" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Ver Calificaciones</span>
                </a>
            </nav>
        </div>

        <div class="mt-auto p-8 border-t border-white/5">
            <a href="logout.php"
                class="flex items-center space-x-3 text-slate-500 hover:text-rose-400 transition-colors group font-bold text-sm uppercase tracking-widest">
                <i data-lucide="log-out" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto bg-slate-50 animate-fade-in custom-scrollbar">
        <div class="p-8 lg:p-12 max-w-7xl mx-auto">
            <header class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
                <div>
                    <h2 class="text-xs font-black text-primary-600 uppercase tracking-[0.3em] mb-2 leading-none">Gestión
                        Académica</h2>
                    <h1 class="text-5xl font-black text-slate-950 tracking-tighter">Panel de <span
                            class="italic text-transparent bg-clip-text bg-gradient-to-r from-slate-950 to-primary-600">Exámenes</span>
                    </h1>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="toggleModal('add-exam-modal')"
                        class="bg-primary-600 text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-primary-500 transition-all shadow-xl shadow-primary-500/20 active:scale-95 flex items-center">
                        <i data-lucide="plus" class="w-5 h-5 mr-2"></i> Crear Examen
                    </button>
                </div>
            </header>

            <?php if ($success): ?>
                <div
                    class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-6 py-4 rounded-3xl text-sm font-bold mb-8 flex items-center animate-slide-up shadow-sm">
                    <i data-lucide="check-circle" class="w-5 h-5 mr-3"></i> <?= $success ?>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div
                class="bg-white p-8 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 mb-12 flex flex-col md:flex-row md:items-end gap-6 animate-slide-up">
                <div class="flex-1 space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Filtrar por
                        Grado</label>
                    <select id="grade_filter"
                        class="w-full px-6 py-4 bg-slate-50 border-2 border-transparent border-slate-100 rounded-2xl outline-none focus:border-primary-500 font-bold transition-all text-slate-900"
                        onchange="applyFilters()">
                        <option value="">Todos los Grados</option>
                        <?php foreach ($grades as $g): ?>
                            <option value="<?= $g['id'] ?>" <?= $filter_grade == $g['id'] ? 'selected' : '' ?>>
                                <?= $g['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex-1 space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Filtrar por
                        Curso</label>
                    <select id="course_filter"
                        class="w-full px-6 py-4 bg-slate-50 border-2 border-transparent border-slate-100 rounded-2xl outline-none focus:border-primary-500 font-bold transition-all text-slate-900"
                        onchange="applyFilters()">
                        <option value="">Todos los Cursos</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $filter_course == $c['id'] ? 'selected' : '' ?>>
                                <?= $c['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button onclick="window.location.href='exams.php'"
                    class="px-8 py-4 bg-slate-100 text-slate-600 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-200 transition-all flex items-center justify-center">
                    <i data-lucide="refresh-ccw" class="w-4 h-4 mr-2"></i> Limpiar
                </button>
            </div>

            <?php if ($error): ?>
                <div
                    class="bg-rose-50 border border-rose-100 text-rose-700 px-6 py-4 rounded-3xl text-sm font-bold mb-8 flex items-center animate-shake">
                    <i data-lucide="alert-circle" class="w-5 h-5 mr-3"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <!-- Exams Overview Cards -->
            <div class="grid grid-cols-1 gap-8 mb-12">
                <?php foreach ($exams as $e):
                    $q_count = $pdo->prepare('SELECT COUNT(*) FROM exam_questions WHERE exam_id = ?');
                    $q_count->execute([$e['id']]);
                    $count = $q_count->fetchColumn();
                    ?>
                    <div
                        class="bg-white rounded-[3rem] p-10 shadow-xl shadow-slate-200/50 border border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-8 group hover:border-primary-200 transition-all duration-500 animate-slide-up">
                        <div class="flex items-center space-x-8">
                            <div
                                class="w-20 h-20 bg-primary-50 text-primary-600 rounded-3xl flex items-center justify-center flex-shrink-0 shadow-inner group-hover:bg-primary-600 group-hover:text-white transition-all transform group-hover:rotate-6">
                                <i data-lucide="file-text" class="w-10 h-10"></i>
                            </div>
                            <div>
                                <div class="flex items-center space-x-3 mb-2">
                                    <h3 class="text-3xl font-black text-slate-900 tracking-tight italic uppercase">
                                        <?= $e['title'] ?>
                                    </h3>
                                    <span
                                        class="px-3 py-1 bg-slate-100 text-[10px] font-black text-slate-500 rounded-full uppercase tracking-widest"><?= $count ?>
                                        Preguntas</span>
                                </div>
                                <p class="text-slate-500 font-medium mb-4 line-clamp-1"><?= $e['description'] ?></p>
                                <div
                                    class="flex flex-wrap items-center gap-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                    <span
                                        class="flex items-center bg-primary-50 text-primary-600 px-3 py-1.5 rounded-xl border border-primary-100 italic">
                                        <i data-lucide="graduation-cap" class="w-3 h-3 mr-2"></i> <?= $e['grade_name'] ?>
                                    </span>
                                    <span
                                        class="flex items-center bg-accent-50 text-accent-600 px-3 py-1.5 rounded-xl border border-accent-100 italic">
                                        <i data-lucide="book" class="w-3 h-3 mr-2"></i> <?= $e['course_name'] ?>
                                    </span>
                                    <span class="flex items-center">
                                        <i data-lucide="calendar" class="w-3 h-3 mr-2 text-primary-500"></i> Límite:
                                        <?= date('d M, Y', strtotime($e['due_date'])) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="edit_exam.php?id=<?= $e['id'] ?>" target="_blank"
                                class="p-4 bg-slate-50 text-slate-400 rounded-2xl hover:bg-primary-50 hover:text-primary-600 transition-all active:scale-95 group/btn">
                                <i data-lucide="edit-3" class="w-6 h-6 group-hover/btn:scale-110 transition-transform"></i>
                            </a>
                            <a href="?delete_exam=<?= $e['id'] ?>" onclick="return confirm('¿Eliminar examen?')"
                                class="p-4 bg-slate-50 text-slate-400 rounded-2xl hover:bg-rose-50 hover:text-rose-600 transition-all active:scale-95 group/btn">
                                <i data-lucide="trash-2" class="w-6 h-6 group-hover/btn:scale-110 transition-transform"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </main>

    <!-- Modals -->
    <div id="add-exam-modal"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 hidden flex items-center justify-center p-6 sm:p-12 overflow-y-auto">
        <div class="bg-white w-full max-w-2xl rounded-[3.5rem] p-12 shadow-2xl relative animate-slide-up">
            <header class="flex justify-between items-center mb-10">
                <h3 class="text-4xl font-black text-slate-950 tracking-tighter italic">Nuevo <span
                        class="text-primary-500">Examen</span></h3>
                <button onclick="toggleModal('add-exam-modal')"
                    class="p-4 bg-slate-100 text-slate-400 rounded-full hover:bg-slate-900 hover:text-white transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </header>
            <form action="" method="POST" class="space-y-6">
                <input type="hidden" name="add_exam" value="1">
                <div class="grid grid-cols-1 gap-6">
                    <input type="text" name="title" required placeholder="Título del desafío"
                        class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2rem] outline-none focus:border-primary-500 font-bold transition-all text-lg tracking-tight">
                    <textarea name="description" placeholder="Instrucciones generales..."
                        class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2rem] outline-none focus:border-primary-500 font-bold h-32 resize-none transition-all"></textarea>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label
                                class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Grado</label>
                            <select name="grade_id" required
                                class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2rem] outline-none focus:border-primary-500 font-black text-xs uppercase tracking-widest appearance-none">
                                <?php foreach ($grades as $g): ?>
                                    <option value="<?= $g['id'] ?>"><?= $g['name'] ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label
                                class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Curso</label>
                            <select name="course_id" required
                                class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2rem] outline-none focus:border-primary-500 font-black text-xs uppercase tracking-widest appearance-none">
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Fecha & Hora
                            de Entrega</label>
                        <input type="datetime-local" name="due_date" required
                            class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2rem] outline-none focus:border-primary-500 font-black text-xs uppercase">
                    </div>
                </div>
                <button type="submit"
                    class="w-full bg-primary-600 text-white font-black py-6 rounded-[2rem] mt-4 hover:bg-primary-500 shadow-2xl shadow-primary-500/30 transition-all uppercase tracking-[0.2em] text-sm">
                    Establecer Examen
                </button>
            </form>
        </div>
    </div>

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
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Vincular a
                            Examen</label>
                        <select name="exam_id" required
                            class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2.5rem] outline-none focus:border-accent-500 font-black text-xs uppercase tracking-widest">
                            <?php foreach ($exams as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= $e['title'] ?> (<?= $e['grade_name'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

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
                            <input type="number" name="points" value="1" min="1" max="100"
                                class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2.5rem] outline-none focus:border-accent-500 font-black text-lg text-center">
                        </div>
                    </div>

                    <div id="options-container" class="space-y-2 hidden animate-fade-in">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Opciones /
                            Pares</label>
                        <input type="text" name="options" placeholder="Opción A, Opción B, Opción C..."
                            class="w-full px-8 py-5 bg-slate-50 border-2 border-slate-100 rounded-[2.5rem] outline-none focus:border-accent-500 font-bold">
                        <p class="text-[9px] text-slate-400 ml-4 font-bold italic">* Para Relación de Columnas use:
                            Pregunta:Respuesta, Pregunta2:Respuesta2</p>
                    </div>
                </div>
                <button type="submit"
                    class="w-full bg-accent-600 text-white font-black py-6 rounded-[2.5rem] mt-4 hover:bg-accent-500 shadow-2xl shadow-accent-500/30 transition-all uppercase tracking-[0.2em] text-sm">
                    Inyectar Pregunta
                </button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function applyFilters() {
            const grade = document.getElementById('grade_filter').value;
            const course = document.getElementById('course_filter').value;
            window.location.href = `exams.php?grade_id=${grade}&course_id=${course}`;
        }

        function toggleModal(id) {
            const modal = document.getElementById(id);
            modal.classList.toggle('hidden');
            document.body.style.overflow = modal.classList.contains('hidden') ? 'auto' : 'hidden';
        }
    </script>
</body>

</html>