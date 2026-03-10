<?php
session_start();
require_once '../config/database.php';
require_once 'includes/sidebar.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$success = '';
$error = '';

// Handle Deletion
if (isset($_GET['delete_submission'])) {
    $stmt = $pdo->prepare('DELETE FROM submissions WHERE id = ?');
    $stmt->execute([$_GET['delete_submission']]);
    header('Location: submissions.php?msg=s_deleted');
    exit;
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 's_deleted')
        $success = "Entrega eliminada.";
}

// Filtering logic
$filter_grade = $_GET['grade_id'] ?? '';
$filter_course = $_GET['course_id'] ?? '';

// Fetch grades and teacher's specific courses for filters
$grades = $pdo->query('SELECT * FROM grades')->fetchAll();
$courses = $pdo->prepare('SELECT * FROM courses WHERE admin_id = ?');
$courses->execute([$_SESSION['admin_id']]);
$courses = $courses->fetchAll();

// Fetch Task Submissions
$sub_query = 'SELECT s.*, st.name as student_name, a.title as task_title 
              FROM submissions s 
              JOIN students st ON s.student_id = st.id 
              JOIN assignments a ON s.assignment_id = a.id
              JOIN courses c ON a.course_id = c.id
              WHERE c.admin_id = ?';

$sub_params = [$_SESSION['admin_id']];
if ($filter_grade) {
    $sub_query .= ' AND st.grade_id = ?';
    $sub_params[] = $filter_grade;
}
if ($filter_course) {
    $sub_query .= ' AND a.course_id = ?';
    $sub_params[] = $filter_course;
}
$sub_query .= ' ORDER BY s.submitted_at DESC';
$submissions = $pdo->prepare($sub_query);
$submissions->execute($sub_params);
$submissions = $submissions->fetchAll();

// Fetch Exam Responses
$exam_query = 'SELECT r.exam_id, r.student_id, st.name as student_name, e.title as exam_title, MAX(r.submitted_at) as finished_at, COUNT(r.id) as answers_count
               FROM exam_responses r 
               JOIN students st ON r.student_id = st.id 
               JOIN exams e ON r.exam_id = e.id
               JOIN courses c ON e.course_id = c.id
               WHERE c.admin_id = ?';

$exam_params = [$_SESSION['admin_id']];
if ($filter_grade) {
    $exam_query .= ' AND st.grade_id = ?';
    $exam_params[] = $filter_grade;
}
if ($filter_course) {
    $exam_query .= ' AND e.course_id = ?';
    $exam_params[] = $filter_course;
}
$exam_query .= ' GROUP BY r.exam_id, r.student_id ORDER BY finished_at DESC';
$exam_responses = $pdo->prepare($exam_query);
$exam_responses->execute($exam_params);
$exam_responses = $exam_responses->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificaciones - Admin</title>
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
    <?php render_admin_sidebar('submissions'); ?>

    <main class="flex-1 overflow-y-auto bg-slate-50 animate-fade-in custom-scrollbar">
        <div class="p-8 lg:p-12 max-w-7xl mx-auto">
            <header class="mb-12">
                <h2 class="text-xs font-black text-primary-600 uppercase tracking-[0.3em] mb-2 leading-none">
                    Calificaciones y Entregas</h2>
                <h1 class="text-5xl font-black text-slate-950 tracking-tighter">Panel de <span
                        class="italic text-transparent bg-clip-text bg-gradient-to-r from-slate-950 to-primary-600">Revisión</span>
                </h1>
            </header>

            <!-- Filters -->
            <div
                class="bg-white p-8 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 mb-12 flex flex-col md:flex-row md:items-end gap-6 animate-slide-up">
                <div class="flex-1 space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Grado</label>
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
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Curso</label>
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
                <button onclick="window.location.href='submissions.php'"
                    class="px-8 py-4 bg-slate-100 text-slate-600 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-200 transition-all flex items-center justify-center">
                    <i data-lucide="refresh-ccw" class="w-4 h-4 mr-2"></i> Limpiar
                </button>
            </div>

            <!-- Tabs/Sections -->
            <div class="space-y-20">
                <!-- Task Submissions -->
                <section>
                    <div class="flex items-center space-x-4 mb-10">
                        <div
                            class="w-12 h-12 bg-accent-50 text-accent-600 rounded-2xl flex items-center justify-center">
                            <i data-lucide="folder-up" class="w-6 h-6"></i>
                        </div>
                        <h2 class="text-3xl font-black text-slate-900 tracking-tight italic uppercase">Tareas <span
                                class="text-accent-500">Recibidas</span></h2>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <?php foreach ($submissions as $s): ?>
                            <div
                                class="bg-white rounded-[2.5rem] p-8 shadow-xl shadow-slate-200/50 border border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-6 hover:shadow-2xl transition-all animate-slide-up">
                                <div class="flex items-center space-x-6">
                                    <div
                                        class="w-14 h-14 bg-slate-50 text-slate-400 rounded-2xl flex items-center justify-center flex-shrink-0 group-hover:bg-accent-500 group-hover:text-white transition-all">
                                        <i data-lucide="file" class="w-7 h-7"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <h3 class="text-lg font-black text-slate-900 truncate"><?= $s['student_name'] ?>
                                        </h3>
                                        <p class="text-xs font-bold text-accent-600 uppercase tracking-widest mb-2 italic">
                                            <?= $s['task_title'] ?>
                                        </p>
                                        <div
                                            class="flex items-center text-[10px] text-slate-400 font-bold uppercase tracking-widest">
                                            <i data-lucide="calendar" class="w-3 h-3 mr-1.5"></i>
                                            <?= date('d M, Y H:i', strtotime($s['submitted_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <a href="../<?= $s['file_path'] ?>" target="_blank"
                                        class="px-6 py-3 bg-slate-950 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-accent-600 transition-all flex items-center">
                                        <i data-lucide="eye" class="w-4 h-4 mr-2"></i> Ver
                                    </a>
                                    <a href="?delete_submission=<?= $s['id'] ?>"
                                        onclick="return confirm('¿Eliminar entrega?')"
                                        class="p-3 bg-rose-50 text-rose-500 rounded-xl hover:bg-rose-500 hover:text-white transition-all">
                                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Exam Submissions -->
                <section>
                    <div class="flex items-center space-x-4 mb-10">
                        <div
                            class="w-12 h-12 bg-primary-50 text-primary-600 rounded-2xl flex items-center justify-center">
                            <i data-lucide="clipboard-check" class="w-6 h-6"></i>
                        </div>
                        <h2 class="text-3xl font-black text-slate-900 tracking-tight italic uppercase">Exámenes <span
                                class="text-primary-500">Completados</span></h2>
                    </div>

                    <div
                        class="bg-white rounded-[3rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead
                                    class="bg-slate-50 text-[10px] uppercase font-black tracking-widest text-slate-400 border-b border-slate-100">
                                    <tr>
                                        <th class="px-10 py-6">Estudiante</th>
                                        <th class="px-10 py-6">Examen Realizado</th>
                                        <th class="px-10 py-6">Preguntas Resp.</th>
                                        <th class="px-10 py-6">Fecha de Finalización</th>
                                        <th class="px-10 py-6 text-right">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <?php foreach ($exam_responses as $r): ?>
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="px-10 py-8">
                                                <span class="text-slate-900 font-bold"><?= $r['student_name'] ?></span>
                                            </td>
                                            <td class="px-10 py-8">
                                                <span
                                                    class="text-[10px] font-black uppercase text-primary-600 bg-primary-50 px-3 py-1.5 rounded-xl border border-primary-100 italic">
                                                    <?= $r['exam_title'] ?>
                                                </span>
                                            </td>
                                            <td class="px-10 py-8">
                                                <span class="text-xs font-black text-slate-500"><?= $r['answers_count'] ?>
                                                    Reactivos</span>
                                            </td>
                                            <td class="px-10 py-8">
                                                <span
                                                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest"><?= date('d M, Y H:i', strtotime($r['finished_at'])) ?></span>
                                            </td>
                                            <td class="px-10 py-8 text-right">
                                                <button
                                                    class="px-6 py-2.5 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-primary-600 transition-all">
                                                    Revisar
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
        function applyFilters() {
            const grade = document.getElementById('grade_filter').value;
            const course = document.getElementById('course_filter').value;
            window.location.href = `submissions.php?grade_id=${grade}&course_id=${course}`;
        }
    </script>
</body>

</html>