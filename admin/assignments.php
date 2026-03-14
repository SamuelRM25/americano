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

// Handle Assignment Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_assignment'])) {
    $title = $_POST['title'] ?? '';
    $desc = $_POST['description'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $grade_id = $_POST['grade_id'] ?? '';
    $course_id = $_POST['course_id'] ?? '';

    if (!empty($title) && !empty($due_date) && !empty($grade_id) && !empty($course_id)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO assignments (title, description, due_date, grade_id, course_id) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$title, $desc, $due_date, $grade_id, $course_id]);
            $success = "Tarea asignada correctamente.";
        } catch (Exception $e) {
            $error = "Error al crear tarea: " . $e->getMessage();
        }
    } else {
        $error = "Por favor completa los campos obligatorios.";
    }
}

// Handle Assignment Update (Primary editing is now in edit_assignment.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_assignment'])) {
    // Keeping logic here just in case of future quick-edits, but modal is removed.
}

// Handle Deletion
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM assignments WHERE id = ?');
    $stmt->execute([$_GET['delete']]);
    header('Location: assignments.php?msg=deleted');
    exit;
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $success = "Tarea eliminada correctamente.";
}

// Fetch grades and teacher's specific courses
$grades = $pdo->query('SELECT * FROM grades')->fetchAll();
$courses = $pdo->prepare('SELECT * FROM courses WHERE admin_id = ?');
$courses->execute([$_SESSION['admin_id']]);
$courses = $courses->fetchAll();

// Filtering logic
$filter_grade = $_GET['grade_id'] ?? '';
$filter_course = $_GET['course_id'] ?? '';

$query = 'SELECT a.*, g.name as grade_name, c.name as course_name 
          FROM assignments a 
          JOIN grades g ON a.grade_id = g.id 
          JOIN courses c ON a.course_id = c.id
          WHERE c.admin_id = ?';

$params = [$_SESSION['admin_id']];
if ($filter_grade) {
    $query .= ' AND a.grade_id = ?';
    $params[] = $filter_grade;
}
if ($filter_course) {
    $query .= ' AND a.course_id = ?';
    $params[] = $filter_course;
}

$query .= ' ORDER BY a.due_date DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$assignments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Tareas - Admin</title>
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
            background: rgba(139, 92, 246, 0.1);
            border-left: 4px solid #8b5cf6;
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

<body class="bg-[#f8fafc] h-screen flex overflow-hidden">
    <?php render_admin_sidebar('assignments'); ?>

    <main class="flex-1 overflow-y-auto bg-slate-50 animate-fade-in custom-scrollbar">
        <div class="p-8 lg:p-12 max-w-7xl mx-auto">
            <header class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
                <div>
                    <h2 class="text-xs font-black text-accent-600 uppercase tracking-[0.3em] mb-2 leading-none">Gestión
                        Académica</h2>
                    <h1 class="text-5xl font-black text-slate-950 tracking-tighter">Panel de <span
                            class="italic text-transparent bg-clip-text bg-gradient-to-r from-slate-950 to-accent-600">Tareas</span>
                    </h1>
                </div>
                <button onclick="toggleModal('add-assignment-modal')"
                    class="bg-accent-600 text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-accent-500 transition-all shadow-xl shadow-accent-500/20 active:scale-95 flex items-center">
                    <i data-lucide="plus" class="w-5 h-5 mr-2"></i> Crear Tarea
                </button>
            </header>

            <?php if ($success): ?>
                <div
                    class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-6 py-4 rounded-3xl text-sm font-bold mb-8 flex items-center animate-slide-up">
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
                <button onclick="window.location.href='assignments.php'"
                    class="px-8 py-4 bg-slate-100 text-slate-600 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-200 transition-all flex items-center justify-center">
                    <i data-lucide="refresh-ccw" class="w-4 h-4 mr-2"></i> Limpiar
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
                <?php foreach ($assignments as $a): ?>
                    <div
                        class="bg-white rounded-[3rem] p-10 shadow-xl shadow-slate-200/50 border border-slate-100 group hover:border-accent-200 transition-all duration-500 animate-slide-up">
                        <div class="flex items-start justify-between mb-8">
                            <div
                                class="w-16 h-16 bg-accent-50 text-accent-600 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-inner group-hover:bg-accent-600 group-hover:text-white transition-all transform group-hover:rotate-6">
                                <i data-lucide="file-text" class="w-8 h-8"></i>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="edit_assignment.php?id=<?= $a['id'] ?>" target="_blank"
                                    class="p-3 bg-slate-50 text-slate-400 rounded-xl hover:bg-primary-50 hover:text-primary-600 transition-all">
                                    <i data-lucide="edit-3" class="w-5 h-5"></i>
                                </a>
                                <a href="?delete=<?= $a['id'] ?>" onclick="return confirm('¿Eliminar?')"
                                    class="p-3 bg-slate-50 text-slate-400 rounded-xl hover:bg-rose-50 hover:text-rose-600 transition-all">
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                </a>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black text-slate-900 tracking-tight italic uppercase mb-2">
                                <?= $a['title'] ?>
                            </h3>
                            <p class="text-slate-500 font-medium mb-6 line-clamp-2"><?= $a['description'] ?></p>

                            <div class="flex flex-wrap items-center gap-4">
                                <span
                                    class="px-3 py-1.5 bg-primary-50 text-primary-600 text-[10px] font-black rounded-xl border border-primary-100 italic uppercase">
                                    <?= $a['grade_name'] ?>
                                </span>
                                <span
                                    class="px-3 py-1.5 bg-accent-50 text-accent-600 text-[10px] font-black rounded-xl border border-accent-100 italic uppercase">
                                    <?= $a['course_name'] ?>
                                </span>
                            </div>
                            <div class="mt-6 pt-6 border-t border-slate-50 flex items-center justify-between">
                                <div class="flex items-center text-xs font-bold text-slate-400">
                                    <i data-lucide="calendar" class="w-4 h-4 mr-2"></i>
                                    <?= date('d M, Y', strtotime($a['due_date'])) ?>
                                </div>
                                <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">ID:
                                    #<?= $a['id'] ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Add Modal -->
    <div id="add-assignment-modal"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 hidden flex items-center justify-center p-6 sm:p-12 overflow-y-auto">
        <div class="bg-white w-full max-w-2xl rounded-[3.5rem] p-12 shadow-2xl relative animate-slide-up">
            <header class="flex justify-between items-center mb-10">
                <h3 class="text-4xl font-black text-slate-950 tracking-tighter italic">Nueva <span
                        class="text-accent-500">Tarea</span></h3>
                <button onclick="toggleModal('add-assignment-modal')"
                    class="p-4 bg-slate-100 text-slate-400 rounded-full hover:bg-slate-900 hover:text-white transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </header>
            <form action="" method="POST" class="space-y-6">
                <input type="hidden" name="add_assignment" value="1">
                <input type="text" name="title" required placeholder="Título de la tarea"
                    class="w-full px-8 py-5 bg-slate-50 border-2 border-slate-100 rounded-[2rem] outline-none focus:border-accent-500 font-bold transition-all text-lg tracking-tight">
                <textarea name="description" placeholder="Descripción detallada..."
                    class="w-full px-8 py-5 bg-slate-50 border-2 border-slate-100 rounded-[2rem] outline-none focus:border-accent-500 font-bold h-32 resize-none transition-all"></textarea>
                <div class="grid grid-cols-2 gap-6">
                    <select name="grade_id" required
                        class="w-full px-8 py-5 bg-slate-50 border-2 border-slate-100 rounded-[2rem] outline-none font-black text-xs uppercase tracking-widest appearance-none">
                        <?php foreach ($grades as $g): ?>
                            <option value="<?= $g['id'] ?>"><?= $g['name'] ?></option><?php endforeach; ?>
                    </select>
                    <select name="course_id" required
                        class="w-full px-8 py-5 bg-slate-50 border-2 border-slate-100 rounded-[2rem] outline-none font-black text-xs uppercase tracking-widest appearance-none">
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option><?php endforeach; ?>
                    </select>
                </div>
                <input type="datetime-local" name="due_date" required
                    class="w-full px-8 py-5 bg-slate-50 border-2 border-slate-100 rounded-[2rem] outline-none focus:border-accent-500 font-black text-xs uppercase">
                <button type="submit"
                    class="w-full bg-accent-600 text-white font-black py-6 rounded-[2rem] mt-4 hover:bg-accent-500 shadow-2xl shadow-accent-500/30 transition-all uppercase tracking-widest text-sm">
                    Publicar Tarea
                </button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function applyFilters() {
            const grade = document.getElementById('grade_filter').value;
            const course = document.getElementById('course_filter').value;
            window.location.href = `assignments.php?grade_id=${grade}&course_id=${course}`;
        }

        function toggleModal(id) {
            const modal = document.getElementById(id);
            modal.classList.toggle('hidden');
            document.body.style.overflow = modal.classList.contains('hidden') ? 'auto' : 'hidden';
        }
    </script>
</body>

</html>