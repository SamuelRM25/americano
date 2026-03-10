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
$course_id = $_SESSION['course_id'];
$grade_name = $_SESSION['grade_name'];
$course_name = $_SESSION['course_name'];

$theme = get_grade_theme($grade_name);

// Fetch assignments and exams for the calendar
$stmt = $pdo->prepare('SELECT title as title, due_date as start, "assignment" as type FROM assignments WHERE grade_id = ? AND course_id = ?
                       UNION
                       SELECT title as title, due_date as start, "exam" as type FROM exams WHERE grade_id = ? AND course_id = ?');
$stmt->execute([$grade_id, $course_id, $grade_id, $course_id]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format events for FullCalendar
$formatted_events = array_map(function ($e) {
    return [
        'title' => ($e['type'] === 'exam' ? '🎓 ' : '📝 ') . $e['title'],
        'start' => $e['start'],
        'className' => $e['type'] === 'exam' ? 'event-exam' : 'event-assignment',
        'allDay' => false
    ];
}, $events);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario - Colegio Americano</title>
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
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
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

        /* FullCalendar Customization */
        .fc {
            --fc-border-color: rgba(226, 232, 240, 0.8);
            --fc-today-bg-color: var(--accent-50);
        }

        .fc .fc-toolbar-title {
            font-weight: 900;
            font-style: italic;
            font-size: 1.5rem !important;
            text-transform: uppercase;
            color: #0f172a;
        }

        .fc .fc-button-primary {
            background: #0f172a;
            border: none;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            border-radius: 1rem;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }

        .fc .fc-button-primary:hover {
            background: var(--accent-600);
            transform: translateY(-2px);
        }

        .fc .fc-button-primary:disabled {
            background: #f1f5f9;
            color: #94a3b8;
        }

        .fc-theme-standard td,
        .fc-theme-standard th {
            border: 1px solid rgba(226, 232, 240, 0.5);
        }

        .fc-daygrid-day-number {
            font-weight: 800;
            font-size: 0.9rem;
            color: #64748b;
            padding: 1rem !important;
        }

        .fc-col-header-cell-cushion {
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: 0.7rem;
            color: #94a3b8;
            padding: 1rem !important;
        }

        .event-exam {
            background: #0f172a !important;
            border: none !important;
            padding: 4px 10px !important;
            border-radius: 8px !important;
            font-weight: 700 !important;
            font-size: 0.75rem !important;
            color: white !important;
        }

        .event-assignment {
            background: var(--accent-500) !important;
            border: none !important;
            padding: 4px 10px !important;
            border-radius: 8px !important;
            font-weight: 700 !important;
            font-size: 0.75rem !important;
            color: white !important;
        }

        .glass-container {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 3rem;
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
                        class="flex items-center space-x-3 p-4 rounded-2xl transition-all text-slate-400 hover:text-white hover:bg-white/5 group">
                        <i data-lucide="clipboard-check" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                        <span class="font-medium">Exámenes</span>
                    </a>
                    <a href="calendar.php"
                        class="flex items-center space-x-3 p-4 rounded-2xl transition-all sidebar-active group">
                        <i data-lucide="calendar" class="w-5 h-5 text-accent-500"></i>
                        <span class="font-bold text-white">Mi Agenda</span>
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
                <h2 class="text-xs font-black text-accent-600 uppercase tracking-[0.3em] mb-2 leading-none">Académico /
                    Agenda</h2>
                <h1 class="text-6xl font-black text-slate-900 tracking-tighter mb-4">Mi <span
                        class="italic text-transparent bg-clip-text bg-gradient-to-r from-slate-900 to-accent-600">Calendario</span>
                </h1>
                <div class="flex items-center space-x-8 text-xs font-black uppercase tracking-[0.2em] text-slate-400">
                    <span class="flex items-center"><span class="w-3 h-3 rounded-full bg-accent-500 mr-2"></span>
                        Tareas</span>
                    <span class="flex items-center"><span class="w-3 h-3 rounded-full bg-slate-900 mr-2"></span>
                        Exámenes</span>
                </div>
            </header>

            <div class="glass-container p-10 bg-white shadow-xl shadow-slate-200/50 animate-slide-up relative z-10">
                <div id='calendar'></div>
            </div>
        </main>
    </div>

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
        <a href="exams.php" class="p-4 text-slate-400 hover:text-white transition-colors">
            <i data-lucide="clipboard-check" class="w-6 h-6"></i>
        </a>
        <a href="calendar.php" class="p-4 text-accent-500 bg-white/5 rounded-2xl">
            <i data-lucide="calendar" class="w-6 h-6"></i>
        </a>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                locale: 'es',
                events: <?= json_encode($formatted_events) ?>,
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    meridiem: false
                }
            });
            calendar.render();
            lucide.createIcons();
        });
    </script>
</body>

</html>