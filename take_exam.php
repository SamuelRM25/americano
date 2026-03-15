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
$grade_name = $_SESSION['grade_name'];
$exam_id = $_GET['id'] ?? null;

if (!$exam_id) {
    header('Location: exams.php');
    exit;
}

$theme = get_grade_theme($grade_name);

// Check if already taken
$stmt = $pdo->prepare('SELECT id FROM exam_responses WHERE exam_id = ? AND student_id = ? LIMIT 1');
$stmt->execute([$exam_id, $student_id]);
if ($stmt->fetch()) {
    header('Location: exams.php?msg=already_taken');
    exit;
}

// Fetch exam details
$stmt = $pdo->prepare('SELECT * FROM exams WHERE id = ?');
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

if (!$exam) {
    header('Location: exams.php');
    exit;
}

// Enforce time gate
$now = time();
if ($exam['start_time'] && $now < strtotime($exam['start_time'])) {
    header('Location: exams.php');
    exit;
}
if ($now > strtotime($exam['due_date'])) {
    header('Location: exams.php');
    exit;
}

// Calculate deadline for countdown
$deadline = null;
if ($exam['duration_minutes']) {
    // Use session-stored start time to be consistent across reloads
    if (!isset($_SESSION['exam_start'][$exam_id])) {
        $_SESSION['exam_start'][$exam_id] = time();
    }
    $deadline = $_SESSION['exam_start'][$exam_id] + ($exam['duration_minutes'] * 60);
    // Also cap at due_date
    $deadline = min($deadline, strtotime($exam['due_date']));
}

// Fetch questions
$stmt = $pdo->prepare('SELECT * FROM exam_questions WHERE exam_id = ? ORDER BY series ASC, id ASC');
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $exam['title'] ?> - Colegio Americano</title>
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

        .focus-mode {
            background: #020617;
        }

        .question-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
        }

        .input-premium {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .input-premium:focus {
            border-color: var(--accent-500);
            background: rgba(255, 255, 255, 0.08);
        }
    </style>
</head>

<body class="focus-mode min-h-screen text-slate-300 overflow-y-auto">
    <!-- Progress Header -->
    <header class="fixed top-0 inset-x-0 h-24 question-card z-50 px-8 flex items-center justify-between">
        <div class="flex items-center space-x-6">
            <div class="bg-accent-500 p-3 rounded-2xl shadow-xl shadow-accent-500/20">
                <i data-lucide="brain-circuit" class="w-6 h-6 text-white"></i>
            </div>
            <div>
                <h1 class="text-xl font-black text-white leading-none uppercase italic"><?= $exam['title'] ?></h1>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] mt-1">Evaluación en tiempo
                    real</p>
            </div>
        </div>

        <div class="hidden md:flex items-center space-x-10">
            <div class="text-right">
                <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest mb-1">Preguntas</p>
                <p class="text-lg font-black text-white"><?= count($questions) ?></p>
            </div>
            <?php if ($deadline): ?>
            <div class="w-px h-10 bg-white/10"></div>
            <div class="text-right">
                <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest mb-1">Tiempo Restante</p>
                <p id="countdown" class="text-2xl font-black text-accent-500">--:--</p>
            </div>
            <?php endif; ?>
            <div class="w-px h-10 bg-white/10"></div>
            <a href="exams.php" onclick="return confirm('\u00bfEst\u00e1s seguro de salir?')"
                class="text-xs font-black text-rose-400 hover:text-rose-300 uppercase tracking-widest transition-colors">
                Terminar
            </a>
        </div>
    </header>

    <main class="max-w-4xl mx-auto pt-40 pb-20 px-6">
        <form id="exam-form" action="submit_exam.php" method="POST" enctype="multipart/form-data" class="space-y-12">
            <input type="hidden" name="exam_id" value="<?= $exam_id ?>">

            <?php 
            $current_series = null;
            foreach ($questions as $index => $q): 
                if ($q['series'] !== $current_series): 
                    $current_series = $q['series'];
            ?>
                <div class="pt-10 mb-4 animate-fade-in">
                    <h2 class="text-3xl font-black text-white italic border-l-8 border-accent-500 pl-6 uppercase tracking-tight">
                        <?= htmlspecialchars($current_series ?: 'Preguntas Generales') ?>
                    </h2>
                    <div class="h-1 w-24 bg-accent-500/30 mt-2 rounded-full"></div>
                </div>
            <?php endif; ?>

                <div class="question-card p-10 rounded-[3rem] animate-slide-up"
                    style="animation-delay: <?= $index * 100 ?>ms">
                    <div class="flex items-start space-x-8">
                        <div
                            class="w-14 h-14 bg-accent-500 text-white rounded-2xl flex items-center justify-center flex-shrink-0 font-black text-xl italic shadow-lg shadow-accent-500/20">
                            <?= $index + 1 ?>
                        </div>
                        <div class="flex-1 space-y-8">
                            <h3 class="text-2xl font-black text-white tracking-tight leading-relaxed italic">
                                <?= $q['question_text'] ?>
                            </h3>

                            <div class="space-y-4">
                                <?php if ($q['question_type'] === 'text' || $q['question_type'] === 'paragraph'): ?>
                                    <textarea name="q_<?= $q['id'] ?>" required
                                        rows="<?= $q['question_type'] === 'text' ? '2' : '5' ?>"
                                        class="w-full p-6 input-premium rounded-3xl text-white placeholder-slate-700 focus:outline-none text-lg font-medium tracking-tight"
                                        placeholder="Escribe tu respuesta aquí..."></textarea>

                                <?php elseif ($q['question_type'] === 'multiple_choice' || $q['question_type'] === 'checkbox' || $q['question_type'] === 'true_false'): ?>
                                    <?php
                                    $options = json_decode($q['options'], true);
                                    $input_type = ($q['question_type'] === 'multiple_choice' || $q['question_type'] === 'true_false') ? 'radio' : 'checkbox';
                                    foreach ($options as $opt_index => $opt): ?>
                                        <label
                                            class="flex items-center p-6 input-premium rounded-2xl cursor-pointer hover:bg-white/5 group transition-all">
                                            <input type="<?= $input_type ?>"
                                                name="q_<?= $q['id'] ?><?= $input_type === 'checkbox' ? '[]' : '' ?>"
                                                value="<?= $opt ?>" required
                                                class="w-5 h-5 border-2 border-white/20 bg-transparent rounded-lg checked:bg-accent-500 checked:border-accent-500 transition-all cursor-pointer">
                                            <span
                                                class="ml-5 text-lg font-bold text-slate-400 group-hover:text-white transition-colors"><?= $opt ?></span>
                                        </label>
                                    <?php endforeach; ?>

                                <?php elseif ($q['question_type'] === 'matching'): ?>
                                    <?php
                                    $pairs = json_decode($q['options'], true);
                                    // Mix the second column
                                    $answers = [];
                                    foreach ($pairs as $pair) {
                                        $parts = explode(':', $pair);
                                        if (isset($parts[1])) {
                                            $answers[] = trim($parts[1]);
                                        }
                                    }
                                    shuffle($answers);
                                    foreach ($pairs as $p_idx => $pair):
                                        $parts = explode(':', $pair);
                                        $premise = trim($parts[0]);
                                        ?>
                                        <div
                                            class="flex flex-col md:flex-row md:items-center gap-4 bg-white/5 p-6 rounded-3xl border border-white/10">
                                            <span class="flex-1 font-bold text-white"><?= $premise ?></span>
                                            <select name="q_<?= $q['id'] ?>_matching_<?= $p_idx ?>" required
                                                class="bg-slate-900 text-accent-500 font-bold px-6 py-3 rounded-xl border border-white/10 outline-none focus:border-accent-500">
                                                <option value="">Selecciona...</option>
                                                <?php foreach ($answers as $ans): ?>
                                                    <option value="<?= $ans ?>"><?= $ans ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endforeach; ?>

                                <?php elseif ($q['question_type'] === 'file_upload'): ?>
                                    <div class="relative group">
                                        <input type="file" name="q_<?= $q['id'] ?>" required
                                            onchange="updateFileLabel(this, 'label_<?= $q['id'] ?>')"
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                        <div
                                            class="p-10 border-2 border-dashed border-white/10 rounded-[2rem] flex flex-col items-center justify-center group-hover:bg-white/5 group-hover:border-accent-500/50 transition-all">
                                            <i data-lucide="upload-cloud"
                                                class="w-12 h-12 text-slate-500 group-hover:text-accent-500 mb-4 transition-colors"></i>
                                            <span id="label_<?= $q['id'] ?>" class="text-sm font-black text-slate-400 uppercase tracking-widest text-center">Subir
                                                Evidencia</span>
                                            <p class="text-xs text-slate-600 mt-2">Formatos permitidos: PDF, JPG, PNG (Max 10MB)
                                            </p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="pt-12 text-center">
                <button type="submit"
                    class="bg-white text-slate-950 font-black py-8 px-20 rounded-[2.5rem] hover:bg-accent-500 hover:text-white transition-all transform active:scale-95 shadow-2xl shadow-accent-500/30 text-xl uppercase tracking-tighter italic">
                    Finalizar y Enviar Evaluación
                </button>
                <p class="mt-8 text-slate-600 font-bold text-xs uppercase tracking-[0.4em]">Verifica tus respuestas
                    antes de enviar</p>
            </div>
        </form>
    </main>

    <script>
        lucide.createIcons();

        function updateFileLabel(input, labelId) {
            const label = document.getElementById(labelId);
            if (input.files && input.files[0]) {
                const fileName = input.files[0].name;
                label.textContent = "✓ " + fileName;
                label.classList.remove('text-slate-400');
                label.classList.add('text-accent-500');
            }
        }

        <?php if ($deadline): ?>
        const examDeadline = <?= $deadline ?> * 1000;
        function updateTimer() {
            const remaining = Math.floor((examDeadline - Date.now()) / 1000);
            const el = document.getElementById('countdown');
            if (remaining <= 0) {
                if (el) el.textContent = '00:00';
                document.getElementById('exam-form').submit();
                return;
            }
            const m = Math.floor(remaining / 60);
            const s = remaining % 60;
            const t = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
            if (el) {
                el.textContent = t;
                if (remaining < 300) {
                    el.classList.remove('text-accent-500');
                    el.classList.add('text-rose-400', 'animate-pulse');
                }
            }
        }
        updateTimer();
        setInterval(updateTimer, 1000);
        <?php endif; ?>
    </script>
</body>
</html>