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

// Handle Student Deletion
if (isset($_GET['delete_student'])) {
    try {
        $stmt = $pdo->prepare('DELETE FROM students WHERE id = ?');
        $stmt->execute([$_GET['delete_student']]);
        header('Location: dashboard.php?msg=deleted');
        exit;
    } catch (Exception $e) {
        $error = "No se puede eliminar el estudiante. Posiblemente tiene tareas o exámenes asociados.";
    }
}

// Handle Student Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $name = $_POST['name'] ?? '';
    $grade_id = $_POST['grade_id'] ?? '';
    $course_ids = $_POST['course_ids'] ?? []; // Array of courses

    // Simplify code: First 3 letters of name + 3 random digits
    $clean_name = strtoupper(preg_replace('/[^A-Za-z]/', '', $name));
    $prefix = substr($clean_name, 0, 3);
    if (strlen($prefix) < 3)
        $prefix = str_pad($prefix, 3, 'X');
    $code = $prefix . rand(100, 999);

    if (!empty($name) && !empty($grade_id) && !empty($course_ids)) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO students (name, code, grade_id) VALUES (?, ?, ?)');
            $stmt->execute([$name, $code, $grade_id]);
            $student_id = $pdo->lastInsertId();

            $stmt_course = $pdo->prepare('INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)');
            foreach ($course_ids as $cid) {
                $stmt_course->execute([$student_id, $cid]);
            }
            $pdo->commit();
            $success = "Estudiante registrado correctamente. Código: $code";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error al registrar: " . $e->getMessage();
        }
    } else {
        $error = "Por favor selecciona al menos un curso y completa los campos.";
    }
}

// Handle Student Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_student'])) {
    $id = $_POST['student_id'];
    $name = $_POST['name'];
    $grade_id = $_POST['grade_id'];
    $course_ids = $_POST['course_ids'] ?? [];

    if (!empty($name) && !empty($grade_id) && !empty($course_ids)) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('UPDATE students SET name = ?, grade_id = ? WHERE id = ?');
            $stmt->execute([$name, $grade_id, $id]);

            // Sync courses
            $pdo->prepare('DELETE FROM student_courses WHERE student_id = ?')->execute([$id]);
            $stmt_course = $pdo->prepare('INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)');
            foreach ($course_ids as $cid) {
                $stmt_course->execute([$id, $cid]);
            }
            $pdo->commit();
            $success = "Estudiante actualizado correctamente.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error al actualizar: " . $e->getMessage();
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $success = "Estudiante eliminado correctamente.";
}

// Fetch grades and teacher's specific courses
$grades = $pdo->query('SELECT * FROM grades')->fetchAll();
$courses = $pdo->prepare('SELECT * FROM courses WHERE admin_id = ?');
$courses->execute([$_SESSION['admin_id']]);
$courses = $courses->fetchAll();

$teacher_course_ids = array_column($courses, 'id');
if (empty($teacher_course_ids)) {
    $teacher_course_ids = [0]; // Prevent empty IN clause issues
}

// Filtering logic
$filter_grade = $_GET['grade_id'] ?? '';
$filter_course = $_GET['course_id'] ?? '';

// Fetch students associated with the current teacher's courses
$query = 'SELECT DISTINCT s.*, g.name as grade_name 
          FROM students s 
          JOIN grades g ON s.grade_id = g.id 
          JOIN student_courses sc ON s.id = sc.student_id
          JOIN courses c ON sc.course_id = c.id
          WHERE c.admin_id = ?';

$params = [$_SESSION['admin_id']];

if ($filter_grade) {
    $query .= ' AND s.grade_id = ?';
    $params[] = $filter_grade;
}
if ($filter_course) {
    $query .= ' AND sc.course_id = ?';
    $params[] = $filter_course;
}

$query .= ' ORDER BY s.id DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Add course list to each student for the UI
foreach ($students as &$s) {
    $stmt_sc = $pdo->prepare('SELECT c.name, c.id FROM courses c JOIN student_courses sc ON c.id = sc.course_id WHERE sc.student_id = ?');
    $stmt_sc->execute([$s['id']]);
    $s['courses'] = $stmt_sc->fetchAll();
    $s['course_names'] = implode(', ', array_column($s['courses'], 'name'));
    $s['course_ids'] = array_column($s['courses'], 'id');
}
unset($s);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Estudiantes - Admin</title>
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
    <?php render_admin_sidebar('students'); ?>

    <main class="flex-1 overflow-y-auto bg-slate-50 animate-fade-in custom-scrollbar">
        <div class="p-8 lg:p-12 max-w-7xl mx-auto">
            <header class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
                <div>
                    <h2 class="text-xs font-black text-primary-600 uppercase tracking-[0.3em] mb-2 leading-none">Gestión
                        Institucional</h2>
                    <h1 class="text-5xl font-black text-slate-950 tracking-tighter">Panel de <span
                            class="italic text-transparent bg-clip-text bg-gradient-to-r from-slate-950 to-primary-600">Estudiantes</span>
                    </h1>
                </div>
                <button onclick="toggleModal('add-student-modal')"
                    class="bg-primary-600 text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-primary-500 transition-all shadow-xl shadow-primary-500/20 active:scale-95 flex items-center">
                    <i data-lucide="user-plus" class="w-5 h-5 mr-2"></i> Registrar Alumno
                </button>
            </header>

            <?php if ($success): ?>
                <div
                    class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-6 py-4 rounded-3xl text-sm font-bold mb-8 flex items-center animate-slide-up shadow-sm">
                    <i data-lucide="check-circle" class="w-5 h-5 mr-3"></i> <?= $success ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div
                    class="bg-rose-50 border border-rose-100 text-rose-700 px-6 py-4 rounded-3xl text-sm font-bold mb-8 flex items-center animate-shake">
                    <i data-lucide="alert-circle" class="w-5 h-5 mr-3"></i> <?= $error ?>
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
                                <?= $g['name'] ?></option>
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
                                <?= $c['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button onclick="window.location.href='dashboard.php'"
                    class="px-8 py-4 bg-slate-100 text-slate-600 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-200 transition-all flex items-center justify-center">
                    <i data-lucide="refresh-ccw" class="w-4 h-4 mr-2"></i> Limpiar
                </button>
            </div>

            <!-- Students List -->
            <div class="bg-white rounded-[3rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead
                            class="bg-slate-50 text-[10px] uppercase font-black tracking-widest text-slate-400 border-b border-slate-100">
                            <tr>
                                <th class="px-10 py-6">Código / Acceso</th>
                                <th class="px-10 py-6">Nombre del Estudiante</th>
                                <th class="px-10 py-6">Grado y Sección</th>
                                <th class="px-10 py-6">Estatus</th>
                                <th class="px-10 py-6 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach ($students as $s): ?>
                                <tr class="hover:bg-slate-50 transition-colors group/row">
                                    <td class="px-10 py-8">
                                        <span
                                            class="text-xs font-black bg-slate-950 text-white px-3 py-1.5 rounded-xl tracking-widest">
                                            <?= $s['code'] ?>
                                        </span>
                                    </td>
                                    <td class="px-10 py-8">
                                        <div class="flex items-center">
                                            <div
                                                class="w-10 h-10 bg-primary-50 text-primary-600 rounded-full flex items-center justify-center font-black mr-4 group-hover/row:scale-110 transition-transform">
                                                <?= substr($s['name'], 0, 1) ?>
                                            </div>
                                            <span class="text-slate-900 font-bold"><?= $s['name'] ?></span>
                                        </div>
                                    </td>
                                    <td class="px-10 py-8">
                                        <div class="flex flex-col">
                                            <span
                                                class="text-[10px] font-black uppercase text-primary-500 italic"><?= $s['grade_name'] ?></span>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                <?php foreach ($s['courses'] as $c): ?>
                                                    <span class="text-[9px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded-lg font-bold uppercase"><?= $c['name'] ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-10 py-8">
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-emerald-50 text-emerald-600 border border-emerald-100">
                                            Activo
                                        </span>
                                    </td>
                                    <td class="px-10 py-8 text-right space-x-2">
                                        <button onclick='openEditModal(<?= json_encode($s) ?>)'
                                            class="p-3 text-slate-300 hover:text-primary-500 transition-colors inline-block hover:scale-110 active:scale-95">
                                            <i data-lucide="edit-3" class="w-6 h-6"></i>
                                        </button>
                                        <a href="?delete_student=<?= $s['id'] ?>"
                                            onclick="return confirm('¿Eliminar estudiante? Esta acción es irreversible.')"
                                            class="p-3 text-slate-300 hover:text-rose-500 transition-colors inline-block hover:scale-110 active:scale-95">
                                            <i data-lucide="trash-2" class="w-6 h-6"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modals -->
    <div id="add-student-modal"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 hidden flex items-center justify-center p-6 sm:p-12 overflow-y-auto">
        <div class="bg-white w-full max-w-2xl rounded-[3.5rem] p-12 shadow-2xl relative animate-slide-up">
            <header class="flex justify-between items-center mb-10">
                <h3 class="text-4xl font-black text-slate-950 tracking-tighter italic">Nuevo <span
                        class="text-primary-500">Estudiante</span></h3>
                <button onclick="toggleModal('add-student-modal')"
                    class="p-4 bg-slate-100 text-slate-400 rounded-full hover:bg-slate-900 hover:text-white transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </header>
            <form action="" method="POST" class="space-y-6">
                <input type="hidden" name="add_student" value="1">
                <input type="text" name="name" required placeholder="Nombre completo"
                    class="w-full px-8 py-5 bg-slate-50 border-2 border-slate-100 rounded-[2rem] outline-none focus:border-primary-500 font-bold transition-all text-lg mb-4">
                <div class="space-y-4">
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Grado</label>
                    <select name="grade_id" required
                        class="w-full px-8 py-5 bg-slate-50 border-2 border-slate-100 rounded-[2rem] font-black text-xs uppercase tracking-widest appearance-none outline-none focus:border-primary-500">
                        <?php foreach ($grades as $g): ?>
                            <option value="<?= $g['id'] ?>"><?= $g['name'] ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="space-y-4">
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Asignar Cursos</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-48 overflow-y-auto p-4 bg-slate-50 rounded-3xl border-2 border-slate-100 custom-scrollbar">
                        <?php foreach ($courses as $c): ?>
                            <label class="flex items-center space-x-3 cursor-pointer group">
                                <input type="checkbox" name="course_ids[]" value="<?= $c['id'] ?>" class="w-5 h-5 rounded-lg border-2 border-slate-200 text-primary-600 focus:ring-primary-500">
                                <span class="text-xs font-bold text-slate-600 group-hover:text-primary-600 transition-colors"><?= $c['name'] ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit"
                    class="w-full bg-primary-600 text-white font-black py-6 rounded-[2rem] mt-4 hover:bg-primary-500 shadow-xl shadow-primary-500/20 transition-all uppercase tracking-widest text-sm">
                    Generar Acceso
                </button>
            </form>
        </div>
    </div>

    <div id="edit-student-modal"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 hidden flex items-center justify-center p-6 overflow-y-auto">
        <div class="bg-white w-full max-w-2xl rounded-[3.5rem] p-12 shadow-2xl relative animate-slide-up">
            <header class="flex justify-between items-center mb-10">
                <h3 class="text-4xl font-black text-slate-950 tracking-tighter italic">Editar <span
                        class="text-primary-500">Perfil</span></h3>
                <button onclick="toggleModal('edit-student-modal')"
                    class="p-4 bg-slate-100 text-slate-400 rounded-full hover:bg-slate-950 hover:text-white transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </header>
            <form action="" method="POST" class="space-y-6">
                <input type="hidden" name="edit_student" value="1">
                <input type="hidden" name="student_id" id="edit_id">
                <input type="text" name="name" id="edit_name" required
                    class="w-full px-8 py-5 bg-slate-50 border-2 border-slate-100 rounded-[2rem] font-bold text-lg mb-4">
                <div class="space-y-4">
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Grado</label>
                    <select name="grade_id" id="edit_grade"
                        class="w-full px-8 py-5 bg-slate-50 border-2 border-slate-100 rounded-[2rem] font-black text-xs uppercase tracking-widest outline-none focus:border-primary-500">
                        <?php foreach ($grades as $g): ?>
                            <option value="<?= $g['id'] ?>"><?= $g['name'] ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="space-y-4">
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Mis Cursos Asignados</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-48 overflow-y-auto p-4 bg-slate-50 rounded-3xl border-2 border-slate-100 custom-scrollbar" id="edit_courses_container">
                        <?php foreach ($courses as $c): ?>
                            <label class="flex items-center space-x-3 cursor-pointer group">
                                <input type="checkbox" name="course_ids[]" value="<?= $c['id'] ?>" class="edit-course-checkbox w-5 h-5 rounded-lg border-2 border-slate-200 text-primary-600 focus:ring-primary-500">
                                <span class="text-xs font-bold text-slate-600 group-hover:text-primary-600 transition-colors"><?= $c['name'] ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit"
                    class="w-full bg-slate-950 text-white font-black py-6 rounded-[2rem] hover:bg-primary-600 transition-all uppercase tracking-widest text-sm shadow-xl">
                    Guardar Cambios
                </button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function applyFilters() {
            const grade = document.getElementById('grade_filter').value;
            const course = document.getElementById('course_filter').value;
            window.location.href = `dashboard.php?grade_id=${grade}&course_id=${course}`;
        }

        function toggleModal(id) {
            const modal = document.getElementById(id);
            modal.classList.toggle('hidden');
            document.body.style.overflow = modal.classList.contains('hidden') ? 'auto' : 'hidden';
        }

        function openEditModal(s) {
            document.getElementById('edit_id').value = s.id;
            document.getElementById('edit_name').value = s.name;
            document.getElementById('edit_grade').value = s.grade_id;
            
            // Clear and set checkboxes
            const checkboxes = document.querySelectorAll('.edit-course-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = s.course_ids.includes(parseInt(cb.value));
            });
            
            toggleModal('edit-student-modal');
        }
    </script>
</body>

</html>