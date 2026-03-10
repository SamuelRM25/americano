<?php
session_start();
require_once 'config/database.php';
require_once 'includes/themes.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit;
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];
$grade_id = $_SESSION['grade_id'];
$grade_name = $_SESSION['grade_name'];

$theme = get_grade_theme($grade_name);

// Fetch all enrolled courses for this student
$stmt_courses = $pdo->prepare('SELECT c.id, c.name FROM courses c JOIN student_courses sc ON c.id = sc.course_id WHERE sc.student_id = ?');
$stmt_courses->execute([$student_id]);
$student_courses = $stmt_courses->fetchAll();

$course_ids = array_column($student_courses, 'id');
if (empty($course_ids)) {
    $course_ids = [0];
}
$placeholders = implode(',', array_fill(0, count($course_ids), '?'));

// Fetch exams and check if already taken
$stmt = $pdo->prepare("SELECT e.*, 
                       (SELECT COUNT(*) FROM exam_responses er WHERE er.exam_id = e.id AND er.student_id = ?) as is_taken
                       FROM exams e 
                       WHERE e.grade_id = ? AND e.course_id IN ($placeholders)
                       ORDER BY e.due_date ASC");
$stmt->execute(array_merge([$student_id, $grade_id], $course_ids));
$exams = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exámenes - Colegio Americano</title>
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
                            50: 'var(--accent-50)', 100: 'var(--accent-100)', 200: 'var(--accent-200)',
                            300: 'var(--accent-300)', 400: 'var(--accent-400)', 500: 'var(--accent-500)',
                            600: 'var(--accent-600)', 700: 'var(--accent-700)', 800: 'var(--accent-800)', 900: 'var(--accent-900)',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.6s ease-out forwards',
                        'slide-up': 'slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { opacity: '0', transform: 'translateY(24px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
    <style>
        :root {
            --accent-50: #f0f9ff;
            --accent-500: #0ea5e9;
            --accent-600: #0284c7;
        }

        <?php if ($theme['accent'] === 'emerald'): ?>
            :root {
                --accent-50: #ecfdf5;
                --accent-500: #10b981;
                --accent-600: #059669;
            }

        <?php elseif ($theme['accent'] === 'amber'): ?>
            :root {
                --accent-50: #fffbeb;
                --accent-500: #f59e0b;
                --accent-600: #d97706;
            }

        <?php elseif ($theme['accent'] === 'rose'): ?>
            :root {
                --accent-50: #fff1f2;
                --accent-500: #f43f5e;
                --accent-600: #e11d48;
            }

        <?php elseif ($theme['accent'] === 'violet'): ?>
            :root {
                --accent-50: #f5f3ff;
                --accent-500: #8b5cf6;
                --accent-600: #7c3aed;
            }

        <?php endif; ?>

        body {
            font-family: 'Outfit', sans-serif;
        }

        .sidebar-active {
            background: rgba(255, 255, 255, 0.05);
            border-left: 4px solid var(--accent-500);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
    </style>
</head>

<body class="bg-slate-50 min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <!-- Unified Student Sidebar -->
        <aside
            class="w-72 bg-slate-950 text-white flex-shrink-0 hidden lg:flex flex-col border-r border-white/5 shadow-2xl z-30 transform transition-transform duration-500">
            <div class="p-8">
                <div class="flex items-center space-x-4 mb-12 animate-fade-in">
                    <div class="bg-accent-500 p-3 rounded-2xl shadow-xl shadow-accent-500/20">
                        <i data-lucide="<?= $theme['icon'] ?>" class="w-8 h-8 text-white"></i>
                    </div>
                    <div>
                        <span
                            class="text-xl font-black tracking-tighter block leading-none italic uppercase">ALUMNO</span>
                        <span
                            class="text-[10px] text-slate-500 font-bold uppercase tracking-widest leading-none">Colegio
                            Americano</span>
                    </div>
                </div>

                <nav class="space-y-2">
                    <a href="dashboard.php"
                        class="flex items-center space-x-3 p-4 rounded-2xl transition-all text-slate-400 hover:text-white hover:bg-white/5 group">
                        <i data-lucide="layout-dashboard"
                            class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                        <span class="font-medium">Inicio</span>
                    </a>
                    <a href="assignments.php"
                        class="flex items-center space-x-3 p-4 rounded-2xl transition-all text-slate-400 hover:text-white hover:bg-white/5 group">
                        <i data-lucide="book-open" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                        <span class="font-medium">Mis Tareas</span>
                    </a>
                    <a href="exams.php"
                        class="flex items-center space-x-3 p-4 rounded-2xl transition-all sidebar-active group">
                        <i data-lucide="clipboard-check" class="w-5 h-5 text-accent-500"></i>
                        <span class="font-bold text-white">Exámenes</span>
                    </a>
                    <a href="calendar.php"
                        class="flex items-center space-x-3 p-4 rounded-2xl transition-all text-slate-400 hover:text-white hover:bg-white/5 group">
                        <i data-lucide="calendar" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                        <span class="font-medium">Mi Agenda</span>
                    </a>
                    <a href="chat.php"
                        class="flex items-center space-x-3 p-4 rounded-2xl transition-all text-slate-400 hover:text-white hover:bg-white/5 group">
                        <i data-lucide="message-square" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                        <span class="font-medium">Chat</span>
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

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto bg-slate-50 relative p-8 lg:p-12">
            <header class="mb-12 relative z-10 animate-fade-in">
                <?php if (isset($_GET['msg'])): ?>
                    <?php if ($_GET['msg'] === 'success'): ?>
                        <div
                            class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-8 py-6 rounded-[2rem] text-sm font-bold mb-12 flex items-center animate-slide-up shadow-xl shadow-emerald-500/10">
                            <i data-lucide="check-circle" class="w-6 h-6 mr-4 text-emerald-500"></i>
                            <div>
                                <p class="text-lg">¡Examen enviado con éxito!</p>
                                <p class="text-xs font-medium opacity-70">Tu evaluación ha sido registrada correctamente.</p>
                            </div>
                        </div>
                    <?php elseif ($_GET['msg'] === 'already_taken'): ?>
                        <div
                            class="bg-amber-50 border border-amber-100 text-amber-700 px-8 py-6 rounded-[2rem] text-sm font-bold mb-12 flex items-center animate-slide-up shadow-xl shadow-amber-500/10">
                            <i data-lucide="alert-triangle" class="w-6 h-6 mr-4 text-amber-500"></i>
                            <div>
                                <p class="text-lg">Ya has realizado este examen</p>
                                <p class="text-xs font-medium opacity-70">No se permiten múltiples intentos para esta
                                    evaluación.</p>
                            </div>
                        </div>
                    <?php elseif ($_GET['msg'] === 'error'): ?>
                        <div
                            class="bg-rose-50 border border-rose-100 text-rose-700 px-8 py-6 rounded-[2rem] text-sm font-bold mb-12 flex items-center animate-slide-up shadow-xl shadow-rose-500/10">
                            <i data-lucide="x-circle" class="w-6 h-6 mr-4 text-rose-500"></i>
                            <div>
                                <p class="text-lg">Error al enviar el examen</p>
                                <p class="text-xs font-medium opacity-70">
                                    <?= htmlspecialchars($_GET['detail'] ?? 'Ocurrió un problema inesperado.') ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <h2 class="text-xs font-black text-accent-600 uppercase tracking-[0.3em] mb-2 leading-none">Académico /
                    Evaluaciones</h2>
                <h1 class="text-6xl font-black text-slate-900 tracking-tighter mb-4">Mis <span
                        class="italic text-transparent bg-clip-text bg-gradient-to-r from-slate-900 to-accent-600">Exámenes</span>
                </h1>
                <p class="text-slate-500 font-medium">Demuestra tus conocimientos y alcanza tus metas.</p>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8 relative z-10">
                <?php if (empty($exams)): ?>
                    <div
                        class="col-span-full bg-white p-24 rounded-[3rem] text-center border-4 border-dashed border-slate-100 flex flex-col items-center">
                        <i data-lucide="coffee" class="w-20 h-20 text-slate-200 mb-6"></i>
                        <p class="text-2xl font-black text-slate-300 italic">No tienes exámenes programados.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($exams as $index => $exam): ?>
                        <div class="bg-white p-10 rounded-[3rem] shadow-xl shadow-slate-200/50 border border-slate-100 hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group animate-slide-up"
                            style="animation-delay: <?= $index * 100 ?>ms">
                            <div class="flex items-center mb-8">
                                <div
                                    class="w-16 h-16 bg-accent-50 text-accent-600 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-inner group-hover:bg-accent-600 group-hover:text-white transition-all transform group-hover:rotate-6">
                                    <i data-lucide="clipboard-list" class="w-8 h-8"></i>
                                </div>
                                <div class="ml-6">
                                    <h3
                                        class="text-2xl font-black text-slate-900 tracking-tight leading-none uppercase italic mb-1">
                                        <?= $exam['title'] ?>
                                    </h3>
                                    <span
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Evaluación
                                        Teórica</span>
                                </div>
                            </div>

                            <div class="space-y-4 mb-8">
                                <div class="flex items-center text-sm font-bold text-slate-500">
                                    <i data-lucide="calendar" class="w-4 h-4 mr-3 text-accent-500"></i>
                                    Fecha: <?= date('d M, Y', strtotime($exam['due_date'])) ?>
                                </div>
                                <div class="flex items-center text-sm font-bold text-slate-500">
                                    <i data-lucide="clock" class="w-4 h-4 mr-3 text-accent-500"></i>
                                    Hora: <?= date('H:i', strtotime($exam['due_date'])) ?>
                                </div>
                            </div>

                            <div class="pt-8 border-t border-slate-50">
                                <?php if ($exam['is_taken']): ?>
                                    <div
                                        class="w-full bg-emerald-50 text-emerald-600 font-black py-5 rounded-2xl flex items-center justify-center space-x-3 text-xs uppercase tracking-widest border border-emerald-100">
                                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                                        <span>Completado</span>
                                    </div>
                                <?php else: ?>
                                    <a href="take_exam.php?id=<?= $exam['id'] ?>"
                                        class="w-full bg-slate-900 text-white font-black py-5 rounded-2xl hover:bg-accent-600 transition-all transform active:scale-95 shadow-xl shadow-slate-900/10 flex items-center justify-center space-x-3 text-xs uppercase tracking-widest">
                                        <span>Empezar Ahora</span>
                                        <i data-lucide="zap" class="w-4 h-4"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
        <!-- Mobile Navigation Bar -->
        <nav
            class="lg:hidden fixed bottom-0 inset-x-0 bg-slate-950 border-t border-white/5 px-8 py-4 flex items-center justify-between z-50">
            <a href="dashboard.php" class="p-4 text-slate-400 hover:text-white transition-colors">
                <i data-lucide="layout-dashboard" class="w-6 h-6"></i>
            </a>
            <a href="assignments.php" class="p-4 text-slate-400 hover:text-white transition-colors">
                <i data-lucide="book-open" class="w-6 h-6"></i>
            </a>
            <a href="chat.php" class="p-4 text-slate-400 hover:text-white transition-colors">
                <i data-lucide="message-square" class="w-6 h-6"></i>
            </a>
            <a href="exams.php" class="p-4 text-accent-500 bg-white/5 rounded-2xl">
                <i data-lucide="clipboard-check" class="w-6 h-6"></i>
            </a>
            <a href="calendar.php" class="p-4 text-slate-400 hover:text-white transition-colors">
                <i data-lucide="calendar" class="w-6 h-6"></i>
            </a>
        </nav>

        <script>lucide.createIcons();</script>
</body>

</html>