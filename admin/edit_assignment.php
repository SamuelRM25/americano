<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$assignment_id = $_GET['id'] ?? null;
if (!$assignment_id) {
    header('Location: assignments.php');
    exit;
}

$success = '';
$error = '';

// Fetch Assignment
$stmt = $pdo->prepare('SELECT a.*, g.name as grade_name, c.name as course_name FROM assignments a JOIN grades g ON a.grade_id = g.id JOIN courses c ON a.course_id = c.id WHERE a.id = ?');
$stmt->execute([$assignment_id]);
$assignment = $stmt->fetch();

if (!$assignment) {
    header('Location: assignments.php');
    exit;
}

// Handle Assignment update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_assignment'])) {
    $title = $_POST['title'] ?? '';
    $desc = $_POST['description'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $grade_id = $_POST['grade_id'] ?? '';
    $course_id = $_POST['course_id'] ?? '';

    if (!empty($title) && !empty($due_date)) {
        try {
            $stmt = $pdo->prepare('UPDATE assignments SET title = ?, description = ?, due_date = ?, grade_id = ?, course_id = ? WHERE id = ?');
            $stmt->execute([$title, $desc, $due_date, $grade_id, $course_id, $assignment_id]);
            $success = "Tarea actualizada correctamente.";
            // Refresh assignment data
            $stmt = $pdo->prepare('SELECT a.*, g.name as grade_name, c.name as course_name FROM assignments a JOIN grades g ON a.grade_id = g.id JOIN courses c ON a.course_id = c.id WHERE a.id = ?');
            $stmt->execute([$assignment_id]);
            $assignment = $stmt->fetch();
        } catch (Exception $e) {
            $error = "Error al actualizar tarea: " . $e->getMessage();
        }
    }
}

$grades = $pdo->query('SELECT * FROM grades')->fetchAll();
$courses = $pdo->query('SELECT * FROM courses')->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Tarea - Admin</title>
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
    </style>
</head>

<body class="bg-slate-50 min-h-screen">
    <main class="p-8 lg:p-12 max-w-4xl mx-auto">
        <nav class="mb-12">
            <a href="assignments.php"
                class="inline-flex items-center text-slate-400 hover:text-accent-600 font-bold uppercase tracking-widest text-xs transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Volver a Tareas
            </a>
        </nav>

        <header class="mb-12">
            <h2 class="text-xs font-black text-accent-600 uppercase tracking-[0.3em] mb-2 leading-none">Módulo de Tarea
            </h2>
            <h1 class="text-5xl font-black text-slate-950 tracking-tighter italic">Editar <span class="text-accent-500">
                    <?= $assignment['title'] ?>
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

        <div
            class="bg-white rounded-[3.5rem] p-12 shadow-xl shadow-slate-200/50 border border-slate-100 animate-slide-up">
            <form action="" method="POST" class="space-y-8">
                <input type="hidden" name="update_assignment" value="1">

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Título de la
                        Tarea</label>
                    <input type="text" name="title" value="<?= $assignment['title'] ?>" required
                        class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2.5rem] outline-none focus:border-accent-500 font-bold transition-all text-xl tracking-tight text-slate-900">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Descripción /
                        Instrucciones</label>
                    <textarea name="description"
                        class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2.5rem] outline-none focus:border-accent-500 font-bold h-48 resize-none transition-all text-lg text-slate-600"><?= $assignment['description'] ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label
                            class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Grado</label>
                        <select name="grade_id" required
                            class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2.5rem] outline-none focus:border-accent-500 font-black text-xs uppercase tracking-widest appearance-none">
                            <?php foreach ($grades as $g): ?>
                                <option value="<?= $g['id'] ?>" <?= $assignment['grade_id'] == $g['id'] ? 'selected' : '' ?>>
                                    <?= $g['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label
                            class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Curso</label>
                        <select name="course_id" required
                            class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2.5rem] outline-none focus:border-accent-500 font-black text-xs uppercase tracking-widest appearance-none">
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $assignment['course_id'] == $c['id'] ? 'selected' : '' ?>>
                                    <?= $c['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Fecha & Hora
                        Límite de Entrega</label>
                    <input type="datetime-local" name="due_date"
                        value="<?= date('Y-m-d\TH:i', strtotime($assignment['due_date'])) ?>" required
                        class="w-full px-8 py-5 bg-slate-50 border-2 border-transparent border-slate-100 rounded-[2.5rem] outline-none focus:border-accent-500 font-black text-xs uppercase tracking-widest">
                </div>

                <div class="pt-6">
                    <button type="submit"
                        class="w-full bg-slate-950 text-white font-black py-7 rounded-[2.5rem] hover:bg-accent-600 shadow-2xl shadow-accent-500/30 transition-all uppercase tracking-[0.2em] text-sm group">
                        <i data-lucide="save"
                            class="w-5 h-5 inline-block mr-2 group-hover:scale-110 transition-transform"></i>
                        Actualizar Tarea
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>