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
    <title>Revisar Examen - Admin</title>
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
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { background-color: #f8fafc; }
        .animate-fade-in { animation: fadeIn 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-800 font-sans antialiased selection:bg-accent-500 selection:text-white pb-24 lg:pb-0">

    <?php render_admin_sidebar(''); ?>

    <div class="lg:ml-[5.5rem] p-6 lg:p-12 min-h-screen">
        <main class="max-w-4xl mx-auto space-y-8 animate-fade-in">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
                <div class="flex items-center space-x-6">
                    <a href="submissions.php" class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-slate-400 hover:text-accent-600 hover:shadow-xl transition-all shadow-sm border border-slate-100 group">
                        <i data-lucide="arrow-left" class="w-6 h-6 group-active:-translate-x-1 transition-transform"></i>
                    </a>
                    <div>
                        <h1 class="text-4xl font-black text-slate-900 tracking-tighter italic">Revisar <span class="text-accent-500">Examen</span></h1>
                        <p class="text-slate-500 font-bold tracking-tight mt-1 flex items-center gap-2">
                            <span><?= htmlspecialchars($student['name']) ?></span>
                            <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                            <span><?= htmlspecialchars($exam['title']) ?></span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Meta Info -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/50">
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Entregado el</p>
                    <p class="font-bold text-slate-800 tracking-tight"><?= date('d M, Y', strtotime($submission['submitted_at'])) ?></p>
                    <p class="text-xs text-slate-400 font-bold"><?= date('h:i A', strtotime($submission['submitted_at'])) ?></p>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/50">
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Total Preguntas</p>
                    <p class="text-2xl font-black text-slate-800 tracking-tighter"><?= count($questions) ?></p>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/50">
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Puntos Posibles</p>
                    <p class="text-2xl font-black text-accent-600 tracking-tighter"><?= $total_score_possible ?></p>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/50">
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Respondidas</p>
                    <p class="text-2xl font-black text-emerald-500 tracking-tighter"><?= count(array_filter($answers)) ?>/<?= count($questions) ?></p>
                </div>
            </div>

            <!-- Responses list -->
            <div class="space-y-10">
                <?php
                if (empty($questions)): ?>
                    <div class="p-12 text-center rounded-[2.5rem] bg-white border border-slate-100 shadow-xl">
                        <p class="font-bold text-slate-400 italic">No hay preguntas registradas en este examen.</p>
                    </div>
                <?php else:
                    $qNum = 0;
                    foreach ($grouped_questions as $seriesName => $seriesQs): ?>
                        <div class="bg-white rounded-[3.5rem] p-10 shadow-xl shadow-slate-200/50 border border-slate-100">
                            <!-- Series Header -->
                            <div class="flex items-center gap-4 mb-8">
                                <div class="h-px flex-1 bg-slate-100"></div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] px-4 py-1.5 bg-slate-50 rounded-full border border-slate-100">
                                    <?= htmlspecialchars($seriesName) ?>
                                </span>
                                <div class="h-px flex-1 bg-slate-100"></div>
                            </div>
                            
                            <div class="space-y-8">
                                <?php foreach ($seriesQs as $q):
                                    $qNum++;
                                    $tl = $typeLabels[$q['question_type']] ?? ['label' => $q['question_type'], 'color' => 'bg-slate-100 text-slate-500'];
                                    $student_answer = $answers[$q['id']] ?? '';
                                    $is_file = ($q['question_type'] === 'file_upload');
                                ?>
                                
                                <div class="p-6 bg-slate-50/50 rounded-[2rem] border border-slate-100">
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-slate-200 rounded-xl flex items-center justify-center font-black text-slate-600 text-sm">
                                                <?= $qNum ?>
                                            </div>
                                            <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-lg <?= $tl['color'] ?>">
                                                <?= $tl['label'] ?>
                                            </span>
                                            <span class="text-[10px] uppercase font-black text-slate-400 border border-slate-200 px-2 py-0.5 rounded-md">
                                                <?= $q['points'] ?> pts
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mb-5">
                                        <p class="font-bold text-slate-900 leading-snug"><?= nl2br(htmlspecialchars($q['question_text'])) ?></p>
                                    </div>

                                    <div class="bg-white p-5 rounded-[1.5rem] border border-slate-200 shadow-sm relative">
                                        <span class="absolute -top-3 left-4 bg-white px-2 text-[9px] font-black uppercase tracking-widest text-slate-400">Respuesta del Alumno</span>
                                        
                                        <?php if ($student_answer === ''): ?>
                                            <p class="text-slate-400 italic text-sm font-bold flex items-center gap-2">
                                                <i data-lucide="x-circle" class="w-4 h-4 text-rose-300"></i> No respondida
                                            </p>
                                        <?php elseif ($is_file): ?>
                                            <a href="../<?= htmlspecialchars($student_answer) ?>" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-sm font-bold text-slate-700 transition-colors">
                                                <i data-lucide="file-down" class="w-4 h-4 text-accent-600"></i>
                                                Descargar / Ver Evidencia Adjunta
                                            </a>
                                        <?php else: ?>
                                            <p class="text-slate-700 font-bold whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars($student_answer) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach;
                endif; ?>
            </div>
            
            <div class="flex justify-end pt-6">
                <a href="submissions.php" class="px-8 py-4 bg-slate-900 text-white font-black rounded-2xl uppercase tracking-widest text-xs hover:bg-accent-600 transition-colors shadow-xl shadow-slate-200">
                    Regresar a Entregas
                </a>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
