<?php
session_start();
require_once 'config/database.php';
require_once 'includes/themes.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit;
}

$student_name = $_SESSION['student_name'];
$grade_name = $_SESSION['grade_name'];
$grade_id = $_SESSION['grade_id'];

// Fetch all enrolled courses for this student
$stmt_courses = $pdo->prepare('SELECT c.id, c.name FROM courses c JOIN student_courses sc ON c.id = sc.course_id WHERE sc.student_id = ?');
$stmt_courses->execute([$_SESSION['student_id']]);
$student_courses = $stmt_courses->fetchAll();

$course_ids = array_column($student_courses, 'id');
$course_names_text = implode(', ', array_column($student_courses, 'name'));

if (empty($course_ids)) {
    $course_ids = [0];
}

$theme = get_grade_theme($grade_name);

// Fetch assignments for all enrolled courses
$placeholders = implode(',', array_fill(0, count($course_ids), '?'));
$stmt = $pdo->prepare("SELECT a.*, c.name as course_name 
                       FROM assignments a 
                       JOIN courses c ON a.course_id = c.id
                       WHERE a.grade_id = ? AND a.course_id IN ($placeholders) 
                       ORDER BY a.due_date ASC");
$stmt->execute(array_merge([$grade_id], $course_ids));
$assignments = $stmt->fetchAll();

// Fetch upcoming exams
$stmt = $pdo->prepare("SELECT e.*, c.name as course_name 
                       FROM exams e 
                       JOIN courses c ON e.course_id = c.id
                       WHERE e.grade_id = ? AND e.course_id IN ($placeholders) 
                       ORDER BY e.due_date ASC");
$stmt->execute(array_merge([$grade_id], $course_ids));
$exams = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Colegio Americano</title>
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
                        'float': 'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { opacity: '0', transform: 'translateY(24px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                        float: { '0%, 100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-10px)' } }
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
            --accent-100: #e0f2fe;
            --accent-200: #bae6fd;
            --accent-300: #7dd3fc;
            --accent-400: #38bdf8;
            --accent-500: #0ea5e9;
            --accent-600: #0284c7;
            --accent-700: #0369a1;
            --accent-800: #075985;
            --accent-900: #0c4a6e;
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
                        class="flex items-center space-x-3 p-4 rounded-2xl transition-all sidebar-active group">
                        <i data-lucide="layout-dashboard" class="w-5 h-5 text-accent-500"></i>
                        <span class="font-bold text-white">Inicio</span>
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

        <!-- Main Dashboard Content -->
        <main class="flex-1 overflow-y-auto bg-slate-50 relative p-8 lg:p-12">
            <!-- Background Orbs -->
            <div
                class="absolute top-0 right-0 w-[500px] h-[500px] bg-accent-500/5 blur-[120px] rounded-full -mr-64 -mt-64 z-0">
            </div>

            <header class="flex justify-between items-start mb-16 relative z-10 animate-fade-in">
                <div>
                    <h2 class="text-xs font-black text-accent-600 uppercase tracking-[0.3em] mb-2 leading-none">Status
                        Académico: Al día</h2>
                    <h1 class="text-6xl font-black text-slate-900 tracking-tighter mb-4">
                        Hola, <span
                            class="text-transparent bg-clip-text bg-gradient-to-r from-slate-900 to-slate-500"><?= explode(' ', $student_name)[0] ?></span>
                    </h1>
                    <div class="flex items-center space-x-4">
                        <span
                            class="px-4 py-2 bg-white rounded-full border border-slate-200 text-xs font-bold text-slate-600 shadow-sm flex items-center">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 mr-2 text-accent-500"></i>
                            <?= $grade_name ?>
                        </span>
                        <span
                            class="px-4 py-2 bg-white rounded-full border border-slate-200 text-xs font-bold text-slate-600 shadow-sm flex items-center">
                            <i data-lucide="graduation-cap" class="w-3.5 h-3.5 mr-2 text-accent-500"></i>
                            <?= $course_names_text ?>
                        </span>
                    </div>
                </div>

                <div class="flex items-center space-x-6">
                    <div class="flex items-center space-x-4">
                        <button id="register-biometry"
                            class="bg-white/5 hover:bg-white/10 text-white/70 hover:text-white px-6 py-3 rounded-2xl transition-all flex items-center space-x-2 text-xs font-bold uppercase tracking-widest border border-white/5">
                            <i data-lucide="fingerprint" class="w-4 h-4"></i>
                            <span>Activar Face ID</span>
                        </button>
                        <div class="text-right hidden sm:block leading-tight">
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Bienvenido
                                de nuevo</p>
                            <p class="text-xl font-black text-white tracking-tighter italic">Alu.
                                <?= explode(' ', $student_name)[0] ?>
                            </p>
                        </div>
                    </div>
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-accent-500 to-accent-700 rounded-[1.5rem] shadow-2xl shadow-accent-500/30 border-2 border-white flex items-center justify-center text-white text-3xl font-black transition-transform hover:rotate-6 cursor-pointer">
                        <?= substr($student_name, 0, 1) ?>
                    </div>
                </div>
            </header>

            <!-- Stats/KPI Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16 relative z-10">
                <div
                    class="glass-card p-10 rounded-[3rem] shadow-xl shadow-slate-200/50 hover:shadow-2xl transition-all hover:-translate-y-2 group animate-slide-up">
                    <div
                        class="w-14 h-14 bg-accent-50 text-accent-600 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                        <i data-lucide="layers" class="w-7 h-7"></i>
                    </div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Tareas Activas</p>
                    <div class="flex items-baseline space-x-3 mt-1">
                        <h3 class="text-5xl font-black text-slate-900 tracking-tighter"><?= count($assignments) ?></h3>
                        <span class="text-accent-600 font-bold text-sm">Pendientes</span>
                    </div>
                </div>

                <div
                    class="glass-card p-10 rounded-[3rem] shadow-xl shadow-slate-200/50 hover:shadow-2xl transition-all hover:-translate-y-2 group animate-slide-up [animation-delay:100ms]">
                    <div
                        class="w-14 h-14 bg-violet-50 text-violet-600 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                        <i data-lucide="zap" class="w-7 h-7"></i>
                    </div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Próximos Exámenes
                    </p>
                    <div class="flex items-baseline space-x-3 mt-1">
                        <h3 class="text-5xl font-black text-slate-900 tracking-tighter"><?= count($exams) ?></h3>
                        <span class="text-violet-600 font-bold text-sm">Vigentes</span>
                    </div>
                </div>

                <div
                    class="glass-card p-10 rounded-[3rem] shadow-xl shadow-slate-200/50 hover:shadow-2xl transition-all hover:-translate-y-2 group animate-slide-up [animation-delay:200ms]">
                    <div
                        class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                        <i data-lucide="bar-chart-3" class="w-7 h-7"></i>
                    </div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Asistencia Mensual
                    </p>
                    <div class="flex items-center space-x-4 mt-1">
                        <h3 class="text-5xl font-black text-slate-900 tracking-tighter">98%</h3>
                        <div class="flex-1 h-3 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                            <div class="h-full bg-emerald-500 transition-all duration-1000" style="width: 98%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Grid: Academic Timeline -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-12 relative z-10">
                <section class="xl:col-span-8 animate-slide-up [animation-delay:300ms]">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Pendientes Críticos</h2>
                            <p class="text-slate-500 font-medium">No dejes que se acumulen</p>
                        </div>
                        <a href="assignments.php"
                            class="px-6 py-3 bg-white text-slate-900 rounded-2xl font-black text-xs uppercase tracking-widest border border-slate-200 hover:bg-slate-50 transition-all shadow-sm">
                            Ver Biblioteca
                        </a>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <?php if (empty($assignments)): ?>
                            <div
                                class="bg-white p-20 rounded-[3rem] text-center border-4 border-dashed border-slate-100 italic font-bold text-slate-300">
                                <i data-lucide="inbox" class="w-16 h-16 mx-auto mb-4 opacity-20"></i>
                                <p>No hay tareas pendientes en este momento.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($assignments, 0, 4) as $assignment): ?>
                                <div
                                    class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 hover:shadow-2xl hover:scale-[1.01] transition-all duration-500 group flex items-center justify-between cursor-pointer">
                                    <div class="flex items-center space-x-8">
                                        <div
                                            class="w-20 h-20 bg-accent-50 text-accent-600 rounded-3xl flex items-center justify-center shadow-inner group-hover:bg-accent-600 group-hover:text-white transition-all transform group-hover:rotate-6">
                                            <i data-lucide="file-text" class="w-10 h-10"></i>
                                        </div>
                                        <div>
                                            <h4
                                                class="text-2xl font-black text-slate-900 tracking-tight group-hover:text-accent-600 transition-colors uppercase italic">
                                                <?= $assignment['title'] ?>
                                            </h4>
                                            <div class="flex items-center mt-2 space-x-6">
                                                <span class="flex items-center text-xs font-bold text-slate-400">
                                                    <i data-lucide="clock" class="w-4 h-4 mr-2 text-accent-500"></i>
                                                    Límite: <?= date('d M - H:i', strtotime($assignment['due_date'])) ?>
                                                </span>
                                                <span
                                                    class="px-3 py-1 bg-slate-100 text-slate-500 text-[10px] font-black uppercase tracking-widest rounded-full"><?= $assignment['course_name'] ?></span>
                                                <span
                                                    class="px-3 py-1 bg-accent-50 text-accent-600 text-[10px] font-black uppercase tracking-widest rounded-full">Próximo</span>
                                            </div>
                                        </div>
                                    </div>
                                    <i data-lucide="arrow-right"
                                        class="text-slate-200 group-hover:text-accent-500 group-hover:translate-x-3 transition-all w-8 h-8"></i>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <aside class="xl:col-span-4 space-y-8 animate-slide-up [animation-delay:400ms]">
                    <div class="bg-slate-900 p-10 rounded-[3rem] text-white shadow-2xl relative overflow-hidden group">
                        <i data-lucide="cpu"
                            class="absolute -right-8 -bottom-8 w-40 h-40 text-white/5 group-hover:scale-125 transition-transform duration-700"></i>
                        <h4 class="text-2xl font-black tracking-tight mb-4 italic">Tip del Día AI</h4>
                        <p class="text-slate-400 text-sm font-medium leading-relaxed mb-8">
                            "Estudiar 45 minutos y descansar 15 mejora la retención cognitiva en un 40%. Intenta aplicar
                            la técnica hoy."
                        </p>
                        <button
                            class="w-full py-4 bg-white text-slate-950 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-accent-400 hover:text-white transition-all">
                            Más Metodologías
                        </button>
                    </div>

                    <div class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm">
                        <div class="flex items-center justify-between mb-8">
                            <h4 class="text-xl font-black text-slate-900 tracking-tight">Evaluación Actual</h4>
                            <span class="w-2 h-2 bg-rose-500 rounded-full animate-ping"></span>
                        </div>
                        <?php if (empty($exams)): ?>
                            <p class="text-slate-300 font-bold italic text-center py-6">Sin exámenes para hoy.</p>
                        <?php else: ?>
                            <div class="space-y-6">
                                <?php foreach (array_slice($exams, 0, 2) as $exam): ?>
                                    <div
                                        class="flex items-start space-x-6 p-4 rounded-3xl hover:bg-slate-50 transition-colors group">
                                        <div class="w-2.5 h-12 bg-accent-500 rounded-full mt-1 group-hover:h-16 transition-all">
                                        </div>
                                        <div class="flex-1">
                                            <h5 class="text-lg font-black text-slate-800 leading-tight mb-1">
                                                <?= $exam['title'] ?>
                                            </h5>
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                                                <?= date('H:i', strtotime($exam['due_date'])) ?> • Aula Virtual
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </aside>
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
        <a href="calendar.php" class="p-4 text-slate-400 hover:text-white transition-colors">
            <i data-lucide="calendar" class="w-6 h-6"></i>
        </a>
    </nav>

    <script>
        lucide.createIcons();

        document.getElementById('register-biometry')?.addEventListener('click', async () => {
            if (!window.PublicKeyCredential) {
                alert("Tu dispositivo no admite biometría.");
                return;
            }

            try {
                // Simulation of WebAuthn registration
                const credentialId = btoa(Math.random().toString());
                const publicKey = "PREMIUM_ENCRYPTED_KEY_SIMULATION";

                const response = await fetch('webauthn_handler.php?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ credential_id: credentialId, public_key: publicKey })
                });

                const result = await response.json();
                if (result.success) {
                    alert("¡Biometría activada con éxito! Ahora puedes entrar con Face ID.");
                    localStorage.setItem('has_biometrics', 'true');
                    localStorage.setItem('bio_id', credentialId);
                }
            } catch (err) {
                console.error(err);
            }
        });
    </script>
</body>

</html>