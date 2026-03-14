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
$active_courses = $_SESSION['active_courses'] ?? [];

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

// Fetch events (Assignments and Exams)
$stmt_events = $pdo->prepare("
    (SELECT title, due_date as date, 'assignment' as type, c.name as course_name 
     FROM assignments a JOIN courses c ON a.course_id = c.id 
     WHERE a.grade_id = ? AND a.course_id IN ($placeholders))
    UNION ALL
    (SELECT title, due_date as date, 'exam' as type, c.name as course_name 
     FROM exams e JOIN courses c ON e.course_id = c.id 
     WHERE e.grade_id = ? AND e.course_id IN ($placeholders))
    ORDER BY date ASC
");
$params = array_merge([$grade_id], $course_ids, [$grade_id], $course_ids);
$stmt_events->execute($params);
$events = $stmt_events->fetchAll();

$events_by_date = [];
foreach ($events as $event) {
    $date = date('Y-m-d', strtotime($event['date']));
    $events_by_date[$date][] = $event;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Agenda - Colegio Americano</title>
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

        .calendar-day {
            min-height: 120px;
        }
    </style>
</head>

<body class="bg-slate-50 selection:bg-accent-500 selection:text-white overflow-hidden">
    <div class="flex h-screen overflow-hidden">
        <!-- Shared Student Sidebar -->
        <?php render_student_sidebar('calendar', $theme, $student_name); ?>

        <!-- Main Content -->
        <main class="flex-1 min-h-0 overflow-y-auto bg-slate-50 p-8 lg:p-12 custom-scrollbar">
            <header class="mb-12 animate-fade-in">
                <h2 class="text-xs font-black text-accent-600 uppercase tracking-[0.3em] mb-2 leading-none">Agenda / Cronograma</h2>
                <h1 class="text-6xl font-black text-slate-900 tracking-tighter italic uppercase">Calendario <span class="text-transparent bg-clip-text bg-gradient-to-r from-slate-900 to-accent-600">Escolar</span></h1>
            </header>

            <div class="bg-white rounded-[3rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden animate-slide-up">
                <div class="p-8 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
                    <h3 class="text-2xl font-black text-slate-900 italic tracking-tight uppercase"><?= date('F Y') ?></h3>
                    <div class="flex space-x-4">
                        <button class="p-3 bg-white border border-slate-200 rounded-2xl text-slate-400 hover:text-accent-500 transition-all shadow-sm">
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </button>
                        <button class="p-3 bg-white border border-slate-200 rounded-2xl text-slate-400 hover:text-accent-500 transition-all shadow-sm">
                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-7 border-b border-slate-50">
                    <?php 
                    $days = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
                    foreach($days as $day): ?>
                        <div class="py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest"><?= $day ?></div>
                    <?php endforeach; ?>
                </div>

                <div class="grid grid-cols-7">
                    <?php
                    $year = date('Y');
                    $month = date('m');
                    $first_day = date('w', strtotime("$year-$month-01"));
                    $days_in_month = date('t', strtotime("$year-$month-01"));
                    
                    // Empty cells before first day
                    for($i = 0; $i < $first_day; $i++) {
                        echo '<div class="calendar-day p-4 border-r border-b border-slate-50 bg-slate-50/10"></div>';
                    }

                    // Days of month
                    for($day = 1; $day <= $days_in_month; $day++) {
                        $date = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                        $has_events = isset($events_by_date[$date]);
                        $is_today = ($date === date('Y-m-d'));
                        
                        echo '<div class="calendar-day p-6 border-r border-b border-slate-50 hover:bg-slate-50/50 transition-colors relative group">';
                        echo '<span class="text-lg font-black ' . ($is_today ? 'bg-accent-500 text-white w-10 h-10 flex items-center justify-center rounded-2xl shadow-lg shadow-accent-500/20' : 'text-slate-400 group-hover:text-slate-900') . ' transition-colors">' . $day . '</span>';
                        
                        if($has_events) {
                            echo '<div class="mt-4 space-y-2">';
                            foreach($events_by_date[$date] as $event) {
                                $color = $event['type'] === 'exam' ? 'rose' : 'accent';
                                echo '<div class="px-3 py-2 bg-'.$color.'-50 text-'.$color.'-600 rounded-xl text-[9px] font-black uppercase tracking-tight border border-'.$color.'-100/50 truncate hover:scale-105 transition-transform cursor-pointer" title="'.$event['title'].'">';
                                echo '<i data-lucide="' . ($event['type'] === 'exam' ? 'zap' : 'file-text') . '" class="w-3 h-3 inline mr-1 opacity-70"></i>';
                                echo $event['title'];
                                echo '</div>';
                            }
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
    <?php include 'includes/footer_scripts.php'; ?>
</body>

</html>