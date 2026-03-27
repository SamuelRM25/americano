<?php
session_start();
require_once '../config/database.php';
require_once 'includes/sidebar.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

if (!isset($_GET['exam_id']) || !isset($_GET['student_id'])) {
    header('Location: submissions.php');
    exit;
}

$exam_id = $_GET['exam_id'];
$student_id = $_GET['student_id'];

// Get Student Info
$stmt = $pdo->prepare("SELECT name FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: submissions.php');
    exit;
}

// Get Exam Info
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

// Get the response JSON
$stmt = $pdo->prepare("SELECT responses, submitted_at FROM exam_responses WHERE exam_id = ? AND student_id = ? ORDER BY submitted_at DESC LIMIT 1");
$stmt->execute([$exam_id, $student_id]);
$submission = $stmt->fetch();

if (!$submission) {
    header('Location: submissions.php?msg=not_found');
    exit;
}

$answers = json_decode($submission['responses'], true) ?? [];
$individual_scores = json_decode($submission['individual_scores'] ?? '{}', true) ?? [];

// Handle Grading Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grades'])) {
    $new_individual_scores = $_POST['question_scores'] ?? [];
    $total_score = 0;
    foreach ($new_individual_scores as $pts) {
        $total_score += floatval($pts);
    }
    
    try {
        $upd = $pdo->prepare("UPDATE exam_responses SET individual_scores = ?, score = ? WHERE exam_id = ? AND student_id = ?");
        $upd->execute([json_encode($new_individual_scores), $total_score, $exam_id, $student_id]);
        header("Location: view_exam_submission.php?exam_id=$exam_id&student_id=$student_id&msg=graded");
        exit;
    } catch (Exception $e) {
        $error = "Error al guardar calificación: " . $e->getMessage();
    }
}

$success_msg = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'graded') {
    $success_msg = "Calificación guardada correctamente.";
}

// Get Questions and group by series (same logic as edit_exam.php)
$stmt = $pdo->prepare("SELECT * FROM exam_questions WHERE exam_id = ? ORDER BY COALESCE(series, 'zzzz'), id ASC");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll();

$grouped_questions = [];
foreach ($questions as $q) {
    $key = $q['series'] ?? '— Sin Serie';
    $grouped_questions[$key][] = $q;
}

$total_score_possible = 0;
foreach ($questions as $q) {
    $total_score_possible += $q['points'];
}

$typeLabels = [
    'text'            => ['label' => 'Respuesta Corta',    'color' => 'bg-sky-100 text-sky-700'],
    'paragraph'       => ['label' => 'Desarrollo',          'color' => 'bg-indigo-100 text-indigo-700'],
    'multiple_choice' => ['label' => 'Opción Múltiple',    'color' => 'bg-violet-100 text-violet-700'],
    'checkbox'        => ['label' => 'Casilla',             'color' => 'bg-purple-100 text-purple-700'],
    'true_false'      => ['label' => 'V / F',               'color' => 'bg-emerald-100 text-emerald-700'],
    'matching'        => ['label' => 'Relación',            'color' => 'bg-amber-100 text-amber-700'],
    'file_upload'     => ['label' => 'Archivo',             'color' => 'bg-rose-100 text-rose-700'],
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificar Examen - Admin</title>
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
        body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; }
        .grade-input::-webkit-inner-spin-button, .grade-input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    </style>
</head>

<body class="bg-[#f8fafc] h-screen flex overflow-hidden text-slate-800 antialiased selection:bg-accent-500 selection:text-white">

    <?php render_admin_sidebar('submissions'); ?>

    <main class="flex-1 overflow-y-auto bg-slate-50 p-6 lg:p-12 animate-fade-in custom-scrollbar pb-32">
        <div class="max-w-4xl mx-auto space-y-8 animate-fade-in text-slate-900">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
                <div class="flex items-center space-x-6">
                    <a href="submissions.php" class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-slate-400 hover:text-accent-600 hover:shadow-xl transition-all shadow-sm border border-slate-100 group">
                        <i data-lucide="arrow-left" class="w-6 h-6 group-active:-translate-x-1 transition-transform"></i>
                    </a>
                    <div>
                        <h1 class="text-4xl font-black text-slate-950 tracking-tighter italic uppercase">Revisar <span class="text-accent-500">Examen</span></h1>
                        <p class="text-slate-500 font-bold tracking-tight mt-1 flex items-center gap-2">
                            <span><?= htmlspecialchars($student['name'] ?? 'Estudiante') ?></span>
                            <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                            <span><?= htmlspecialchars($exam['title'] ?? 'Examen') ?></span>
                        </p>
                    </div>
                </div>
            </div>

            <?php if ($success_msg): ?>
                <div class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-6 py-4 rounded-3xl text-sm font-bold flex items-center animate-slide-up shadow-sm">
                    <i data-lucide="check-circle" class="w-5 h-5 mr-3"></i> <?= $success_msg ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="bg-rose-50 border border-rose-100 text-rose-700 px-6 py-4 rounded-3xl text-sm font-bold flex items-center animate-shake">
                    <i data-lucide="alert-circle" class="w-5 h-5 mr-3"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="grading-form">
                <input type="hidden" name="save_grades" value="1">
                
                <!-- Meta Info Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
                    <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/50 group hover:border-accent-200 transition-all">
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em] mb-2">Entregado el</p>
                        <p class="font-bold text-slate-800 tracking-tight leading-none"><?= date('d M, Y', strtotime($submission['submitted_at'])) ?></p>
                        <p class="text-[10px] text-slate-400 font-bold mt-1"><?= date('h:i A', strtotime($submission['submitted_at'])) ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/50">
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em] mb-2">Puntos Posibles</p>
                        <p class="text-3xl font-black text-slate-950 tracking-tighter"><?= $total_score_possible ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/50">
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em] mb-2">Calificación</p>
                        <p class="text-3xl font-black text-accent-600 tracking-tighter italic">
                            <span id="current-total-score"><?= $submission['score'] ?? '0' ?></span>
                        </p>
                    </div>
                     <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/50">
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em] mb-2">Progreso</p>
                        <div class="flex items-center gap-2">
                            <span class="text-2xl font-black text-emerald-500 tracking-tighter"><?= count(array_filter($answers)) ?></span>
                            <span class="text-sm font-bold text-slate-300">/ <?= count($questions) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Responses list -->
                <div class="space-y-12">
                    <?php if (empty($questions)): ?>
                        <div class="p-16 text-center rounded-[3rem] bg-white border border-slate-100 shadow-xl">
                            <i data-lucide="clipboard-x" class="w-12 h-12 mx-auto mb-4 text-slate-200"></i>
                            <p class="font-bold text-slate-400 italic">No hay preguntas registradas.</p>
                        </div>
                    <?php else:
                        $qNum = 0;
                        foreach ($grouped_questions as $seriesName => $seriesQs): ?>
                            <div class="animate-slide-up" style="animation-delay: <?= $qNum * 100 ?>ms">
                                <div class="flex items-center gap-6 mb-8">
                                    <h2 class="text-3xl font-black text-slate-950 italic tracking-tighter uppercase whitespace-nowrap">
                                        <?= htmlspecialchars($seriesName) ?>
                                    </h2>
                                    <div class="h-px flex-1 bg-gradient-to-r from-slate-200 to-transparent"></div>
                                </div>
                                
                                <div class="space-y-6">
                                    <?php foreach ($seriesQs as $q):
                                        $qNum++;
                                        $tl = $typeLabels[$q['question_type']] ?? ['label' => $q['question_type'], 'color' => 'bg-slate-100 text-slate-500'];
                                        $student_answer = $answers[$q['id']] ?? '';
                                        $is_file = ($q['question_type'] === 'file_upload');
                                        
                                        $correct_answers_arr = json_decode($q['correct_answers'] ?? '[]', true);
                                        $has_correct_answer = !empty($correct_answers_arr) && $q['question_type'] !== 'paragraph' && $q['question_type'] !== 'file_upload';
                                        
                                        // Auto-calculate for display if not saved yet
                                        if (isset($individual_scores[$q['id']])) {
                                            $saved_pts = $individual_scores[$q['id']];
                                        } else {
                                            $saved_pts = 0;
                                            if ($has_correct_answer) {
                                                $earned = 0;
                                                $t = $q['question_type'];
                                                if ($t === 'text') {
                                                    if (trim(mb_strtolower($student_answer)) === trim(mb_strtolower($correct_answers_arr[0]))) $earned = floatval($q['points']);
                                                } elseif ($t === 'multiple_choice' || $t === 'true_false') {
                                                    if (trim(mb_strtolower($student_answer)) === trim(mb_strtolower($correct_answers_arr[0]))) $earned = floatval($q['points']);
                                                } elseif ($t === 'checkbox') {
                                                    $student_arr = array_map('trim', explode(',', $student_answer));
                                                    $correct_arr = array_map('trim', $correct_answers_arr);
                                                    sort($student_arr); sort($correct_arr);
                                                    if ($student_arr === $correct_arr && !empty(array_filter($student_arr))) $earned = floatval($q['points']);
                                                } elseif ($t === 'matching') {
                                                    $student_parts = array_map('trim', explode('|', $student_answer));
                                                    $options_arr = json_decode($q['options'] ?? '[]', true);
                                                    $correct_parts = [];
                                                    foreach ($options_arr as $concept) {
                                                        $correct_parts[] = trim($correct_answers_arr[$concept] ?? '');
                                                    }
                                                    $correct_count = 0;
                                                    $total_pairs = count($correct_parts);
                                                    for ($i = 0; $i < $total_pairs; $i++) {
                                                        if (isset($student_parts[$i]) && $student_parts[$i] === $correct_parts[$i]) {
                                                            $correct_count++;
                                                        }
                                                    }
                                                    if ($total_pairs > 0) {
                                                        $earned = round(($correct_count / $total_pairs) * floatval($q['points']), 1);
                                                    }
                                                }
                                                $saved_pts = $earned;
                                            }
                                        }
                                    ?>
                                    
                                    <div class="p-8 bg-white rounded-[2.5rem] border border-slate-100 shadow-lg shadow-slate-200/50 group hover:border-accent-200 transition-all">
                                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                                            <div class="flex items-center gap-4">
                                                <div class="w-10 h-10 bg-slate-950 text-white rounded-xl flex items-center justify-center font-black text-sm italic">
                                                    <?= $qNum ?>
                                                </div>
                                                <span class="text-[9px] font-black uppercase tracking-widest px-3 py-1 rounded-lg <?= $tl['color'] ?>">
                                                    <?= $tl['label'] ?>
                                                </span>
                                            </div>
                                            
                                            <!-- Points Input -->
                                            <div class="flex items-center gap-3">
                                                <?php if ($has_correct_answer): ?>
                                                    <button type="button" onclick="openCorrectAnswerModal(<?= htmlspecialchars(json_encode([$q['question_type'], $correct_answers_arr]), ENT_QUOTES, 'UTF-8') ?>)" 
                                                        class="p-2.5 bg-accent-50 text-accent-500 hover:bg-accent-500 hover:text-white rounded-xl transition-all border border-accent-100 shadow-sm flex items-center justify-center group/btn" title="Ver Respuesta Correcta">
                                                        <i data-lucide="help-circle" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <div class="flex items-center gap-3 bg-slate-50 p-2 pl-4 rounded-2xl border border-slate-100 group-hover:bg-accent-50 group-hover:border-accent-100 transition-colors">
                                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest group-hover:text-accent-600">Calificación</span>
                                                    <div class="flex items-center">
                                                        <input type="number" name="question_scores[<?= $q['id'] ?>]" 
                                                            value="<?= $saved_pts ?>" min="0" max="<?= $q['points'] ?>" step="0.5"
                                                            oninput="updateTotalScore()"
                                                            class="w-14 bg-white border border-slate-200 rounded-xl px-2 py-2 text-center font-black text-slate-900 focus:border-accent-500 outline-none grade-input shadow-sm">
                                                        <span class="ml-2 text-xs font-bold text-slate-300">/ <?= $q['points'] ?> pts</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-6">
                                            <p class="text-lg font-bold text-slate-900 leading-snug tracking-tight italic"><?= nl2br(htmlspecialchars($q['question_text'])) ?></p>
                                        </div>

                                        <div class="bg-slate-50/80 p-6 rounded-3xl border border-slate-100 relative group-hover:bg-white transition-colors">
                                            <span class="absolute -top-3 left-6 bg-white px-3 py-0.5 rounded-full border border-slate-100 text-[9px] font-black uppercase tracking-widest text-slate-400">Respuesta del Alumno</span>
                                            
                                            <?php if ($student_answer === ''): ?>
                                                <p class="text-slate-400 italic text-sm font-bold flex items-center gap-3">
                                                    <i data-lucide="slash" class="w-4 h-4 text-rose-300"></i> No respondida
                                                </p>
                                            <?php elseif ($is_file): ?>
                                                <div class="flex items-center gap-4">
                                                    <a href="../<?= htmlspecialchars($student_answer) ?>" target="_blank" 
                                                        class="inline-flex items-center gap-3 px-6 py-3 bg-slate-950 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-accent-600 transition-all shadow-xl shadow-slate-200">
                                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                                        Ver Evidencia
                                                    </a>
                                                    <span class="text-[10px] text-slate-400 font-bold italic"><?= basename($student_answer) ?></span>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-slate-700 font-bold whitespace-pre-wrap leading-relaxed text-base italic"><?= htmlspecialchars($student_answer) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach;
                    endif; ?>
                </div>

                <!-- Floating Save Bar -->
                <div class="fixed bottom-10 left-1/2 -translate-x-1/2 lg:left-auto lg:translate-x-0 lg:right-12 z-50 animate-slide-up" style="animation-delay: 400ms">
                    <div class="bg-slate-950/90 backdrop-blur-xl p-4 rounded-[2.5rem] border border-white/10 shadow-2xl flex items-center gap-6 pr-6">
                        <div class="pl-4">
                            <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest leading-none mb-1">Nota Final</p>
                            <p class="text-2xl font-black text-white italic tracking-tighter leading-none">
                                <span id="footer-total-score"><?= $submission['score'] ?? '0' ?></span>
                                <span class="text-xs text-slate-600 tracking-normal ml-1">/ <?= $total_score_possible ?></span>
                            </p>
                        </div>
                        <button type="submit" class="bg-accent-500 hover:bg-accent-600 text-white px-8 py-4 rounded-2xl font-black uppercase tracking-widest text-xs transition-all flex items-center gap-3 shadow-xl shadow-accent-500/20 active:scale-95">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            Guardar Nota
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <!-- Modal Respuesta Correcta -->
    <div id="correct-answer-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 hidden flex items-center justify-center p-6 overflow-y-auto opacity-0 transition-opacity duration-300">
        <div class="bg-white w-full max-w-lg rounded-[3.5rem] p-10 shadow-2xl relative transform scale-95 transition-transform duration-300" id="ca-modal-content">
            <header class="flex justify-between items-start mb-8">
                <div>
                    <h3 class="text-2xl font-black text-slate-950 tracking-tighter italic">Respuesta <span class="text-accent-500">Correcta</span></h3>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">Definida por el profesor</p>
                </div>
                <button type="button" onclick="closeCorrectAnswerModal()" class="p-3 bg-slate-100 text-slate-400 rounded-full hover:bg-slate-950 hover:text-white transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </header>
            <div id="ca-modal-body" class="space-y-4">
                <!-- Content injected via JS -->
            </div>
            <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                <button type="button" onclick="closeCorrectAnswerModal()" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-4 rounded-2xl transition-colors text-sm uppercase tracking-widest">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        function openCorrectAnswerModal(data) {
            const type = data[0];
            const answers = data[1];
            const modal = document.getElementById('correct-answer-modal');
            const content = document.getElementById('ca-modal-content');
            const body = document.getElementById('ca-modal-body');
            
            let html = '';
            
            if (type === 'matching') {
                html += '<div class="space-y-3">';
                for (const [concept, definition] of Object.entries(answers)) {
                    html += `
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 flex flex-col sm:flex-row gap-2 sm:gap-4 sm:items-center">
                            <span class="font-bold text-slate-700 text-sm flex-1">${concept}</span>
                            <i data-lucide="arrow-right" class="w-4 h-4 text-slate-300 hidden sm:block"></i>
                            <span class="font-black text-accent-600 text-sm flex-1 bg-accent-50 py-2 px-3 rounded-xl border border-accent-100">${definition}</span>
                        </div>
                    `;
                }
                html += '</div>';
            } else if (type === 'checkbox') {
                html += '<div class="flex flex-wrap gap-2">';
                answers.forEach(ans => {
                    html += `<span class="px-4 py-2 bg-accent-50 text-accent-600 font-bold rounded-xl border border-accent-100 text-sm flex items-center gap-2"><i data-lucide="check-square" class="w-4 h-4"></i> ${ans}</span>`;
                });
                html += '</div>';
            } else {
                html += `
                    <div class="bg-accent-50/50 p-6 rounded-3xl border border-accent-100 text-center">
                        <span class="text-xl font-black text-accent-600 italic tracking-tight">${answers[0]}</span>
                    </div>
                `;
            }
            
            body.innerHTML = html;
            lucide.createIcons();
            
            modal.classList.remove('hidden');
            requestAnimationFrame(() => {
                modal.classList.remove('opacity-0');
                content.classList.remove('scale-95');
            });
        }

        function closeCorrectAnswerModal() {
            const modal = document.getElementById('correct-answer-modal');
            const content = document.getElementById('ca-modal-content');
            
            modal.classList.add('opacity-0');
            content.classList.add('scale-95');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        function updateTotalScore() {
            let total = 0;
            const inputs = document.querySelectorAll('.grade-input');
            inputs.forEach(input => {
                total += parseFloat(input.value || 0);
            });
            
            // Format to 1 decimal place if needed
            const formattedTotal = Number.isInteger(total) ? total : total.toFixed(1);
            
            document.getElementById('current-total-score').textContent = formattedTotal;
            document.getElementById('footer-total-score').textContent = formattedTotal;
        }

        // Initialize lucide on potential dynamic elements if any were added
        document.addEventListener('DOMContentLoaded', updateTotalScore);
    </script>
</body>
</html>
