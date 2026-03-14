<?php
session_start();
require_once 'config/database.php';
require_once 'includes/themes.php';
require_once 'includes/student_sidebar.php';

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

// Fetch assignments and check if already submitted
$stmt = $pdo->prepare("SELECT a.*, c.name as course_name,
                       (SELECT COUNT(*) FROM student_assignments sa WHERE sa.assignment_id = a.id AND sa.student_id = ?) as is_submitted
                       FROM assignments a 
                       JOIN courses c ON a.course_id = c.id
                       WHERE a.grade_id = ? AND a.course_id IN ($placeholders)
                       ORDER BY a.due_date ASC");
$stmt->execute(array_merge([$student_id, $grade_id], $course_ids));
$assignments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tareas - Colegio Americano</title>
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

<body class="bg-slate-50 selection:bg-accent-500 selection:text-white">
    <div class="flex h-screen overflow-hidden">
        <!-- Shared Student Sidebar -->
        <?php render_student_sidebar('assignments', $theme, $student_name); ?>

        <!-- Main Content -->
        <main class="flex-1 min-h-0 overflow-y-auto bg-slate-50 relative p-8 lg:p-12 custom-scrollbar scroll-smooth">
            <header class="mb-12 relative z-10 animate-fade-in">
                <?php if (isset($_GET['msg'])): ?>
                    <?php if ($_GET['msg'] === 'success'): ?>
                        <div
                            class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-8 py-6 rounded-[2rem] text-sm font-bold mb-12 flex items-center animate-slide-up shadow-xl shadow-emerald-500/10">
                            <i data-lucide="check-circle" class="w-6 h-6 mr-4 text-emerald-500"></i>
                            <div>
                                <p class="text-lg">¡Tarea enviada con éxito!</p>
                                <p class="text-xs font-medium opacity-70">Tu profesor revisará tu entrega pronto.</p>
                            </div>
                        </div>
                    <?php elseif ($_GET['msg'] === 'error'): ?>
                        <div
                            class="bg-rose-50 border border-rose-100 text-rose-700 px-8 py-6 rounded-[2rem] text-sm font-bold mb-12 flex items-center animate-slide-up shadow-xl shadow-rose-500/10">
                            <i data-lucide="x-circle" class="w-6 h-6 mr-4 text-rose-500"></i>
                            <div>
                                <p class="text-lg">Error al enviar la tarea</p>
                                <p class="text-xs font-medium opacity-70">Ocurrió un problema al procesar tu archivo.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <h2 class="text-xs font-black text-accent-600 uppercase tracking-[0.3em] mb-2 leading-none">Académico /
                    Actividades</h2>
                <h1 class="text-6xl font-black text-slate-900 tracking-tighter mb-4 italic uppercase">Mis <span
                        class="text-transparent bg-clip-text bg-gradient-to-r from-slate-900 to-accent-600">Tareas</span>
                </h1>
                <p class="text-slate-500 font-medium">Gestiona tus entregas y mantente al día con tus clases.</p>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8 relative z-10">
                <?php if (empty($assignments)): ?>
                    <div
                        class="col-span-full bg-white p-24 rounded-[3rem] text-center border-4 border-dashed border-slate-100 flex flex-col items-center">
                        <i data-lucide="inbox" class="w-20 h-20 text-slate-200 mb-6"></i>
                        <p class="text-2xl font-black text-slate-300 italic">No tienes tareas pendientes por ahora.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($assignments as $index => $assignment): ?>
                        <div class="bg-white p-10 rounded-[3rem] shadow-xl shadow-slate-200/50 border border-slate-100 hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group animate-slide-up"
                            style="animation-delay: <?= $index * 100 ?>ms">
                            <div class="flex items-center mb-8">
                                <div
                                    class="w-16 h-16 bg-accent-50 text-accent-600 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-inner group-hover:bg-accent-600 group-hover:text-white transition-all transform group-hover:rotate-6">
                                    <i data-lucide="file-text" class="w-8 h-8"></i>
                                </div>
                                <div class="ml-6">
                                    <h3
                                        class="text-2xl font-black text-slate-900 tracking-tight leading-none uppercase italic mb-1">
                                        <?= $assignment['title'] ?>
                                    </h3>
                                    <span
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none"><?= $assignment['course_name'] ?></span>
                                </div>
                            </div>

                            <p class="text-slate-500 text-sm font-medium mb-8 leading-relaxed line-clamp-2">
                                <?= $assignment['description'] ?>
                            </p>

                            <div class="space-y-4 mb-8">
                                <div class="flex items-center text-sm font-bold text-slate-500">
                                    <i data-lucide="calendar" class="w-4 h-4 mr-3 text-accent-500"></i>
                                    Vence: <?= date('d M, Y', strtotime($assignment['due_date'])) ?>
                                </div>
                                <?php if ($assignment['file_path']): ?>
                                    <a href="admin/<?= $assignment['file_path'] ?>" download
                                        class="flex items-center text-xs font-black text-accent-600 uppercase tracking-widest hover:text-accent-700 transition-colors">
                                        <i data-lucide="download" class="w-4 h-4 mr-2"></i>
                                        Descargar Guía
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div class="pt-8 border-t border-slate-50">
                                <?php if ($assignment['is_submitted']): ?>
                                    <div
                                        class="w-full bg-emerald-50 text-emerald-600 font-black py-5 rounded-2xl flex items-center justify-center space-x-3 text-xs uppercase tracking-widest border border-emerald-100">
                                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                                        <span>Entregada</span>
                                    </div>
                                <?php else: ?>
                                    <form action="submit_assignment.php" method="POST" enctype="multipart/form-data"
                                        class="space-y-4">
                                        <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                                        <div class="relative group/input">
                                            <input type="file" name="submission" required
                                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                            <div
                                                class="w-full bg-slate-50 border-2 border-dashed border-slate-200 py-4 rounded-2xl flex items-center justify-center text-xs font-bold text-slate-400 group-hover/input:border-accent-500 group-hover/input:text-accent-500 transition-all">
                                                <i data-lucide="upload-cloud" class="w-4 h-4 mr-2"></i>
                                                Seleccionar archivo
                                            </div>
                                        </div>
                                        <button type="submit"
                                            class="w-full bg-slate-950 text-white font-black py-5 rounded-2xl hover:bg-accent-600 transition-all transform active:scale-95 shadow-xl shadow-slate-900/10 flex items-center justify-center space-x-3 text-xs uppercase tracking-widest">
                                            <span>Subir Tarea</span>
                                            <i data-lucide="send" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
    <?php include 'includes/footer_scripts.php'; ?>
</body>

</html>